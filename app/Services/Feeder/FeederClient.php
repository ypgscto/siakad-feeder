<?php

namespace App\Services\Feeder;

use App\Support\Feeder\FeederResponseParser;
use App\Support\Feeder\FeederXmlEncoder;
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

        return $this->post($body, 'application/xml');
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

        if ($contentType === 'application/xml') {
            $body = FeederXmlEncoder::encode($data);
        } else {
            $body = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        try {
            $response = Http::timeout((int) config('feeder.timeout', 180))
                ->withBody((string) $body, $contentType)
                ->post($url);
            $response->throw();
        } catch (\Throwable $e) {
            if ($useTokenCache) {
                Cache::forget('feeder_ws_token');
            }

            Log::error('Feeder WS request failed.', [
                'act' => $data['act'] ?? null,
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('Koneksi Neo Feeder gagal: '.$e->getMessage(), 0, $e);
        }

        $parsed = FeederResponseParser::parse($response->body());
        if (! is_array($parsed)) {
            throw new RuntimeException('Respons Neo Feeder tidak dapat dibaca.');
        }

        return $parsed;
    }
}
