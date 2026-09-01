import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { supabase } from '../lib/supabaseClient'
import { useRealtimeRefresh } from '../lib/useRealtimeRefresh'
import { useTheme } from '../contexts/ThemeContext'
import StatCard from '../components/dashboard/StatCard'
import ChartCard from '../components/dashboard/ChartCard'
import ProgressBar from '../components/dashboard/ProgressBar'
import UrgencyBadge, { urgencyFromRatio } from '../components/dashboard/UrgencyBadge'
import VendaModal from '../components/dashboard/VendaModal'
import AjusteEstoqueModal from '../components/dashboard/AjusteEstoqueModal'
import PontoFormModal from '../components/dashboard/PontoFormModal'
import { IconPencil, IconMinusCircle, IconTrash } from '../components/icons'
import { formatCurrency, formatKg } from '../lib/format'
import {
  deleteButtonClass,
  editButtonClass,
  primaryButtonClass,
  tableCardClass,
  tableHeaderRowClass,
  tbodyClass,
  thClass,
  theadClass,
} from '../lib/ui'

const TIPO_LABELS = { balada: 'Balada', mercado: 'Mercado', evento: 'Evento', bar: 'Bar' }

export default function PontoDetalhe() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { theme } = useTheme()
  const axisColor = theme === 'escuro' ? '#64748b' : '#94a3b8'
  const gridColor = theme === 'escuro' ? '#1c304a' : '#e2e8f0'

  const [ponto, setPonto] = useState(null)
  const [vendas, setVendas] = useState([])
  const [ajustes, setAjustes] = useState([])
  const [notFound, setNotFound] = useState(false)
  const [showVenda, setShowVenda] = useState(false)
  const [showAjuste, setShowAjuste] = useState(false)
  const [showEdit, setShowEdit] = useState(false)
  const [editingVenda, setEditingVenda] = useState(null)
  const [editingAjuste, setEditingAjuste] = useState(null)

  const loadPonto = useCallback(async () => {
    const { data } = await supabase.from('v_pontos_estoque').select('*').eq('id', id).maybeSingle()
    if (!data) {
      setNotFound(true)
      return
    }
    setPonto(data)
  }, [id])

  const loadVendas = useCallback(async () => {
    const { data } = await supabase
      .from('movimentacoes_estoque')
      .select('*')
      .eq('ponto_id', id)
      .order('data', { ascending: false })
      .order('created_at', { ascending: false })
      .limit(100)
    setVendas(data ?? [])
  }, [id])

  const loadAjustes = useCallback(async () => {
    const { data } = await supabase
      .from('ajustes_estoque')
      .select('*')
      .eq('ponto_id', id)
      .order('data', { ascending: false })
      .order('created_at', { ascending: false })
      .limit(100)
    setAjustes(data ?? [])
  }, [id])

  const refreshAll = useCallback(() => {
    loadPonto()
    loadVendas()
    loadAjustes()
  }, [loadPonto, loadVendas, loadAjustes])

  useEffect(() => {
    refreshAll()
  }, [refreshAll])

  useRealtimeRefresh(['pontos', 'movimentacoes_estoque', 'ajustes_estoque'], refreshAll)

  async function handleDeletePonto() {
    if (!confirm(`Excluir "${ponto.nome}"? Isso também apaga todo o histórico de movimentações desse ponto. Essa ação não pode ser desfeita.`)) return
    await supabase.from('pontos').delete().eq('id', id)
    navigate('/pontos')
  }

  async function handleDeleteVenda(v) {
    if (!confirm(`Excluir essa venda de ${formatKg(v.quantidade_kg)}? Essa ação não pode ser desfeita.`)) return
    await supabase.from('movimentacoes_estoque').delete().eq('id', v.id)
    refreshAll()
  }

  async function handleDeleteAjuste(a) {
    if (!confirm(`Excluir esse ajuste de ${formatKg(Math.abs(a.quantidade_kg))}? Essa ação não pode ser desfeita.`)) return
    await supabase.from('ajustes_estoque').delete().eq('id', a.id)
    refreshAll()
  }

  const consumoDiario = useMemo(() => {
    const byDay = {}
    for (const v of vendas) {
      byDay[v.data] ??= 0
      byDay[v.data] += v.quantidade_kg
    }
    return Object.entries(byDay)
      .map(([data, quantidade_kg]) => ({ data, quantidade_kg }))
      .sort((a, b) => a.data.localeCompare(b.data))
      .slice(-30)
  }, [vendas])

  if (notFound) {
    return (
      <div className="space-y-4">
        <p className="text-sm text-slate-400 dark:text-slate-500">Ponto não encontrado.</p>
        <Link to="/pontos" className="text-sm font-medium text-cyan-600 hover:underline dark:text-cyan-400">
          ← Voltar pra Pontos de Freezer
        </Link>
      </div>
    )
  }

  if (!ponto) return <p className="text-sm text-slate-400 dark:text-slate-500">Carregando...</p>

  const ratio = ponto.estoque_atual_kg / ponto.capacidade_kg
  const urgency = urgencyFromRatio(ponto.estoque_atual_kg, ponto.consumo_medio_dia)

  return (
    <div className="space-y-6">
      <div>
        <Link to="/pontos" className="text-sm font-medium text-cyan-600 hover:underline dark:text-cyan-400">
          ← Pontos de Freezer
        </Link>
      </div>

      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="font-display text-xl font-bold text-navy-950 dark:text-white">{ponto.nome}</h1>
            <UrgencyBadge status={ponto.status} />
          </div>
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{ponto.endereco}</p>
          <p className="mt-1 text-xs text-slate-400 dark:text-slate-500">
            {TIPO_LABELS[ponto.tipo] ?? ponto.tipo} · {ponto.regiao} · Consumo médio: {formatKg(ponto.consumo_medio_dia)}/dia
          </p>
        </div>
        <div className="flex gap-2">
          <button
            onClick={() => setShowEdit(true)}
            className="flex items-center gap-1.5 rounded-[10px] border border-slate-200 px-3 py-2 text-sm font-semibold text-navy-950 hover:bg-slate-50 dark:border-navy-700 dark:text-white dark:hover:bg-navy-800"
          >
            <IconPencil className="h-4 w-4" />
            Editar
          </button>
          <button
            onClick={handleDeletePonto}
            className="flex items-center gap-1.5 rounded-[10px] border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:text-red-400 dark:hover:bg-red-500/10"
          >
            <IconTrash className="h-4 w-4" />
            Excluir
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard label="Estoque atual" value={formatKg(ponto.estoque_atual_kg)} hint={`de ${formatKg(ponto.capacidade_kg)}`} />
        <StatCard
          label="Previsão de esgotamento"
          value={ponto.previsao_esgotamento_dias != null ? `~${ponto.previsao_esgotamento_dias}d` : '—'}
        />
        <StatCard label="Custo médio/kg" value={formatCurrency(ponto.custo_medio_kg)} />
        <StatCard
          label="Valor em estoque"
          value={formatCurrency(ponto.estoque_atual_kg * (ponto.custo_medio_kg || 0))}
        />
      </div>

      <div className="rounded-card border border-slate-200 bg-white p-5 shadow-card dark:border-navy-700 dark:bg-navy-900">
        <div className="flex items-center justify-between">
          <UrgencyBadge status={urgency} />
          <span className="text-xs text-slate-400 dark:text-slate-500">
            {ponto.ultimo_movimento ? `última movimentação em ${ponto.ultimo_movimento}` : 'sem movimentações'}
          </span>
        </div>
        <div className="mt-3">
          <ProgressBar ratio={ratio} />
        </div>
        <div className="mt-4 flex gap-2">
          <button onClick={() => setShowVenda(true)} className={primaryButtonClass}>
            Registrar Venda
          </button>
          <button
            onClick={() => setShowAjuste(true)}
            className="flex items-center justify-center gap-1.5 rounded-[10px] border border-slate-200 px-3 py-2 text-sm font-semibold text-navy-950 hover:bg-slate-50 dark:border-navy-700 dark:text-white dark:hover:bg-navy-800"
          >
            <IconMinusCircle className="h-4 w-4" />
            Ajustar Estoque
          </button>
        </div>
      </div>

      {consumoDiario.length > 0 && (
        <ChartCard title="Vendas por dia — últimos registros">
          <div className="h-56">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={consumoDiario}>
                <CartesianGrid strokeDasharray="3 3" stroke={gridColor} vertical={false} />
                <XAxis
                  dataKey="data"
                  tick={{ fontSize: 11, fill: axisColor }}
                  tickFormatter={(v) => v.slice(8, 10) + '/' + v.slice(5, 7)}
                  axisLine={false}
                  tickLine={false}
                />
                <YAxis tick={{ fontSize: 11, fill: axisColor }} axisLine={false} tickLine={false} />
                <Tooltip formatter={(v) => formatKg(v)} />
                <Bar dataKey="quantidade_kg" name="Vendido" fill="#06b6d4" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </ChartCard>
      )}

      <div className={tableCardClass}>
        <div className={tableHeaderRowClass}>
          <h3 className="font-display text-sm font-semibold text-navy-950 dark:text-white">Histórico de Vendas</h3>
        </div>
        <table className="w-full text-left text-sm">
          <thead className={theadClass}>
            <tr>
              {['Data', 'Quantidade', 'Preço/kg', 'Custo/kg', 'Lucro', ''].map((h) => (
                <th key={h} className={thClass}>
                  {h}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className={tbodyClass}>
            {vendas.length === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
                  Nenhuma venda registrada ainda.
                </td>
              </tr>
            )}
            {vendas.map((v) => (
              <tr key={v.id}>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{v.data}</td>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{formatKg(v.quantidade_kg)}</td>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{formatCurrency(v.preco_venda_kg)}</td>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{formatCurrency(v.custo_kg)}</td>
                <td className="px-4 py-3 font-medium text-emerald-700 dark:text-emerald-400">
                  {formatCurrency(v.quantidade_kg * (v.preco_venda_kg - v.custo_kg))}
                </td>
                <td className="px-4 py-3 text-right">
                  <div className="flex justify-end gap-1">
                    <button onClick={() => setEditingVenda(v)} className={editButtonClass} aria-label="Editar venda">
                      <IconPencil className="h-4 w-4" />
                    </button>
                    <button onClick={() => handleDeleteVenda(v)} className={deleteButtonClass} aria-label="Excluir venda">
                      <IconTrash className="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className={tableCardClass}>
        <div className={tableHeaderRowClass}>
          <h3 className="font-display text-sm font-semibold text-navy-950 dark:text-white">Histórico de Ajustes</h3>
        </div>
        <table className="w-full text-left text-sm">
          <thead className={theadClass}>
            <tr>
              {['Data', 'Quantidade', 'Motivo', ''].map((h) => (
                <th key={h} className={thClass}>
                  {h}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className={tbodyClass}>
            {ajustes.length === 0 && (
              <tr>
                <td colSpan={4} className="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
                  Nenhum ajuste manual registrado ainda.
                </td>
              </tr>
            )}
            {ajustes.map((a) => (
              <tr key={a.id}>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{a.data}</td>
                <td className="px-4 py-3 text-red-600 dark:text-red-400">-{formatKg(Math.abs(a.quantidade_kg))}</td>
                <td className="px-4 py-3 text-slate-400 dark:text-slate-500">{a.motivo ?? '—'}</td>
                <td className="px-4 py-3 text-right">
                  <div className="flex justify-end gap-1">
                    <button onClick={() => setEditingAjuste(a)} className={editButtonClass} aria-label="Editar ajuste">
                      <IconPencil className="h-4 w-4" />
                    </button>
                    <button onClick={() => handleDeleteAjuste(a)} className={deleteButtonClass} aria-label="Excluir ajuste">
                      <IconTrash className="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {showVenda && (
        <VendaModal pontos={[ponto]} defaultPontoId={ponto.id} onClose={() => setShowVenda(false)} onSaved={refreshAll} />
      )}

      {showAjuste && <AjusteEstoqueModal ponto={ponto} onClose={() => setShowAjuste(false)} onSaved={refreshAll} />}

      {showEdit && <PontoFormModal ponto={ponto} onClose={() => setShowEdit(false)} onSaved={refreshAll} />}

      {editingVenda && (
        <VendaModal
          pontos={[ponto]}
          venda={editingVenda}
          onClose={() => setEditingVenda(null)}
          onSaved={refreshAll}
        />
      )}

      {editingAjuste && (
        <AjusteEstoqueModal
          ajuste={editingAjuste}
          ponto={ponto}
          onClose={() => setEditingAjuste(null)}
          onSaved={refreshAll}
        />
      )}
    </div>
  )
}
