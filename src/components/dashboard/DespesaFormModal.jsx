import { useState } from 'react'
import { supabase } from '../../lib/supabaseClient'
import { inputClass, labelClass, modalOverlayClass, modalShellClass, primaryButtonClass, secondaryButtonClass } from '../../lib/ui'
import { enqueueOffline } from '../../lib/offlineQueue'
import { useOnlineStatus } from '../../lib/useOnlineStatus'
import { formatCurrency } from '../../lib/format'

const CATEGORIAS = [
  { value: 'equipamento', label: 'Equipamento' },
  { value: 'manutencao', label: 'Manutenção' },
  { value: 'reforma', label: 'Reforma' },
  { value: 'outro', label: 'Outro' },
]

export default function DespesaFormModal({ despesa, onClose, onSaved }) {
  const isEdit = Boolean(despesa)
  const [descricao, setDescricao] = useState(despesa?.descricao ?? '')
  const [categoria, setCategoria] = useState(despesa?.categoria ?? 'equipamento')
  const [valorTotal, setValorTotal] = useState(despesa?.valor_total ?? '')
  const [parcelado, setParcelado] = useState(despesa?.parcelado ?? false)
  const [numeroParcelas, setNumeroParcelas] = useState(despesa?.numero_parcelas ?? '')
  const [data, setData] = useState(despesa?.data ?? (() => new Date().toISOString().slice(0, 10)))
  const [observacao, setObservacao] = useState(despesa?.observacao ?? '')
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)
  const online = useOnlineStatus()

  const valorParcela =
    parcelado && Number(numeroParcelas) > 0 && Number(valorTotal) > 0
      ? Number(valorTotal) / Number(numeroParcelas)
      : null

  async function handleSubmit(e) {
    e.preventDefault()
    setSaving(true)
    setError(null)

    const payload = {
      descricao,
      categoria,
      valor_total: Number(valorTotal),
      parcelado,
      numero_parcelas: parcelado ? Number(numeroParcelas) : null,
      data,
      observacao: observacao || null,
    }

    if (!navigator.onLine) {
      enqueueOffline({
        table: 'despesas',
        operation: isEdit ? 'update' : 'insert',
        rowId: isEdit ? despesa.id : null,
        payload,
      })
      setSaving(false)
      onSaved?.()
      onClose()
      return
    }

    const { error } = isEdit
      ? await supabase.from('despesas').update(payload).eq('id', despesa.id)
      : await supabase.from('despesas').insert(payload)

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
          {isEdit ? 'Editar Despesa' : 'Nova Despesa'}
        </h2>

        <form onSubmit={handleSubmit} className="mt-4 space-y-4">
          {!online && (
            <p className="rounded-[10px] bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600 dark:bg-navy-800 dark:text-slate-300">
              📴 Sem conexão — vai salvar localmente e enviar sozinho quando a internet voltar.
            </p>
          )}

          <div>
            <label className={labelClass}>Descrição</label>
            <input
              required
              placeholder="Ex: Máquina de gelo, troca de filtro, reforma da fábrica..."
              value={descricao}
              onChange={(e) => setDescricao(e.target.value)}
              className={inputClass}
            />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className={labelClass}>Categoria</label>
              <select value={categoria} onChange={(e) => setCategoria(e.target.value)} className={inputClass}>
                {CATEGORIAS.map((c) => (
                  <option key={c.value} value={c.value}>
                    {c.label}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className={labelClass}>Data</label>
              <input type="date" required value={data} onChange={(e) => setData(e.target.value)} className={inputClass} />
            </div>
          </div>

          <div>
            <label className={labelClass}>Valor total (R$)</label>
            <input
              type="number"
              min="0.01"
              step="0.01"
              required
              value={valorTotal}
              onChange={(e) => setValorTotal(e.target.value)}
              className={inputClass}
            />
          </div>

          <div className="grid grid-cols-2 gap-2">
            <button
              type="button"
              onClick={() => setParcelado(false)}
              className={`rounded-[10px] px-3 py-2 text-sm font-semibold transition-colors ${
                !parcelado
                  ? 'bg-navy-950 text-white dark:bg-cyan-600'
                  : 'bg-slate-100 text-slate-500 dark:bg-navy-800 dark:text-slate-400'
              }`}
            >
              À vista
            </button>
            <button
              type="button"
              onClick={() => setParcelado(true)}
              className={`rounded-[10px] px-3 py-2 text-sm font-semibold transition-colors ${
                parcelado
                  ? 'bg-navy-950 text-white dark:bg-cyan-600'
                  : 'bg-slate-100 text-slate-500 dark:bg-navy-800 dark:text-slate-400'
              }`}
            >
              Parcelado
            </button>
          </div>

          {parcelado && (
            <div>
              <label className={labelClass}>Número de parcelas</label>
              <input
                type="number"
                min="1"
                step="1"
                required
                value={numeroParcelas}
                onChange={(e) => setNumeroParcelas(e.target.value)}
                className={inputClass}
              />
              {valorParcela != null && (
                <p className="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                  {formatCurrency(valorParcela)}/mês, a partir da data acima.
                </p>
              )}
            </div>
          )}

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
