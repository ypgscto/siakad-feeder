<?php

namespace App\Support\Feeder;

use InvalidArgumentException;

final class HandphoneNormalizer
{
    /**
     * @return list<string>
     */
    public static function candidates(?string $raw, ?string $nimFallback = null): array
    {
        $seen = [];
        $out = [];

        $add = function (string $phone) use (&$seen, &$out): void {
            if ($phone === '' || isset($seen[$phone])) {
                return;
            }

            $seen[$phone] = true;
            $out[] = $phone;
        };

        $add(self::forFeeder($raw, $nimFallback));

        $nimDigits = self::digitsOnly($nimFallback ?? '');
        if ($nimDigits !== '') {
            foreach (['088', '089', '087', '086', '085'] as $prefix) {
                $candidate = $prefix.$nimDigits;
                if (strlen($candidate) >= 10 && strlen($candidate) <= 15) {
                    $add($candidate);
                }
            }
        }

        return $out;
    }

    public static function forFeeder(?string $raw, ?string $nimFallback = null): string
    {
        $digits = self::digitsOnly($raw ?? '');

        if ($digits !== '' && ! self::isPlaceholder($digits)) {
            $normalized = self::normalize($digits);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        $nimDigits = self::digitsOnly($nimFallback ?? '');
        if ($nimDigits !== '') {
            $fromNim = self::normalize($nimDigits);
            if ($fromNim !== null) {
                return $fromNim;
            }

            return '08'.$nimDigits;
        }

        throw new InvalidArgumentException('NIM kosong — tidak bisa menentukan nomor HP untuk Feeder.');
    }

    public static function digitsOnly(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    public static function isPlaceholder(string $digits): bool
    {
        if ($digits === '') {
            return true;
        }

        return (bool) preg_match('/^0+$/', $digits);
    }

    public static function normalize(string $digits): ?string
    {
        if ($digits === '' || self::isPlaceholder($digits)) {
            return null;
        }

        if (str_starts_with($digits, '62') && strlen($digits) >= 11) {
            $digits = '0'.substr($digits, 2);
        }

        if (str_starts_with($digits, '0') && strlen($digits) >= 10 && strlen($digits) <= 15) {
            return $digits;
        }

        if (! str_starts_with($digits, '0') && strlen($digits) >= 8 && strlen($digits) <= 12) {
            $candidate = '08'.$digits;
            if (strlen($candidate) >= 10 && strlen($candidate) <= 15) {
                return $candidate;
            }
        }

        return null;
    }
}
