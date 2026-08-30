@props(['label', 'value', 'hint' => null])

<div class="rounded-lg bg-white p-4 shadow">
    <p class="text-sm text-gray-500">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
    @endif
</div>
