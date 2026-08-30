<div>
    @if ($showDrawer && $point)
        <div class="fixed inset-0 z-40 flex justify-end bg-black/50">
            <div class="h-full w-full max-w-xl overflow-y-auto bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ $point->name }}</h2>
                        <p class="text-sm text-gray-500">{{ $point->type }} — <x-dashboard.status-badge :status="$point->status" /></p>
                    </div>
                    <button wire:click="$set('showDrawer', false)" class="text-gray-400 hover:text-gray-600">Fechar</button>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-3">
                    <x-dashboard.kpi-card label="Estoque atual" value="{{ number_format($currentStock, 1) }} kg" :hint="number_format($stockPercentage, 1) . '% da capacidade'" />
                    <x-dashboard.kpi-card label="Média mensal" value="{{ number_format($monthlyAverage, 1) }} kg" />
                    <x-dashboard.kpi-card label="Capacidade" value="{{ number_format($point->capacity_kg, 1) }} kg" />
                </div>

                <div class="mt-6 flex gap-2">
                    <button
                        wire:click="$dispatch('open-point-form', { pointId: {{ $point->id }} })"
                        class="rounded-md border px-4 py-2 text-sm text-gray-700"
                    >
                        Editar ponto
                    </button>
                    <button
                        wire:click="$dispatch('open-movement-form', { pointId: {{ $point->id }} })"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
                    >
                        Lançar movimentação
                    </button>
                </div>

                <h3 class="mt-8 text-sm font-medium text-gray-700">Histórico de movimentações</h3>

                <x-dashboard.data-table :headers="['Data', 'Tipo', 'Quantidade (kg)', 'Custo', 'Receita']" :paginator="$movements" class="mt-2">
                    @php
                        $movementTypeLabels = [
                            'reposicao' => 'Reposição',
                            'retirada' => 'Retirada',
                            'ajuste' => 'Ajuste',
                        ];
                    @endphp
                    @foreach ($movements as $movement)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $movement->occurred_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">
                                {{ $movementTypeLabels[$movement->type] ?? ucfirst($movement->type) }}
                                @if ($movement->type === 'ajuste')
                                    ({{ $movement->adjustment_direction === 'increase' ? 'aumento' : 'redução' }})
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($movement->quantity_kg, 1) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $movement->cost ? 'R$ '.number_format($movement->cost, 2, ',', '.') : '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $movement->revenue ? 'R$ '.number_format($movement->revenue, 2, ',', '.') : '—' }}</td>
                        </tr>
                    @endforeach
                </x-dashboard.data-table>
            </div>
        </div>
    @endif
</div>
