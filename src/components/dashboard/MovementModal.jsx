import { useState } from 'react'
import { supabase } from '../../lib/supabaseClient'

export default function MovementModal({ movimentacao, onClose, onSaved }) {
  const isEdit = Boolean(movimentacao)
  const [tipo, setTipo] = useState(movimentacao?.tipo ?? 'entrada')
  const [quantidade, setQuantidade] = useState(movimentacao?.quantidade_kg ?? '')
  const [valorUnitario, setValorUnitario] = useState(movimentacao?.valor_unitario ?? '')
  const [data, setData] = useState(movimentacao?.data ?? (() => new Date().toISOString().slice(0, 10)))
  const [observacao, setObservacao] = useState(movimentacao?.observacao ?? '')
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)

  async function handleSubmit(e) {
    e.preventDefault()
    setSaving(true)
    setError(null)

    const payload = {
      tipo,
      quantidade_kg: Number(quantidade),
      valor_unitario: Number(valorUnitario),
      data,
      observacao: observacao || null,
    }

    const { error } = isEdit
      ? await supabase.from('movimentacoes_fabrica').update(payload).eq('id', movimentacao.id)
      : await supabase.from('movimentacoes_fabrica').insert(payload)

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
          {isEdit ? 'Editar Movimentação' : 'Registrar Movimentação'}
        </h2>

        <form onSubmit={handleSubmit} className="mt-4 space-y-4">
          <div className="grid grid-cols-2 gap-2">
            <button
              type="button"
              onClick={() => setTipo('entrada')}
              className={`rounded-[10px] px-3 py-2 text-sm font-semibold transition-colors ${
                tipo === 'entrada' ? 'bg-navy-950 text-white' : 'bg-slate-100 text-slate-500'
              }`}
            >
              Entrada
            </button>
            <button
              type="button"
              onClick={() => setTipo('saida')}
              className={`rounded-[10px] px-3 py-2 text-sm font-semibold transition-colors ${
                tipo === 'saida' ? 'bg-navy-950 text-white' : 'bg-slate-100 text-slate-500'
              }`}
            >
              Saída
            </button>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="mb-1 block text-xs font-medium text-slate-500">Quantidade (kg)</label>
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
              <label className="mb-1 block text-xs font-medium text-slate-500">Custo/kg (R$)</label>
              <input
                type="number"
                min="0"
                step="0.01"
                required
                value={valorUnitario}
                onChange={(e) => setValorUnitario(e.target.value)}
                className="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
              />
            </div>
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
            <label className="mb-1 block text-xs font-medium text-slate-500">Observação (opcional)</label>
            <input
              type="text"
              value={observacao}
              onChange={(e) => setObservacao(e.target.value)}
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
