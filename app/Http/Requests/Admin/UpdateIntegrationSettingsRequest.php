<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIntegrationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'siakad.api.base_url' => ['required', 'string', 'max:500'],
            'siakad.api.token' => ['nullable', 'string', 'max:500'],
            'siakad.api.timeout' => ['required', 'integer', 'min:5', 'max:600'],
            'siakad.api.host' => ['nullable', 'string', 'max:255'],
            'siakad.kode_id' => ['nullable', 'string', 'max:30'],
            'feeder.ws_url' => ['required', 'string', 'max:500'],
            'feeder.username' => ['required', 'string', 'max:255'],
            'feeder.password' => ['nullable', 'string', 'max:255'],
            'feeder.timeout' => ['required', 'integer', 'min:5', 'max:600'],
            'feeder.token_ttl_seconds' => ['required', 'integer', 'min:60', 'max:3600'],
            'feeder.id_perguruan_tinggi' => ['nullable', 'string', 'max:64'],
            'feeder.default_id_wilayah' => ['nullable', 'string', 'max:20'],
            'feeder.id_pt_pindahan' => ['nullable', 'string', 'max:64'],
            'feeder.id_pt_rpl' => ['nullable', 'string', 'max:64'],
            'feeder.default_email' => ['nullable', 'email', 'max:255'],
            'auth.allow_local_fallback' => ['nullable', 'boolean'],
            'auth.use_legacy_login_fallback' => ['nullable', 'boolean'],
            'auth.email_domain' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'siakad.api.base_url' => 'Base URL Siakad-API',
            'siakad.api.token' => 'Token Siakad-API',
            'siakad.api.timeout' => 'Timeout Siakad-API',
            'siakad.api.host' => 'Header Host',
            'siakad.kode_id' => 'Kode ID institusi',
            'feeder.ws_url' => 'URL Neo Feeder',
            'feeder.username' => 'Username Neo Feeder',
            'feeder.password' => 'Password Neo Feeder',
            'feeder.timeout' => 'Timeout Neo Feeder',
            'feeder.token_ttl_seconds' => 'Cache token Feeder',
            'feeder.id_perguruan_tinggi' => 'UUID Perguruan Tinggi',
            'feeder.default_id_wilayah' => 'ID Wilayah default',
            'feeder.id_pt_pindahan' => 'UUID PT Pindahan',
            'feeder.id_pt_rpl' => 'UUID PT RPL',
            'feeder.default_email' => 'Email default mahasiswa',
            'auth.allow_local_fallback' => 'Login lokal bootstrap',
            'auth.use_legacy_login_fallback' => 'Login legacy Siakad-GS',
            'auth.email_domain' => 'Domain email SSO',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'auth.allow_local_fallback' => $this->boolean('auth.allow_local_fallback'),
            'auth.use_legacy_login_fallback' => $this->boolean('auth.use_legacy_login_fallback'),
        ]);
    }
}
