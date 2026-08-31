@props(['urgency'])

@php
    $map = [
        'critico' => ['bg-red-100 text-red-700', 'Crítico'],
        'repor_em_breve' => ['bg-amber-100 text-amber-700', 'Repor em breve'],
        'ok' => ['bg-emerald-100 text-emerald-700', 'OK'],
    ];
    [$classes, $label] = $map[$urgency] ?? $map['ok'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-[3px] text-[11px] font-semibold $classes"]) }}>
    {{ $label }}
</span>
