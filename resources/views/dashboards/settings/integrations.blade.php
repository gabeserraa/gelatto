<x-app-layout>
    <x-slot name="header">Configurações</x-slot>

    <div class="flex flex-col gap-6 lg:flex-row">
        @include('dashboards.settings.partials.nav')

        <div class="flex-1">
            <div class="rounded-lg bg-white p-6 shadow">
                <h2 class="text-lg font-semibold text-gray-900">Integrações</h2>
                <p class="mt-1 text-sm text-gray-500">Conecte o Gelatto ICE CO. a ferramentas externas para automatizar processos.</p>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ([
                    ['name' => 'WhatsApp Business', 'description' => 'Envie alertas de reposição via WhatsApp.', 'badge' => 'Em breve'],
                    ['name' => 'Google Planilhas', 'description' => 'Sincronize dados com Google Sheets.', 'badge' => 'Em breve'],
                    ['name' => 'Nota Fiscal Eletrônica', 'description' => 'Emita NF-e diretamente do painel.', 'badge' => 'Beta'],
                    ['name' => 'Pix Automático', 'description' => 'Cobranças automáticas para parceiros.', 'badge' => 'Em breve'],
                ] as $integration)
                    <div class="rounded-lg bg-white p-4 shadow">
                        <div class="flex items-start justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">{{ $integration['name'] }}</h3>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $integration['badge'] }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">{{ $integration['description'] }}</p>
                        <button type="button" disabled class="mt-4 w-full rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-400">
                            {{ $integration['badge'] === 'Beta' ? 'Solicitar acesso' : 'Em breve' }}
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
