@props(['action', 'filters', 'master', 'loadName' => 'load'])

<form method="GET" action="{{ $action }}" class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
    <input type="hidden" name="{{ $loadName }}" value="1">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Program Studi</label>
            <x-admin.prodi-select
                name="prodi_id"
                :prodi-list="$master['prodi'] ?? []"
                :selected="$filters['prodi_id'] ?? ''"
                :locked="auth()->user()?->isProdi() ?? false"
                :required="auth()->user()?->isProdi() ?? false"
            />
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Tahun Akademik</label>
            <select name="tahun_id" class="w-full rounded-lg border-slate-300 text-sm">
                @foreach ($master['tahun'] ?? [] as $row)
                    @php $id = $row['id'] ?? $row['tahun_id'] ?? ''; @endphp
                    <option value="{{ $id }}" @selected(($filters['tahun_id'] ?? '') === (string) $id)>{{ $id }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <x-admin.filter-submit label="Tampilkan Data" class="!mt-4" />
</form>
