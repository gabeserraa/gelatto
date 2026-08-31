@props([
    'icon' => 'chart-bar',
    'iconClass' => 'bg-cyan-500/10 text-cyan-600',
    'label',
    'value',
    'meta' => null,
    'trendValue' => null,
    'trendLabel' => 'vs mês anterior',
    'trendSuffix' => '%',
    'trendDecimals' => 1,
])

@php
    $trendUp = $trendValue !== null && $trendValue >= 0;
@endphp

<div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
    <div class="flex items-start justify-between">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</p>
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $iconClass }}">
            <x-dashboard.icon :name="$icon" class="h-4 w-4" />
        </span>
    </div>
    <p class="mt-1.5 font-display text-[26px] font-bold leading-none text-navy-950">{{ $value }}</p>
    @if ($meta)
        <p class="mt-2 text-xs text-slate-400">{{ $meta }}</p>
    @endif
    @if ($trendValue !== null)
        <p class="mt-2 flex items-center gap-1 text-xs {{ $trendUp ? 'text-emerald-600' : 'text-red-600' }}">
            <x-dashboard.icon :name="$trendUp ? 'arrow-up-right' : 'arrow-down-right'" class="h-3 w-3 shrink-0" />
            {{ $trendUp ? '+' : '' }}{{ number_format($trendValue, $trendDecimals) }}{{ $trendSuffix }} <span class="text-slate-400">{{ $trendLabel }}</span>
        </p>
    @endif
    {{ $slot ?? '' }}
</div>
