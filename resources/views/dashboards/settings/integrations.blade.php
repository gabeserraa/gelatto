<x-app-layout>
    <x-slot name="header">Configurações</x-slot>

    <div class="flex flex-col gap-6 lg:flex-row">
        @include('dashboards.settings.partials.nav')

        <div class="flex-1">
            <div class="rounded-card border border-slate-200 bg-white p-6 shadow-card">
                <h2 class="font-display text-lg font-semibold text-navy-950">Integrações</h2>
                <p class="mt-1 text-sm text-slate-500">Conecte o Gelatto ICE CO. a ferramentas externas para automatizar processos.</p>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ([
                    ['name' => 'WhatsApp Business', 'description' => 'Envie alertas de reposição via WhatsApp.', 'badge' => 'Em breve'],
                    ['name' => 'Google Planilhas', 'description' => 'Sincronize dados com Google Sheets.', 'badge' => 'Em breve'],
                    ['name' => 'Nota Fiscal Eletrônica', 'description' => 'Emita NF-e diretamente do painel.', 'badge' => 'Beta'],
                    ['name' => 'Pix Automático', 'description' => 'Cobranças automáticas para parceiros.', 'badge' => 'Em breve'],
                ] as $integration)
                    <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
                        <div class="flex items-start justify-between">
                            <h3 class="font-display text-sm font-semibold text-navy-950">{{ $integration['name'] }}</h3>
                            <span class="rounded-full px-2.5 py-[3px] text-[11px] font-semibold {{ $integration['badge'] === 'Beta' ? 'bg-cyan-500/[0.13] text-cyan-700' : 'bg-slate-100 text-slate-500' }}">{{ $integration['badge'] }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ $integration['description'] }}</p>
                        <button type="button" disabled class="mt-4 w-full rounded-[10px] border border-slate-200 px-4 py-2 text-[13px] font-semibold text-slate-400">
                            {{ $integration['badge'] === 'Beta' ? 'Solicitar acesso' : 'Em breve' }}
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
