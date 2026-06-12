<x-app-layout :title="$title">
    <div class="max-w-7xl mx-auto space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Nilai Perkuliahan</h2>
            <p class="text-sm text-slate-500 mt-1">Pilih kelas lalu kirim nilai ke Neo Feeder</p>
        </div>

        <x-admin.module-tabs module="nilai" />

        @if ($error)<div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ $error }}</div>@endif

        <x-admin.prodi-tahun-filter :action="route('admin.nilai.index')" :filters="$filters" :master="$master" />

        @if ($loaded)
            <div class="flex justify-end">
                <x-admin.sync-log-link module="nilai" />
            </div>

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-3">MK</th>
                            <th class="px-3 py-3">Kelas</th>
                            <th class="px-3 py-3">Peserta</th>
                            <th class="px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($classes as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2">{{ $row['nama_mk'] ?? $row['mk_kode'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $row['kelas_nama'] ?? $row['nama_kelas'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $row['jumlah_mhsw'] ?? '-' }}</td>
                                <td class="px-3 py-2">
                                    <a href="{{ route('admin.nilai.peserta', [
                                        'prodi_id' => $filters['prodi_id'],
                                        'tahun_id' => $filters['tahun_id'],
                                        'jadwal_id' => $row['id'] ?? '',
                                        'mk_kode' => $row['mk_kode'] ?? '',
                                        'nama_kelas' => $row['nama_kelas'] ?? '',
                                    ]) }}" class="text-teal-700 text-xs font-medium hover:underline">Nilai peserta →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Tidak ada kelas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
