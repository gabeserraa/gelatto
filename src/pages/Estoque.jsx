import { useCallback, useEffect, useMemo, useState } from 'react'
import { supabase } from '../lib/supabaseClient'
import { usePontosEstoque } from '../lib/usePontosEstoque'
import { useRealtimeRefresh } from '../lib/useRealtimeRefresh'
import VendaModal from '../components/dashboard/VendaModal'
import AjusteEstoqueModal from '../components/dashboard/AjusteEstoqueModal'
import StatCard from '../components/dashboard/StatCard'
import UrgencyBadge from '../components/dashboard/UrgencyBadge'
import { IconPencil, IconTrash } from '../components/icons'
import { formatCurrency, formatKg, formatPercent } from '../lib/format'
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

export default function Estoque() {
  const { pontos, loading, refresh } = usePontosEstoque()
  const [margens, setMargens] = useState({})
  const [showModal, setShowModal] = useState(false)
  const [editingVenda, setEditingVenda] = useState(null)
  const [movimentacoes, setMovimentacoes] = useState([])
  const [ajustes, setAjustes] = useState([])
  const [editingAjuste, setEditingAjuste] = useState(null)

  const loadMovimentacoes = useCallback(async () => {
    const { data } = await supabase
      .from('movimentacoes_estoque')
      .select('id, ponto_id, quantidade_kg, preco_venda_kg, custo_kg, data, observacao, pontos(nome)')
      .order('data', { ascending: false })
      .order('created_at', { ascending: false })
      .limit(50)
    setMovimentacoes(data ?? [])
  }, [])

  const loadAjustes = useCallback(async () => {
    const { data } = await supabase
      .from('ajustes_estoque')
      .select('id, ponto_id, quantidade_kg, motivo, data, pontos(nome)')
      .order('data', { ascending: false })
      .order('created_at', { ascending: false })
      .limit(50)
    setAjustes(data ?? [])
  }, [])

  useEffect(() => {
    loadMovimentacoes()
    loadAjustes()
  }, [loadMovimentacoes, loadAjustes])

  async function handleDeleteMovimentacao(m) {
    if (!confirm(`Excluir essa movimentação de ${formatKg(m.quantidade_kg)} em ${m.pontos?.nome ?? '—'}? Essa ação não pode ser desfeita.`)) return
    await supabase.from('movimentacoes_estoque').delete().eq('id', m.id)
    handleMovementSaved()
  }

  async function handleDeleteAjuste(a) {
    if (!confirm(`Excluir esse ajuste de ${formatKg(Math.abs(a.quantidade_kg))} em ${a.pontos?.nome ?? '—'}? Essa ação não pode ser desfeita.`)) return
    await supabase.from('ajustes_estoque').delete().eq('id', a.id)
    handleMovementSaved()
  }

  const handleMovementSaved = useCallback(() => {
    refresh()
    loadMovimentacoes()
    loadAjustes()
  }, [refresh, loadMovimentacoes, loadAjustes])

  useRealtimeRefresh(['movimentacoes_estoque', 'ajustes_estoque'], handleMovementSaved)

  useEffect(() => {
    const since = new Date()
    since.setMonth(since.getMonth() - 1)
    supabase
      .from('v_movimentacoes_margem')
      .select('ponto_id, margem_pct')
      .gte('data', since.toISOString().slice(0, 10))
      .then(({ data }) => {
        const byPonto = {}
        for (const row of data ?? []) {
          byPonto[row.ponto_id] ??= []
          byPonto[row.ponto_id].push(row.margem_pct)
        }
        const avg = {}
        for (const [id, values] of Object.entries(byPonto)) {
          avg[id] = values.reduce((a, b) => a + b, 0) / values.length
        }
        setMargens(avg)
      })
  }, [pontos.length])

  const resumo = useMemo(() => {
    const estoqueTotal = pontos.reduce((s, p) => s + Number(p.estoque_atual_kg || 0), 0)
    const valorTotal = pontos.reduce((s, p) => s + Number(p.estoque_atual_kg || 0) * Number(p.custo_medio_kg || 0), 0)
    return { estoqueTotal, valorTotal }
  }, [pontos])

  function exportCsv() {
    const header = ['Ponto', 'Estoque atual (kg)', 'Custo médio/kg', 'Margem %', 'Valor total', 'Status']
    const rows = pontos.map((p) => [
      p.nome,
      p.estoque_atual_kg,
      p.custo_medio_kg?.toFixed(2) ?? '0',
      (margens[p.id] ?? 0).toFixed(1),
      (p.estoque_atual_kg * (p.custo_medio_kg || 0)).toFixed(2),
      p.status,
    ])
    const csv = [header, ...rows].map((r) => r.join(';')).join('\n')
    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `estoque-gelatto-${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
    URL.revokeObjectURL(url)
  }

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <StatCard label="Estoque total" value={formatKg(resumo.estoqueTotal)} />
        <StatCard label="Valor total em estoque" value={formatCurrency(resumo.valorTotal)} />
        <StatCard label="Pontos monitorados" value={pontos.length} />
      </div>

      <div className="flex justify-end gap-2">
        <button
          onClick={exportCsv}
          className="rounded-[10px] border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-navy-950 hover:bg-slate-50 dark:border-navy-700 dark:bg-navy-900 dark:text-white dark:hover:bg-navy-800"
        >
          Exportar CSV
        </button>
        <button onClick={() => setShowModal(true)} className={primaryButtonClass}>
          Registrar Venda
        </button>
      </div>

      <div className={tableCardClass}>
        <table className="w-full text-left text-sm">
          <thead className={theadClass}>
            <tr>
              {['Ponto', 'Estoque atual', 'Custo/kg', 'Margem %', 'Valor total', 'Status'].map((h) => (
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
            {!loading &&
              pontos.map((p) => (
                <tr key={p.id}>
                  <td className="px-4 py-3 font-medium text-navy-950 dark:text-white">{p.nome}</td>
                  <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{formatKg(p.estoque_atual_kg)}</td>
                  <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{formatCurrency(p.custo_medio_kg)}</td>
                  <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{formatPercent(margens[p.id] ?? 0)}</td>
                  <td className="px-4 py-3 text-slate-600 dark:text-slate-300">
                    {formatCurrency(p.estoque_atual_kg * (p.custo_medio_kg || 0))}
                  </td>
                  <td className="px-4 py-3">
                    <UrgencyBadge status={p.status} />
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>

      <div className={tableCardClass}>
        <div className={tableHeaderRowClass}>
          <h3 className="font-display text-sm font-semibold text-navy-950 dark:text-white">Movimentações Recentes</h3>
        </div>
        <table className="w-full text-left text-sm">
          <thead className={theadClass}>
            <tr>
              {['Data', 'Ponto', 'Quantidade', 'Preço/kg', 'Custo/kg', 'Lucro', ''].map((h) => (
                <th key={h} className={thClass}>
                  {h}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className={tbodyClass}>
            {movimentacoes.length === 0 && (
              <tr>
                <td colSpan={7} className="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
                  Nenhuma venda registrada ainda.
                </td>
              </tr>
            )}
            {movimentacoes.map((m) => (
              <tr key={m.id}>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{m.data}</td>
                <td className="px-4 py-3 font-medium text-navy-950 dark:text-white">{m.pontos?.nome ?? '—'}</td>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{formatKg(m.quantidade_kg)}</td>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{formatCurrency(m.preco_venda_kg)}</td>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{formatCurrency(m.custo_kg)}</td>
                <td className="px-4 py-3 font-medium text-emerald-700 dark:text-emerald-400">
                  {formatCurrency(m.quantidade_kg * (m.preco_venda_kg - m.custo_kg))}
                </td>
                <td className="px-4 py-3 text-right">
                  <div className="flex justify-end gap-1">
                    <button onClick={() => setEditingVenda(m)} className={editButtonClass} aria-label="Editar movimentação">
                      <IconPencil className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => handleDeleteMovimentacao(m)}
                      className={deleteButtonClass}
                      aria-label="Excluir movimentação"
                    >
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
          <h3 className="font-display text-sm font-semibold text-navy-950 dark:text-white">Ajustes Recentes</h3>
        </div>
        <table className="w-full text-left text-sm">
          <thead className={theadClass}>
            <tr>
              {['Data', 'Ponto', 'Quantidade', 'Motivo', ''].map((h) => (
                <th key={h} className={thClass}>
                  {h}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className={tbodyClass}>
            {ajustes.length === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
                  Nenhum ajuste manual registrado ainda.
                </td>
              </tr>
            )}
            {ajustes.map((a) => (
              <tr key={a.id}>
                <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{a.data}</td>
                <td className="px-4 py-3 font-medium text-navy-950 dark:text-white">{a.pontos?.nome ?? '—'}</td>
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

      {showModal && (
        <VendaModal pontos={pontos} onClose={() => setShowModal(false)} onSaved={handleMovementSaved} />
      )}

      {editingVenda && (
        <VendaModal
          pontos={pontos}
          venda={editingVenda}
          onClose={() => setEditingVenda(null)}
          onSaved={handleMovementSaved}
        />
      )}

      {editingAjuste && (
        <AjusteEstoqueModal
          ajuste={editingAjuste}
          onClose={() => setEditingAjuste(null)}
          onSaved={handleMovementSaved}
        />
      )}
    </div>
  )
}
