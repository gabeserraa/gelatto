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
        @case('trending-up')
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
            @break
        @case('droplet')
            <path fill="currentColor" stroke="none" d="M12 2.25c-.4 0-.78.17-1.05.47C9.1 4.79 4.5 10.44 4.5 14.5c0 4.28 3.36 7.75 7.5 7.75s7.5-3.47 7.5-7.75c0-4.06-4.6-9.71-6.45-11.78a1.4 1.4 0 00-1.05-.47z" />
            @break
        @case('cog')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.02-.397-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.869a6.52 6.52 0 01-.22-.128c-.325-.195-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.127.332-.184.582-.496.645-.87l.213-1.28z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            @break
        @case('document')
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15h7.5M8.25 18h4.5" />
            @break
        @default
            <circle cx="12" cy="12" r="8" stroke-linecap="round" />
    @endswitch
</svg>
