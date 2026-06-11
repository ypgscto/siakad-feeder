<x-app-layout :title="$title">
    <div class="max-w-7xl mx-auto space-y-6" x-data="{
        selected: [],
        toggleAll(checked, codes) { this.selected = checked ? codes : [] },
        toggleOne(code, checked) {
            if (checked) {
                if (!this.selected.includes(code)) this.selected.push(code);
            } else {
                this.selected = this.selected.filter(c => c !== code);
            }
        }
    }">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Nilai Konversi Mahasiswa</h2>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $nama ?: '—' }} · NIM <span class="font-mono">{{ $nim }}</span>
                </p>
            </div>
            <a href="{{ route('admin.konversi-nilai.index', array_merge($filters, ['load' => 1])) }}" class="text-sm text-teal-700 hover:underline">← Daftar mahasiswa</a>
        </div>

        <x-admin.module-tabs module="konversi-nilai" />

        @if ($error)
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ $error }}</div>
        @endif

        @php $mkCodes = collect($rows)->map(fn ($r) => trim((string) ($r['mk_kode'] ?? '')))->filter()->values()->all(); @endphp

        <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
            <form method="POST" action="{{ route('admin.konversi-nilai.send') }}"
                  @submit="if(!confirm('Kirim nilai konversi ke Feeder?')) $event.preventDefault()">
                @csrf
                <input type="hidden" name="program_id" value="{{ $filters['program_id'] }}">
                <input type="hidden" name="prodi_id" value="{{ $filters['prodi_id'] }}">
                <input type="hidden" name="angkatan" value="{{ $filters['angkatan'] }}">
                <input type="hidden" name="status_awal_id" value="{{ $filters['status_awal_id'] }}">
                <input type="hidden" name="mhsw_id" value="{{ $mhswId }}">
                <input type="hidden" name="nim" value="{{ $nim }}">
                <input type="hidden" name="nama" value="{{ $nama }}">
                <input type="hidden" name="only_selected" :value="selected.length ? '1' : '0'">
                <template x-for="code in selected" :key="code"><input type="hidden" name="mk_kodes[]" :value="code"></template>
                <p class="text-xs text-slate-500 mb-3">{{ count($rows) }} mata kuliah · Kosongkan centang = kirim semua</p>
                <button type="submit" class="px-3 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700">
                    Kirim Konversi ke Feeder
                </button>
            </form>
            <div class="mt-3 pt-3 border-t border-slate-100">
                <x-admin.sync-log-link module="konversi-nilai" />
            </div>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-3 w-10">
                            <input type="checkbox" class="rounded"
                                   :checked="selected.length > 0 && selected.length === @js(count($mkCodes))"
                                   @change="toggleAll($event.target.checked, @js($mkCodes))">
                        </th>
                        <th class="px-3 py-3">Kode MK</th>
                        <th class="px-3 py-3">Nama MK</th>
                        <th class="px-3 py-3">Semester KRS</th>
                        <th class="px-3 py-3">SKS</th>
                        <th class="px-3 py-3">Grade</th>
                        <th class="px-3 py-3">Bobot</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        @php $code = trim((string) ($row['mk_kode'] ?? '')); @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">
                                <input type="checkbox" class="rounded" value="{{ $code }}"
                                       @change="toggleOne('{{ $code }}', $event.target.checked)"
                                       :checked="selected.includes('{{ $code }}')">
                            </td>
                            <td class="px-3 py-2 font-mono text-xs">{{ $code ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $row['nama_mk'] ?? '—' }}</td>
                            <td class="px-3 py-2 font-mono text-xs">{{ $row['tahun_id'] ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $row['sks_mk'] ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $row['nilai_huruf'] ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $row['bobot'] ?? $row['nilai_angka'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Tidak ada mata kuliah konversi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
