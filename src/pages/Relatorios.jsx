import { useEffect, useState } from 'react'
import { supabase } from '../lib/supabaseClient'
import { REPORT_TYPES, generateReport } from '../lib/reports'
import { IconFile } from '../components/icons'
import { inputClass } from '../lib/ui'

function defaultRange() {
  const fim = new Date()
  const inicio = new Date()
  inicio.setDate(inicio.getDate() - 30)
  return { inicio: inicio.toISOString().slice(0, 10), fim: fim.toISOString().slice(0, 10) }
}

export default function Relatorios() {
  const [{ inicio, fim }, setRange] = useState(defaultRange)
  const [generating, setGenerating] = useState(null)
  const [historico, setHistorico] = useState([])

  async function loadHistorico() {
    const { data } = await supabase
      .from('relatorios_gerados')
      .select('*')
      .order('created_at', { ascending: false })
      .limit(10)
    setHistorico(data ?? [])
  }

  useEffect(() => {
    loadHistorico()
  }, [])

  async function handleGenerate(tipo, formato) {
    setGenerating(`${tipo}-${formato}`)
    try {
      await generateReport(tipo, formato, inicio, fim)
      await loadHistorico()
    } finally {
      setGenerating(null)
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center gap-3 rounded-card border border-slate-200 bg-white p-4 shadow-card dark:border-navy-700 dark:bg-navy-900">
        <span className="text-sm font-medium text-slate-500 dark:text-slate-400">Período</span>
        <input
          type="date"
          value={inicio}
          onChange={(e) => setRange((r) => ({ ...r, inicio: e.target.value }))}
          className={`w-auto ${inputClass}`}
        />
        <span className="text-sm text-slate-400 dark:text-slate-500">até</span>
        <input
          type="date"
          value={fim}
          onChange={(e) => setRange((r) => ({ ...r, fim: e.target.value }))}
          className={`w-auto ${inputClass}`}
        />
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        {Object.entries(REPORT_TYPES).map(([tipo, config]) => (
          <div key={tipo} className="rounded-card border border-slate-200 bg-white p-5 shadow-card dark:border-navy-700 dark:bg-navy-900">
            <div className="flex items-start gap-3">
              <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyan-500/[0.13]">
                <IconFile className="h-4 w-4 text-cyan-600 dark:text-cyan-400" />
              </span>
              <div>
                <p className="font-display text-sm font-semibold text-navy-950 dark:text-white">{config.label}</p>
                <p className="mt-1 text-xs text-slate-400 dark:text-slate-500">{config.description}</p>
              </div>
            </div>
            <div className="mt-4 flex gap-2">
              <button
                onClick={() => handleGenerate(tipo, 'pdf')}
                disabled={generating === `${tipo}-pdf`}
                className="flex-1 rounded-[10px] bg-navy-950 px-3 py-2 text-xs font-semibold text-white hover:bg-navy-800 disabled:opacity-60 dark:bg-cyan-600 dark:hover:bg-cyan-500"
              >
                {generating === `${tipo}-pdf` ? 'Gerando...' : 'Gerar PDF'}
              </button>
              <button
                onClick={() => handleGenerate(tipo, 'excel')}
                disabled={generating === `${tipo}-excel`}
                className="flex-1 rounded-[10px] border border-slate-200 px-3 py-2 text-xs font-semibold text-navy-950 hover:bg-slate-50 disabled:opacity-60 dark:border-navy-700 dark:text-white dark:hover:bg-navy-800"
              >
                {generating === `${tipo}-excel` ? 'Gerando...' : 'Gerar Excel'}
              </button>
            </div>
          </div>
        ))}
      </div>

      <div className="rounded-card border border-slate-200 bg-white shadow-card dark:border-navy-700 dark:bg-navy-900">
        <div className="border-b border-slate-100 px-5 py-4 dark:border-navy-700">
          <h3 className="font-display text-sm font-semibold text-navy-950 dark:text-white">Últimos Relatórios Gerados</h3>
        </div>
        <div className="divide-y divide-slate-100 dark:divide-navy-700">
          {historico.length === 0 && (
            <p className="px-5 py-6 text-sm text-slate-400 dark:text-slate-500">Nenhum relatório gerado ainda.</p>
          )}
          {historico.map((r) => (
            <div key={r.id} className="flex items-center justify-between px-5 py-3 text-sm">
              <div>
                <p className="font-medium text-navy-950 dark:text-white">{REPORT_TYPES[r.tipo]?.label ?? r.tipo}</p>
                <p className="text-xs text-slate-400 dark:text-slate-500">
                  {r.periodo_inicio} a {r.periodo_fim} · {r.formato.toUpperCase()}
                </p>
              </div>
              <span className="text-xs text-slate-400 dark:text-slate-500">
                {new Date(r.created_at).toLocaleString('pt-BR')}
              </span>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
