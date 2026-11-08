<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper font-sans text-ink antialiased" x-data="{ sidebar: false }">
    <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-ink/40 lg:hidden"></div>

    <aside class="fixed inset-y-0 left-0 z-40 w-60 -translate-x-full border-r border-line bg-white transition-transform lg:translate-x-0"
           :class="{ 'translate-x-0': sidebar }">
        <div class="px-5 py-5">
            <p class="text-base font-semibold">{{ config('app.name') }}</p>
        </div>
        <nav class="space-y-1 px-3" aria-label="Menu utama">
            <x-nav-link :href="route(auth()->user()->homeRouteName())" :active="request()->routeIs(auth()->user()->homeRouteName())">
                Dashboard
            </x-nav-link>
        </nav>
    </aside>

    <div class="lg:pl-60">
        <header class="flex items-center justify-between border-b border-line bg-white px-4 py-3 lg:px-10">
            <div class="flex items-center gap-3">
                <button type="button" class="rounded p-2 text-ink hover:bg-paper lg:hidden"
                        @click="sidebar = true" aria-label="Buka menu">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 5h14M3 10h14M3 15h14" stroke-linecap="round"/>
                    </svg>
                </button>
                <nav aria-label="Breadcrumb" class="text-sm text-muted">@yield('breadcrumb')</nav>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-medium leading-tight">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-muted">{{ auth()->user()->role->label() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded border border-line px-3 py-1.5 text-sm hover:bg-paper">Keluar</button>
                </form>
            </div>
        </header>

        <main class="px-4 py-8 lg:px-10">
            <div class="mx-auto max-w-5xl">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
