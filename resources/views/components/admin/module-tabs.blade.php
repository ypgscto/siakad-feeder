@props(['module'])

@php
    $config = config("feeder_sync_log.modules.{$module}");
@endphp

@if (is_array($config))
    @php
        $moduleRoutePattern = preg_replace('/\.index$/', '.*', (string) ($config['index_route'] ?? ''));
        $onLogPage = request()->routeIs((string) ($config['log_route'] ?? ''));
        $onDataPage = ! $onLogPage && $moduleRoutePattern !== '' && request()->routeIs($moduleRoutePattern);
    @endphp
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex flex-wrap gap-1" aria-label="Sub menu">
            <a
                href="{{ route($config['index_route']) }}"
                @class([
                    'inline-flex items-center px-4 py-2.5 text-sm font-medium border-b-2 transition',
                    'border-teal-600 text-teal-700' => $onDataPage,
                    'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' => ! $onDataPage,
                ])
            >
                {{ $config['label'] }}
            </a>
            <a
                href="{{ route($config['log_route']) }}"
                @class([
                    'inline-flex items-center px-4 py-2.5 text-sm font-medium border-b-2 transition',
                    'border-teal-600 text-teal-700' => $onLogPage,
                    'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' => ! $onLogPage,
                ])
            >
                Log Kirim
            </a>
        </nav>
    </div>
@endif
