@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 shadow-sm']) !!}>
