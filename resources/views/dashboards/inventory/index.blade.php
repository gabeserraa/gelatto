<x-app-layout>
    <x-slot name="header">Controle de Estoque</x-slot>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-dashboard.stat-card icon="droplet" iconClass="bg-cyan-500/10 text-cyan-600" label="Estoque total" value="{{ number_format($totals['stockTotal'], 0) }} kg" />
        <x-dashboard.stat-card icon="trending-up" iconClass="bg-emerald-500/10 text-emerald-600" label="Valor em estoque" value="R$ {{ number_format($totals['stockValueTotal'], 2, ',', '.') }}" />
        <x-dashboard.stat-card icon="cube" iconClass="bg-amber-500/10 text-amber-600" label="Giro médio" value="{{ number_format($totals['turnoverRate'], 0) }}%" />
    </div>

    <div class="mt-6 flex flex-wrap items-end gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-500">Status</label>
                <select name="status" class="mt-1 rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="ativo" @selected($status === 'ativo')>Ativo</option>
                    <option value="inativo" @selected($status === 'inativo')>Inativo</option>
                    <option value="manutencao" @selected($status === 'manutencao')>Em manutenção</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500">Região</label>
                <select name="region" class="mt-1 rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500" onchange="this.form.submit()">
                    <option value="">Todas as regiões</option>
                    @foreach ($regions as $regionOption)
                        <option value="{{ $regionOption }}" @selected($region === $regionOption)>{{ $regionOption }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500">Mês</label>
                <input type="number" name="month" min="1" max="12" value="{{ $month }}" class="mt-1 w-20 rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500">Ano</label>
                <input type="number" name="year" value="{{ $year }}" class="mt-1 w-24 rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
            </div>
            <button type="submit" class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800">Filtrar</button>
        </form>

        <div class="ml-auto flex items-center gap-2">
            <a href="{{ route('dashboards.inventory.export', request()->query()) }}" class="flex items-center gap-1.5 rounded-[10px] border border-slate-300 px-4 py-2 text-[13px] font-semibold text-slate-700 hover:bg-slate-50">
                <x-dashboard.icon name="document" class="h-4 w-4" />
                Exportar CSV
            </a>
            <button
                onclick="Livewire.dispatch('open-movement-form')"
                class="flex items-center gap-1.5 rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800"
            >
                <x-dashboard.icon name="plus" class="h-4 w-4" />
                Registrar Movimentação
            </button>
        </div>
    </div>

    <div class="mt-6">
        <x-dashboard.data-table :headers="['Ponto', 'Região', 'Entrada', 'Saída', 'Estoque atual', 'Custo unit.', 'Valor total', 'Margem %', 'Situação', '']" :paginator="$rows">
            @forelse ($rows as $row)
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-navy-950">{{ $row['point']->name }}</td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ $row['point']->region ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format($row['entrada'], 1) }} kg</td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format($row['saida'], 1) }} kg</td>
                    <td class="px-4 py-3 text-sm font-semibold {{ $row['urgency'] === 'critico' ? 'text-red-600' : ($row['urgency'] === 'repor_em_breve' ? 'text-amber-600' : 'text-emerald-600') }}">
                        {{ number_format($row['currentStock'], 1) }} kg
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700">R$ {{ number_format($row['costPerKg'], 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-navy-950">R$ {{ number_format($row['stockValue'], 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm font-medium {{ $row['margin'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ number_format($row['margin'], 0) }}%</td>
                    <td class="px-4 py-3"><x-dashboard.urgency-badge :urgency="$row['urgency']" /></td>
                    <td class="px-4 py-3 text-right">
                        <button
                            onclick="Livewire.dispatch('open-movement-form', { pointId: {{ $row['point']->id }} })"
                            class="text-sm text-cyan-600 hover:text-cyan-700"
                        >
                            Lançar movimentação
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-4 py-6 text-center text-sm text-slate-500">Nenhum ponto encontrado.</td>
                </tr>
            @endforelse
        </x-dashboard.data-table>
    </div>

    <livewire:movement-form-modal />
</x-app-layout>
