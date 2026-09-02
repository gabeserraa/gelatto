import { useCallback, useEffect, useState } from 'react'
import { supabase } from '../lib/supabaseClient'
import { useRealtimeRefresh } from '../lib/useRealtimeRefresh'
import DespesaFormModal from '../components/dashboard/DespesaFormModal'
import GoalProgressBar from '../components/dashboard/GoalProgressBar'
import StatCard from '../components/dashboard/StatCard'
import { IconPencil, IconPlus, IconTrash } from '../components/icons'
import { formatCurrency } from '../lib/format'
import {
  deleteButtonClass,
  editButtonClass,
  primaryButtonClass,
  tableCardClass,
  tbodyClass,
  thClass,
  theadClass,
} from '../lib/ui'

const CATEGORIA_LABELS = { equipamento: 'Equipamento', manutencao: 'Manutenção', reforma: 'Reforma', outro: 'Outro' }
const CATEGORIA_COLORS = {
  equipamento: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-400',
  manutencao: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
  reforma: 'bg-purple-100 text-purple-700 dark:bg-purple-500/15 dark:text-purple-400',
  outro: 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-slate-300',
}

export default function Despesas() {
  const [despesas, setDespesas] = useState([])
  const [loading, setLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [editingDespesa, setEditingDespesa] = useState(null)

  const load = useCallback(async () => {
    const { data } = await supabase.from('v_despesas').select('*').order('data', { ascending: false })
    setDespesas(data ?? [])
    setLoading(false)
  }, [])

  useEffect(() => {
    load()
  }, [load])

  useRealtimeRefresh(['despesas'], load)

  async function handleDelete(d) {
    if (!confirm(`Excluir "${d.descricao}"? Essa ação não pode ser desfeita.`)) return
    await supabase.from('despesas').delete().eq('id', d.id)
    load()
  }

  const totalGasto = despesas.reduce((sum, d) => sum + Number(d.valor_total), 0)
  const totalPendente = despesas.reduce((sum, d) => {
    const pago = d.valor_parcela * d.parcelas_pagas
    return sum + Math.max(0, d.valor_total - pago)
  }, 0)
  const parcelasAtivas = despesas.filter((d) => d.parcelado && d.parcelas_pagas < d.numero_parcelas).length

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <StatCard label="Gasto total" value={formatCurrency(totalGasto)} />
        <StatCard label="Pendente a pagar" value={formatCurrency(totalPendente)} />
        <StatCard label="Parcelamentos em aberto" value={parcelasAtivas} />
      </div>

      <div className="flex justify-end">
        <button
          onClick={() => setShowModal(true)}
          className={`flex items-center gap-1.5 ${primaryButtonClass}`}
        >
          <IconPlus className="h-4 w-4" />
          Nova Despesa
        </button>
      </div>

      <div className={tableCardClass}>
        <table className="w-full text-left text-sm">
          <thead className={theadClass}>
            <tr>
              {['Data', 'Descrição', 'Categoria', 'Valor total', 'Pagamento', ''].map((h) => (
                <th key={h} className={thClass}>
                  {h}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className={tbodyClass}>
            {loading && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
                  Carregando...
                </td>
              </tr>
            )}
            {!loading && despesas.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
                  Nenhuma despesa registrada ainda.
                </td>
              </tr>
            )}
            {despesas.map((d) => (
              <tr key={d.id}>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{d.data}</td>
                <td className="px-4 py-3 font-medium text-navy-950 dark:text-white">{d.descricao}</td>
                <td className="px-4 py-3">
                  <span className={`rounded-full px-2.5 py-[3px] text-[11px] font-semibold ${CATEGORIA_COLORS[d.categoria]}`}>
                    {CATEGORIA_LABELS[d.categoria] ?? d.categoria}
                  </span>
                </td>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{formatCurrency(d.valor_total)}</td>
                <td className="px-4 py-3">
                  {d.parcelado ? (
                    <div className="min-w-[140px]">
                      <div className="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>
                          {d.parcelas_pagas}/{d.numero_parcelas}x {formatCurrency(d.valor_parcela)}
                        </span>
                      </div>
                      <div className="mt-1">
                        <GoalProgressBar ratio={d.parcelas_pagas / d.numero_parcelas} />
                      </div>
                    </div>
                  ) : (
                    <span className="text-xs text-slate-400 dark:text-slate-500">À vista</span>
                  )}
                </td>
                <td className="px-4 py-3 text-right">
                  <div className="flex justify-end gap-1">
                    <button onClick={() => setEditingDespesa(d)} className={editButtonClass} aria-label="Editar despesa">
                      <IconPencil className="h-4 w-4" />
                    </button>
                    <button onClick={() => handleDelete(d)} className={deleteButtonClass} aria-label="Excluir despesa">
                      <IconTrash className="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {showModal && <DespesaFormModal onClose={() => setShowModal(false)} onSaved={load} />}

      {editingDespesa && (
        <DespesaFormModal despesa={editingDespesa} onClose={() => setEditingDespesa(null)} onSaved={load} />
      )}
    </div>
  )
}
