<x-app-layout :title="$title">
    <div class="max-w-7xl mx-auto space-y-6" x-data="{ selected: [] }">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Peserta Kelas</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $mkKode }} · {{ $namaKelas }} · Jadwal {{ $jadwalId }}</p>
            </div>
            <a href="{{ route('admin.kelas.index', array_merge($filters, ['load' => 1])) }}" class="text-sm text-teal-700 hover:underline">← Daftar kelas</a>
        </div>

        <x-admin.module-tabs module="kelas" />

        @if ($error)<div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ $error }}</div>@endif

        <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
            <form method="POST" action="{{ route('admin.kelas.send-peserta') }}" @submit="if(!confirm('Kirim peserta (+ dosen pengajar jika NIDN ada) ke Feeder?')) $event.preventDefault()">
                @csrf
                <input type="hidden" name="prodi_id" value="{{ $filters['prodi_id'] }}">
                <input type="hidden" name="tahun_id" value="{{ $filters['tahun_id'] }}">
                <input type="hidden" name="jadwal_id" value="{{ $jadwalId }}">
                <input type="hidden" name="mk_kode" value="{{ $mkKode }}">
                <input type="hidden" name="nama_kelas" value="{{ $namaKelas }}">
                <input type="hidden" name="nidn" value="{{ $nidn }}">
                <input type="hidden" name="only_selected" :value="selected.length ? '1' : '0'">
                <template x-for="nim in selected" :key="nim"><input type="hidden" name="nims[]" :value="nim"></template>
                <p class="text-xs text-slate-500 mb-3">NIDN dosen pengajar: <strong>{{ $nidn ?: '— (tidak dikirim)' }}</strong></p>
                <button type="submit" class="px-3 py-2 bg-teal-600 text-white text-xs font-semibold rounded-lg hover:bg-teal-700">Kirim Peserta ke Feeder</button>
            </form>
            <div class="mt-3 pt-3 border-t border-slate-100">
                <x-admin.sync-log-link module="kelas" />
            </div>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-3 w-10"></th>
                        <th class="px-3 py-3">NIM</th>
                        <th class="px-3 py-3">Nama</th>
                        <th class="px-3 py-3">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($participants as $row)
                        @php $nim = (string) ($row['nim'] ?? ''); @endphp
                        <tr>
                            <td class="px-3 py-2"><input type="checkbox" class="rounded" @change="$event.target.checked ? selected.push('{{ $nim }}') : selected = selected.filter(n => n !== '{{ $nim }}')"></td>
                            <td class="px-3 py-2 font-mono text-xs">{{ $nim }}</td>
                            <td class="px-3 py-2">{{ $row['nama_mahasiswa'] ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $row['nilai_huruf'] ?? '-' }} ({{ $row['nilai_angka'] ?? '-' }})</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Tidak ada peserta.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
