@props(['module' => null])

@php
    use App\Support\Sync\SyncLogModuleResolver;

    $resolvedModule = SyncLogModuleResolver::resolveFromSession($module);
    $logConfig = SyncLogModuleResolver::moduleConfig($resolvedModule);
    $failedItems = session('sync_failed_items', []);
    $showPanel = SyncLogModuleResolver::shouldShowPanel($module);
    $logQuery = SyncLogModuleResolver::logQuery();
@endphp

@if (session('success') && ! $showPanel)
    <div {{ $attributes->merge(['class' => 'mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm']) }}>
        {{ session('success') }}
    </div>
@endif

@if (session('error') && ! $showPanel)
    <div {{ $attributes->merge(['class' => 'mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm']) }}>
        {{ session('error') }}
    </div>
@endif

@if ($showPanel)
    <div {{ $attributes->merge(['class' => 'mb-4 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden']) }}>
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50 flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-800">Hasil kirim data ke Neo Feeder</h3>
            @if (is_array($logConfig) && \Illuminate\Support\Facades\Route::has($logConfig['log_route']))
                <a
                    href="{{ route($logConfig['log_route'], $logQuery) }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-teal-200 bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-800 hover:bg-teal-100"
                >
                    Buka halaman log
                </a>
            @endif
        </div>

        <div class="px-4 py-3 space-y-3">
            @if (session('success'))
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if (is_array($failedItems) && $failedItems !== [])
                <div class="rounded-lg border border-red-100 bg-red-50/50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-red-800 mb-2">
                        Detail gagal per baris ({{ count($failedItems) }})
                    </p>
                    <ul class="max-h-56 overflow-y-auto space-y-1.5 text-sm text-red-900">
                        @foreach ($failedItems as $item)
                            <li class="font-mono text-xs leading-relaxed">
                                <span class="font-semibold text-red-950">{{ $item['subject'] ?? '-' }}</span>
                                <span class="text-red-700"> — {{ $item['message'] ?? '' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @elseif (session('error'))
                <p class="text-xs text-slate-500">
                    Detail per NIM belum tersimpan di sesi ini. Buka log kirim untuk riwayat lengkap di database.
                </p>
            @endif

            @if (is_array($logConfig) && \Illuminate\Support\Facades\Route::has($logConfig['log_route']))
                <div class="flex flex-wrap items-center gap-3 pt-1 border-t border-slate-100">
                    <a
                        href="{{ route($logConfig['log_route'], $logQuery) }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Lihat detail log kirim
                    </a>
                    <span class="text-xs text-slate-500">
                        Payload lengkap &amp; riwayat hari ini
                    </span>
                </div>
            @endif
        </div>
    </div>
@endif
