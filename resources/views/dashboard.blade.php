<x-app-layout :title="$title">
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Dashboard</h2>
                <p class="text-sm text-slate-500 mt-1">Status integrasi Siakad-API dan Neo Feeder</p>
            </div>
            <x-siakad-maskot class="h-[300px] w-auto max-w-full shrink-0 self-center sm:self-auto" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Siakad-API</h3>
                    <span @class([
                        'text-xs font-medium px-2 py-1 rounded-full',
                        'bg-emerald-100 text-emerald-800' => $siakadStatus['ok'],
                        'bg-red-100 text-red-800' => ! $siakadStatus['ok'],
                    ])>
                        {{ $siakadStatus['ok'] ? 'OK' : 'Gagal' }}
                    </span>
                </div>
                <p class="text-sm text-slate-600 mt-3">{{ $siakadStatus['message'] }}</p>
            </div>

            <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Neo Feeder</h3>
                    <span @class([
                        'text-xs font-medium px-2 py-1 rounded-full',
                        'bg-emerald-100 text-emerald-800' => $feederStatus['ok'],
                        'bg-red-100 text-red-800' => ! $feederStatus['ok'],
                    ])>
                        {{ $feederStatus['ok'] ? 'OK' : 'Gagal' }}
                    </span>
                </div>
                <p class="text-sm text-slate-600 mt-3">{{ $feederStatus['message'] }}</p>
            </div>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
            <h3 class="font-semibold text-slate-800 mb-2">Langkah berikutnya</h3>
            <ul class="text-sm text-slate-600 list-disc list-inside space-y-1">
                <li>Pastikan <code class="text-xs bg-slate-100 px-1 rounded">SIAKAD_API_BASE_URL</code> dan token sudah benar di <code class="text-xs bg-slate-100 px-1 rounded">.env</code></li>
                <li>Atur kredensial Neo Feeder (<code class="text-xs bg-slate-100 px-1 rounded">FEEDER_USERNAME</code>, <code class="text-xs bg-slate-100 px-1 rounded">FEEDER_PASSWORD</code>)</li>
                <li>Buka menu <strong>Data Mahasiswa</strong> untuk melihat biodata dari Siakad-API</li>
                @if (auth()->user()?->isSuperadmin())
                    <li>Superadmin: kelola <strong>Master Pengguna</strong> (cari SSO) dan <strong>Pemetaan Feeder</strong></li>
                @endif
            </ul>
        </div>
    </div>
</x-app-layout>
