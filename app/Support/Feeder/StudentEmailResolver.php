<?php

namespace App\Support\Feeder;

final class StudentEmailResolver
{
    /**
     * @param  array<string, mixed>  $student
     */
    public static function forFeeder(array $student, ?string $nimFallback = null): string
    {
        $email = trim((string) ($student['email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return strtolower($email);
        }

        $nim = trim((string) ($student['nim'] ?? $student['mhsw_id'] ?? $nimFallback ?? ''));
        if ($nim !== '') {
            $local = preg_replace('/[^a-zA-Z0-9._+-]/', '', $nim) ?? '';
            if ($local !== '') {
                $domain = (string) config('feeder_maps.student_email_domain', 'stikes.gunungsari.id');

                return strtolower($local).'@'.ltrim($domain, '@');
            }
        }

        return (string) config('feeder_maps.default_email', 'yayasanpendidikan.gunungsari@gmail.com');
    }
}
