@props(['name' => 'prodi_id', 'prodiList' => [], 'selected' => '', 'required' => false, 'locked' => false])

@php
    $selected = (string) $selected;
    $label = collect($prodiList)->first(fn ($row) => (string) ($row['id'] ?? '') === $selected);
    $labelText = $label['nama'] ?? $selected;
@endphp

@if ($locked && $selected !== '')
    <input type="hidden" name="{{ $name }}" value="{{ $selected }}">
    <div class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
        {{ $labelText ?: $selected }}
    </div>
@else
    <select name="{{ $name }}" class="w-full rounded-lg border-slate-300 text-sm" @required($required) {{ $attributes }}>
        @if (! $required)
            <option value="">— Semua prodi —</option>
        @endif
        @foreach ($prodiList as $row)
            @php $id = (string) ($row['id'] ?? ''); @endphp
            <option value="{{ $id }}" @selected($selected === $id)>{{ $row['nama'] ?? $id }}</option>
        @endforeach
    </select>
@endif
