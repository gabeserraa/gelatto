import { useState } from 'react'
import { supabase } from '../../lib/supabaseClient'

const TIPOS = [
  { value: 'balada', label: 'Balada' },
  { value: 'mercado', label: 'Mercado' },
  { value: 'evento', label: 'Evento' },
  { value: 'bar', label: 'Bar' },
]

export default function PontoFormModal({ onClose, onSaved }) {
  const [form, setForm] = useState({
    nome: '',
    endereco: '',
    tipo: 'bar',
    capacidade_kg: '',
    consumo_medio_dia: '',
    regiao: '',
    latitude: '',
    longitude: '',
  })
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)

  function set(field) {
    return (e) => setForm((f) => ({ ...f, [field]: e.target.value }))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setSaving(true)
    setError(null)

    const { error } = await supabase.from('pontos').insert({
      nome: form.nome,
      endereco: form.endereco,
      tipo: form.tipo,
      capacidade_kg: Number(form.capacidade_kg),
      consumo_medio_dia: Number(form.consumo_medio_dia),
      regiao: form.regiao,
      latitude: form.latitude ? Number(form.latitude) : null,
      longitude: form.longitude ? Number(form.longitude) : null,
    })

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
        <h2 className="font-display text-base font-semibold text-navy-950">Novo Ponto</h2>

        <form onSubmit={handleSubmit} className="mt-4 space-y-4">
          <div>
            <label className="mb-1 block text-xs font-medium text-slate-500">Nome</label>
            <input
              required
              value={form.nome}
              onChange={set('nome')}
              className="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
            />
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-slate-500">Endereço</label>
            <input
              required
              value={form.endereco}
              onChange={set('endereco')}
              className="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
            />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="mb-1 block text-xs font-medium text-slate-500">Tipo</label>
              <select
                value={form.tipo}
                onChange={set('tipo')}
                className="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
              >
                {TIPOS.map((t) => (
                  <option key={t.value} value={t.value}>
                    {t.label}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="mb-1 block text-xs font-medium text-slate-500">Região</label>
              <input
                required
                value={form.regiao}
                onChange={set('regiao')}
                className="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
              />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="mb-1 block text-xs font-medium text-slate-500">Capacidade (kg)</label>
              <input
                type="number"
                min="1"
                step="0.1"
                required
                value={form.capacidade_kg}
                onChange={set('capacidade_kg')}
                className="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
              />
            </div>
            <div>
              <label className="mb-1 block text-xs font-medium text-slate-500">Consumo médio/dia (kg)</label>
              <input
                type="number"
                min="0"
                step="0.1"
                required
                value={form.consumo_medio_dia}
                onChange={set('consumo_medio_dia')}
                className="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="mb-1 block text-xs font-medium text-slate-500">Latitude (opcional)</label>
              <input
                type="number"
                step="any"
                value={form.latitude}
                onChange={set('latitude')}
                className="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
              />
            </div>
            <div>
              <label className="mb-1 block text-xs font-medium text-slate-500">Longitude (opcional)</label>
              <input
                type="number"
                step="any"
                value={form.longitude}
                onChange={set('longitude')}
                className="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
              />
            </div>
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
