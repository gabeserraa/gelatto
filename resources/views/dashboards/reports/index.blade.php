<x-app-layout>
    <x-slot name="header">Relatórios</x-slot>

    <form method="GET" class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
        <div class="flex flex-wrap items-center gap-4">
            <x-dashboard.icon name="document" class="hidden h-5 w-5 text-cyan-600 sm:block" />
            <div class="flex items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-500">Período dos relatórios — De</label>
                    <input type="date" name="start" value="{{ $start }}" class="mt-1 rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500">até</label>
                    <input type="date" name="end" value="{{ $end }}" class="mt-1 rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                </div>
            </div>
            <button type="submit" class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800">Aplicar período</button>
            <p class="ml-auto text-xs text-slate-400">Período aplicado a todos os relatórios abaixo.</p>
        </div>
    </form>

    @php
        $reportStart = \Illuminate\Support\Carbon::parse($start);
    @endphp

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-600">
                    <x-dashboard.icon name="trending-up" class="h-[18px] w-[18px]" />
                </span>
                <div>
                    <h3 class="font-display text-base font-semibold text-navy-950">Relatório Financeiro Mensal</h3>
                    <p class="mt-1 text-sm text-slate-500">Receita, custo e lucro por ponto. Comparativo mês anterior.</p>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('dashboards.financial.index') }}" class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800">Ver dashboard</a>
                <a href="{{ route('dashboards.reports.financial', ['start' => $start, 'end' => $end]) }}" class="rounded-[10px] border border-slate-300 px-4 py-2 text-[13px] font-semibold text-slate-700 hover:bg-slate-50">CSV</a>
            </div>
        </div>

        <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-600">
                    <x-dashboard.icon name="droplet" class="h-[18px] w-[18px]" />
                </span>
                <div>
                    <h3 class="font-display text-base font-semibold text-navy-950">Relatório de Consumo por Ponto</h3>
                    <p class="mt-1 text-sm text-slate-500">Consumo detalhado de gelo por ponto parceiro e período.</p>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('dashboards.reports.consumption', ['start' => $start, 'end' => $end]) }}" class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800">CSV</a>
            </div>
        </div>

        <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600">
                    <x-dashboard.icon name="refresh" class="h-[18px] w-[18px]" />
                </span>
                <div>
                    <h3 class="font-display text-base font-semibold text-navy-950">Relatório de Reposições</h3>
                    <p class="mt-1 text-sm text-slate-500">Histórico de reposições, datas e volumes por localização.</p>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('dashboards.reports.replenishments', ['start' => $start, 'end' => $end]) }}" class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800">CSV</a>
            </div>
        </div>

        <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-navy-950/10 text-navy-800">
                    <x-dashboard.icon name="cube" class="h-[18px] w-[18px]" />
                </span>
                <div>
                    <h3 class="font-display text-base font-semibold text-navy-950">Relatório de Estoque Consolidado</h3>
                    <p class="mt-1 text-sm text-slate-500">Estoque atual, giro e previsão de esgotamento por ponto.</p>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('dashboards.inventory.index') }}" class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800">Ver dashboard</a>
                <a href="{{ route('dashboards.inventory.export', ['month' => $reportStart->month, 'year' => $reportStart->year]) }}" class="rounded-[10px] border border-slate-300 px-4 py-2 text-[13px] font-semibold text-slate-700 hover:bg-slate-50">CSV</a>
            </div>
        </div>
    </div>
</x-app-layout>
