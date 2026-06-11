<?php

namespace App\Support\Feeder;

use App\Models\FeederSyncLog;

class SyncLogPresenter
{
    public static function syncTypeLabel(string $syncType): string
    {
        return (string) (config('feeder_sync_log.sync_type_labels')[$syncType] ?? $syncType);
    }

    public static function moduleConfig(string $module): ?array
    {
        $config = config('feeder_sync_log.modules')[$module] ?? null;

        return is_array($config) ? $config : null;
    }

    public static function subject(FeederSyncLog $log): string
    {
        $payload = $log->payload_summary ?? [];

        foreach (['nim', 'key', 'mk_kode', 'label'] as $field) {
            $value = $payload[$field] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '-';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function sentRecord(FeederSyncLog $log): ?array
    {
        $payload = $log->payload_summary ?? [];

        if (isset($payload['record']) && is_array($payload['record'])) {
            return $payload['record'];
        }

        if (isset($payload['records']) && is_array($payload['records'])) {
            return $payload['records'];
        }

        return null;
    }

    public static function sentRecordJson(FeederSyncLog $log): ?string
    {
        $record = self::sentRecord($log);

        if ($record === null) {
            return null;
        }

        return json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
