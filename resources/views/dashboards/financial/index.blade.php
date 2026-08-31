<x-app-layout>
    <x-slot name="header">Financeiro / Lucro</x-slot>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-dashboard.chart-card title="Receita × Custo × Lucro (últimos 6 meses)" canvasId="financial-series-chart" />
        </div>

        <div class="space-y-4">
            <div class="rounded-lg bg-white p-4 shadow">
                <p class="text-sm text-gray-500">Margem de lucro — {{ ucfirst($currentMonthLabel) }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($marginChange['current'], 1) }}%</p>
                <p class="mt-1 text-xs {{ $marginChange['deltaPp'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $marginChange['deltaPp'] >= 0 ? '+' : '' }}{{ number_format($marginChange['deltaPp'], 1) }}pp vs mês anterior
                </p>
                <div class="mt-3 flex justify-between text-sm text-gray-600">
                    <span>Receita</span>
                    <span class="font-medium text-gray-900">R$ {{ number_format($comparison['current']['revenue'], 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Lucro</span>
                    <span class="font-medium text-gray-900">R$ {{ number_format($comparison['current']['profit'], 2, ',', '.') }}</span>
                </div>
            </div>

            <div class="rounded-lg bg-white p-4 shadow">
                <p class="text-sm text-gray-500">Projeção de {{ $nextMonthLabel }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">R$ {{ number_format($projection['projectedProfit'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-gray-500">
                    Baseado em {{ $projection['growthRatePercent'] >= 0 ? '+' : '' }}{{ number_format($projection['growthRatePercent'], 1) }}% vs {{ $currentMonthLabel }}
                </p>
                <p class="mt-2 text-sm text-gray-600">
                    Se mantiver o ritmo atual, a empresa deve atingir aproximadamente
                    <span class="font-medium text-gray-900">R$ {{ number_format($projection['projectedProfit'], 2, ',', '.') }}</span>
                    de lucro líquido em {{ $nextMonthLabel }}.
                </p>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <h3 class="mb-2 text-sm font-medium text-gray-700">Ranking de lucro por ponto</h3>
            <x-dashboard.data-table :headers="['#', 'Ponto', 'Receita', 'Custo', 'Lucro', 'Var. %', 'Margem']">
                @foreach ($ranking as $index => $row)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-500">#{{ $index + 1 }}</td>
                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $row['point']->name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">R$ {{ number_format($row['revenue'], 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">R$ {{ number_format($row['cost'], 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-sm font-medium text-gray-900">R$ {{ number_format($row['profit'], 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-sm">
                            @if ($row['variationPercent'] === null)
                                <span class="text-gray-400">-</span>
                            @else
                                <span class="{{ $row['variationPercent'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $row['variationPercent'] >= 0 ? '↗' : '↘' }} {{ number_format(abs($row['variationPercent']), 1) }}%
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($row['margin'], 0) }}%</td>
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
                        { label: 'Receita', data: @json(collect($series)->pluck('revenue')), backgroundColor: '#2563eb' },
                        { label: 'Custo', data: @json(collect($series)->pluck('cost')), backgroundColor: '#dc2626' },
                        { label: 'Lucro', data: @json(collect($series)->pluck('profit')), backgroundColor: '#16a34a' },
                    ],
                },
            });

            new Chart(document.getElementById('profit-share-chart'), {
                type: 'doughnut',
                data: {
                    labels: @json(array_keys($profitShare)),
                    datasets: [{
                        data: @json(array_values($profitShare)),
                        backgroundColor: ['#1d4ed8', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe', '#1e40af', '#2563eb', '#38bdf8', '#7dd3fc', '#0ea5e9'],
                    }],
                },
                options: { plugins: { legend: { position: 'bottom' } } },
            });
        });
    </script>
    @endpush
</x-app-layout>
