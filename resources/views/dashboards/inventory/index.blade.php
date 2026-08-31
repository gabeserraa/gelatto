<x-app-layout>
    <x-slot name="header">Estoque</x-slot>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-dashboard.kpi-card label="Estoque total" value="{{ number_format($totals['stockTotal'], 1) }} kg" />
        <x-dashboard.kpi-card label="Valor em estoque" value="R$ {{ number_format($totals['stockValueTotal'], 2, ',', '.') }}" />
        <x-dashboard.kpi-card label="Giro médio" value="{{ number_format($totals['turnoverRate'], 1) }}%" />
    </div>

    <form method="GET" class="mt-6 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-sm text-gray-600">Status</label>
            <select name="status" class="mt-1 rounded-md border-gray-300" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="ativo" @selected($status === 'ativo')>Ativo</option>
                <option value="inativo" @selected($status === 'inativo')>Inativo</option>
                <option value="manutencao" @selected($status === 'manutencao')>Em manutenção</option>
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-600">Região</label>
            <select name="region" class="mt-1 rounded-md border-gray-300" onchange="this.form.submit()">
                <option value="">Todas as regiões</option>
                @foreach ($regions as $regionOption)
                    <option value="{{ $regionOption }}" @selected($region === $regionOption)>{{ $regionOption }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-600">Mês</label>
            <input type="number" name="month" min="1" max="12" value="{{ $month }}" class="mt-1 w-20 rounded-md border-gray-300">
        </div>
        <div>
            <label class="block text-sm text-gray-600">Ano</label>
            <input type="number" name="year" value="{{ $year }}" class="mt-1 w-24 rounded-md border-gray-300">
        </div>
        <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Filtrar</button>
        <a href="{{ route('dashboards.inventory.export', request()->query()) }}" class="ml-auto rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
            Exportar CSV
        </a>
    </form>

    <div class="mt-6">
        <x-dashboard.data-table :headers="['Ponto', 'Região', 'Entrada', 'Saída', 'Estoque atual', 'Custo unit.', 'Valor total', 'Margem %', 'Situação', '']" :paginator="$rows">
            @forelse ($rows as $row)
                <tr>
                    <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $row['point']->name }}</td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{ $row['point']->region ?? '-' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($row['entrada'], 1) }} kg</td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($row['saida'], 1) }} kg</td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($row['currentStock'], 1) }} kg</td>
                    <td class="px-4 py-2 text-sm text-gray-700">R$ {{ number_format($row['costPerKg'], 2, ',', '.') }}</td>
                    <td class="px-4 py-2 text-sm font-medium text-gray-900">R$ {{ number_format($row['stockValue'], 2, ',', '.') }}</td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($row['margin'], 1) }}%</td>
                    <td class="px-4 py-2">
                        @if ($row['urgency'] === 'critico')
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Crítico</span>
                        @elseif ($row['urgency'] === 'repor_em_breve')
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Repor em breve</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">OK</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-right">
                        <button
                            onclick="Livewire.dispatch('open-movement-form', { pointId: {{ $row['point']->id }} })"
                            class="text-sm text-blue-600 hover:text-blue-800"
                        >
                            Lançar movimentação
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-4 py-6 text-center text-sm text-gray-500">Nenhum ponto encontrado.</td>
                </tr>
            @endforelse
        </x-dashboard.data-table>
    </div>

    <livewire:movement-form-modal />
</x-app-layout>
