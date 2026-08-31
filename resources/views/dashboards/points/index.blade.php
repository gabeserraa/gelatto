<x-app-layout>
    <x-slot name="header">Pontos de Freezer</x-slot>

    <div
        x-data="{
            search: '',
            urgency: 'todos',
            view: 'grid',
            matches(el) {
                const okUrgency = this.urgency === 'todos' || el.dataset.urgency === this.urgency;
                const okSearch = this.search === '' || el.dataset.search.includes(this.search.toLowerCase());
                return okUrgency && okSearch;
            },
        }"
    >
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-[220px] flex-1">
                <x-dashboard.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    type="text"
                    x-model="search"
                    placeholder="Buscar ponto ou endereço..."
                    class="w-full rounded-[10px] border-slate-300 pl-9 text-sm focus:border-cyan-500 focus:ring-cyan-500"
                >
            </div>

            <div class="flex items-center gap-1 rounded-full bg-slate-100 p-1 text-[13px] font-medium">
                <button type="button" @click="urgency = 'todos'" :class="urgency === 'todos' ? 'bg-white text-navy-950 shadow-card' : 'text-slate-500'" class="rounded-full px-3 py-1.5">Todos</button>
                <button type="button" @click="urgency = 'ok'" :class="urgency === 'ok' ? 'bg-white text-navy-950 shadow-card' : 'text-slate-500'" class="rounded-full px-3 py-1.5">OK</button>
                <button type="button" @click="urgency = 'repor_em_breve'" :class="urgency === 'repor_em_breve' ? 'bg-white text-navy-950 shadow-card' : 'text-slate-500'" class="rounded-full px-3 py-1.5">Repor em breve</button>
                <button type="button" @click="urgency = 'critico'" :class="urgency === 'critico' ? 'bg-white text-navy-950 shadow-card' : 'text-slate-500'" class="rounded-full px-3 py-1.5">Crítico</button>
            </div>

            <form method="GET">
                <select name="region" class="rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500" onchange="this.form.submit()">
                    <option value="">Todas as regiões</option>
                    @foreach ($regions as $regionOption)
                        <option value="{{ $regionOption }}" @selected($region === $regionOption)>{{ $regionOption }}</option>
                    @endforeach
                </select>
            </form>

            <div class="flex items-center gap-0.5 rounded-[10px] border border-slate-200 p-1">
                <button type="button" @click="view = 'grid'" :class="view === 'grid' ? 'bg-slate-100 text-navy-950' : 'text-slate-400'" class="rounded-[7px] p-1.5">
                    <x-dashboard.icon name="grid" class="h-4 w-4" />
                </button>
                <button type="button" @click="view = 'list'" :class="view === 'list' ? 'bg-slate-100 text-navy-950' : 'text-slate-400'" class="rounded-[7px] p-1.5">
                    <x-dashboard.icon name="list" class="h-4 w-4" />
                </button>
            </div>

            <button
                onclick="Livewire.dispatch('open-point-form')"
                class="ml-auto flex items-center gap-1.5 rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800"
            >
                <x-dashboard.icon name="plus" class="h-4 w-4" />
                Novo Ponto
            </button>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3" x-show="view === 'grid'">
            @foreach ($rows as $row)
                @php $point = $row['point']; @endphp
                <div
                    x-show="matches($el)"
                    data-urgency="{{ $row['urgency'] }}"
                    data-search="{{ Illuminate\Support\Str::lower($point->name.' '.$point->address) }}"
                    class="rounded-card border border-slate-200 bg-white p-5 shadow-card"
                >
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-display text-base font-semibold text-navy-950">{{ $point->name }}</h3>
                        <x-dashboard.urgency-badge :urgency="$row['urgency']" />
                    </div>

                    <div class="mt-1 flex items-center justify-between gap-2">
                        <p class="truncate text-xs text-slate-500">{{ $point->address }}{{ $point->region ? ' — '.$point->region : '' }}</p>
                        <div class="flex shrink-0 items-center gap-2">
                            <button onclick="Livewire.dispatch('open-point-form', { pointId: {{ $point->id }} })" class="text-slate-400 hover:text-navy-950" title="Editar ponto">
                                <x-dashboard.icon name="pencil" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Capacidade</p>
                            <p class="mt-0.5 text-sm font-semibold text-navy-950">{{ number_format($point->capacity_kg, 0) }} kg</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Consumo médio</p>
                            <p class="mt-0.5 text-sm font-semibold text-navy-950">{{ number_format($row['dailyAverage'], 0) }} kg/dia</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Último abast.</p>
                            <p class="mt-0.5 text-sm text-slate-700">{{ $row['lastRestockAt']?->format('d/m/Y') ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Previsão esgot.</p>
                            <p class="mt-0.5 text-sm text-slate-700">
                                {{ $row['daysUntilStockout'] !== null ? '~'.number_format($row['daysUntilStockout'], 0).' dias' : '-' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Estoque atual</span>
                            <span class="font-semibold {{ $row['urgency'] === 'critico' ? 'text-red-600' : ($row['urgency'] === 'repor_em_breve' ? 'text-amber-600' : 'text-emerald-600') }}">
                                {{ number_format($row['currentStock'], 0) }} kg ({{ number_format($row['stockPercentage'], 0) }}%)
                            </span>
                        </div>
                        <x-dashboard.progress-bar class="mt-1.5" :percent="$row['stockPercentage']" :urgency="$row['urgency']" />
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6" x-show="view === 'list'" x-cloak>
            <x-dashboard.data-table :headers="['Ponto', 'Região', 'Estoque', '% capacidade', 'Previsão esgot.', 'Situação', '']">
                @foreach ($rows as $row)
                    <tr
                        x-show="matches($el)"
                        data-urgency="{{ $row['urgency'] }}"
                        data-search="{{ Illuminate\Support\Str::lower($row['point']->name.' '.$row['point']->address) }}"
                    >
                        <td class="px-4 py-3 text-sm font-medium text-navy-950">{{ $row['point']->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $row['point']->region ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ number_format($row['currentStock'], 1) }} kg</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ number_format($row['stockPercentage'], 1) }}%</td>
                        <td class="px-4 py-3 text-sm text-slate-700">
                            {{ $row['daysUntilStockout'] !== null ? '~'.number_format($row['daysUntilStockout'], 0).' dias' : '-' }}
                        </td>
                        <td class="px-4 py-3"><x-dashboard.urgency-badge :urgency="$row['urgency']" /></td>
                        <td class="px-4 py-3 text-right">
                            <button onclick="Livewire.dispatch('open-point-form', { pointId: {{ $row['point']->id }} })" class="text-sm text-cyan-600 hover:text-cyan-700">
                                Editar
                            </button>
                        </td>
                    </tr>
                @endforeach
            </x-dashboard.data-table>
        </div>
    </div>

    <livewire:point-form-modal />
    <livewire:movement-form-modal />
    <livewire:point-detail />
</x-app-layout>
