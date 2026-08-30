@props(['status'])

@php
    $map = [
        'ativo' => 'bg-green-100 text-green-800',
        'inativo' => 'bg-gray-100 text-gray-800',
        'manutencao' => 'bg-yellow-100 text-yellow-800',
    ];
    $labels = [
        'ativo' => 'Ativo',
        'inativo' => 'Inativo',
        'manutencao' => 'Em manutenção',
    ];
    $classes = $map[$status] ?? 'bg-gray-100 text-gray-800';
    $label = $labels[$status] ?? $status;
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium $classes"]) }}>
    {{ $label }}
</span>
