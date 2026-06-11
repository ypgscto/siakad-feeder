@props(['module'])

@php
    use App\Support\Sync\SyncLogModuleResolver;

    $config = SyncLogModuleResolver::moduleConfig($module);
    $logRoute = is_array($config) ? ($config['log_route'] ?? null) : null;
    $query = ['date_from' => now()->format('Y-m-d')];
    $logUrl = null;

    if (is_string($logRoute) && $logRoute !== '' && \Illuminate\Support\Facades\Route::has($logRoute)) {
        $logUrl = route($logRoute, $query);
    } else {
        $logUrl = url('admin/'.$module.'/log?'.http_build_query($query));
    }
@endphp

<a href="{{ $logUrl }}" {{ $attributes->merge(['class' => 'text-teal-700 underline hover:text-teal-900 text-xs font-semibold']) }}>
    lihat detail log
</a>
