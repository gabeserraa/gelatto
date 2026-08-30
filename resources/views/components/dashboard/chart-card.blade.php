@props(['title', 'canvasId'])

<div class="rounded-lg bg-white p-4 shadow">
    <h3 class="mb-2 text-sm font-medium text-gray-700">{{ $title }}</h3>
    <canvas id="{{ $canvasId }}"></canvas>
</div>
