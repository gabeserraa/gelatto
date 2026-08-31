<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Gelatto ICE CO.') }} - {{ $header ?? '' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-100 font-sans text-navy-950 antialiased" x-data="{ sidebarOpen: false }">
    <!--
        THESIS: An operate-mode admin panel refuses the default gray Breeze skin
        for a deep-navy authority + cyan-accent system, pinned exactly to the
        client's own Figma reference — no direction invented here.
        OWN-WORLD: Near-black navy sidebar (navy-950) with a soft cyan pill for
        the active route; slate-100 canvas; white 16px-radius cards on a
        hairline slate-200 border and a whisper-soft shadow; Outfit 700 for
        headings and KPI numbers, Inter for body copy; pastel-pill status
        badges (emerald/amber/red) at 11px/600; solid navy buttons, never a
        lighter blue.
        STORY: the admin scans KPIs, sidebar state, and status pills in
        seconds without hunting for what needs attention.
        FIRST VIEWPORT: Visão Executiva — sidebar left, a row of white KPI
        cards, then the monthly evolution chart.
        FORM: pinned to churn-space-56833911.figma.site, no direction roll.
        FINISH: unreviewed and undocumented is unfinished; this build ends
        with the finish review, the verdict, DESIGN.md, and every shipping
        raster carrying its provenance.
    -->
    <div class="flex min-h-screen">
        @include('layouts.partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="border-b border-slate-200 bg-white">
                <div class="flex items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-4">
                        <button class="text-slate-500 md:hidden" @click="sidebarOpen = !sidebarOpen" aria-label="Abrir menu">
                            <x-dashboard.icon name="menu" class="h-6 w-6" />
                        </button>

                        <div>
                            <h1 class="font-display text-lg font-bold leading-tight text-navy-950">{{ $header ?? config('app.name') }}</h1>
                            <p class="text-xs text-slate-400">{{ config('app.name', 'Gelatto ICE CO.') }} &middot; Painel de Gestão</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 text-sm">
                        <a href="{{ route('profile.edit') }}" class="text-slate-500 hover:text-navy-950">Perfil</a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-slate-500 hover:text-navy-950">Sair</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-toast />

    @stack('scripts')
    @livewireScripts
</body>
</html>
