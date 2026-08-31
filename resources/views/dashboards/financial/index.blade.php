<x-app-layout>
    <x-slot name="header">Financeiro / Lucro</x-slot>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-card border border-slate-200 bg-white p-5 shadow-card">
            <h3 class="font-display text-sm font-semibold text-navy-950">Receita × Custo × Lucro</h3>
            <p class="text-xs text-slate-400">Últimos 6 meses</p>
            <div class="mt-3">
                <canvas id="financial-series-chart" height="110"></canvas>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Margem de lucro — {{ Illuminate\Support\Str::upper($currentMonthLabel) }}</p>
                <p class="mt-1 font-display text-3xl font-bold text-cyan-600">{{ number_format($marginChange['current'], 1) }}%</p>
                <span class="mt-2 inline-flex items-center gap-1 rounded-full px-2 py-[3px] text-[11px] font-semibold {{ $marginChange['deltaPp'] >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                    <x-dashboard.icon :name="$marginChange['deltaPp'] >= 0 ? 'arrow-up-right' : 'arrow-down-right'" class="h-3 w-3" />
                    {{ $marginChange['deltaPp'] >= 0 ? '+' : '' }}{{ number_format($marginChange['deltaPp'], 1) }}pp vs {{ $previousMonthLabel ?? 'mês anterior' }}
                </span>
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
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Projeção de {{ Illuminate\Support\Str::upper($nextMonthLabel) }}</p>
                <p class="mt-1 font-display text-3xl font-bold text-cyan-600">R$ {{ number_format($projection['projectedProfit'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    Baseado em {{ $projection['growthRatePercent'] >= 0 ? '+' : '' }}{{ number_format($projection['growthRatePercent'], 1) }}% vs {{ $currentMonthLabel }}
                </p>
                <p class="mt-3 rounded-[10px] bg-slate-50 p-3 text-sm text-slate-600">
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
                        <td class="px-4 py-3 text-sm font-bold text-cyan-600">#{{ $index + 1 }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-navy-950">{{ $row['point']->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">R$ {{ number_format($row['revenue'], 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">R$ {{ number_format($row['cost'], 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-navy-950">R$ {{ number_format($row['profit'], 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($row['variationPercent'] === null)
                                <span class="text-slate-400">-</span>
                            @else
                                <span class="inline-flex items-center gap-1 {{ $row['variationPercent'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    <x-dashboard.icon :name="$row['variationPercent'] >= 0 ? 'arrow-up-right' : 'arrow-down-right'" class="h-3 w-3" />
                                    {{ number_format(abs($row['variationPercent']), 1) }}%
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ number_format($row['margin'], 0) }}%</td>
                    </tr>
                @endforeach
            </x-dashboard.data-table>
        </div>

        <div>
            <h3 class="mb-3 font-display text-sm font-semibold text-navy-950">Participação no lucro</h3>
            <div class="rounded-card border border-slate-200 bg-white p-5 shadow-card">
                <canvas id="profit-share-chart"></canvas>
            </div>
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
                        { label: 'Receita', data: @json(collect($series)->pluck('revenue')), backgroundColor: '#06b6d4', borderRadius: 4 },
                        { label: 'Custo', data: @json(collect($series)->pluck('cost')), backgroundColor: '#94a3b8', borderRadius: 4 },
                        { label: 'Lucro', data: @json(collect($series)->pluck('profit')), backgroundColor: '#10b981', borderRadius: 4 },
                    ],
                },
                options: { scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f5f9' } } } },
            });

            new Chart(document.getElementById('profit-share-chart'), {
                type: 'doughnut',
                data: {
                    labels: @json(array_keys($profitShare)),
                    datasets: [{
                        data: @json(array_values($profitShare)),
                        backgroundColor: window.chartPalette,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }],
                },
                options: { cutout: '65%' },
            });
        });
    </script>
    @endpush
</x-app-layout>
