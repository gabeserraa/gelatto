import { useEffect, useState } from 'react'
import { supabase } from '../../lib/supabaseClient'
import { inputClass, labelClass, modalOverlayClass, modalShellClass, primaryButtonClass, secondaryButtonClass } from '../../lib/ui'
import { formatCurrency, formatKg } from '../../lib/format'

export default function VendaModal({ pontos, defaultPontoId, venda, onClose, onSaved }) {
  const isEdit = Boolean(venda)
  const [pontoId, setPontoId] = useState(venda?.ponto_id ?? defaultPontoId ?? pontos[0]?.id ?? '')
  const [quantidade, setQuantidade] = useState(venda?.quantidade_kg ?? '')
  const [precoVenda, setPrecoVenda] = useState(venda?.preco_venda_kg ?? '')
  const [custo, setCusto] = useState(venda?.custo_kg ?? '')
  const [data, setData] = useState(venda?.data ?? (() => new Date().toISOString().slice(0, 10)))
  const [observacao, setObservacao] = useState(venda?.observacao ?? '')
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)

  const prejuizo =
    precoVenda !== '' && custo !== '' && Number(precoVenda) <= Number(custo)
      ? Number(custo) - Number(precoVenda)
      : null

  const pontoSelecionado = pontos.find((p) => p.id === pontoId)
  const excedeCapacidade =
    quantidade !== '' && pontoSelecionado && Number(quantidade) > pontoSelecionado.capacidade_kg
      ? pontoSelecionado.capacidade_kg
      : null

  useEffect(() => {
    if (defaultPontoId) setPontoId(defaultPontoId)
  }, [defaultPontoId])

  async function handleSubmit(e) {
    e.preventDefault()
    setSaving(true)
    setError(null)

    const payload = {
      ponto_id: pontoId,
      quantidade_kg: Number(quantidade),
      preco_venda_kg: Number(precoVenda),
      custo_kg: Number(custo),
      data,
      observacao: observacao || null,
    }

    const { error } = isEdit
      ? await supabase.from('movimentacoes_estoque').update(payload).eq('id', venda.id)
      : await supabase.from('movimentacoes_estoque').insert(payload)

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
          {isEdit ? 'Editar Venda' : 'Registrar Venda'}
        </h2>

        <form onSubmit={handleSubmit} className="mt-4 space-y-4">
          <div>
            <label className={labelClass}>Ponto</label>
            <select required value={pontoId} onChange={(e) => setPontoId(e.target.value)} className={inputClass}>
              {pontos.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.nome}
                </option>
              ))}
            </select>
          </div>

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

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className={labelClass}>Preço venda/kg (R$)</label>
              <input
                type="number"
                min="0"
                step="0.01"
                required
                value={precoVenda}
                onChange={(e) => setPrecoVenda(e.target.value)}
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
                value={custo}
                onChange={(e) => setCusto(e.target.value)}
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

          {excedeCapacidade != null && (
            <p className="rounded-[10px] bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
              ⚠ Essa quantidade passa da capacidade do freezer ({formatKg(excedeCapacidade)}).
            </p>
          )}

          {prejuizo != null && (
            <p className="rounded-[10px] bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
              ⚠ Essa venda dá prejuízo de {formatCurrency(prejuizo)}/kg (custo maior que o preço de venda).
            </p>
          )}

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
