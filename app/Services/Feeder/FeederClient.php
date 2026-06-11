<?php

namespace App\Services\Feeder;

use App\Support\Feeder\FeederResponseParser;
use App\Support\Feeder\FeederXmlEncoder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FeederClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function callJson(string $action, array $payload = []): array
    {
        $body = array_merge(['act' => $action, 'token' => $this->token()], $payload);

        return $this->post($body, 'application/json');
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function callXmlBody(array $body): array
    {
        $body['token'] = $this->token();

        return $this->post($body, $this->wsContentType());
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    public function callXml(string $action, array $record): array
    {
        return $this->callXmlBody([
            'act' => $action,
            'record' => $record,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getList(string $action, string $filter, int $limit = 1, int $offset = 0): array
    {
        $result = $this->callXmlBody([
            'act' => $action,
            'filter' => $filter,
            'order' => '',
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $data = $result['data'] ?? [];
        if (! is_array($data)) {
            return [];
        }

        if ($data === []) {
            return [];
        }

        if (array_is_list($data)) {
            return array_values(array_filter($data, 'is_array'));
        }

        return [$data];
    }

    public function clearTokenCache(): void
    {
        Cache::forget('feeder_ws_token');
    }

    /**
     * Uji koneksi + kredensial (selalu ambil token baru).
     */
    public function ping(): void
    {
        $this->clearTokenCache();
        $this->token();
    }

    /**
     * Token baru + uji operasi WS (selain GetToken) sebelum kirim data.
     */
    public function ensureReadyForWrite(): void
    {
        $this->ping();

        try {
            $this->callJson('GetProfilPT', []);
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                'Neo Feeder merespons GetToken tetapi belum siap untuk kirim data: '.$e->getMessage(),
                0,
                $e,
            );
        }
    }

    protected function wsContentType(): string
    {
        return config('feeder.prefer_json', true)
            ? 'application/json'
            : 'application/xml';
    }

    public function token(): string
    {
        $cacheKey = 'feeder_ws_token';

        return Cache::remember($cacheKey, config('feeder.token_ttl_seconds', 300), function (): string {
            $username = config('feeder.username');
            $password = config('feeder.password');

            if ($username === '' || $password === '') {
                throw new RuntimeException('FEEDER_USERNAME / FEEDER_PASSWORD belum diatur di .env.');
            }

            $result = $this->post([
                'act' => 'GetToken',
                'username' => $username,
                'password' => $password,
            ], 'application/json', false);

            $token = $result['data']['token'] ?? null;
            if (! is_string($token) || $token === '') {
                throw new RuntimeException(
                    'GetToken gagal: '.($result['error_desc'] ?? 'token kosong'),
                );
            }

            return $token;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function post(array $data, string $contentType, bool $useTokenCache = true): array
    {
        $url = (string) config('feeder.ws_url');
        if ($url === '') {
            throw new RuntimeException('FEEDER_WS_URL belum diatur di .env.');
        }

        $act = (string) ($data['act'] ?? '');
        $isTokenRequest = $act === 'GetToken';

        if ($contentType === 'application/xml') {
            $body = FeederXmlEncoder::encode($data);
        } else {
            $body = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        $isWrite = $this->isWriteAction($act);
        $connectTimeout = (int) config('feeder.connect_timeout', 15);
        $timeout = $isTokenRequest
            ? (int) config('feeder.token_timeout', 45)
            : ($isWrite
                ? (int) config('feeder.write_timeout', 45)
                : (int) config('feeder.timeout', 120));
        $maxAttempts = $isWrite
            ? max(1, (int) config('feeder.write_retry_attempts', 2))
            : max(1, (int) config('feeder.retry_attempts', 3));
        $retryDelayUs = max(0, (int) config('feeder.retry_delay_ms', 1000)) * 1000;

        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::connectTimeout($connectTimeout)
                    ->timeout($timeout)
                    ->withHeaders(['Connection' => 'close'])
                    ->withBody((string) $body, $contentType)
                    ->post($url);

                $response->throw();

                $parsed = FeederResponseParser::parse($response->body());
                if (! is_array($parsed)) {
                    throw new RuntimeException('Respons Neo Feeder tidak dapat dibaca.');
                }

                return $parsed;
            } catch (\Throwable $e) {
                $lastException = $e;

                if ($useTokenCache) {
                    Cache::forget('feeder_ws_token');
                }

                $retryable = $this->isRetryableConnectionError($e);
                if (! $retryable || $attempt >= $maxAttempts) {
                    break;
                }

                Log::warning('Feeder WS retry.', [
                    'act' => $act,
                    'attempt' => $attempt,
                    'message' => $e->getMessage(),
                ]);

                if ($retryDelayUs > 0) {
                    usleep($retryDelayUs);
                }
            }
        }

        Log::error('Feeder WS request failed.', [
            'act' => $act,
            'attempts' => $maxAttempts,
            'timeout' => $timeout,
            'message' => $lastException?->getMessage(),
        ]);

        throw new RuntimeException(
            ($act !== '' ? "[{$act}] " : '').'Koneksi Neo Feeder gagal: '.$this->formatConnectionError($lastException, $act, $timeout),
            0,
            $lastException,
        );
    }

    protected function isWriteAction(string $act): bool
    {
        return str_starts_with($act, 'Insert')
            || str_starts_with($act, 'Update')
            || str_starts_with($act, 'Delete');
    }

    protected function isRetryableConnectionError(\Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        if ($e instanceof RequestException && $e->response === null) {
            return true;
        }

        $message = strtolower($e->getMessage());

        return str_contains($message, 'connection refused')
            || str_contains($message, 'failed to connect')
            || str_contains($message, 'could not connect')
            || str_contains($message, 'timed out')
            || str_contains($message, 'timeout')
            || str_contains($message, 'curl error 7')
            || str_contains($message, 'curl error 28');
    }

    protected function formatConnectionError(?\Throwable $e, string $act = '', int $timeoutSeconds = 0): string
    {
        $detail = trim((string) ($e?->getMessage() ?? ''));
        $url = (string) config('feeder.ws_url');
        $isWrite = $this->isWriteAction($act);

        if (
            stripos($detail, 'Connection refused') !== false
            || stripos($detail, 'Failed to connect') !== false
            || stripos($detail, 'Could not connect') !== false
            || stripos($detail, 'No connection could be made') !== false
            || stripos($detail, 'cURL error 7') !== false
        ) {
            $hint = $isWrite
                ? 'Tes Neo Feeder (GetToken) bisa sukses sementara kirim data gagal — backend Feeder sering restart atau kehabisan resource. Tunggu 1–2 menit lalu coba lagi.'
                : 'Server Neo Feeder tidak merespons (layanan mati, port 8100 tertutup, atau firewall).';

            return "tidak dapat terhubung ke {$url}. {$hint}";
        }

        if (
            stripos($detail, 'timed out') !== false
            || stripos($detail, 'timeout') !== false
            || stripos($detail, 'cURL error 28') !== false
        ) {
            $seconds = $timeoutSeconds > 0 ? $timeoutSeconds : (int) config('feeder.timeout', 120);
            $writeHint = $isWrite
                ? ' GetToken mungkin cepat, tetapi Insert/Update di Feeder tidak menjawab — cek status server Neo Feeder atau kurangi timeout kirim di FEEDER_WRITE_TIMEOUT.'
                : ' Coba lagi saat Tes Neo Feeder merespons dalam hitungan detik (bukan menit).';

            return "timeout ke {$url} setelah {$seconds} detik.{$writeHint}";
        }

        return $detail !== '' ? $detail : 'error tidak diketahui';
    }
}
