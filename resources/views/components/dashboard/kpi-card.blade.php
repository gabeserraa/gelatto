@props(['label', 'value', 'hint' => null])

<div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</p>
    <p class="mt-1.5 font-display text-[28px] font-bold leading-none text-navy-950">{{ $value }}</p>
    @if ($hint)
        <p class="mt-2 text-xs text-slate-400">{{ $hint }}</p>
    @endif
</div>
