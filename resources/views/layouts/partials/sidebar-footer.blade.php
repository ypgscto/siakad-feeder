@php
    $forceExpanded = $forceExpanded ?? false;
@endphp

<div class="mt-auto shrink-0 border-t border-white/10 px-2 py-3" title="YPGS IT Division">
    <div class="flex items-center justify-center gap-2">
        <img
            src="{{ asset('images/ypgs-it-division.png') }}"
            alt="Logo YPGS IT Division"
            class="h-9 w-9 shrink-0 object-contain drop-shadow-sm"
            width="36"
            height="36"
        />
        @if ($forceExpanded)
            <p class="text-xs font-semibold leading-tight text-teal-50 text-center">
                YPGS IT Division
            </p>
        @else
            <p
                class="text-xs font-semibold leading-tight text-teal-50 text-center min-w-0"
                x-show="!$store.sidebar.collapsed"
                x-cloak
            >
                YPGS IT Division
            </p>
        @endif
    </div>
</div>
