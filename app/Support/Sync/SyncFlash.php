<?php

namespace App\Support\Sync;

use App\DTOs\SyncBatchResult;
use Illuminate\Http\RedirectResponse;

class SyncFlash
{
    public static function apply(
        RedirectResponse $redirect,
        SyncBatchResult $result,
        string $label,
        string $logModule,
    ): RedirectResponse {
        if ($result->successCount > 0) {
            $redirect = $redirect->with('success', "{$label}: ".$result->flashSuccess());
        }

        if ($result->failedCount > 0) {
            $redirect = $redirect->with('error', "{$label}: ".$result->flashError());
            $redirect = $redirect->with('sync_failed_items', $result->failedItems);
        }

        if ($result->successCount > 0 || $result->failedCount > 0) {
            $redirect = $redirect
                ->with('sync_log_module', $logModule)
                ->with('sync_result', true);
        }

        return $redirect;
    }
}
