<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Siakad-Feeder') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-100 text-slate-800" x-data>
    <div class="min-h-screen flex">
        <div
            class="hidden lg:flex shrink-0 transition-all duration-300"
            :class="$store.sidebar.collapsed ? 'w-[4.5rem]' : 'w-64'"
        >
            @include('layouts.partials.sidebar')
        </div>

        <div
            x-show="$store.sidebar.mobileOpen"
            x-cloak
            class="lg:hidden fixed inset-0 z-50 flex"
            style="display: none;"
        >
            <div class="fixed inset-0 bg-slate-900/60" @click="$store.sidebar.closeMobile()"></div>
            <div class="relative z-50 w-72 max-w-[85vw] shadow-2xl">
                @include('layouts.partials.sidebar', ['forceExpanded' => true])
            </div>
        </div>

        <div class="flex-1 flex flex-col min-w-0 min-h-screen">
            @include('layouts.partials.topbar')

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @if (session('success'))
                    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                        {{ session('error') }}
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
