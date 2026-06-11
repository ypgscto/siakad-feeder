<x-app-layout :title="$title">
    <div class="max-w-7xl mx-auto space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Konversi Nilai Pindahan</h2>
            <p class="text-sm text-slate-500 mt-1">
                Mahasiswa pindahan / RPL · KRS konversi dari Siakad-API · Kirim <code class="text-xs">InsertNilaiTransferPendidikanMahasiswa</code>
            </p>
        </div>

        <x-admin.module-tabs module="konversi-nilai" />

        @if ($error)
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ $error }}</div>
        @endif

        <form method="GET" action="{{ route('admin.konversi-nilai.index') }}" class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm space-y-4">
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
                    <label class="block text-xs font-medium text-slate-600 mb-1">Angkatan</label>
                    <select name="angkatan" class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($master['cohorts'] as $row)
                            @php $id = $row['id'] ?? ''; @endphp
                            <option value="{{ $id }}" @selected($filters['angkatan'] === (string) $id)>{{ $row['nama'] ?? $id }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Status Awal</label>
                    <select name="status_awal_id" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="" @selected($filters['status_awal_id'] === '')>Pindahan &amp; RPL (default)</option>
                        @foreach ($master['status_awal'] as $row)
                            @php $id = $row['id'] ?? ''; @endphp
                            <option value="{{ $id }}" @selected($filters['status_awal_id'] === (string) $id)>{{ $row['nama'] ?? $id }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <x-admin.filter-submit />
        </form>

        @if ($loaded)
            <div class="flex justify-end mb-2">
                <x-admin.sync-log-link module="konversi-nilai" />
            </div>

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-x-auto">
                <p class="px-4 py-3 text-sm text-slate-600 border-b">{{ count($students) }} mahasiswa dengan KRS konversi</p>
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-3">NIM</th>
                            <th class="px-3 py-3">Nama</th>
                            <th class="px-3 py-3">Status Awal</th>
                            <th class="px-3 py-3">Prodi</th>
                            <th class="px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($students as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2 font-mono text-xs">{{ $row['nim'] }}</td>
                                <td class="px-3 py-2">{{ $row['nama'] }}</td>
                                <td class="px-3 py-2 text-xs">{{ $row['status_awal_nama'] ?: $row['status_awal_id'] }}</td>
                                <td class="px-3 py-2 text-xs">{{ $row['prodi_id'] }}</td>
                                <td class="px-3 py-2">
                                    <a href="{{ route('admin.konversi-nilai.matakuliah', array_merge($filters, [
                                        'mhsw_id' => $row['mhsw_id'],
                                        'nim' => $row['nim'],
                                        'nama' => $row['nama'],
                                    ])) }}" class="text-teal-700 text-xs font-medium hover:underline">Mata kuliah →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Tidak ada mahasiswa konversi untuk filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
