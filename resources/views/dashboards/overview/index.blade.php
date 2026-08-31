<x-app-layout>
    <x-slot name="header">Visão Geral</x-slot>

    @php
        $prevRevenue = $comparison['previous']['revenue'];
        $prevProfit = $comparison['previous']['profit'];
        $prevIceKg = $ice['previous']['kg'];
        $revenueTrend = $prevRevenue > 0 ? (($comparison['current']['revenue'] - $prevRevenue) / $prevRevenue) * 100 : null;
        $profitTrend = $prevProfit > 0 ? (($comparison['current']['profit'] - $prevProfit) / $prevProfit) * 100 : null;
        $iceTrend = $prevIceKg > 0 ? (($ice['current']['kg'] - $prevIceKg) / $prevIceKg) * 100 : null;
        $activePointsDelta = $comparison['current']['active_points'] - $comparison['previous']['active_points'];
        $consumptionTotal = array_sum($consumptionByPointType);
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-dashboard.stat-card
            icon="trending-up"
            iconClass="bg-cyan-500/10 text-cyan-600"
            label="Receita do mês"
            value="R$ {{ number_format($comparison['current']['revenue'], 0, ',', '.') }}"
            :meta="now()->translatedFormat('F Y')"
            :trendValue="$revenueTrend"
        />
        <x-dashboard.stat-card
            icon="trending-up"
            iconClass="bg-emerald-500/10 text-emerald-600"
            label="Lucro líquido"
            value="R$ {{ number_format($comparison['current']['profit'], 0, ',', '.') }}"
            :meta="'Margem '.number_format($margin, 1).'%'"
            :trendValue="$profitTrend"
        />
        <x-dashboard.stat-card
            icon="droplet"
            iconClass="bg-cyan-500/10 text-cyan-600"
            label="Gelo distribuído"
            value="{{ number_format($ice['current']['tons'], 1, ',', '.') }} t"
            :meta="number_format($ice['current']['kg'], 0, ',', '.').' kg no mês'"
            :trendValue="$iceTrend"
        />
        <x-dashboard.stat-card
            icon="cube"
            iconClass="bg-navy-950/10 text-navy-800"
            label="Pontos ativos"
            value="{{ $comparison['current']['active_points'] }}"
            :trendValue="$activePointsDelta"
            trendSuffix=""
            :trendDecimals="0"
        >
            <p class="mt-2 text-xs text-cyan-600">{{ $regionsCovered }} {{ $regionsCovered === 1 ? 'região coberta' : 'regiões cobertas' }}</p>
        </x-dashboard.stat-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card lg:col-span-2">
            <h3 class="font-display text-sm font-semibold text-navy-950">Receita × Custo × Lucro</h3>
            <p class="text-xs text-slate-400">Últimos 12 meses</p>
            <div class="mt-3">
                <canvas id="monthly-chart" height="90"></canvas>
            </div>
        </div>

        <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
            <h3 class="font-display text-sm font-semibold text-navy-950">Consumo por Tipo</h3>
            <p class="text-xs text-slate-400">Distribuição por categoria</p>
            <div class="mt-3 flex items-center gap-4">
                <div class="h-32 w-32 shrink-0">
                    <canvas id="consumption-by-type-chart"></canvas>
                </div>
                <ul class="min-w-0 flex-1 space-y-1.5 text-xs">
                    @foreach ($consumptionByPointType as $typeLabel => $typeValue)
                        <li class="flex items-center justify-between gap-2 text-slate-600">
                            <span class="flex min-w-0 items-center gap-1.5">
                                <span class="h-2 w-2 shrink-0 rounded-full" style="background-color: {{ ['#06b6d4', '#0891b2', '#22d3ee', '#0e7490', '#67e8f9', '#155e75'][$loop->index % 6] }}"></span>
                                <span class="truncate">{{ $typeLabel }}</span>
                            </span>
                            <span class="shrink-0 font-medium text-navy-950">{{ $consumptionTotal > 0 ? number_format(($typeValue / $consumptionTotal) * 100, 0) : 0 }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <div class="flex items-center justify-between">
            <h3 class="font-display text-sm font-semibold text-navy-950">Pontos que Precisam de Reposição</h3>
            <span class="text-xs text-slate-400">{{ $restockTotal }} {{ $restockTotal === 1 ? 'ponto requer' : 'pontos requerem' }} atenção</span>
        </div>

        @if ($restockList->isEmpty())
            <p class="mt-3 rounded-card border border-slate-200 bg-white p-5 text-sm text-slate-500 shadow-card">Nenhum ponto ativo precisa de reposição no momento.</p>
        @else
            <div class="mt-3 space-y-3">
                @foreach ($restockList as $row)
                    @php $critical = $row['urgency'] === 'critico'; @endphp
                    <div class="flex flex-wrap items-center justify-between gap-4 rounded-card border p-4 {{ $critical ? 'border-red-100 bg-red-50/60' : 'border-amber-100 bg-amber-50/60' }}">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h4 class="font-semibold text-navy-950">{{ $row['point']->name }}</h4>
                                <x-dashboard.urgency-badge :urgency="$row['urgency']" />
                            </div>
                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $row['point']->address }}{{ $row['point']->region ? ' — '.$row['point']->region : '' }}</p>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-[11px] uppercase tracking-wide text-slate-400">Estoque / Previsão</p>
                                <p class="text-sm font-semibold {{ $critical ? 'text-red-600' : 'text-amber-600' }}">
                                    {{ number_format($row['currentStock'], 0) }} kg · {{ $row['daysUntilStockout'] !== null ? '~'.number_format($row['daysUntilStockout'], 0).' dias' : '-' }}
                                </p>
                                <x-dashboard.progress-bar class="mt-1 w-24" :percent="$row['stockPercentage']" :urgency="$row['urgency']" />
                            </div>
                            <button
                                onclick="Livewire.dispatch('open-movement-form', { pointId: {{ $row['point']->id }} })"
                                class="flex items-center gap-1.5 rounded-[10px] bg-navy-950 px-3 py-2 text-[13px] font-semibold text-white hover:bg-navy-800"
                            >
                                <x-dashboard.icon name="plus" class="h-4 w-4" />
                                Repor
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-dashboard.chart-card title="Movimentação por tipo (mês atual)" canvasId="movement-type-chart" />
        <x-dashboard.chart-card title="Estoque por ponto" canvasId="stock-by-point-chart" />
        <x-dashboard.chart-card title="Composição financeira do mês" canvasId="financial-chart" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div>
            <h3 class="mb-3 font-display text-sm font-semibold text-navy-950">Mais lucrativos</h3>
            <x-dashboard.data-table :headers="['Ponto', 'Lucro do mês']">
                @foreach ($ranking['top'] as $item)
                    <tr>
                        <td class="px-4 py-3 text-sm text-navy-950">{{ $item['point']->name }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-emerald-600">R$ {{ number_format($item['profit'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </x-dashboard.data-table>
        </div>
        <div>
            <h3 class="mb-3 font-display text-sm font-semibold text-navy-950">Menos lucrativos</h3>
            <x-dashboard.data-table :headers="['Ponto', 'Lucro do mês']">
                @foreach ($ranking['bottom'] as $item)
                    <tr>
                        <td class="px-4 py-3 text-sm text-navy-950">{{ $item['point']->name }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-600">R$ {{ number_format($item['profit'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </x-dashboard.data-table>
        </div>
    </div>

    <livewire:movement-form-modal />

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const monthlyCtx = document.getElementById('monthly-chart').getContext('2d');
            const revenueFill = monthlyCtx.createLinearGradient(0, 0, 0, 220);
            revenueFill.addColorStop(0, 'rgba(6, 182, 212, 0.25)');
            revenueFill.addColorStop(1, 'rgba(6, 182, 212, 0)');
            const costFill = monthlyCtx.createLinearGradient(0, 0, 0, 220);
            costFill.addColorStop(0, 'rgba(245, 158, 11, 0.20)');
            costFill.addColorStop(1, 'rgba(245, 158, 11, 0)');

            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: @json(collect($series)->pluck('label')),
                    datasets: [
                        { label: 'Receita', data: @json(collect($series)->pluck('revenue')), borderColor: '#06b6d4', backgroundColor: revenueFill, fill: true, tension: 0.35, pointRadius: 2 },
                        { label: 'Custo', data: @json(collect($series)->pluck('cost')), borderColor: '#f59e0b', backgroundColor: costFill, fill: true, tension: 0.35, pointRadius: 2 },
                        { label: 'Lucro', data: @json(collect($series)->pluck('profit')), borderColor: '#10b981', backgroundColor: '#10b981', fill: false, tension: 0.35, pointRadius: 2 },
                    ],
                },
                options: { scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f5f9' } } } },
            });

            new Chart(document.getElementById('movement-type-chart'), {
                type: 'pie',
                data: {
                    labels: ['Reposição', 'Retirada', 'Ajuste'],
                    datasets: [{
                        data: [
                            {{ $movementTypes['reposicao'] }},
                            {{ $movementTypes['retirada'] }},
                            {{ $movementTypes['ajuste'] }},
                        ],
                        backgroundColor: ['#06b6d4', '#f59e0b', '#94a3b8'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }],
                },
            });

            new Chart(document.getElementById('stock-by-point-chart'), {
                type: 'pie',
                data: {
                    labels: @json(array_keys($stockByPoint)),
                    datasets: [{
                        data: @json(array_values($stockByPoint)),
                        backgroundColor: window.chartPalette,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }],
                },
            });

            new Chart(document.getElementById('financial-chart'), {
                type: 'pie',
                data: {
                    labels: ['Receita', 'Custo', 'Lucro'],
                    datasets: [{
                        data: [
                            {{ $comparison['current']['revenue'] }},
                            {{ $comparison['current']['cost'] }},
                            {{ max($comparison['current']['profit'], 0) }},
                        ],
                        backgroundColor: ['#06b6d4', '#ef4444', '#10b981'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }],
                },
            });

            new Chart(document.getElementById('consumption-by-type-chart'), {
                type: 'doughnut',
                data: {
                    labels: @json(array_keys($consumptionByPointType)),
                    datasets: [{
                        data: @json(array_values($consumptionByPointType)),
                        backgroundColor: ['#06b6d4', '#0891b2', '#22d3ee', '#0e7490', '#67e8f9', '#155e75'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }],
                },
                options: { cutout: '70%', plugins: { legend: { display: false } } },
            });
        });
    </script>
    @endpush
</x-app-layout>
