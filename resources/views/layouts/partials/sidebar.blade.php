@php
    $forceExpanded = $forceExpanded ?? false;
@endphp
<aside
    class="flex flex-col h-full bg-gradient-to-b from-teal-950 to-teal-900 text-white shadow-xl w-full"
>
    <div class="px-3 py-5 border-b border-white/10 shrink-0 w-full">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group" title="Siakad-Feeder">
            <div class="h-12 w-12 shrink-0 rounded-full bg-white/10 ring-2 ring-white/30 flex items-center justify-center font-bold text-lg">
                SF
            </div>
            @if ($forceExpanded)
                <div class="min-w-0 flex-1">
                    <p class="text-lg font-extrabold leading-tight">Siakad-Feeder</p>
                    <p class="text-xs text-teal-100/90 mt-1">SIAKAD → Neo Feeder</p>
                </div>
            @else
                <div class="min-w-0 flex-1" x-show="!$store.sidebar.collapsed" x-cloak>
                    <p class="text-lg font-extrabold leading-tight">Siakad-Feeder</p>
                    <p class="text-xs text-teal-100/90 mt-1">SIAKAD → Neo Feeder</p>
                </div>
            @endif
        </a>
    </div>

    @include('layouts.partials.sidebar-nav')

    @include('layouts.partials.sidebar-footer', ['forceExpanded' => $forceExpanded])
</aside>
