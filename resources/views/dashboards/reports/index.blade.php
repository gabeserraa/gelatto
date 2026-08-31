<x-app-layout>
    <x-slot name="header">Relatórios</x-slot>

    <form method="GET" class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Período dos relatórios — De</label>
                <input type="date" name="start" value="{{ $start }}" class="mt-1 rounded-[10px] border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">até</label>
                <input type="date" name="end" value="{{ $end }}" class="mt-1 rounded-[10px] border-slate-300">
            </div>
            <button type="submit" class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800">Aplicar período</button>
            <p class="text-sm text-slate-500">Período aplicado a todos os relatórios abaixo.</p>
        </div>
    </form>

    @php
        $reportStart = \Illuminate\Support\Carbon::parse($start);
    @endphp

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
            <h3 class="font-display text-base font-semibold text-navy-950">Relatório Financeiro Mensal</h3>
            <p class="mt-1 text-sm text-slate-500">Receita, custo e lucro por ponto. Comparativo mês anterior.</p>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('dashboards.financial.index') }}" class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800">Ver dashboard</a>
                <a href="{{ route('dashboards.reports.financial', ['start' => $start, 'end' => $end]) }}" class="rounded-[10px] border border-slate-300 px-4 py-2 text-[13px] font-semibold text-slate-700 hover:bg-slate-50">CSV</a>
            </div>
        </div>

        <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
            <h3 class="font-display text-base font-semibold text-navy-950">Relatório de Consumo por Ponto</h3>
            <p class="mt-1 text-sm text-slate-500">Consumo detalhado de gelo por ponto parceiro e período.</p>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('dashboards.reports.consumption', ['start' => $start, 'end' => $end]) }}" class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800">CSV</a>
            </div>
        </div>

        <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
            <h3 class="font-display text-base font-semibold text-navy-950">Relatório de Reposições</h3>
            <p class="mt-1 text-sm text-slate-500">Histórico de reposições, datas e volumes por localização.</p>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('dashboards.reports.replenishments', ['start' => $start, 'end' => $end]) }}" class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800">CSV</a>
            </div>
        </div>

        <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
            <h3 class="font-display text-base font-semibold text-navy-950">Relatório de Estoque Consolidado</h3>
            <p class="mt-1 text-sm text-slate-500">Estoque atual, giro e previsão de esgotamento por ponto.</p>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('dashboards.inventory.index') }}" class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800">Ver dashboard</a>
                <a href="{{ route('dashboards.inventory.export', ['month' => $reportStart->month, 'year' => $reportStart->year]) }}" class="rounded-[10px] border border-slate-300 px-4 py-2 text-[13px] font-semibold text-slate-700 hover:bg-slate-50">CSV</a>
            </div>
        </div>
    </div>
</x-app-layout>
