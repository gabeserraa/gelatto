@props(['title', 'canvasId'])

<div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
    <h3 class="mb-3 font-display text-sm font-semibold text-navy-950">{{ $title }}</h3>
    <canvas id="{{ $canvasId }}"></canvas>
</div>
