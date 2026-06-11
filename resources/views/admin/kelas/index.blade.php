<x-app-layout :title="$title">
    <div class="max-w-7xl mx-auto space-y-6" x-data="{ selected: [], toggleAll(c, keys) { this.selected = c ? keys : [] } }">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Kelas Perkuliahan</h2>
            <p class="text-sm text-slate-500 mt-1">Data jadwal dari Siakad-API · Kirim kelas ke Neo Feeder</p>
        </div>

        <x-admin.module-tabs module="kelas" />

        @if ($error)<div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ $error }}</div>@endif

        <x-admin.prodi-tahun-filter :action="route('admin.kelas.index')" :filters="$filters" :master="$master" />

        @if ($loaded)
            @php $classKeys = collect($classes)->map(fn ($r) => ($r['mk_kode'] ?? '').'|'.($r['nama_kelas'] ?? ''))->values()->all(); @endphp
            <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
                <form method="POST" action="{{ route('admin.kelas.send-kelas') }}" @submit="if(!confirm('Kirim kelas terpilih / semua filter ke Feeder?')) $event.preventDefault()">
                    @csrf
                    <input type="hidden" name="prodi_id" value="{{ $filters['prodi_id'] }}">
                    <input type="hidden" name="tahun_id" value="{{ $filters['tahun_id'] }}">
                    <input type="hidden" name="only_selected" :value="selected.length ? '1' : '0'">
                    <template x-for="k in selected" :key="k"><input type="hidden" name="class_keys[]" :value="k"></template>
                    <button type="submit" class="px-3 py-2 bg-teal-600 text-white text-xs font-semibold rounded-lg hover:bg-teal-700">Kirim Kelas ke Feeder</button>
                </form>
                <div class="mt-3 pt-3 border-t border-slate-100">
                    <x-admin.sync-log-link module="kelas" />
                </div>
            </div>

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-3">
                                <input type="checkbox" class="rounded"
                                       :checked="selected.length > 0 && selected.length === @js(count($classKeys))"
                                       @change="toggleAll($event.target.checked, @js($classKeys))">
                            </th>
                            <th class="px-3 py-3">MK</th>
                            <th class="px-3 py-3">Kelas</th>
                            <th class="px-3 py-3">Dosen</th>
                            <th class="px-3 py-3">Peserta</th>
                            <th class="px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($classes as $row)
                            @php
                                $key = ($row['mk_kode'] ?? '').'|'.($row['nama_kelas'] ?? '');
                                $pesertaUrl = route('admin.kelas.peserta', [
                                    'prodi_id' => $filters['prodi_id'],
                                    'tahun_id' => $filters['tahun_id'],
                                    'jadwal_id' => $row['id'] ?? '',
                                    'mk_kode' => $row['mk_kode'] ?? '',
                                    'nama_kelas' => $row['nama_kelas'] ?? '',
                                    'dosen_login' => $row['dosen_login'] ?? '',
                                ]);
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2">
                                    <input type="checkbox" class="rounded" value="{{ $key }}"
                                           :checked="selected.includes('{{ $key }}')"
                                           @change="$event.target.checked ? selected.push('{{ $key }}') : selected = selected.filter(k => k !== '{{ $key }}')">
                                </td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $row['mk_kode'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $row['nama_kelas'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-xs">{{ $row['dosen_login'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $row['jumlah_mhsw'] ?? '-' }}</td>
                                <td class="px-3 py-2"><a href="{{ $pesertaUrl }}" class="text-teal-700 text-xs font-medium hover:underline">Peserta →</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Tidak ada kelas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
