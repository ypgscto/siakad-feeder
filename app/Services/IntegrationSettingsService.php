<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class IntegrationSettingsService
{
    public const CACHE_KEY = 'sifeeder.integration_settings';

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $runtimeCache = null;

    /**
     * @return array<string, array{label: string, group: string, type: string, secret?: bool, config?: string|list<string>, env?: string, help?: string}>
     */
    public function definitions(): array
    {
        return [
            'siakad.api.base_url' => [
                'label' => 'Base URL Siakad-API',
                'group' => 'siakad',
                'type' => 'string',
                'config' => 'siakad.base_url',
                'env' => 'SIAKAD_API_BASE_URL',
                'help' => 'Contoh: http://siakad-api.test atau http://127.0.0.1/siakad-api/public',
            ],
            'siakad.api.token' => [
                'label' => 'Token Siakad-API',
                'group' => 'siakad',
                'type' => 'string',
                'secret' => true,
                'config' => 'siakad.token',
                'env' => 'SIAKAD_API_TOKEN',
            ],
            'siakad.api.timeout' => [
                'label' => 'Timeout Siakad-API (detik)',
                'group' => 'siakad',
                'type' => 'integer',
                'config' => 'siakad.timeout',
                'env' => 'SIAKAD_API_TIMEOUT',
            ],
            'siakad.api.host' => [
                'label' => 'Header Host (opsional)',
                'group' => 'siakad',
                'type' => 'string',
                'config' => 'siakad.api_host',
                'env' => 'SIAKAD_API_HOST',
                'help' => 'Isi jika base URL memakai 127.0.0.1 tetapi vhost Laragon berbeda.',
            ],
            'siakad.kode_id' => [
                'label' => 'Kode ID institusi (KodeID)',
                'group' => 'siakad',
                'type' => 'string',
                'config' => 'sifeeder_auth.kode_id',
                'env' => 'SIAKAD_KODE_ID',
            ],
            'feeder.ws_url' => [
                'label' => 'URL Web Service Neo Feeder',
                'group' => 'feeder',
                'type' => 'string',
                'config' => 'feeder.ws_url',
                'env' => 'FEEDER_WS_URL',
            ],
            'feeder.username' => [
                'label' => 'Username Neo Feeder',
                'group' => 'feeder',
                'type' => 'string',
                'config' => 'feeder.username',
                'env' => 'FEEDER_USERNAME',
            ],
            'feeder.password' => [
                'label' => 'Password Neo Feeder',
                'group' => 'feeder',
                'type' => 'string',
                'secret' => true,
                'config' => 'feeder.password',
                'env' => 'FEEDER_PASSWORD',
            ],
            'feeder.timeout' => [
                'label' => 'Timeout Neo Feeder (detik)',
                'group' => 'feeder',
                'type' => 'integer',
                'config' => 'feeder.timeout',
                'env' => 'FEEDER_TIMEOUT',
            ],
            'feeder.token_ttl_seconds' => [
                'label' => 'Cache token Feeder (detik)',
                'group' => 'feeder',
                'type' => 'integer',
                'config' => 'feeder.token_ttl_seconds',
                'env' => 'FEEDER_TOKEN_TTL',
                'help' => 'Berapa lama token WS disimpan sebelum diambil ulang.',
            ],
            'feeder.id_perguruan_tinggi' => [
                'label' => 'UUID Perguruan Tinggi (Feeder)',
                'group' => 'feeder',
                'type' => 'string',
                'config' => ['feeder.id_perguruan_tinggi', 'feeder_maps.id_perguruan_tinggi'],
                'env' => 'FEEDER_ID_PERGURUAN_TINGGI',
            ],
            'feeder.default_id_wilayah' => [
                'label' => 'ID Wilayah default',
                'group' => 'feeder',
                'type' => 'string',
                'config' => 'feeder.default_id_wilayah',
                'env' => 'FEEDER_DEFAULT_ID_WILAYAH',
            ],
            'feeder.id_pt_pindahan' => [
                'label' => 'UUID PT Pindahan',
                'group' => 'feeder',
                'type' => 'string',
                'config' => 'feeder_maps.id_perguruan_tinggi_pindahan',
                'env' => 'FEEDER_ID_PT_PINDAHAN',
            ],
            'feeder.id_pt_rpl' => [
                'label' => 'UUID PT RPL',
                'group' => 'feeder',
                'type' => 'string',
                'config' => 'feeder_maps.id_perguruan_tinggi_rpl',
                'env' => 'FEEDER_ID_PT_RPL',
            ],
            'feeder.default_email' => [
                'label' => 'Email default mahasiswa (Feeder)',
                'group' => 'feeder',
                'type' => 'string',
                'config' => 'feeder_maps.default_email',
                'env' => 'FEEDER_DEFAULT_EMAIL',
            ],
            'auth.allow_local_fallback' => [
                'label' => 'Izinkan login lokal (bootstrap admin)',
                'group' => 'auth',
                'type' => 'boolean',
                'config' => 'sifeeder_auth.allow_local_fallback',
                'env' => 'SIFEEDER_ALLOW_LOCAL_LOGIN_FALLBACK',
            ],
            'auth.use_legacy_login_fallback' => [
                'label' => 'Cadangan login legacy Siakad-GS',
                'group' => 'auth',
                'type' => 'boolean',
                'config' => 'sifeeder_auth.use_legacy_login_fallback',
                'env' => 'SIFEEDER_USE_LEGACY_LOGIN_FALLBACK',
                'help' => 'Coba login-password-hash / mysql-legacy jika login-app gagal.',
            ],
            'auth.email_domain' => [
                'label' => 'Domain email sintetis SSO',
                'group' => 'auth',
                'type' => 'string',
                'config' => 'sifeeder_auth.email_domain',
                'env' => 'SIFEEDER_SIAKAD_EMAIL_DOMAIN',
            ],
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function hasStored(string $key): bool
    {
        if (! Schema::hasTable('settings')) {
            return false;
        }

        return Setting::query()->where('key', $key)->exists();
    }

    public function isSecret(string $key): bool
    {
        return (bool) ($this->definitions()[$key]['secret'] ?? false);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function updateFromInput(array $input): void
    {
        foreach ($this->definitions() as $key => $definition) {
            if (! Arr::has($input, $key)) {
                continue;
            }

            $value = Arr::get($input, $key);

            if (($definition['secret'] ?? false) && trim((string) $value) === '') {
                continue;
            }

            if ($definition['type'] === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            if ($definition['type'] === 'integer') {
                $value = (int) $value;
            }

            if ($key === 'siakad.api.base_url') {
                $value = rtrim(trim((string) $value), '/');
                if ($value !== '' && ! str_starts_with($value, 'http')) {
                    throw new \InvalidArgumentException(
                        'Base URL Siakad-API harus diawali http:// atau https:// (bukan path folder server).',
                    );
                }
            }

            $this->set($key, $value);
        }

        Cache::forget(self::CACHE_KEY);
        $this->runtimeCache = null;
        Cache::forget('feeder_ws_token');
    }

    public function set(string $key, mixed $value): void
    {
        $type = $this->definitions()[$key]['type'] ?? Setting::TYPE_STRING;
        $serialized = Setting::serializeValue($value, $type);

        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $serialized['stored'],
                'type' => $serialized['type'],
            ]
        );

        Cache::forget(self::CACHE_KEY);
        $this->runtimeCache = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->runtimeCache !== null) {
            return $this->runtimeCache;
        }

        $this->runtimeCache = Cache::rememberForever(self::CACHE_KEY, function () {
            $settings = $this->defaultsFromEnv();

            if (Schema::hasTable('settings')) {
                foreach (Setting::query()->get() as $row) {
                    $settings[$row->key] = Setting::castStoredValue($row->value, $row->type);
                }
            }

            return $settings;
        });

        return $this->runtimeCache;
    }

    /**
     * @return array<string, mixed>
     */
    public function forForm(): array
    {
        $values = $this->all();

        foreach ($this->definitions() as $key => $definition) {
            if (($definition['secret'] ?? false) && trim((string) ($values[$key] ?? '')) !== '') {
                $values[$key] = '';
            }
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultsFromEnv(): array
    {
        $defaults = [];

        foreach ($this->definitions() as $key => $definition) {
            $envKey = $definition['env'] ?? null;
            $configKeys = $this->normalizeConfigKeys($definition['config'] ?? null);

            $value = null;

            if ($envKey !== null && env($envKey) !== null && env($envKey) !== '') {
                $value = env($envKey);
            } elseif ($configKeys !== []) {
                $value = config($configKeys[0]);
            }

            if ($definition['type'] === 'integer') {
                $value = (int) ($value ?? 0);
            } elseif ($definition['type'] === 'boolean') {
                $value = filter_var($value ?? false, FILTER_VALIDATE_BOOLEAN);
            } else {
                $value = (string) ($value ?? '');
            }

            $defaults[$key] = $value;
        }

        return $defaults;
    }

    public function applyToConfig(): void
    {
        foreach ($this->definitions() as $key => $definition) {
            $value = $this->get($key);
            $configKeys = $this->normalizeConfigKeys($definition['config'] ?? null);

            if ($configKeys === []) {
                continue;
            }

            if ($definition['type'] === 'integer') {
                $value = (int) $value;
            } elseif ($definition['type'] === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif ($definition['type'] === 'string' && $key === 'siakad.api.base_url') {
                $value = rtrim((string) $value, '/');
            }

            foreach ($configKeys as $configKey) {
                config([$configKey => $value]);
            }
        }
    }

    public function seedMissingFromEnv(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        foreach ($this->defaultsFromEnv() as $key => $value) {
            if (! Setting::query()->where('key', $key)->exists()) {
                $this->set($key, $value);
            }
        }
    }

    /**
     * @param  string|list<string>|null  $config
     * @return list<string>
     */
    protected function normalizeConfigKeys(string|array|null $config): array
    {
        if ($config === null) {
            return [];
        }

        return is_array($config) ? $config : [$config];
    }
}
