<x-app-layout :title="$title">
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Profil Perguruan Tinggi</h2>
            <p class="text-sm text-slate-500 mt-1">Data dari Neo Feeder (<code class="text-xs bg-slate-100 px-1 rounded">GetProfilPT</code>)</p>
        </div>

        @if ($error)
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                {{ $error }}
            </div>
        @endif

        @if ($profil)
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <dl class="divide-y divide-slate-100">
                    @foreach ($profil as $key => $value)
                        @if (! is_array($value))
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-4 py-3">
                                <dt class="text-xs font-medium text-slate-500 uppercase">{{ str_replace('_', ' ', $key) }}</dt>
                                <dd class="sm:col-span-2 text-sm text-slate-800">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </div>
        @elseif (! $error)
            <p class="text-sm text-slate-500">Profil PT tidak ditemukan. Periksa <code class="text-xs bg-slate-100 px-1 rounded">FEEDER_ID_PERGURUAN_TINGGI</code> di .env.</p>
        @endif
    </div>
</x-app-layout>
