<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public const TYPE_STRING = 'string';

    public const TYPE_TEXT = 'text';

    public const TYPE_JSON = 'json';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_BOOLEAN = 'boolean';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    public static function castStoredValue(?string $value, ?string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            self::TYPE_JSON => json_decode($value, true),
            self::TYPE_INTEGER => (int) $value,
            self::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }

    /**
     * @return array{type: string, stored: ?string}
     */
    public static function serializeValue(mixed $value, string $type = self::TYPE_STRING): array
    {
        if ($type === self::TYPE_BOOLEAN) {
            return [
                'type' => self::TYPE_BOOLEAN,
                'stored' => $value ? '1' : '0',
            ];
        }

        if ($type === self::TYPE_INTEGER) {
            return [
                'type' => self::TYPE_INTEGER,
                'stored' => (string) (int) $value,
            ];
        }

        if (is_array($value)) {
            return [
                'type' => self::TYPE_JSON,
                'stored' => json_encode($value, JSON_UNESCAPED_UNICODE),
            ];
        }

        $string = (string) $value;

        return [
            'type' => strlen($string) > 255 ? self::TYPE_TEXT : self::TYPE_STRING,
            'stored' => $string === '' ? null : $string,
        ];
    }
}
