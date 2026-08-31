@props(['name'])

<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" {{ $attributes->merge(['class' => 'h-5 w-5']) }}>
    @switch($name)
        @case('cube')
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
            @break
        @case('chart-bar')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5h3v6H3v-6zm6.75-4.5h3v10.5h-3V9zm6.75-3h3v13.5h-3V6z" />
            @break
        @case('menu')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            @break
        @case('archive-box')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5h16.5M3.75 7.5v10.5A2.25 2.25 0 006 20.25h12a2.25 2.25 0 002.25-2.25V7.5M3.75 7.5l1.5-3h13.5l1.5 3M10.5 12h3" />
            @break
        @default
            <circle cx="12" cy="12" r="8" stroke-linecap="round" />
    @endswitch
</svg>
