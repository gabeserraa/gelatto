<x-app-layout>
    <x-slot name="header">Visão Executiva</x-slot>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-dashboard.kpi-card label="Receita do mês" value="R$ {{ number_format($comparison['current']['revenue'], 2, ',', '.') }}" :hint="'Mês anterior: R$ '.number_format($comparison['previous']['revenue'], 2, ',', '.')" />
        <x-dashboard.kpi-card label="Custo do mês" value="R$ {{ number_format($comparison['current']['cost'], 2, ',', '.') }}" :hint="'Mês anterior: R$ '.number_format($comparison['previous']['cost'], 2, ',', '.')" />
        <x-dashboard.kpi-card label="Lucro do mês" value="R$ {{ number_format($comparison['current']['profit'], 2, ',', '.') }}" :hint="'Mês anterior: R$ '.number_format($comparison['previous']['profit'], 2, ',', '.')" />
        <x-dashboard.kpi-card label="Margem" value="{{ number_format($margin, 1) }}%" />
    </div>

    <div class="mt-6">
        <x-dashboard.chart-card title="Evolução mensal (últimos 12 meses)" canvasId="monthly-chart" />
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
                        { label: 'Receita', data: @json(collect($series)->pluck('revenue')), borderColor: '#4f46e5' },
                        { label: 'Custo', data: @json(collect($series)->pluck('cost')), borderColor: '#dc2626' },
                        { label: 'Lucro', data: @json(collect($series)->pluck('profit')), borderColor: '#16a34a' },
                    ],
                },
            });
        });
    </script>
    @endpush
</x-app-layout>
