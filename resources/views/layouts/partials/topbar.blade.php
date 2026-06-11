<header class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm">
    <div class="flex items-center justify-between h-14 px-4 sm:px-6">
        <div class="flex items-center gap-3">
            <button
                type="button"
                class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100"
                @click="$store.sidebar.openMobile()"
            >
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <button
                type="button"
                class="hidden lg:inline-flex p-2 rounded-lg text-slate-600 hover:bg-slate-100"
                @click="$store.sidebar.toggleCollapse()"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
            </button>
            <h1 class="text-sm font-semibold text-slate-700 truncate">{{ $title ?? 'Siakad-Feeder' }}</h1>
        </div>

        <div class="flex items-center gap-3">
            <span class="hidden sm:inline text-xs text-slate-500">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs font-medium text-teal-700 hover:text-teal-900">Keluar</button>
            </form>
        </div>
    </div>
</header>
