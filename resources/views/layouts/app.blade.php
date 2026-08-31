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
                <div class="flex items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-4">
                        <button class="text-slate-500 md:hidden" @click="sidebarOpen = !sidebarOpen" aria-label="Abrir menu">
                            <x-dashboard.icon name="menu" class="h-6 w-6" />
                        </button>

                        <div>
                            <h1 class="font-display text-lg font-bold leading-tight text-navy-950">{{ $header ?? config('app.name') }}</h1>
                            <p class="text-xs text-slate-400">{{ config('app.name', 'Gelatto ICE CO.') }} &middot; Painel de Gestão</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="hidden items-center gap-0.5 rounded-full bg-slate-100 p-1 text-[13px] font-medium sm:flex">
                            <span class="cursor-not-allowed rounded-full px-3 py-1.5 text-slate-400" title="Em breve">Hoje</span>
                            <span class="cursor-not-allowed rounded-full px-3 py-1.5 text-slate-400" title="Em breve">Esta semana</span>
                            <span class="rounded-full bg-white px-3 py-1.5 text-navy-950 shadow-card">Este mês</span>
                        </div>

                        <a
                            href="{{ route('dashboards.settings.preferences') }}"
                            title="Modo escuro — em Preferências"
                            class="hidden h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 sm:flex"
                        >
                            <x-dashboard.icon name="moon" class="h-[18px] w-[18px]" />
                        </a>

                        <x-dropdown align="right" width="72">
                            <x-slot name="trigger">
                                <button class="relative flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Notificações">
                                    <x-dashboard.icon name="bell" class="h-[18px] w-[18px]" />
                                    @if ($headerAlerts->isNotEmpty())
                                        <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500"></span>
                                    @endif
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div class="px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Pontos que precisam de atenção</div>
                                @forelse ($headerAlerts as $alert)
                                    <a
                                        href="{{ route('dashboards.points.index') }}"
                                        class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50"
                                    >
                                        <span class="truncate font-medium text-navy-950">{{ $alert['point']->name }}</span>
                                        <span class="shrink-0 rounded-full px-2 py-[2px] text-[11px] font-semibold {{ $alert['urgency'] === 'critico' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $alert['urgency'] === 'critico' ? 'Crítico' : 'Repor em breve' }}
                                        </span>
                                    </a>
                                @empty
                                    <p class="px-4 py-3 text-sm text-slate-500">Nenhuma pendência no momento.</p>
                                @endforelse
                            </x-slot>
                        </x-dropdown>

                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 hover:bg-slate-50">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-navy-950 text-xs font-semibold text-cyan-400">
                                        {{ Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('') }}
                                    </span>
                                    @php
                                        $nameParts = explode(' ', auth()->user()->name, 2);
                                        $displayName = count($nameParts) > 1 ? $nameParts[0].' '.mb_substr($nameParts[1], 0, 1).'.' : $nameParts[0];
                                    @endphp
                                    <span class="hidden text-left sm:block">
                                        <span class="block text-sm font-medium leading-tight text-navy-950">{{ $displayName }}</span>
                                    </span>
                                    <x-dashboard.icon name="chevron-down" class="h-4 w-4 text-slate-400" />
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Perfil</a>
                                <a href="{{ route('dashboards.settings.company') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Configurações</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Sair</button>
                                </form>
                            </x-slot>
                        </x-dropdown>
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
