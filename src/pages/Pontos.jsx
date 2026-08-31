import { lazy, Suspense, useMemo, useState } from 'react'
import { supabase } from '../lib/supabaseClient'
import { usePontosEstoque } from '../lib/usePontosEstoque'
import UrgencyBadge, { urgencyFromRatio } from '../components/dashboard/UrgencyBadge'
import ProgressBar from '../components/dashboard/ProgressBar'
import VendaModal from '../components/dashboard/VendaModal'
import PontoFormModal from '../components/dashboard/PontoFormModal'
import AjusteEstoqueModal from '../components/dashboard/AjusteEstoqueModal'
import { IconMinusCircle, IconPencil, IconPlus, IconSearch, IconTrash } from '../components/icons'

const PontosMap = lazy(() => import('../components/dashboard/PontosMap'))
import { formatKg } from '../lib/format'

const TIPO_LABELS = { balada: 'Balada', mercado: 'Mercado', evento: 'Evento', bar: 'Bar' }

export default function Pontos() {
  const { pontos, loading, refresh } = usePontosEstoque()
  const [view, setView] = useState('grid')
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('todos')
  const [regiaoFilter, setRegiaoFilter] = useState('todas')
  const [movementFor, setMovementFor] = useState(null)
  const [showNewPonto, setShowNewPonto] = useState(false)
  const [editingPonto, setEditingPonto] = useState(null)
  const [adjustingPonto, setAdjustingPonto] = useState(null)

  const regioes = useMemo(() => [...new Set(pontos.map((p) => p.regiao))].sort(), [pontos])

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase()
    return pontos.filter((p) => {
      if (statusFilter !== 'todos' && p.status !== statusFilter) return false
      if (regiaoFilter !== 'todas' && p.regiao !== regiaoFilter) return false
      if (q && !p.nome.toLowerCase().includes(q) && !p.endereco.toLowerCase().includes(q)) return false
      return true
    })
  }, [pontos, search, statusFilter, regiaoFilter])

  async function handleDelete(p) {
    if (!confirm(`Excluir "${p.nome}"? Isso também apaga todo o histórico de movimentações desse ponto. Essa ação não pode ser desfeita.`)) return
    await supabase.from('pontos').delete().eq('id', p.id)
    refresh()
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center gap-3">
        <div className="relative flex-1 min-w-[220px]">
          <IconSearch className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Buscar por nome ou endereço..."
            className="w-full rounded-[10px] border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500 dark:border-navy-700 dark:bg-navy-900 dark:text-white dark:placeholder-slate-500"
          />
        </div>

        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          className="rounded-[10px] border border-slate-200 bg-white px-3 py-2 text-sm text-navy-950 dark:border-navy-700 dark:bg-navy-900 dark:text-white"
        >
          <option value="todos">Todos os status</option>
          <option value="ativo">Ativo</option>
          <option value="inativo">Inativo</option>
          <option value="manutencao">Manutenção</option>
        </select>

        <select
          value={regiaoFilter}
          onChange={(e) => setRegiaoFilter(e.target.value)}
          className="rounded-[10px] border border-slate-200 bg-white px-3 py-2 text-sm text-navy-950 dark:border-navy-700 dark:bg-navy-900 dark:text-white"
        >
          <option value="todas">Todas as regiões</option>
          {regioes.map((r) => (
            <option key={r} value={r}>
              {r}
            </option>
          ))}
        </select>

        <div className="flex rounded-[10px] border border-slate-200 bg-white p-1 dark:border-navy-700 dark:bg-navy-900">
          <button
            onClick={() => setView('grid')}
            className={`rounded-[7px] px-3 py-1.5 text-xs font-semibold ${view === 'grid' ? 'bg-navy-950 text-white dark:bg-cyan-600' : 'text-slate-500 dark:text-slate-400'}`}
          >
            Lista
          </button>
          <button
            onClick={() => setView('map')}
            className={`rounded-[7px] px-3 py-1.5 text-xs font-semibold ${view === 'map' ? 'bg-navy-950 text-white dark:bg-cyan-600' : 'text-slate-500 dark:text-slate-400'}`}
          >
            Mapa
          </button>
        </div>

        <button
          onClick={() => setShowNewPonto(true)}
          className="flex items-center gap-1.5 rounded-[10px] bg-navy-950 px-4 py-2 text-sm font-semibold text-white hover:bg-navy-800 dark:bg-cyan-600 dark:hover:bg-cyan-500"
        >
          <IconPlus className="h-4 w-4" />
          Novo Ponto
        </button>
      </div>

      {loading && <p className="text-sm text-slate-400 dark:text-slate-500">Carregando pontos...</p>}

      {!loading && view === 'map' && (
        <Suspense fallback={<p className="text-sm text-slate-400 dark:text-slate-500">Carregando mapa...</p>}>
          <PontosMap pontos={filtered} />
        </Suspense>
      )}

      {!loading && view === 'grid' && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {filtered.map((p) => {
            const ratio = p.estoque_atual_kg / p.capacidade_kg
            const urgency = urgencyFromRatio(p.estoque_atual_kg, p.consumo_medio_dia)
            return (
              <div key={p.id} className="flex flex-col rounded-card border border-slate-200 bg-white p-5 shadow-card dark:border-navy-700 dark:bg-navy-900">
                <div className="flex items-start justify-between gap-2">
                  <div className="min-w-0">
                    <p className="truncate font-display text-sm font-semibold text-navy-950 dark:text-white">{p.nome}</p>
                    <p className="mt-0.5 truncate text-xs text-slate-400 dark:text-slate-500">{p.endereco}</p>
                  </div>
                  <div className="flex shrink-0 items-center gap-2">
                    <UrgencyBadge status={p.status} />
                    <button
                      onClick={() => setEditingPonto(p)}
                      className="flex h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-navy-950 dark:text-slate-500 dark:hover:bg-navy-800 dark:hover:text-white"
                      aria-label={`Editar ${p.nome}`}
                    >
                      <IconPencil className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => handleDelete(p)}
                      className="flex h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600 dark:text-slate-500 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                      aria-label={`Excluir ${p.nome}`}
                    >
                      <IconTrash className="h-4 w-4" />
                    </button>
                  </div>
                </div>

                <div className="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                  <span>{TIPO_LABELS[p.tipo] ?? p.tipo}</span>
                  <span>{p.regiao}</span>
                  <span>Consumo médio: {formatKg(p.consumo_medio_dia)}/dia</span>
                </div>

                <div className="mt-4">
                  <div className="flex items-center justify-between text-xs">
                    <span className="font-medium text-navy-950 dark:text-white">{formatKg(p.estoque_atual_kg)}</span>
                    <span className="text-slate-400 dark:text-slate-500">de {formatKg(p.capacidade_kg)}</span>
                  </div>
                  <div className="mt-1.5">
                    <ProgressBar ratio={ratio} />
                  </div>
                </div>

                <div className="mt-3 flex items-center justify-between text-xs">
                  <UrgencyBadge status={urgency} />
                  <span className="text-slate-400 dark:text-slate-500">
                    {p.previsao_esgotamento_dias != null
                      ? `esgota em ~${p.previsao_esgotamento_dias}d`
                      : 'sem previsão'}
                  </span>
                </div>

                <div className="mt-4 flex gap-2">
                  <button
                    onClick={() => setMovementFor(p.id)}
                    className="flex-1 rounded-[10px] bg-navy-950 px-3 py-2 text-xs font-semibold text-white hover:bg-navy-800 dark:bg-cyan-600 dark:hover:bg-cyan-500"
                  >
                    Registrar Venda
                  </button>
                  <button
                    onClick={() => setAdjustingPonto(p)}
                    className="flex items-center justify-center gap-1.5 rounded-[10px] border border-slate-200 px-3 py-2 text-xs font-semibold text-navy-950 hover:bg-slate-50 dark:border-navy-700 dark:text-white dark:hover:bg-navy-800"
                    aria-label={`Ajustar estoque de ${p.nome}`}
                  >
                    <IconMinusCircle className="h-4 w-4" />
                    Ajustar
                  </button>
                </div>
              </div>
            )
          })}
        </div>
      )}

      {movementFor && (
        <VendaModal
          pontos={pontos}
          defaultPontoId={movementFor}
          onClose={() => setMovementFor(null)}
          onSaved={refresh}
        />
      )}

      {showNewPonto && <PontoFormModal onClose={() => setShowNewPonto(false)} onSaved={refresh} />}

      {editingPonto && (
        <PontoFormModal ponto={editingPonto} onClose={() => setEditingPonto(null)} onSaved={refresh} />
      )}

      {adjustingPonto && (
        <AjusteEstoqueModal
          ponto={adjustingPonto}
          onClose={() => setAdjustingPonto(null)}
          onSaved={refresh}
        />
      )}
    </div>
  )
}
