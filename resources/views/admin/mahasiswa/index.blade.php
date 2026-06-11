<x-app-layout :title="$title">
    <div class="max-w-7xl mx-auto space-y-6" x-data="{
        selected: [],
        toggleAll(checked, nims) {
            this.selected = checked ? nims : [];
        },
        toggleOne(nim, checked) {
            if (checked) {
                if (!this.selected.includes(nim)) this.selected.push(nim);
            } else {
                this.selected = this.selected.filter(i => i !== nim);
            }
        }
    }">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Data Mahasiswa</h2>
            <p class="text-sm text-slate-500 mt-1">
                Master akademik (prodi, mahasiswa, dll.) dari <strong>Siakad-API</strong> ·
                Kirim ke <strong>Neo Feeder</strong> · DB lokal hanya user &amp; log/mapping pendukung
            </p>
        </div>

        @if ($error)
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ $error }}</div>
        @endif

        <form method="GET" action="{{ route('admin.mahasiswa.index') }}" class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm space-y-4">
            <input type="hidden" name="load" value="1">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Program</label>
                    <select name="program_id" class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($master['programs'] as $row)
                            @php $id = $row['id'] ?? ''; @endphp
                            <option value="{{ $id }}" @selected($filters['program_id'] === (string) $id)>
                                {{ ($row['nama'] ?? $id) !== $id ? ($id.' — '.$row['nama']) : $id }}
                            </option>
                        @endforeach
                        @if ($master['programs'] === [])
                            <option value="{{ $filters['program_id'] }}" selected>{{ $filters['program_id'] }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Program Studi</label>
                    <x-admin.prodi-select
                        name="prodi_id"
                        :prodi-list="$master['prodi']"
                        :selected="$filters['prodi_id']"
                        :locked="auth()->user()?->isProdi() ?? false"
                        :required="auth()->user()?->isProdi() ?? false"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tahun Akademik</label>
                    <select name="tahun_id" class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($master['tahun'] as $row)
                            @php $id = $row['id'] ?? $row['tahun_id'] ?? ''; @endphp
                            <option value="{{ $id }}" @selected($filters['tahun_id'] === (string) $id)>{{ $id }}</option>
                        @endforeach
                        @if ($master['tahun'] === [])
                            <option value="{{ $filters['tahun_id'] }}" selected>{{ $filters['tahun_id'] }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Status Awal</label>
                    <select name="status_awal_id" class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($master['status_awal'] as $row)
                            @php $id = $row['id'] ?? ''; @endphp
                            <option value="{{ $id }}" @selected($filters['status_awal_id'] === (string) $id)>
                                {{ ($row['nama'] ?? $id) }}
                            </option>
                        @endforeach
                        @if ($master['status_awal'] === [])
                            <option value="{{ $filters['status_awal_id'] }}" selected>{{ $filters['status_awal_id'] }}</option>
                        @endif
                    </select>
                </div>
            </div>
            <x-admin.filter-submit />
        </form>

        @if ($loaded)
            @php
                $allNims = collect($students)->map(fn ($r) => (string) ($r['nim'] ?? $r['mhsw_id'] ?? ''))->filter()->values()->all();
            @endphp

            <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
                <p class="text-xs text-slate-500 mb-3">
                    <strong>Tambah</strong> = biodata + riwayat ·
                    <strong>Riwayat</strong> = mahasiswa sudah ada di Feeder ·
                    <strong>Update</strong> = perbarui riwayat terdaftar
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach ([
                        ['route' => 'admin.mahasiswa.send-full', 'label' => 'Kirim Biodata + Riwayat', 'class' => 'bg-teal-600 hover:bg-teal-700'],
                        ['route' => 'admin.mahasiswa.send-riwayat', 'label' => 'Tambah Riwayat', 'class' => 'bg-amber-600 hover:bg-amber-700'],
                        ['route' => 'admin.mahasiswa.update-riwayat', 'label' => 'Update Riwayat', 'class' => 'bg-indigo-600 hover:bg-indigo-700'],
                    ] as $action)
                        <form method="POST" action="{{ route($action['route']) }}"
                              data-confirm="Kirim data ke Neo Feeder untuk baris terpilih / semua filter?"
                              @submit="if($el.getAttribute('data-confirm') && !confirm($el.getAttribute('data-confirm'))) $event.preventDefault()">
                            @csrf
                            <input type="hidden" name="program_id" value="{{ $filters['program_id'] }}">
                            <input type="hidden" name="prodi_id" value="{{ $filters['prodi_id'] }}">
                            <input type="hidden" name="tahun_id" value="{{ $filters['tahun_id'] }}">
                            <input type="hidden" name="status_awal_id" value="{{ $filters['status_awal_id'] }}">
                            <input type="hidden" name="only_selected" :value="selected.length > 0 ? '1' : '0'">
                            <template x-for="nim in selected" :key="nim">
                                <input type="hidden" name="nims[]" :value="nim">
                            </template>
                            <button type="submit" class="px-3 py-2 text-white text-xs font-semibold rounded-lg {{ $action['class'] }}">
                                {{ $action['label'] }}
                            </button>
                        </form>
                    @endforeach
                </div>
                <p class="text-xs text-slate-400 mt-2" x-show="selected.length > 0" x-cloak>
                    <span x-text="selected.length"></span> baris terpilih · kosongkan centang = kirim semua filter
                </p>
            </div>

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-700">{{ count($students) }} mahasiswa</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-3 w-10">
                                    <input type="checkbox" class="rounded border-slate-300"
                                           :checked="selected.length > 0 && selected.length === @js(count($allNims))"
                                           @change="toggleAll($event.target.checked, @js($allNims))">
                                </th>
                                <th class="px-3 py-3">NIM</th>
                                <th class="px-3 py-3">Nama</th>
                                <th class="px-3 py-3">NIK</th>
                                <th class="px-3 py-3">HP Siakad</th>
                                <th class="px-3 py-3">HP ke Feeder</th>
                                <th class="px-3 py-3">NISN</th>
                                <th class="px-3 py-3">Prodi</th>
                                <th class="px-3 py-3">Tahun</th>
                                <th class="px-3 py-3">Status Awal</th>
                                <th class="px-3 py-3">Feeder</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($students as $row)
                                @php $nim = (string) ($row['nim'] ?? $row['mhsw_id'] ?? ''); @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-3 py-2">
                                        <input type="checkbox" class="rounded border-slate-300 row-check"
                                               value="{{ $nim }}"
                                               :checked="selected.includes('{{ $nim }}')"
                                               @change="toggleOne('{{ $nim }}', $event.target.checked)">
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $nim ?: '-' }}</td>
                                    <td class="px-3 py-2">{{ $row['nama'] ?? '-' }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['nik'] ?? '-' }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['handphone'] ?? '-' }}</td>
                                    <td class="px-3 py-2 font-mono text-xs text-teal-700">
                                        {{ \App\Support\Feeder\HandphoneNormalizer::forFeeder(
                                            (string) ($row['handphone'] ?? ''),
                                            (string) ($row['nim'] ?? $row['mhsw_id'] ?? ''),
                                        ) }}
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['nisn_placeholder'] ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $row['prodi_id'] ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $row['tahun_id'] ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $row['status_awal_nama'] ?? $row['status_awal_id'] ?? '-' }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-600">—</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-4 py-8 text-center text-slate-500">Tidak ada data untuk filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="rounded-lg bg-sky-50 border border-sky-200 text-sky-800 px-4 py-3 text-sm">
                Pilih filter lalu klik <strong>Tampilkan Data</strong> untuk memuat mahasiswa dari Siakad-API.
            </div>
        @endif
    </div>
</x-app-layout>
