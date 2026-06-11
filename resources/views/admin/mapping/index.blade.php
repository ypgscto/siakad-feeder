@php
    $tab = request('tab', 'scan');
    $categoryLabels = [
        'agama' => 'Agama',
        'jenis_daftar' => 'Status Awal (statusawal) → Jenis Daftar',
        'jenis_keluar' => 'Keluar/Lulus (L, D, …) → Jenis Keluar',
        'status_mahasiswa' => 'Status Mhsw (statusmhsw) → Status Aktif Feeder',
        'kelamin' => 'Jenis Kelamin',
    ];
@endphp

<x-app-layout :title="$title">
    <div class="max-w-7xl mx-auto space-y-6" x-data="{ tab: @js($tab) }">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Pemetaan Siakad ↔ Neo Feeder</h2>
                <p class="text-sm text-slate-500 mt-1">
                    Master akademik tetap di Siakad-API · Nilai di sini hanya kode pendukung kirim WS Feeder
                </p>
            </div>
            <form method="POST" action="{{ route('admin.mapping.sync-siakad') }}">
                @csrf
                <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold rounded-lg shadow-sm">
                    Tarik status dari Siakad-API
                </button>
            </form>
        </div>

        @if ($siakadMaster['error'])
            <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm">
                Siakad-API: {{ $siakadMaster['error'] }}
            </div>
        @endif

        <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-2">
            @foreach ([
                'scan' => 'Hasil scan CI',
                'siakad' => 'Master Siakad-GS',
                'prodi' => 'Prodi (UUID)',
                'jenis_daftar' => 'Status Awal',
                'jenis_keluar' => 'Lulus / DO',
                'status_mahasiswa' => 'Status Mhsw',
                'agama' => 'Agama',
                'kelamin' => 'Kelamin',
                'konstanta' => 'Konstanta',
            ] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg">{{ $label }}</button>
            @endforeach
        </div>

        {{-- Master Siakad-GS --}}
        <div x-show="tab === 'siakad'" x-cloak class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 bg-slate-50 border-b">
                    <h3 class="font-semibold text-slate-800">statusawal — masuk mahasiswa</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ $siakadReference['usage_notes']['statusawal'] ?? '' }}</p>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500"><tr><th class="px-3 py-2">Kode</th><th class="px-3 py-2">Nama</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($siakadReference['statusawal'] ?? [] as $code => $nama)
                            <tr><td class="px-3 py-2 font-mono">{{ $code }}</td><td class="px-3 py-2">{{ $nama }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 bg-slate-50 border-b">
                    <h3 class="font-semibold text-slate-800">statusmhsw — status akademik</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ $siakadReference['usage_notes']['statusmhsw'] ?? '' }}</p>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500"><tr><th class="px-3 py-2">Kode</th><th class="px-3 py-2">Nama</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($siakadReference['statusmhsw'] ?? [] as $code => $nama)
                            <tr @class(['bg-amber-50' => $code === 'D'])>
                                <td class="px-3 py-2 font-mono font-semibold">{{ $code }}</td>
                                <td class="px-3 py-2">{{ $nama }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="lg:col-span-2 rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-900">
                <strong>DO = kode <code>D</code></strong> (Drop-out) · Modul Lulus/DO memakai <code>ta.StatusLulusID</code> dari API <code>/api/status-lulus</code> — pastikan kode di tabel <code>statuslulus</code> selaras (L, D, …).
            </div>
        </div>

        {{-- Scan sifeeder2 --}}
        <div x-show="tab === 'scan'" class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-x-auto">
            <div class="px-4 py-3 border-b bg-slate-50">
                <h3 class="font-semibold text-slate-800">Mapping hardcoded di sifeeder2 (CodeIgniter)</h3>
                <p class="text-xs text-slate-500 mt-1">Ditemukan dari scan controller — beberapa belum dipetakan dinamis (terutama jenis keluar DO).</p>
            </div>
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Grup</th>
                        <th class="px-3 py-2 text-left">Siakad</th>
                        <th class="px-3 py-2 text-left">Feeder</th>
                        <th class="px-3 py-2 text-left">Nilai CI</th>
                        <th class="px-3 py-2 text-left">File</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($scanFindings as $row)
                        <tr class="hover:bg-slate-50 align-top">
                            <td class="px-3 py-2 text-xs font-medium">{{ $row['group'] }}</td>
                            <td class="px-3 py-2 text-xs">{{ $row['siakad_field'] }}</td>
                            <td class="px-3 py-2 text-xs font-mono">{{ $row['feeder_field'] }}</td>
                            <td class="px-3 py-2 text-xs">
                                <div>{{ $row['ci_value'] }}</div>
                                <div class="text-slate-500 mt-1">→ {{ $row['feeder_value'] }}</div>
                                @if ($row['notes'])
                                    <div class="text-amber-700 mt-1">{{ $row['notes'] }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs text-slate-500">{{ implode(', ', $row['source_files']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Prodi --}}
        <div x-show="tab === 'prodi'" x-cloak class="space-y-4">
            <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
                <h3 class="font-semibold text-slate-800 mb-3">Tambah / perbarui prodi</h3>
                <form method="POST" action="{{ route('admin.mapping.prodi.store') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @csrf
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Siakad ProdiID</label>
                        <select name="siakad_prodi_id" class="w-full rounded-lg border-slate-300 text-sm" required>
                            @foreach ($siakadMaster['prodi'] as $p)
                                <option value="{{ $p['id'] ?? '' }}">{{ $p['id'] ?? '' }} — {{ $p['nama'] ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">UUID Feeder (id_prodi)</label>
                        <input type="text" name="feeder_id_prodi" class="w-full rounded-lg border-slate-300 text-sm font-mono" placeholder="uuid" required>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">UUID prodi asal (pindahan)</label>
                        <input type="text" name="feeder_id_prodi_asal" class="w-full rounded-lg border-slate-300 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">UUID prodi RPL</label>
                        <input type="text" name="feeder_id_prodi_rpl" class="w-full rounded-lg border-slate-300 text-sm font-mono">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <button type="submit" class="px-4 py-2 bg-teal-600 text-white text-sm rounded-lg">Simpan prodi</button>
                    </div>
                </form>
            </div>
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Siakad ProdiID</th>
                            <th class="px-3 py-2">id_prodi</th>
                            <th class="px-3 py-2">prodi_asal</th>
                            <th class="px-3 py-2">prodi_rpl</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($prodiMaps as $row)
                            <tr>
                                <td class="px-3 py-2 font-medium">{{ $row->siakad_prodi_id }}</td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $row->feeder_id_prodi }}</td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $row->feeder_id_prodi_asal ?: '—' }}</td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $row->feeder_id_prodi_rpl ?: '—' }}</td>
                                <td class="px-3 py-2">
                                    <form method="POST" action="{{ route('admin.mapping.prodi.destroy', $row) }}" onsubmit="return confirm('Hapus pemetaan?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 text-xs">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada pemetaan prodi di DB.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Code map categories --}}
        @foreach (['jenis_daftar', 'jenis_keluar', 'status_mahasiswa', 'agama', 'kelamin'] as $cat)
            <div x-show="tab === '{{ $cat }}'" x-cloak class="space-y-4">
                <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
                    <h3 class="font-semibold text-slate-800">{{ $categoryLabels[$cat] ?? $cat }}</h3>
                    @if ($cat === 'jenis_keluar')
                        <p class="text-xs text-amber-700 mt-1">
                            Default: <strong>L → 1 (Lulus)</strong>, <strong>D → 3 (Dikeluarkan / DO)</strong>, <strong>K → 4 (Mengundurkan diri)</strong>.
                            Sesuaikan jika kode <code>statuslulus</code> kampus berbeda.
                        </p>
                    @endif
                    @if ($cat === 'status_mahasiswa')
                        <p class="text-xs text-slate-500 mt-1">Dari tabel <code>statusmhsw</code> · dipakai saat kirim aktivitas kuliah per semester.</p>
                    @endif
                    @if ($cat === 'jenis_daftar')
                        <p class="text-xs text-slate-500 mt-1">Master Siakad: StatusAwalID (B/P/J) → id_jenis_daftar Feeder.</p>
                    @endif

                    <form method="POST" action="{{ route('admin.mapping.code.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        @csrf
                        <input type="hidden" name="category" value="{{ $cat }}">
                        <div>
                            <label class="block text-xs text-slate-600 mb-1">Kode Siakad</label>
                            @if ($cat === 'jenis_daftar' && $siakadMaster['status_awal'] !== [])
                                <select name="siakad_key" class="w-full rounded-lg border-slate-300 text-sm" required>
                                    @foreach ($siakadMaster['status_awal'] as $s)
                                        <option value="{{ $s['id'] ?? '' }}">{{ $s['id'] ?? '' }} — {{ $s['nama'] ?? '' }}</option>
                                    @endforeach
                                </select>
                            @elseif ($cat === 'jenis_keluar' && $siakadMaster['status_lulus'] !== [])
                                <select name="siakad_key" class="w-full rounded-lg border-slate-300 text-sm" required>
                                    @foreach ($siakadMaster['status_lulus'] as $s)
                                        <option value="{{ $s['id'] ?? '' }}">{{ $s['id'] ?? '' }} — {{ $s['nama'] ?? '' }}</option>
                                    @endforeach
                                </select>
                            @elseif ($cat === 'status_mahasiswa')
                                <select name="siakad_key" class="w-full rounded-lg border-slate-300 text-sm" required>
                                    @foreach ($siakadReference['statusmhsw'] ?? [] as $code => $nama)
                                        <option value="{{ $code }}">{{ $code }} — {{ $nama }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="siakad_key" class="w-full rounded-lg border-slate-300 text-sm" required>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs text-slate-600 mb-1">Label Siakad</label>
                            <input type="text" name="siakad_label" class="w-full rounded-lg border-slate-300 text-sm" placeholder="opsional">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-600 mb-1">Kode Feeder</label>
                            @if (isset($feederReference[$cat]))
                                <select name="feeder_value" class="w-full rounded-lg border-slate-300 text-sm" required>
                                    @foreach ($feederReference[$cat] as $code => $label)
                                        <option value="{{ $code }}">{{ $code }} — {{ $label }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="feeder_value" class="w-full rounded-lg border-slate-300 text-sm" required>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs text-slate-600 mb-1">Catatan</label>
                            <input type="text" name="notes" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="px-4 py-2 bg-teal-600 text-white text-sm rounded-lg w-full">Simpan</button>
                        </div>
                    </form>
                </div>

                <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Siakad</th>
                                <th class="px-3 py-2">Label</th>
                                <th class="px-3 py-2">Feeder</th>
                                <th class="px-3 py-2">Label Feeder</th>
                                <th class="px-3 py-2">Aktif</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($codeMaps[$cat] ?? [] as $row)
                                <tr @class(['opacity-50' => ! $row->is_active])>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row->siakad_key }}</td>
                                    <td class="px-3 py-2 text-xs">{{ $row->siakad_label }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row->feeder_value ?: '—' }}</td>
                                    <td class="px-3 py-2 text-xs">{{ $row->feeder_label ?: ($feederReference[$cat][$row->feeder_value] ?? '—') }}</td>
                                    <td class="px-3 py-2 text-xs">{{ $row->is_active ? 'Ya' : 'Tidak' }}</td>
                                    <td class="px-3 py-2">
                                        <form method="POST" action="{{ route('admin.mapping.code.destroy', $row) }}" onsubmit="return confirm('Hapus?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 text-xs">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada pemetaan. Klik "Tarik status dari Siakad-API" atau tambah manual.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        {{-- Konstanta --}}
        <div x-show="tab === 'konstanta'" x-cloak class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-x-auto">
            <div class="px-4 py-3 border-b bg-slate-50">
                <h3 class="font-semibold text-slate-800">Konstanta Feeder (dari config / .env)</h3>
                <p class="text-xs text-slate-500">Ubah di <code>config/feeder_maps.php</code> atau variabel <code>FEEDER_*</code> di <code>.env</code></p>
            </div>
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    @foreach ($constants as $key => $value)
                        <tr>
                            <td class="px-4 py-2 text-xs font-medium text-slate-600 w-1/3">{{ $key }}</td>
                            <td class="px-4 py-2 font-mono text-xs">{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
