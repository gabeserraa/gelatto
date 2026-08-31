@props(['percent' => 0, 'urgency' => 'ok'])

@php
    $colors = [
        'critico' => 'bg-red-500',
        'repor_em_breve' => 'bg-amber-400',
        'ok' => 'bg-emerald-500',
    ];
    $barColor = $colors[$urgency] ?? $colors['ok'];
    $clamped = max(0, min(100, $percent));
@endphp

<div {{ $attributes->merge(['class' => 'h-1.5 w-full overflow-hidden rounded-full bg-slate-100']) }}>
    <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $clamped }}%"></div>
</div>
