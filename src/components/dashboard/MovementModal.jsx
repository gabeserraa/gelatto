import { useState } from 'react'
import { supabase } from '../../lib/supabaseClient'
import { inputClass, labelClass, modalOverlayClass, modalShellClass, primaryButtonClass, secondaryButtonClass } from '../../lib/ui'
import { enqueueOffline } from '../../lib/offlineQueue'
import { useOnlineStatus } from '../../lib/useOnlineStatus'

export default function MovementModal({ movimentacao, onClose, onSaved }) {
  const isEdit = Boolean(movimentacao)
  const [tipo, setTipo] = useState(movimentacao?.tipo ?? 'entrada')
  const [quantidade, setQuantidade] = useState(movimentacao?.quantidade_kg ?? '')
  const [valorUnitario, setValorUnitario] = useState(movimentacao?.valor_unitario ?? '')
  const [data, setData] = useState(movimentacao?.data ?? (() => new Date().toISOString().slice(0, 10)))
  const [observacao, setObservacao] = useState(movimentacao?.observacao ?? '')
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)
  const online = useOnlineStatus()

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

    if (!navigator.onLine) {
      enqueueOffline({
        table: 'movimentacoes_fabrica',
        operation: isEdit ? 'update' : 'insert',
        rowId: isEdit ? movimentacao.id : null,
        payload,
      })
      setSaving(false)
      onSaved?.()
      onClose()
      return
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
    <div className={modalOverlayClass}>
      <div className={modalShellClass}>
        <h2 className="font-display text-base font-semibold text-navy-950 dark:text-white">
          {isEdit ? 'Editar Movimentação' : 'Registrar Movimentação'}
        </h2>

        <form onSubmit={handleSubmit} className="mt-4 space-y-4">
          {!online && (
            <p className="rounded-[10px] bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600 dark:bg-navy-800 dark:text-slate-300">
              📴 Sem conexão — vai salvar localmente e enviar sozinho quando a internet voltar.
            </p>
          )}

          <div className="grid grid-cols-2 gap-2">
            <button
              type="button"
              onClick={() => setTipo('entrada')}
              className={`rounded-[10px] px-3 py-2 text-sm font-semibold transition-colors ${
                tipo === 'entrada'
                  ? 'bg-navy-950 text-white dark:bg-cyan-600'
                  : 'bg-slate-100 text-slate-500 dark:bg-navy-800 dark:text-slate-400'
              }`}
            >
              Entrada
            </button>
            <button
              type="button"
              onClick={() => setTipo('saida')}
              className={`rounded-[10px] px-3 py-2 text-sm font-semibold transition-colors ${
                tipo === 'saida'
                  ? 'bg-navy-950 text-white dark:bg-cyan-600'
                  : 'bg-slate-100 text-slate-500 dark:bg-navy-800 dark:text-slate-400'
              }`}
            >
              Saída
            </button>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className={labelClass}>Quantidade (kg)</label>
              <input
                type="number"
                min="0.1"
                step="0.1"
                required
                value={quantidade}
                onChange={(e) => setQuantidade(e.target.value)}
                className={inputClass}
              />
            </div>
            <div>
              <label className={labelClass}>Custo/kg (R$)</label>
              <input
                type="number"
                min="0"
                step="0.01"
                required
                value={valorUnitario}
                onChange={(e) => setValorUnitario(e.target.value)}
                className={inputClass}
              />
            </div>
          </div>

          <div>
            <label className={labelClass}>Data</label>
            <input type="date" required value={data} onChange={(e) => setData(e.target.value)} className={inputClass} />
          </div>

          <div>
            <label className={labelClass}>Observação (opcional)</label>
            <input
              type="text"
              value={observacao}
              onChange={(e) => setObservacao(e.target.value)}
              className={inputClass}
            />
          </div>

          {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}

          <div className="flex justify-end gap-2 pt-2">
            <button type="button" onClick={onClose} className={secondaryButtonClass}>
              Cancelar
            </button>
            <button type="submit" disabled={saving} className={primaryButtonClass}>
              {saving ? 'Salvando...' : 'Salvar'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
