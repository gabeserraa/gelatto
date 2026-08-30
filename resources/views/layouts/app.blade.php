<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Gelatto ICE CO.') }} - {{ $header ?? '' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 antialiased" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">
        @include('layouts.partials.sidebar')

        <div class="flex-1">
            <header class="bg-white shadow">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <button class="md:hidden" @click="sidebarOpen = !sidebarOpen" aria-label="Abrir menu">
                        <x-dashboard.icon name="menu" class="h-6 w-6 text-gray-700" />
                    </button>

                    <h1 class="text-lg font-semibold text-gray-900">{{ $header ?? config('app.name') }}</h1>

                    <div class="flex items-center gap-4">
                        <a href="{{ route('profile.edit') }}" class="text-sm text-gray-500 hover:text-gray-700">Perfil</a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Sair</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-toast />

    @stack('scripts')
    @livewireScripts
</body>
</html>
