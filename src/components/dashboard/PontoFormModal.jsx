import { useState } from 'react'
import { supabase } from '../../lib/supabaseClient'
import { inputClass, labelClass, modalOverlayClass, modalShellClass, primaryButtonClass, secondaryButtonClass } from '../../lib/ui'
import { enqueueOffline } from '../../lib/offlineQueue'
import { useOnlineStatus } from '../../lib/useOnlineStatus'
import { geocodeAddress } from '../../lib/geocode'
import { IconSearch } from '../icons'

const TIPOS = [
  { value: 'balada', label: 'Balada' },
  { value: 'mercado', label: 'Mercado' },
  { value: 'evento', label: 'Evento' },
  { value: 'bar', label: 'Bar' },
]

const STATUS = [
  { value: 'ativo', label: 'Ativo' },
  { value: 'inativo', label: 'Inativo' },
  { value: 'manutencao', label: 'Manutenção' },
]

export default function PontoFormModal({ ponto, onClose, onSaved }) {
  const isEdit = Boolean(ponto)
  const [form, setForm] = useState({
    nome: ponto?.nome ?? '',
    endereco: ponto?.endereco ?? '',
    tipo: ponto?.tipo ?? 'bar',
    status: ponto?.status ?? 'ativo',
    capacidade_kg: ponto?.capacidade_kg ?? '',
    consumo_medio_dia: ponto?.consumo_medio_dia ?? '',
    regiao: ponto?.regiao ?? '',
    latitude: ponto?.latitude ?? '',
    longitude: ponto?.longitude ?? '',
    meta_mensal_kg: ponto?.meta_mensal_kg ?? '',
  })
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)
  const [geocoding, setGeocoding] = useState(false)
  const [geocodeMsg, setGeocodeMsg] = useState(null)
  const online = useOnlineStatus()

  function set(field) {
    return (e) => setForm((f) => ({ ...f, [field]: e.target.value }))
  }

  async function handleGeocode() {
    if (!form.endereco.trim()) return
    setGeocoding(true)
    setGeocodeMsg(null)
    try {
      const query = form.regiao ? `${form.endereco}, ${form.regiao}` : form.endereco
      const result = await geocodeAddress(query)
      if (!result) {
        setGeocodeMsg('Endereço não encontrado — pode preencher latitude/longitude na mão.')
        return
      }
      setForm((f) => ({ ...f, latitude: result.latitude, longitude: result.longitude }))
      setGeocodeMsg('Coordenadas preenchidas — confere se o pino ficou no lugar certo.')
    } catch {
      setGeocodeMsg('Não foi possível buscar agora. Tenta de novo ou preenche na mão.')
    } finally {
      setGeocoding(false)
    }
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setSaving(true)
    setError(null)

    const payload = {
      nome: form.nome,
      endereco: form.endereco,
      tipo: form.tipo,
      status: form.status,
      capacidade_kg: Number(form.capacidade_kg),
      consumo_medio_dia: Number(form.consumo_medio_dia),
      regiao: form.regiao,
      latitude: form.latitude ? Number(form.latitude) : null,
      longitude: form.longitude ? Number(form.longitude) : null,
      meta_mensal_kg: form.meta_mensal_kg ? Number(form.meta_mensal_kg) : null,
    }

    if (!navigator.onLine) {
      enqueueOffline({
        table: 'pontos',
        operation: isEdit ? 'update' : 'insert',
        rowId: isEdit ? ponto.id : null,
        payload,
      })
      setSaving(false)
      onSaved?.()
      onClose()
      return
    }

    const { error } = isEdit
      ? await supabase.from('pontos').update(payload).eq('id', ponto.id)
      : await supabase.from('pontos').insert(payload)

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
          {isEdit ? 'Editar Ponto' : 'Novo Ponto'}
        </h2>

        <form onSubmit={handleSubmit} className="mt-4 space-y-4">
          {!online && (
            <p className="rounded-[10px] bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600 dark:bg-navy-800 dark:text-slate-300">
              📴 Sem conexão — vai salvar localmente e enviar sozinho quando a internet voltar.
            </p>
          )}

          <div>
            <label className={labelClass}>Nome</label>
            <input required value={form.nome} onChange={set('nome')} className={inputClass} />
          </div>
          <div>
            <label className={labelClass}>Endereço</label>
            <input required value={form.endereco} onChange={set('endereco')} className={inputClass} />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className={labelClass}>Tipo</label>
              <select value={form.tipo} onChange={set('tipo')} className={inputClass}>
                {TIPOS.map((t) => (
                  <option key={t.value} value={t.value}>
                    {t.label}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className={labelClass}>Região</label>
              <input required value={form.regiao} onChange={set('regiao')} className={inputClass} />
            </div>
          </div>

          {isEdit && (
            <div>
              <label className={labelClass}>Status</label>
              <select value={form.status} onChange={set('status')} className={inputClass}>
                {STATUS.map((s) => (
                  <option key={s.value} value={s.value}>
                    {s.label}
                  </option>
                ))}
              </select>
            </div>
          )}

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className={labelClass}>Capacidade (kg)</label>
              <input
                type="number"
                min="1"
                step="0.1"
                required
                value={form.capacidade_kg}
                onChange={set('capacidade_kg')}
                className={inputClass}
              />
            </div>
            <div>
              <label className={labelClass}>Consumo médio/dia (kg)</label>
              <input
                type="number"
                min="0"
                step="0.1"
                required
                value={form.consumo_medio_dia}
                onChange={set('consumo_medio_dia')}
                className={inputClass}
              />
            </div>
          </div>

          <div>
            <label className={labelClass}>Meta mensal (kg, opcional)</label>
            <input
              type="number"
              min="0"
              step="1"
              placeholder="Ex: 100 — deixa em branco se não tiver meta"
              value={form.meta_mensal_kg}
              onChange={set('meta_mensal_kg')}
              className={inputClass}
            />
          </div>

          <div>
            <button
              type="button"
              onClick={handleGeocode}
              disabled={geocoding || !form.endereco.trim()}
              className="flex items-center gap-1.5 rounded-[10px] border border-slate-200 px-3 py-2 text-xs font-semibold text-navy-950 hover:bg-slate-50 disabled:opacity-60 dark:border-navy-700 dark:text-white dark:hover:bg-navy-800"
            >
              <IconSearch className="h-3.5 w-3.5" />
              {geocoding ? 'Buscando...' : 'Buscar coordenadas pelo endereço'}
            </button>
            {geocodeMsg && <p className="mt-1.5 text-xs text-slate-500 dark:text-slate-400">{geocodeMsg}</p>}
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className={labelClass}>Latitude (opcional)</label>
              <input type="number" step="any" value={form.latitude} onChange={set('latitude')} className={inputClass} />
            </div>
            <div>
              <label className={labelClass}>Longitude (opcional)</label>
              <input type="number" step="any" value={form.longitude} onChange={set('longitude')} className={inputClass} />
            </div>
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
