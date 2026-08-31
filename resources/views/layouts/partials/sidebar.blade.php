<aside
    class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full transform flex-col bg-navy-950 text-slate-300 transition-transform duration-200 ease-in-out md:relative md:translate-x-0"
    :class="{ 'translate-x-0': sidebarOpen }"
>
    <div class="flex items-center gap-2.5 px-5 py-6">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-400">
            <x-dashboard.icon name="droplet" class="h-5 w-5" />
        </span>
        <span class="font-display text-base font-bold leading-tight text-white">Gelatto<br class="hidden" /> ICE CO.</span>
    </div>

    <nav class="mt-2 flex-1 space-y-1 px-3">
        @foreach (collect(config('dashboards.items'))->sortBy('order') as $item)
            <a
                href="{{ route($item['route']) }}"
                class="flex items-center gap-3 rounded-[9px] border-l-2 px-3 py-2 text-[13px] font-medium transition-colors {{ request()->routeIs($item['route']) ? 'border-cyan-400 bg-cyan-500/[0.13] font-semibold text-cyan-400' : 'border-transparent text-slate-400 hover:bg-white/5 hover:text-slate-100' }}"
            >
                <x-dashboard.icon :name="$item['icon']" class="h-[18px] w-[18px] shrink-0" />
                {{ $item['name'] }}
            </a>
        @endforeach
    </nav>

    <div class="flex items-center gap-3 border-t border-white/10 px-5 py-4">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-navy-800 text-xs font-semibold text-cyan-400">
            {{ Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('') }}
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-slate-100">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-slate-500">{{ auth()->user()->job_title ?: 'Admin' }}</p>
        </div>
    </div>
</aside>
