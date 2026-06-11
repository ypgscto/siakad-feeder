<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeederSyncLog;
use App\Support\Feeder\SyncLogPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SyncLogController extends Controller
{
    public function index(Request $request): View
    {
        $module = (string) ($request->route()?->parameter('module') ?? '');

        $moduleConfig = SyncLogPresenter::moduleConfig($module);

        abort_if($module === '' || $moduleConfig === null, 404);

        $filters = $request->validate([
            'status' => ['nullable', 'in:success,failed'],
            'sync_type' => ['nullable', 'string', 'max:80'],
            'q' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $query = FeederSyncLog::query()
            ->with('user')
            ->whereIn('sync_type', $moduleConfig['sync_types'])
            ->latest('created_at');

        if (($filters['status'] ?? '') === 'success') {
            $query->where('success', true);
        } elseif (($filters['status'] ?? '') === 'failed') {
            $query->where('success', false);
        }

        if (($filters['sync_type'] ?? '') !== '') {
            $query->where('sync_type', $filters['sync_type']);
        }

        if (($filters['date_from'] ?? '') !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (($filters['date_to'] ?? '') !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (($filters['q'] ?? '') !== '') {
            $search = (string) $filters['q'];
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('feeder_error_desc', 'like', '%'.$search.'%')
                    ->orWhere('sync_type', 'like', '%'.$search.'%')
                    ->orWhere('payload_summary->nim', 'like', '%'.$search.'%')
                    ->orWhere('payload_summary->key', 'like', '%'.$search.'%')
                    ->orWhere('payload_summary->mk_kode', 'like', '%'.$search.'%');
            });
        }

        return view('admin.sync-log.index', [
            'title' => 'Log Kirim — '.$moduleConfig['label'],
            'module' => $module,
            'moduleConfig' => $moduleConfig,
            'filters' => array_merge([
                'status' => '',
                'sync_type' => '',
                'q' => '',
                'date_from' => '',
                'date_to' => '',
            ], $filters),
            'logs' => $query->paginate(30)->withQueryString(),
        ]);
    }
}
