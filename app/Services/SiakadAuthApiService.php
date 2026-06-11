<?php

namespace App\Services;

use App\Support\Siakad\SiakadConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SiakadAuthApiService
{
    protected ?string $lastErrorMessage = null;

    protected bool $rateLimited = false;

    public function lastErrorMessage(): ?string
    {
        return $this->lastErrorMessage;
    }

    public function wasRateLimited(): bool
    {
        return $this->rateLimited;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function attemptLogin(string $login, string $password): ?array
    {
        $profile = $this->attemptLoginApp($login, $password);
        if ($profile !== null) {
            return $profile;
        }

        if ($this->rateLimited) {
            return null;
        }

        if (! config('sifeeder_auth.use_legacy_login_fallback', false)) {
            return null;
        }

        return $this->attemptLegacyLogin($login, $password);
    }

    /**
     * Login SSO standar — satu panggilan ke /api/auth/login-app (seperti SI-Tercapai).
     *
     * @return array<string, mixed>|null
     */
    public function attemptLoginApp(string $login, string $password): ?array
    {
        $login = trim($login);
        if ($login === '' || $password === '') {
            return null;
        }

        return $this->postAuthEndpoint(
            (string) config('sifeeder_auth.login_endpoint', '/api/auth/login-app'),
            $this->authPayload($login, $password),
        );
    }

    /**
     * Cadangan password legacy Siakad-GS (karyawan LevelID).
     *
     * @return array<string, mixed>|null
     */
    public function attemptLegacyLogin(string $login, string $password): ?array
    {
        $login = trim($login);
        if ($login === '' || $password === '' || $this->rateLimited) {
            return null;
        }

        $basePayload = $this->authPayload($login, $password);

        foreach (config('sifeeder_auth.login_level_ids', ['1', '20']) as $levelId) {
            $levelId = (string) $levelId;
            $withLevel = array_merge($basePayload, ['level_id' => $levelId]);

            foreach ([
                (string) config('sifeeder_auth.login_password_hash_endpoint', '/api/auth/login-password-hash'),
                (string) config('sifeeder_auth.login_mysql_legacy_endpoint', '/api/auth/login-mysql-legacy'),
            ] as $endpoint) {
                $profile = $this->postAuthEndpoint($endpoint, $withLevel);
                if ($profile !== null) {
                    return $profile;
                }

                if ($this->rateLimited) {
                    return null;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function authPayload(string $login, string $password): array
    {
        $payload = [
            'login' => $login,
            'password' => $password,
        ];

        $kodeId = trim((string) config('sifeeder_auth.kode_id', ''));
        if ($kodeId !== '') {
            $payload['kode_id'] = $kodeId;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function lookupSso(string $login): ?array
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }

        $endpoint = (string) config('sifeeder_auth.sso_lookup_endpoint', '/api/users/sso-lookup');

        try {
            $query = ['login' => $login];
            $kodeId = trim((string) config('sifeeder_auth.kode_id', ''));
            if ($kodeId !== '') {
                $query['kode_id'] = $kodeId;
            }

            $response = $this->httpClient()->get($endpoint, $query);
            $response->throw();
        } catch (ConnectionException $e) {
            Log::error('Siakad SSO lookup connection failed.', ['message' => $e->getMessage()]);
            throw new RuntimeException('Koneksi ke Siakad-API gagal saat pencarian SSO.');
        } catch (RequestException) {
            return null;
        }

        $json = $response->json();
        if (! is_array($json)) {
            return null;
        }

        $data = $json['data'] ?? $json['user'] ?? null;

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>|null
     */
    protected function postAuthEndpoint(string $endpoint, array $body): ?array
    {
        try {
            $response = $this->httpClient()->post($endpoint, $body);
        } catch (ConnectionException $e) {
            Log::error('Siakad auth connection failed.', ['endpoint' => $endpoint, 'message' => $e->getMessage()]);
            throw new RuntimeException('Koneksi ke Siakad-API gagal saat login.');
        } catch (RequestException $e) {
            $this->rememberAuthError($e->response);

            return null;
        }

        return $this->parseAuthResponse($response);
    }

    protected function rememberAuthError(?Response $response): void
    {
        if ($response === null) {
            return;
        }

        if ($response->status() === 429) {
            $this->rateLimited = true;
            $this->lastErrorMessage = 'Terlalu banyak percobaan login ke Siakad-API. Tunggu 1 menit lalu coba lagi.';

            return;
        }

        $json = $response->json();
        if (is_array($json)) {
            $message = trim((string) ($json['message'] ?? ''));
            if ($message !== '') {
                $this->lastErrorMessage = $message;
            }
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function parseAuthResponse(Response $response): ?array
    {
        if ($response->status() === 429) {
            $this->rateLimited = true;
            $this->lastErrorMessage = 'Terlalu banyak percobaan login ke Siakad-API. Tunggu 1 menit lalu coba lagi.';

            return null;
        }

        if ($response->failed()) {
            $this->rememberAuthError($response);

            return null;
        }

        $json = $response->json();
        if (! is_array($json)) {
            return null;
        }

        if (($json['success'] ?? true) === false) {
            $this->lastErrorMessage = trim((string) ($json['message'] ?? 'Login gagal.'));

            return null;
        }

        $data = $json['data'] ?? $json['user'] ?? null;

        return is_array($data) ? $data : null;
    }

    protected function httpClient()
    {
        $client = Http::timeout((int) config('siakad.timeout', 120))
            ->acceptJson()
            ->baseUrl(SiakadConfig::baseUrl());

        if (SiakadConfig::token() !== '') {
            $client = $client->withToken(SiakadConfig::token());
        }

        $apiHost = trim((string) config('siakad.api_host', ''));
        if ($apiHost !== '' && str_contains(SiakadConfig::baseUrl(), '127.0.0.1')) {
            $client = $client->withHeaders(['Host' => $apiHost]);
        }

        return $client;
    }
}
