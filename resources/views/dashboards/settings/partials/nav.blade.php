@php
    $settingsNavItems = [
        ['label' => 'Perfil', 'route' => 'profile.edit'],
        ['label' => 'Empresa', 'route' => 'dashboards.settings.company'],
        ['label' => 'Preferências', 'route' => 'dashboards.settings.preferences'],
        ['label' => 'Integrações', 'route' => 'dashboards.settings.integrations'],
    ];
@endphp

<nav class="w-48 shrink-0 space-y-1">
    @foreach ($settingsNavItems as $item)
        <a
            href="{{ route($item['route']) }}"
            class="block rounded-[9px] px-3 py-2 text-[13px] font-medium {{ request()->routeIs($item['route']) ? 'bg-cyan-500/[0.13] font-semibold text-cyan-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700' }}"
        >
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
