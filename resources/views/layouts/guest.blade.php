<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Gelatto ICE CO.') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-navy-950 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-slate-100 px-6">
            <div class="flex items-center gap-2.5">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-navy-950">
                    <x-dashboard.icon name="droplet" class="h-5 w-5 text-cyan-400" />
                </span>
                <span class="font-display text-lg font-bold text-navy-950">Gelatto ICE CO.</span>
            </div>

            <div class="mt-6 w-full overflow-hidden rounded-card border border-slate-200 bg-white px-6 py-6 shadow-card sm:max-w-md">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
