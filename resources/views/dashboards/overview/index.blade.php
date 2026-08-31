<x-app-layout>
    <x-slot name="header">Visão Executiva</x-slot>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <x-dashboard.kpi-card label="Receita do mês" value="R$ {{ number_format($comparison['current']['revenue'], 2, ',', '.') }}" :hint="'Mês anterior: R$ '.number_format($comparison['previous']['revenue'], 2, ',', '.')" />
        <x-dashboard.kpi-card label="Custo do mês" value="R$ {{ number_format($comparison['current']['cost'], 2, ',', '.') }}" :hint="'Mês anterior: R$ '.number_format($comparison['previous']['cost'], 2, ',', '.')" />
        <x-dashboard.kpi-card label="Lucro do mês" value="R$ {{ number_format($comparison['current']['profit'], 2, ',', '.') }}" :hint="'Mês anterior: R$ '.number_format($comparison['previous']['profit'], 2, ',', '.')" />
        <x-dashboard.kpi-card label="Margem" value="{{ number_format($margin, 1) }}%" />
        <x-dashboard.kpi-card label="Gelo distribuído" value="{{ number_format($ice['current']['tons'], 2, ',', '.') }} t" :hint="number_format($ice['current']['kg'], 0, ',', '.').' kg no mês'" />
        <x-dashboard.kpi-card label="Pontos ativos" value="{{ $comparison['current']['active_points'] }}" :hint="$regionsCovered.' '.($regionsCovered === 1 ? 'região coberta' : 'regiões cobertas')" />
    </div>

    <div class="mt-6">
        <x-dashboard.chart-card title="Evolução mensal (últimos 12 meses)" canvasId="monthly-chart" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-4">
        <x-dashboard.chart-card title="Movimentação por tipo (mês atual)" canvasId="movement-type-chart" />
        <x-dashboard.chart-card title="Estoque por ponto" canvasId="stock-by-point-chart" />
        <x-dashboard.chart-card title="Composição financeira do mês" canvasId="financial-chart" />
        <x-dashboard.chart-card title="Consumo por tipo de ponto" canvasId="consumption-by-type-chart" />
    </div>

    <div class="mt-6">
        <h3 class="mb-2 text-sm font-medium text-gray-700">Pontos que precisam de reposição</h3>
        @if ($restockList->isEmpty())
            <p class="rounded-lg bg-white p-4 text-sm text-gray-500 shadow">Nenhum ponto ativo precisa de reposição no momento.</p>
        @else
            <x-dashboard.data-table :headers="['Ponto', 'Situação', 'Estoque atual', 'Previsão de esgotamento']">
                @foreach ($restockList as $row)
                    <tr>
                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $row['point']->name }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $row['urgency'] === 'critico' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $row['urgency'] === 'critico' ? 'Crítico' : 'Repor em breve' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($row['currentStock'], 1) }} kg</td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            {{ $row['daysUntilStockout'] !== null ? '~'.number_format($row['daysUntilStockout'], 0).' dias' : '-' }}
                        </td>
                    </tr>
                @endforeach
            </x-dashboard.data-table>
        @endif
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div>
            <h3 class="mb-2 text-sm font-medium text-gray-700">Mais lucrativos</h3>
            <x-dashboard.data-table :headers="['Ponto', 'Lucro do mês']">
                @foreach ($ranking['top'] as $item)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $item['point']->name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">R$ {{ number_format($item['profit'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </x-dashboard.data-table>
        </div>
        <div>
            <h3 class="mb-2 text-sm font-medium text-gray-700">Menos lucrativos</h3>
            <x-dashboard.data-table :headers="['Ponto', 'Lucro do mês']">
                @foreach ($ranking['bottom'] as $item)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $item['point']->name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">R$ {{ number_format($item['profit'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </x-dashboard.data-table>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new Chart(document.getElementById('monthly-chart'), {
                type: 'line',
                data: {
                    labels: @json(collect($series)->pluck('label')),
                    datasets: [
                        { label: 'Receita', data: @json(collect($series)->pluck('revenue')), borderColor: '#2563eb' },
                        { label: 'Custo', data: @json(collect($series)->pluck('cost')), borderColor: '#dc2626' },
                        { label: 'Lucro', data: @json(collect($series)->pluck('profit')), borderColor: '#16a34a' },
                    ],
                },
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
                        backgroundColor: ['#2563eb', '#f59e0b', '#94a3b8'],
                    }],
                },
                options: { plugins: { legend: { position: 'bottom' } } },
            });

            new Chart(document.getElementById('stock-by-point-chart'), {
                type: 'pie',
                data: {
                    labels: @json(array_keys($stockByPoint)),
                    datasets: [{
                        data: @json(array_values($stockByPoint)),
                        backgroundColor: ['#1d4ed8', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe', '#1e40af', '#2563eb', '#38bdf8', '#7dd3fc', '#0ea5e9'],
                    }],
                },
                options: { plugins: { legend: { position: 'bottom' } } },
            });

            new Chart(document.getElementById('consumption-by-type-chart'), {
                type: 'pie',
                data: {
                    labels: @json(array_keys($consumptionByPointType)),
                    datasets: [{
                        data: @json(array_values($consumptionByPointType)),
                        backgroundColor: ['#2563eb', '#38bdf8', '#f59e0b', '#94a3b8', '#16a34a', '#a855f7'],
                    }],
                },
                options: { plugins: { legend: { position: 'bottom' } } },
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
                        backgroundColor: ['#2563eb', '#dc2626', '#16a34a'],
                    }],
                },
                options: { plugins: { legend: { position: 'bottom' } } },
            });
        });
    </script>
    @endpush
</x-app-layout>
