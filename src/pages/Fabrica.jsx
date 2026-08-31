import { useCallback, useEffect, useState } from 'react'
import { supabase } from '../lib/supabaseClient'
import MovementModal from '../components/dashboard/MovementModal'
import StatCard from '../components/dashboard/StatCard'
import { IconTrash } from '../components/icons'
import { formatCurrency, formatKg } from '../lib/format'

export default function Fabrica() {
  const [estoque, setEstoque] = useState(null)
  const [movimentacoes, setMovimentacoes] = useState([])
  const [showModal, setShowModal] = useState(false)

  const loadEstoque = useCallback(async () => {
    const { data } = await supabase.from('v_estoque_fabrica').select('*').single()
    setEstoque(data)
  }, [])

  const loadMovimentacoes = useCallback(async () => {
    const { data } = await supabase
      .from('movimentacoes_fabrica')
      .select('id, tipo, quantidade_kg, valor_unitario, data, observacao')
      .order('data', { ascending: false })
      .order('created_at', { ascending: false })
      .limit(50)
    setMovimentacoes(data ?? [])
  }, [])

  useEffect(() => {
    loadEstoque()
    loadMovimentacoes()
  }, [loadEstoque, loadMovimentacoes])

  function handleSaved() {
    loadEstoque()
    loadMovimentacoes()
  }

  async function handleDelete(m) {
    if (!confirm(`Excluir essa movimentação de ${formatKg(m.quantidade_kg)}? Essa ação não pode ser desfeita.`)) return
    await supabase.from('movimentacoes_fabrica').delete().eq('id', m.id)
    handleSaved()
  }

  function exportCsv() {
    const header = ['Data', 'Tipo', 'Quantidade (kg)', 'Valor/kg', 'Observação']
    const rows = movimentacoes.map((m) => [
      m.data,
      m.tipo === 'entrada' ? 'Entrada' : 'Saída',
      m.quantidade_kg,
      m.valor_unitario.toFixed(2),
      m.observacao ?? '',
    ])
    const csv = [header, ...rows].map((r) => r.join(';')).join('\n')
    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `estoque-fabrica-gelatto-${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
    URL.revokeObjectURL(url)
  }

  const valorTotal = (estoque?.estoque_atual_kg ?? 0) * (estoque?.custo_medio_kg ?? 0)

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <StatCard label="Estoque atual na fábrica" value={formatKg(estoque?.estoque_atual_kg)} />
        <StatCard label="Custo médio por kg" value={formatCurrency(estoque?.custo_medio_kg)} />
        <StatCard label="Valor total em estoque" value={formatCurrency(valorTotal)} />
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
        <div className="border-b border-slate-100 px-5 py-4">
          <h3 className="font-display text-sm font-semibold text-navy-950">Movimentações Recentes</h3>
        </div>
        <table className="w-full text-left text-sm">
          <thead className="bg-slate-50/60">
            <tr>
              {['Data', 'Tipo', 'Quantidade', 'Valor/kg', 'Observação', ''].map((h) => (
                <th key={h} className="px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                  {h}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {movimentacoes.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-400">
                  Nenhuma movimentação registrada ainda.
                </td>
              </tr>
            )}
            {movimentacoes.map((m) => (
              <tr key={m.id}>
                <td className="px-4 py-3 text-slate-600">{m.data}</td>
                <td className="px-4 py-3">
                  <span
                    className={`rounded-full px-2.5 py-[3px] text-[11px] font-semibold ${
                      m.tipo === 'entrada' ? 'bg-emerald-100 text-emerald-700' : 'bg-cyan-100 text-cyan-700'
                    }`}
                  >
                    {m.tipo === 'entrada' ? 'Entrada' : 'Saída'}
                  </span>
                </td>
                <td className="px-4 py-3 text-slate-600">{formatKg(m.quantidade_kg)}</td>
                <td className="px-4 py-3 text-slate-600">{formatCurrency(m.valor_unitario)}</td>
                <td className="px-4 py-3 text-slate-400">{m.observacao ?? '—'}</td>
                <td className="px-4 py-3 text-right">
                  <button
                    onClick={() => handleDelete(m)}
                    className="flex h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600"
                    aria-label="Excluir movimentação"
                  >
                    <IconTrash className="h-4 w-4" />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {showModal && <MovementModal table="movimentacoes_fabrica" onClose={() => setShowModal(false)} onSaved={handleSaved} />}
    </div>
  )
}
