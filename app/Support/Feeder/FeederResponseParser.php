<?php

namespace App\Support\Feeder;

final class FeederResponseParser
{
    /**
     * @return array<string, mixed>|null
     */
    public static function parse(string $body): ?array
    {
        $body = trim($body);
        if ($body === '') {
            return null;
        }

        $json = json_decode($body, true);
        if (is_array($json)) {
            return $json;
        }

        if (str_contains($body, '<?xml') || str_contains($body, '<result')) {
            $xml = @simplexml_load_string($body);
            if ($xml !== false) {
                $decoded = json_decode(json_encode($xml), true);

                return is_array($decoded) ? $decoded : null;
            }
        }

        return null;
    }

    public static function isSuccess(?array $result): bool
    {
        return is_array($result)
            && isset($result['error_code'])
            && (int) $result['error_code'] === 0;
    }

    public static function errorDescription(?array $result): string
    {
        if (! is_array($result)) {
            return 'Respons Feeder kosong atau tidak valid.';
        }

        return (string) ($result['error_desc'] ?? 'Error Feeder tidak diketahui.');
    }
}
