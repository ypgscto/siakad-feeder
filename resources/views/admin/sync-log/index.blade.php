<x-app-layout :title="$title">
    <div class="max-w-7xl mx-auto space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">{{ $moduleConfig['label'] }}</h2>
            <p class="text-sm text-slate-500 mt-1">
                Riwayat pengiriman data ke Neo Feeder · status berhasil/gagal · data payload &amp; keterangan error
            </p>
        </div>

        <x-admin.module-tabs :module="$module" />

        <form method="GET" action="{{ route($moduleConfig['log_route']) }}" class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="" @selected($filters['status'] === '')>Semua</option>
                        <option value="success" @selected($filters['status'] === 'success')>Berhasil</option>
                        <option value="failed" @selected($filters['status'] === 'failed')>Gagal</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Jenis kirim</label>
                    <select name="sync_type" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="" @selected($filters['sync_type'] === '')>Semua jenis</option>
                        @foreach ($moduleConfig['sync_types'] as $type)
                            <option value="{{ $type }}" @selected($filters['sync_type'] === $type)>
                                {{ \App\Support\Feeder\SyncLogPresenter::syncTypeLabel($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Cari (NIM / keterangan)</label>
                    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="NIM, error…" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Dari tanggal</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Sampai tanggal</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
            </div>
            <x-admin.filter-submit label="Terapkan Filter" />
        </form>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <span class="text-sm font-medium text-slate-700">{{ $logs->total() }} entri log</span>
                <span class="text-xs text-slate-500">Terbaru di atas</span>
            </div>

            @if ($logs->isEmpty())
                <p class="px-4 py-10 text-center text-sm text-slate-500">
                    Belum ada log kirim untuk modul ini. Log tercatat otomatis saat Anda menekan tombol kirim ke Neo Feeder.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Waktu</th>
                                <th class="px-3 py-2">Jenis</th>
                                <th class="px-3 py-2">Subjek</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Keterangan</th>
                                <th class="px-3 py-2">Data dikirim</th>
                                <th class="px-3 py-2">Operator</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($logs as $log)
                                @php
                                    $recordJson = \App\Support\Feeder\SyncLogPresenter::sentRecordJson($log);
                                    $payload = $log->payload_summary ?? [];
                                @endphp
                                <tr class="hover:bg-slate-50/80 align-top">
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">
                                        {{ $log->created_at?->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-3 py-2 text-slate-700">
                                        {{ \App\Support\Feeder\SyncLogPresenter::syncTypeLabel($log->sync_type) }}
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs text-slate-800">
                                        {{ \App\Support\Feeder\SyncLogPresenter::subject($log) }}
                                    </td>
                                    <td class="px-3 py-2">
                                        @if ($log->success)
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/20">
                                                Berhasil
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-600/20">
                                                Gagal
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-600 max-w-xs">
                                        @if ($log->success)
                                            <span class="text-emerald-700">OK</span>
                                        @else
                                            <span class="text-red-700">{{ $log->feeder_error_desc ?: '—' }}</span>
                                            @if ($log->feeder_error_code)
                                                <span class="block text-xs text-slate-400 mt-0.5">Kode: {{ $log->feeder_error_code }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 max-w-md">
                                        @if ($recordJson)
                                            <details class="group">
                                                <summary class="cursor-pointer text-teal-700 hover:underline text-xs font-medium">
                                                    Lihat payload
                                                </summary>
                                                <pre class="mt-2 max-h-48 overflow-auto rounded-lg bg-slate-900 text-slate-100 text-xs p-3">{{ $recordJson }}</pre>
                                            </details>
                                        @else
                                            <div class="text-xs text-slate-500 space-y-0.5">
                                                @foreach ($payload as $key => $value)
                                                    @if (is_scalar($value) && ! in_array($key, ['record', 'records'], true))
                                                        <div><span class="text-slate-400">{{ $key }}:</span> {{ $value }}</div>
                                                    @endif
                                                @endforeach
                                                @if ($payload === [])
                                                    <span>—</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-600 whitespace-nowrap">
                                        {{ $log->user?->name ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div class="px-4 py-3 border-t border-slate-100">
                        {{ $logs->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
