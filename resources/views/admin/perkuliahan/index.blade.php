<x-app-layout :title="$title">
    <div class="max-w-7xl mx-auto space-y-6" x-data="{
        selected: [],
        toggleAll(checked, nims) { this.selected = checked ? nims : [] },
        toggleOne(nim, checked) {
            if (checked) {
                if (!this.selected.includes(nim)) this.selected.push(nim);
            } else {
                this.selected = this.selected.filter(i => i !== nim);
            }
        }
    }">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Aktivitas Kuliah Mahasiswa</h2>
            <p class="text-sm text-slate-500 mt-1">
                KHS per semester dari <strong>Siakad-API</strong> · Kirim <code class="text-xs">InsertPerkuliahanMahasiswa</code> ke Neo Feeder
            </p>
        </div>

        <x-admin.module-tabs module="perkuliahan" />

        @if ($error)
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ $error }}</div>
        @endif

        <form method="GET" action="{{ route('admin.perkuliahan.index') }}" class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm space-y-4">
            <input type="hidden" name="load" value="1">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
                    <label class="block text-xs font-medium text-slate-600 mb-1">Semester (TahunID)</label>
                    <select name="tahun_id" class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($master['tahun'] as $row)
                            @php $id = $row['id'] ?? $row['tahun_id'] ?? ''; @endphp
                            <option value="{{ $id }}" @selected($filters['tahun_id'] === (string) $id)>{{ $id }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <x-admin.filter-submit />
        </form>

        @if ($loaded)
            @php $nims = collect($rows)->map(fn ($r) => (string) ($r['nim'] ?? ''))->filter()->values()->all(); @endphp

            <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
                <form method="POST" action="{{ route('admin.perkuliahan.send') }}"
                      @submit="if(!confirm('Kirim aktivitas kuliah ke Feeder?')) $event.preventDefault()">
                    @csrf
                    <input type="hidden" name="program_id" value="{{ $filters['program_id'] }}">
                    <input type="hidden" name="prodi_id" value="{{ $filters['prodi_id'] }}">
                    <input type="hidden" name="tahun_id" value="{{ $filters['tahun_id'] }}">
                    <input type="hidden" name="only_selected" :value="selected.length ? '1' : '0'">
                    <template x-for="nim in selected" :key="nim"><input type="hidden" name="nims[]" :value="nim"></template>
                    <p class="text-xs text-slate-500 mb-3">
                        {{ count($rows) }} baris KHS · Kosongkan centang = kirim semua filter
                    </p>
                    <button type="submit" class="px-3 py-2 bg-teal-600 text-white text-xs font-semibold rounded-lg hover:bg-teal-700">
                        Kirim Aktivitas Kuliah ke Feeder
                    </button>
                </form>
                <div class="mt-3 pt-3 border-t border-slate-100">
                    <x-admin.sync-log-link module="perkuliahan" />
                </div>
            </div>

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-3 w-10">
                                <input type="checkbox" class="rounded"
                                       :checked="selected.length > 0 && selected.length === @js(count($nims))"
                                       @change="toggleAll($event.target.checked, @js($nims))">
                            </th>
                            <th class="px-3 py-3">NIM</th>
                            <th class="px-3 py-3">Nama</th>
                            <th class="px-3 py-3">Semester</th>
                            <th class="px-3 py-3">IPS</th>
                            <th class="px-3 py-3">IPK</th>
                            <th class="px-3 py-3">SKS</th>
                            <th class="px-3 py-3">Total SKS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            @php $nim = (string) ($row['nim'] ?? ''); @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2">
                                    <input type="checkbox" class="rounded" value="{{ $nim }}"
                                           @change="toggleOne('{{ $nim }}', $event.target.checked)"
                                           :checked="selected.includes('{{ $nim }}')">
                                </td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $nim }}</td>
                                <td class="px-3 py-2">{{ $row['nama'] ?? '-' }}</td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $row['tahun_id'] ?? $filters['tahun_id'] }}</td>
                                <td class="px-3 py-2">{{ $row['ip_semester'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $row['ipk'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $row['sks_semester'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $row['total_sks'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">Tidak ada data KHS untuk filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
