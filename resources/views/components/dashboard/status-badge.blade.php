@props(['status'])

@php
    $map = [
        'ativo' => 'bg-emerald-100 text-emerald-700',
        'inativo' => 'bg-slate-100 text-slate-600',
        'manutencao' => 'bg-amber-100 text-amber-700',
    ];
    $labels = [
        'ativo' => 'Ativo',
        'inativo' => 'Inativo',
        'manutencao' => 'Em manutenção',
    ];
    $classes = $map[$status] ?? 'bg-slate-100 text-slate-600';
    $label = $labels[$status] ?? $status;
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-[3px] text-[11px] font-semibold $classes"]) }}>
    {{ $label }}
</span>
