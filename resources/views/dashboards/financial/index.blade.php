<x-app-layout>
    <x-slot name="header">Financeiro / Lucro</x-slot>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-dashboard.chart-card title="Receita × Custo × Lucro (últimos 6 meses)" canvasId="financial-series-chart" />
        </div>

        <div class="space-y-4">
            <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
                <p class="text-sm text-slate-500">Margem de lucro — {{ ucfirst($currentMonthLabel) }}</p>
                <p class="mt-1 text-2xl font-semibold text-navy-950">{{ number_format($marginChange['current'], 1) }}%</p>
                <p class="mt-1 text-xs {{ $marginChange['deltaPp'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $marginChange['deltaPp'] >= 0 ? '+' : '' }}{{ number_format($marginChange['deltaPp'], 1) }}pp vs mês anterior
                </p>
                <div class="mt-3 flex justify-between text-sm text-slate-600">
                    <span>Receita</span>
                    <span class="font-medium text-navy-950">R$ {{ number_format($comparison['current']['revenue'], 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Lucro</span>
                    <span class="font-medium text-navy-950">R$ {{ number_format($comparison['current']['profit'], 2, ',', '.') }}</span>
                </div>
            </div>

            <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
                <p class="text-sm text-slate-500">Projeção de {{ $nextMonthLabel }}</p>
                <p class="mt-1 text-2xl font-semibold text-navy-950">R$ {{ number_format($projection['projectedProfit'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    Baseado em {{ $projection['growthRatePercent'] >= 0 ? '+' : '' }}{{ number_format($projection['growthRatePercent'], 1) }}% vs {{ $currentMonthLabel }}
                </p>
                <p class="mt-2 text-sm text-slate-600">
                    Se mantiver o ritmo atual, a empresa deve atingir aproximadamente
                    <span class="font-medium text-navy-950">R$ {{ number_format($projection['projectedProfit'], 2, ',', '.') }}</span>
                    de lucro líquido em {{ $nextMonthLabel }}.
                </p>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <h3 class="mb-3 font-display text-sm font-semibold text-navy-950">Ranking de lucro por ponto</h3>
            <x-dashboard.data-table :headers="['#', 'Ponto', 'Receita', 'Custo', 'Lucro', 'Var. %', 'Margem']">
                @foreach ($ranking as $index => $row)
                    <tr>
                        <td class="px-4 py-3 text-sm text-slate-500">#{{ $index + 1 }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-navy-950">{{ $row['point']->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">R$ {{ number_format($row['revenue'], 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">R$ {{ number_format($row['cost'], 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-navy-950">R$ {{ number_format($row['profit'], 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($row['variationPercent'] === null)
                                <span class="text-slate-400">-</span>
                            @else
                                <span class="{{ $row['variationPercent'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $row['variationPercent'] >= 0 ? '↗' : '↘' }} {{ number_format(abs($row['variationPercent']), 1) }}%
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ number_format($row['margin'], 0) }}%</td>
                    </tr>
                @endforeach
            </x-dashboard.data-table>
        </div>

        <div>
            <x-dashboard.chart-card title="Participação no lucro" canvasId="profit-share-chart" />
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new Chart(document.getElementById('financial-series-chart'), {
                type: 'bar',
                data: {
                    labels: @json(collect($series)->pluck('label')),
                    datasets: [
                        { label: 'Receita', data: @json(collect($series)->pluck('revenue')), backgroundColor: '#06b6d4' },
                        { label: 'Custo', data: @json(collect($series)->pluck('cost')), backgroundColor: '#ef4444' },
                        { label: 'Lucro', data: @json(collect($series)->pluck('profit')), backgroundColor: '#10b981' },
                    ],
                },
            });

            new Chart(document.getElementById('profit-share-chart'), {
                type: 'doughnut',
                data: {
                    labels: @json(array_keys($profitShare)),
                    datasets: [{
                        data: @json(array_values($profitShare)),
                        backgroundColor: ['#06b6d4', '#22d3ee', '#67e8f9', '#0e7490', '#0891b2', '#a5f3fc', '#155e75', '#cffafe', '#164e63', '#38bdf8'], borderWidth: 2, borderColor: '#ffffff',
                    }],
                },
                options: { plugins: { legend: { position: 'bottom' } } },
            });
        });
    </script>
    @endpush
</x-app-layout>
