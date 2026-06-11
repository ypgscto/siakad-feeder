<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SiakadUserSyncService
{
    /**
     * Sinkron data profil Siakad saat login — role tetap dari DB lokal.
     *
     * @param  array<string, mixed>  $profile
     */
    public function syncOnLogin(array $profile): User
    {
        $login = $this->resolveSiakadLogin($profile);
        if ($login === '') {
            throw new InvalidArgumentException('Profil Siakad tanpa login.');
        }

        $this->assertJenisUserAllowed($profile);

        $email = $this->resolveEmail($profile, $login);
        $name = (string) ($profile['nama'] ?? $profile['Nama'] ?? $login);
        $siakadUserId = (string) ($profile['siakad_user_id'] ?? $profile['UserID'] ?? $login);
        $jenisUser = (string) ($profile['jenis_user'] ?? $profile['JenisUser'] ?? '');

        $user = $this->findExistingUser($siakadUserId, $login, $email);

        if ($user === null) {
            throw new InvalidArgumentException('Akun belum terdaftar di Siakad-Feeder. Hubungi superadmin.');
        }

        if (! $user->is_active) {
            throw new InvalidArgumentException('Akun tidak aktif. Hubungi superadmin.');
        }

        $user->fill([
            'name' => $name,
            'email' => $email,
            'siakad_user_id' => $siakadUserId,
            'siakad_login' => $login,
            'jenis_user' => $jenisUser,
        ]);
        // prodi_id & role tetap dari penugasan superadmin
        $user->save();

        return $user;
    }

    /**
     * Provisioning manual dari hasil lookup SSO (superadmin).
     *
     * @param  array<string, mixed>  $profile
     */
    public function provisionFromSsoProfile(array $profile, string $role, ?string $prodiId = null): User
    {
        if (! in_array($role, config('sifeeder_auth.assignable_roles', []), true)) {
            throw new InvalidArgumentException('Role tidak valid.');
        }

        if (in_array($role, config('sifeeder_auth.roles_requiring_prodi', []), true)) {
            $prodiId = trim((string) ($prodiId ?? $profile['prodi_id'] ?? ''));
            if ($prodiId === '') {
                throw new InvalidArgumentException('Program studi wajib dipilih untuk role Ketua Prodi.');
            }
        } else {
            $prodiId = null;
        }

        $login = $this->resolveSiakadLogin($profile);
        if ($login === '') {
            throw new InvalidArgumentException('Profil SSO tanpa login.');
        }

        $this->assertJenisUserAllowed($profile);

        $email = $this->resolveEmail($profile, $login);
        $name = (string) ($profile['nama'] ?? $profile['Nama'] ?? $login);
        $siakadUserId = (string) ($profile['siakad_user_id'] ?? $profile['UserID'] ?? $login);
        $jenisUser = (string) ($profile['jenis_user'] ?? $profile['JenisUser'] ?? '');

        $user = $this->findExistingUser($siakadUserId, $login, $email);

        $attributes = [
            'name' => $name,
            'email' => $email,
            'siakad_user_id' => $siakadUserId,
            'siakad_login' => $login,
            'jenis_user' => $jenisUser,
            'role' => $role,
            'prodi_id' => $prodiId,
            'is_active' => true,
            'password' => Hash::make(Str::random(32)),
        ];

        if ($user) {
            $user->fill($attributes);
            $user->save();

            return $user;
        }

        return User::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    protected function assertJenisUserAllowed(array $profile): void
    {
        $jenisUser = (string) ($profile['jenis_user'] ?? $profile['JenisUser'] ?? '');

        if (in_array($jenisUser, config('sifeeder_auth.denied_jenis_user', []), true)) {
            throw new InvalidArgumentException('Jenis akun Siakad tidak diizinkan di Siakad-Feeder.');
        }

        $allowed = config('sifeeder_auth.allowed_jenis_user', []);
        if ($allowed !== [] && $jenisUser !== '' && ! in_array($jenisUser, $allowed, true)) {
            throw new InvalidArgumentException('Jenis akun Siakad tidak diizinkan di Siakad-Feeder.');
        }
    }

    protected function findExistingUser(string $siakadUserId, string $login, string $email): ?User
    {
        return User::query()
            ->where(function ($query) use ($siakadUserId, $login, $email): void {
                if ($siakadUserId !== '') {
                    $query->orWhere('siakad_user_id', $siakadUserId);
                }
                if ($login !== '') {
                    $query->orWhere('siakad_login', $login);
                }
                if ($email !== '') {
                    $query->orWhere('email', $email);
                }
            })
            ->first();
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    /**
     * @param  array<string, mixed>  $profile
     */
    protected function resolveSiakadLogin(array $profile): string
    {
        foreach (['login', 'Login', 'ref_id', 'Ref_ID'] as $key) {
            $value = trim((string) ($profile[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $email = trim((string) ($profile['email'] ?? $profile['Email'] ?? ''));

        return $email;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    protected function resolveEmail(array $profile, string $login): string
    {
        $email = trim((string) ($profile['email'] ?? $profile['Email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return strtolower($email);
        }

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return strtolower($login);
        }

        return strtolower($login).'@'.config('sifeeder_auth.email_domain');
    }
}
