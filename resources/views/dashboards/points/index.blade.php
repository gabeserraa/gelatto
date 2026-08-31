<x-app-layout>
    <x-slot name="header">Pontos de Freezer</x-slot>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <x-dashboard.kpi-card label="Pontos ativos" value="{{ $summary['active_points'] }}" />
        <x-dashboard.kpi-card label="Estoque total" value="{{ number_format($summary['total_stock'], 1) }} kg" />
        <x-dashboard.kpi-card label="Receita do mês" value="R$ {{ number_format($summary['revenue'], 2, ',', '.') }}" />
        <x-dashboard.kpi-card label="Custo do mês" value="R$ {{ number_format($summary['cost'], 2, ',', '.') }}" />
        <x-dashboard.kpi-card label="Lucro do mês" value="R$ {{ number_format($summary['profit'], 2, ',', '.') }}" />
    </div>

    <form method="GET" class="mt-6 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-sm text-slate-600">Status</label>
            <select name="status" class="mt-1 rounded-[10px] border-slate-300" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="ativo" @selected($status === 'ativo')>Ativo</option>
                <option value="inativo" @selected($status === 'inativo')>Inativo</option>
                <option value="manutencao" @selected($status === 'manutencao')>Em manutenção</option>
            </select>
        </div>
        <div>
            <label class="block text-sm text-slate-600">Região</label>
            <select name="region" class="mt-1 rounded-[10px] border-slate-300" onchange="this.form.submit()">
                <option value="">Todas as regiões</option>
                @foreach ($regions as $regionOption)
                    <option value="{{ $regionOption }}" @selected($region === $regionOption)>{{ $regionOption }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm text-slate-600">Mês</label>
            <input type="number" name="month" min="1" max="12" value="{{ $month }}" class="mt-1 w-20 rounded-[10px] border-slate-300">
        </div>
        <div>
            <label class="block text-sm text-slate-600">Ano</label>
            <input type="number" name="year" value="{{ $year }}" class="mt-1 w-24 rounded-[10px] border-slate-300">
        </div>
        <button type="submit" class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800">Filtrar</button>
    </form>

    <div class="mt-6">
        <x-dashboard.data-table :headers="['Ponto', 'Região', 'Status', 'Estoque', '% capacidade', 'Previsão esgot.', 'Lucro do mês', 'Situação', '']">
            @foreach ($rows as $row)
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-navy-950">{{ $row['point']->name }}</td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ $row['point']->region ?? '-' }}</td>
                    <td class="px-4 py-3"><x-dashboard.status-badge :status="$row['point']->status" /></td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format($row['currentStock'], 1) }} kg</td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format($row['stockPercentage'], 1) }}%</td>
                    <td class="px-4 py-3 text-sm text-slate-700">
                        {{ $row['daysUntilStockout'] !== null ? '~'.number_format($row['daysUntilStockout'], 0).' dias' : '-' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700">R$ {{ number_format($row['profit'], 2, ',', '.') }}</td>
                    <td class="px-4 py-3">
                        @if ($row['urgency'] === 'critico')
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-[3px] text-[11px] font-semibold text-red-700">Crítico</span>
                        @elseif ($row['urgency'] === 'repor_em_breve')
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-[3px] text-[11px] font-semibold text-amber-700">Repor em breve</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-[3px] text-[11px] font-semibold text-emerald-700">OK</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button
                            onclick="Livewire.dispatch('open-point-detail', { pointId: {{ $row['point']->id }} })"
                            class="text-sm text-cyan-600 hover:text-cyan-700"
                        >
                            Ver detalhes
                        </button>
                    </td>
                </tr>
            @endforeach
        </x-dashboard.data-table>
    </div>

    <div class="mt-4">
        <button
            onclick="Livewire.dispatch('open-point-form')"
            class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800"
        >
            Novo ponto
        </button>
    </div>

    <livewire:point-form-modal />
    <livewire:movement-form-modal />
    <livewire:point-detail />
</x-app-layout>
