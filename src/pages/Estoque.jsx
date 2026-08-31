import { useEffect, useMemo, useState } from 'react'
import { supabase } from '../lib/supabaseClient'
import { usePontosEstoque } from '../lib/usePontosEstoque'
import MovementModal from '../components/dashboard/MovementModal'
import StatCard from '../components/dashboard/StatCard'
import UrgencyBadge from '../components/dashboard/UrgencyBadge'
import { formatCurrency, formatKg, formatPercent } from '../lib/format'

export default function Estoque() {
  const { pontos, loading, refresh } = usePontosEstoque()
  const [margens, setMargens] = useState({})
  const [showModal, setShowModal] = useState(false)

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
          className="rounded-[10px] border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-navy-950 hover:bg-slate-50"
        >
          Exportar CSV
        </button>
        <button
          onClick={() => setShowModal(true)}
          className="rounded-[10px] bg-navy-950 px-4 py-2 text-sm font-semibold text-white hover:bg-navy-800"
        >
          Registrar Movimentação
        </button>
      </div>

      <div className="overflow-x-auto rounded-card border border-slate-200 bg-white shadow-card">
        <table className="w-full text-left text-sm">
          <thead className="bg-slate-50/60">
            <tr>
              {['Ponto', 'Estoque atual', 'Custo/kg', 'Margem %', 'Valor total', 'Status'].map((h) => (
                <th key={h} className="px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                  {h}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {loading && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-400">
                  Carregando...
                </td>
              </tr>
            )}
            {!loading &&
              pontos.map((p) => (
                <tr key={p.id}>
                  <td className="px-4 py-3 font-medium text-navy-950">{p.nome}</td>
                  <td className="px-4 py-3 text-slate-600">{formatKg(p.estoque_atual_kg)}</td>
                  <td className="px-4 py-3 text-slate-600">{formatCurrency(p.custo_medio_kg)}</td>
                  <td className="px-4 py-3 text-slate-600">{formatPercent(margens[p.id] ?? 0)}</td>
                  <td className="px-4 py-3 text-slate-600">
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

      {showModal && <MovementModal pontos={pontos} onClose={() => setShowModal(false)} onSaved={refresh} />}
    </div>
  )
}
