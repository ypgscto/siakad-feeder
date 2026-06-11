@php
    $tab = request('tab', 'siakad');
    $groups = [
        'siakad' => 'Siakad-API',
        'feeder' => 'Neo Feeder',
        'auth' => 'Auth & SSO',
    ];
@endphp

<x-app-layout :title="$title">
    <div class="max-w-4xl mx-auto space-y-6" x-data="{ tab: @js($tab) }">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Pengaturan Koneksi</h2>
            <p class="text-sm text-slate-500 mt-1">
                Parameter integrasi Siakad-API, Neo Feeder, timeout, dan auth — disimpan di database lokal (superadmin).
            </p>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-teal-50 border border-teal-200 text-teal-900 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if (session('test_success'))
            <div class="rounded-lg bg-teal-50 border border-teal-200 text-teal-900 px-4 py-3 text-sm">{{ session('test_success') }}</div>
        @endif
        @if (session('test_error'))
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('test_error') }}</div>
        @endif

        <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-2 items-center justify-between">
            <div class="flex flex-wrap gap-2">
                @foreach ($groups as $key => $label)
                    <button type="button" @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg">{{ $label }}</button>
                @endforeach
            </div>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('admin.settings.test-siakad') }}">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700">
                        Tes Siakad-API
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.settings.test-feeder') }}">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700">
                        Tes Neo Feeder
                    </button>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="rounded-xl bg-white border border-slate-200 p-6 shadow-sm space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="tab" :value="tab">

            @foreach ($groups as $groupKey => $groupLabel)
                <div x-show="tab === '{{ $groupKey }}'" x-cloak class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-800">{{ $groupLabel }}</h3>

                    @foreach ($definitions as $key => $def)
                        @if (($def['group'] ?? '') !== $groupKey)
                            @continue
                        @endif

                        @php
                            $type = $def['type'] ?? 'string';
                            $isSecret = (bool) ($def['secret'] ?? false);
                            $value = old($key, $values[$key] ?? '');
                            $hasSecret = $isSecret && ($secretSet[$key] ?? false);
                        @endphp

                        <div>
                            <label for="{{ str_replace('.', '_', $key) }}" class="block text-xs font-medium text-slate-600 mb-1">
                                {{ $def['label'] }}
                            </label>

                            @php $fieldName = \App\Support\SettingsFieldName::html($key); @endphp

                            @if ($type === 'boolean')
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="hidden" name="{{ $fieldName }}" value="0">
                                    <input type="checkbox" name="{{ $fieldName }}" value="1" id="{{ str_replace('.', '_', $key) }}"
                                           @checked(filter_var(old($key, $values[$key] ?? false), FILTER_VALIDATE_BOOLEAN))
                                           class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                    Aktif
                                </label>
                            @elseif ($isSecret)
                                <input type="password" name="{{ $fieldName }}" id="{{ str_replace('.', '_', $key) }}"
                                       autocomplete="new-password"
                                       placeholder="{{ $hasSecret ? '•••••••• (kosongkan jika tidak diubah)' : 'Isi token / password' }}"
                                       class="w-full rounded-lg border-slate-300 text-sm">
                            @else
                                <input type="{{ $type === 'integer' ? 'number' : 'text' }}" name="{{ $fieldName }}"
                                       id="{{ str_replace('.', '_', $key) }}"
                                       value="{{ $type === 'integer' ? (int) $value : $value }}"
                                       @if ($type === 'integer') min="1" @endif
                                       class="w-full rounded-lg border-slate-300 text-sm"
                                       @if (in_array($key, ['siakad.api.base_url', 'feeder.ws_url', 'feeder.username'], true)) required @endif>
                            @endif

                            @if (! empty($def['help']))
                                <p class="text-xs text-slate-400 mt-1">{{ $def['help'] }}</p>
                            @endif
                            @error($key)
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            @endforeach

            <div class="flex justify-end pt-2 border-t border-slate-100">
                <button type="submit" class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-lg">
                    Simpan pengaturan
                </button>
            </div>
        </form>

        <p class="text-xs text-slate-400">
            Nilai awal diambil dari <code class="text-slate-500">.env</code> saat pertama kali dibuka.
            Password/token kosong saat simpan = tetap memakai nilai sebelumnya.
        </p>
    </div>
</x-app-layout>
