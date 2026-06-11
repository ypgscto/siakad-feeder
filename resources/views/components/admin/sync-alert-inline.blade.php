@props(['module' => 'mahasiswa'])

@php
    use App\Support\Sync\SyncLogModuleResolver;

    $resolvedModule = SyncLogModuleResolver::resolveFromSession($module) ?? $module;
    $config = SyncLogModuleResolver::moduleConfig($resolvedModule);
    $logRoute = is_array($config) ? ($config['log_route'] ?? null) : null;
    $query = SyncLogModuleResolver::logQuery();
    $logUrl = null;

    if (is_string($logRoute) && $logRoute !== '' && \Illuminate\Support\Facades\Route::has($logRoute)) {
        $logUrl = route($logRoute, $query);
    } else {
        $logUrl = url('admin/'.$resolvedModule.'/log?'.http_build_query($query));
    }

    $failedItems = session('sync_failed_items', []);
    $hasFlash = session('success') || session('error');
@endphp

@if ($hasFlash)
    <div @class([
        'mb-4 rounded-lg border px-4 py-3 text-sm space-y-2',
        'bg-red-50 border-red-200 text-red-800' => session('error'),
        'bg-emerald-50 border-emerald-200 text-emerald-800' => session('success') && ! session('error'),
    ])>
        @if (session('success'))
            <p>{{ session('success') }}</p>
        @endif

        @if (session('error'))
            <p>{{ session('error') }}</p>
        @endif

        @if (is_array($failedItems) && $failedItems !== [])
            <ul class="mt-2 space-y-1 text-xs border-t border-red-200/80 pt-2 max-h-40 overflow-y-auto">
                @foreach ($failedItems as $item)
                    <li>
                        <span class="font-semibold font-mono">{{ $item['subject'] ?? '-' }}</span>
                        <span> — {{ $item['message'] ?? '' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <p class="pt-1">
            <a href="{{ $logUrl }}" class="font-semibold text-teal-700 underline hover:text-teal-900">
                lihat detail
            </a>
        </p>
    </div>
@endif
