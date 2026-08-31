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
            class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs($item['route']) ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50' }}"
        >
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
