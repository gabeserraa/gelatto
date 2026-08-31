@php
    $settingsNavItems = [
        ['label' => 'Perfil', 'icon' => 'user', 'route' => 'profile.edit'],
        ['label' => 'Empresa', 'icon' => 'building', 'route' => 'dashboards.settings.company'],
        ['label' => 'Preferências', 'icon' => 'sliders', 'route' => 'dashboards.settings.preferences'],
        ['label' => 'Integrações', 'icon' => 'plug', 'route' => 'dashboards.settings.integrations'],
    ];
@endphp

<nav class="w-full shrink-0 space-y-1 lg:w-52">
    @foreach ($settingsNavItems as $item)
        <a
            href="{{ route($item['route']) }}"
            class="flex items-center gap-2.5 rounded-[10px] px-3 py-2 text-[13px] font-medium {{ request()->routeIs($item['route']) ? 'bg-cyan-500/[0.13] font-semibold text-cyan-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700' }}"
        >
            <x-dashboard.icon :name="$item['icon']" class="h-[18px] w-[18px] shrink-0" />
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
