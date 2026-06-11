<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\SiakadAuthApiService;
use App\Services\SiakadUserSyncService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class LoginRequest extends FormRequest
{
    protected ?SiakadAuthApiService $authApi = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'login' => 'email atau username Siakad',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim((string) $this->input('login'));
        $password = (string) $this->input('password');

        $user = $this->attemptSiakadLogin($login, $password)
            ?? $this->attemptLocalFallback($login, $password);

        if (! $user) {
            RateLimiter::hit($this->throttleKey());

            $registered = $this->findRegisteredUser($login);
            if ($registered !== null) {
                $authApi = $this->authApi ?? app(SiakadAuthApiService::class);

                if ($authApi->wasRateLimited()) {
                    throw ValidationException::withMessages([
                        'login' => $authApi->lastErrorMessage()
                            ?? 'Terlalu banyak percobaan login. Tunggu 1 menit lalu coba lagi.',
                    ]);
                }

                $apiMessage = $this->sanitizeAuthErrorMessage($authApi->lastErrorMessage());

                throw ValidationException::withMessages([
                    'login' => $apiMessage
                        ? 'Password Siakad salah. '.$apiMessage
                        : 'Password Siakad salah. Gunakan password login aplikasi Siakad (SSO), bukan password lokal Siakad-Feeder.',
                ]);
            }

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        if (! $user->canLoginToFeeder()) {
            throw ValidationException::withMessages([
                'login' => 'Akun tidak aktif atau tidak diizinkan login ke Siakad-Feeder.',
            ]);
        }

        Auth::login($user, $this->boolean('remember'));
        RateLimiter::clear($this->throttleKey());
    }

    protected function attemptSiakadLogin(string $login, string $password): ?User
    {
        $this->authApi = app(SiakadAuthApiService::class);
        $authApi = $this->authApi;
        $identifiers = $this->loginIdentifiers($login);

        foreach ($identifiers as $identifier) {
            try {
                $profile = $authApi->attemptLoginApp($identifier, $password);
            } catch (RuntimeException $e) {
                if (! config('sifeeder_auth.allow_local_fallback', true)) {
                    throw ValidationException::withMessages([
                        'login' => $e->getMessage(),
                    ]);
                }

                return null;
            }

            if ($authApi->wasRateLimited()) {
                break;
            }

            if ($profile === null) {
                continue;
            }

            return $this->syncProfileOnLogin($profile);
        }

        if ($authApi->wasRateLimited()) {
            return null;
        }

        if (! config('sifeeder_auth.use_legacy_login_fallback', false)) {
            return null;
        }

        foreach ($identifiers as $identifier) {
            try {
                $profile = $authApi->attemptLegacyLogin($identifier, $password);
            } catch (RuntimeException) {
                return null;
            }

            if ($authApi->wasRateLimited()) {
                break;
            }

            if ($profile === null) {
                continue;
            }

            return $this->syncProfileOnLogin($profile);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    protected function syncProfileOnLogin(array $profile): User
    {
        try {
            return app(SiakadUserSyncService::class)->syncOnLogin($profile);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'login' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return list<string>
     */
    protected function loginIdentifiers(string $login): array
    {
        $login = trim($login);
        $identifiers = [$login];

        $registered = $this->findRegisteredUser($login);
        if ($registered !== null) {
            foreach ([$registered->siakad_login, $registered->email] as $candidate) {
                $candidate = trim((string) $candidate);
                if ($candidate !== '' && ! in_array($candidate, $identifiers, true)) {
                    $identifiers[] = $candidate;
                }
            }
        }

        try {
            $ssoProfile = app(SiakadAuthApiService::class)->lookupSso($login);
        } catch (RuntimeException) {
            $ssoProfile = null;
        }

        if (is_array($ssoProfile)) {
            foreach (['login', 'ref_id', 'email'] as $key) {
                $candidate = trim((string) ($ssoProfile[$key] ?? ''));
                if ($candidate !== '' && ! in_array($candidate, $identifiers, true)) {
                    $identifiers[] = $candidate;
                }
            }
        }

        foreach (array_values($identifiers) as $identifier) {
            if (! str_contains($identifier, '@')) {
                continue;
            }

            $localPart = strstr($identifier, '@', true);
            if (! is_string($localPart) || $localPart === '') {
                continue;
            }

            if (! in_array($localPart, $identifiers, true)) {
                $identifiers[] = $localPart;
            }

            $shortLogin = strtok($localPart, '.');
            if (is_string($shortLogin) && $shortLogin !== '' && $shortLogin !== $localPart && ! in_array($shortLogin, $identifiers, true)) {
                $identifiers[] = $shortLogin;
            }
        }

        return array_values(array_unique($identifiers));
    }

    protected function findRegisteredUser(string $login): ?User
    {
        $login = trim($login);
        $email = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? strtolower($login)
            : strtolower($login).'@'.config('sifeeder_auth.email_domain');

        return User::query()
            ->where('email', $email)
            ->orWhere('siakad_login', $login)
            ->orWhere('email', $login)
            ->first();
    }

    protected function attemptLocalFallback(string $login, string $password): ?User
    {
        if (! config('sifeeder_auth.allow_local_fallback', true)) {
            return null;
        }

        $user = $this->findRegisteredUser($login);

        if (! $user || $user->isSiakadSourced()) {
            return null;
        }

        if (! Auth::getProvider()->validateCredentials($user, ['password' => $password])) {
            return null;
        }

        if (! $user->canLoginToFeeder()) {
            return null;
        }

        return $user;
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('login')).'|'.$this->ip());
    }

    protected function sanitizeAuthErrorMessage(?string $message): ?string
    {
        $message = trim((string) $message);
        if ($message === '') {
            return null;
        }

        if (str_contains($message, 'SQLSTATE') || str_contains($message, 'no such table')) {
            return null;
        }

        return $message;
    }
}
