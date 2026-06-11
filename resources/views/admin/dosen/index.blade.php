<x-app-layout :title="$title">
    <div class="max-w-7xl mx-auto space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Data Dosen</h2>
            <p class="text-sm text-slate-500 mt-1">Master dosen dari Siakad-API · opsional cek registrasi di Feeder (max 30 baris)</p>
        </div>

        @if ($error)<div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ $error }}</div>@endif

        <form method="GET" action="{{ route('admin.dosen.index') }}" class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm space-y-4">
            <input type="hidden" name="load" value="1">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Filter Prodi{{ auth()->user()?->isProdi() ? '' : ' (opsional)' }}</label>
                    <x-admin.prodi-select
                        name="prodi_id"
                        :prodi-list="$master['prodi'] ?? []"
                        :selected="$filters['prodi_id'] ?? ''"
                        :locked="auth()->user()?->isProdi() ?? false"
                        :required="auth()->user()?->isProdi() ?? false"
                    />
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="cek_feeder" value="1" class="rounded border-slate-300" @checked($cekFeeder)>
                        Cek status Feeder (NIDN)
                    </label>
                </div>
            </div>
            <x-admin.filter-submit />
        </form>

        @if ($loaded)
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-x-auto">
                <p class="px-4 py-3 text-sm text-slate-600 border-b">{{ count($lecturers) }} dosen</p>
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-3">Login</th>
                            <th class="px-3 py-3">NIDN</th>
                            <th class="px-3 py-3">Nama</th>
                            <th class="px-3 py-3">Prodi</th>
                            <th class="px-3 py-3">Email</th>
                            @if ($cekFeeder)<th class="px-3 py-3">Feeder</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($lecturers as $row)
                            @php $nidn = (string) ($row['nidn'] ?? ''); @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2 font-mono text-xs">{{ $row['id'] ?? '-' }}</td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $nidn ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row['nama'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-xs">{{ $row['prodi_kode'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-xs">{{ $row['email'] ?? '—' }}</td>
                                @if ($cekFeeder)
                                    <td class="px-3 py-2">
                                        @php $st = $feederStatus[$nidn] ?? '—'; @endphp
                                        <span @class([
                                            'text-xs px-2 py-0.5 rounded-full',
                                            'bg-emerald-100 text-emerald-800' => $st === 'terdaftar',
                                            'bg-amber-100 text-amber-800' => $st === 'belum',
                                            'bg-slate-100 text-slate-600' => ! in_array($st, ['terdaftar', 'belum']),
                                        ])>{{ $st }}</span>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $cekFeeder ? 6 : 5 }}" class="px-4 py-8 text-center text-slate-500">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
