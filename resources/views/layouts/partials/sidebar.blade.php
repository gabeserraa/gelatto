<aside
    class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full transform bg-blue-900 text-blue-100 transition-transform duration-200 ease-in-out md:relative md:translate-x-0"
    :class="{ 'translate-x-0': sidebarOpen }"
>
    <div class="px-4 py-5 text-lg font-bold text-white">Gelatto ICE CO.</div>

    <nav class="mt-2 space-y-1 px-2">
        @foreach (collect(config('dashboards.items'))->sortBy('order') as $item)
            <a
                href="{{ route($item['route']) }}"
                class="flex items-center rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs($item['route']) ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}"
            >
                <x-dashboard.icon :name="$item['icon']" class="mr-3 h-5 w-5" />
                {{ $item['name'] }}
            </a>
        @endforeach
    </nav>
</aside>
