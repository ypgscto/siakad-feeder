<nav class="flex-1 overflow-y-auto px-2 py-4 space-y-1">
    @forelse ($sidebarMenu ?? [] as $item)
        <a
            href="{{ $item['url'] }}"
            @class([
                'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                'bg-teal-600 text-white shadow-md' => $item['active'],
                'text-teal-100 hover:bg-white/10 hover:text-white' => ! $item['active'],
            ])
            title="{{ $item['label'] }}"
        >
            @include('layouts.partials.sidebar-icon', ['name' => $item['icon'] ?? 'link'])
            <span class="truncate" x-show="!$store.sidebar.collapsed">{{ $item['label'] }}</span>
        </a>
    @empty
        <p class="px-3 py-2 text-xs text-teal-200/60">Tidak ada menu.</p>
    @endforelse
</nav>
