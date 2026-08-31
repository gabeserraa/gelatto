import { useState } from 'react'
import { supabase } from '../../lib/supabaseClient'

export default function AjusteEstoqueModal({ ponto, ajuste, onClose, onSaved }) {
  const isEdit = Boolean(ajuste)
  const pontoNome = ajuste?.pontos?.nome ?? ponto?.nome
  const [quantidade, setQuantidade] = useState(ajuste ? Math.abs(ajuste.quantidade_kg) : '')
  const [motivo, setMotivo] = useState(ajuste?.motivo ?? '')
  const [data, setData] = useState(ajuste?.data ?? (() => new Date().toISOString().slice(0, 10)))
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)

  async function handleSubmit(e) {
    e.preventDefault()
    setSaving(true)
    setError(null)

    const payload = {
      quantidade_kg: -Math.abs(Number(quantidade)),
      motivo: motivo || null,
      data,
    }

    const { error } = isEdit
      ? await supabase.from('ajustes_estoque').update(payload).eq('id', ajuste.id)
      : await supabase.from('ajustes_estoque').insert({ ...payload, ponto_id: ponto.id })

    setSaving(false)
    if (error) {
      setError('Não foi possível salvar. Tente novamente.')
      return
    }
    onSaved?.()
    onClose()
  }

  return (
    <div className="fixed inset-0 z-30 flex items-center justify-center bg-navy-950/40 px-4">
      <div className="w-full max-w-md rounded-card border border-slate-200 bg-white p-6 shadow-card">
        <h2 className="font-display text-base font-semibold text-navy-950">
          {isEdit ? 'Editar Ajuste' : 'Ajustar Estoque'}
        </h2>
        <p className="mt-1 text-xs text-slate-400">
          {pontoNome} — use quando vender mais rápido que o previsto e precisar tirar do estoque na hora.
        </p>

        <form onSubmit={handleSubmit} className="mt-4 space-y-4">
          <div>
            <label className="mb-1 block text-xs font-medium text-slate-500">Quantidade a remover (kg)</label>
            <input
              type="number"
              min="0.1"
              step="0.1"
              required
              value={quantidade}
              onChange={(e) => setQuantidade(e.target.value)}
              className="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
            />
          </div>

          <div>
            <label className="mb-1 block text-xs font-medium text-slate-500">Data</label>
            <input
              type="date"
              required
              value={data}
              onChange={(e) => setData(e.target.value)}
              className="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
            />
          </div>

          <div>
            <label className="mb-1 block text-xs font-medium text-slate-500">Motivo (opcional)</label>
            <input
              type="text"
              placeholder="Ex: vendeu mais rápido que o esperado"
              value={motivo}
              onChange={(e) => setMotivo(e.target.value)}
              className="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
            />
          </div>

          {error && <p className="text-sm text-red-600">{error}</p>}

          <div className="flex justify-end gap-2 pt-2">
            <button
              type="button"
              onClick={onClose}
              className="rounded-[10px] px-4 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100"
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={saving}
              className="rounded-[10px] bg-navy-950 px-4 py-2 text-sm font-semibold text-white hover:bg-navy-800 disabled:opacity-60"
            >
              {saving ? 'Salvando...' : 'Salvar'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
