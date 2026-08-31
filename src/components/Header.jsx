import { useEffect, useState } from 'react'
import { useAuth } from '../contexts/AuthContext'
import { supabase } from '../lib/supabaseClient'
import { IconBell, IconLogout } from './icons'

const PAGE_TITLES = {
  '/': 'Visão Geral',
  '/pontos': 'Pontos de Freezer',
  '/estoque': 'Estoque',
  '/financeiro': 'Financeiro & Lucro',
  '/relatorios': 'Relatórios',
  '/configuracoes': 'Configurações',
}

export default function Header({ path }) {
  const { signOut } = useAuth()
  const [open, setOpen] = useState(false)
  const [alerts, setAlerts] = useState([])

  useEffect(() => {
    supabase
      .from('pontos')
      .select('id, nome, estoque_atual_kg, capacidade_kg')
      .then(({ data }) => {
        if (!data) return
        const critical = data.filter((p) => p.estoque_atual_kg / p.capacidade_kg <= 0.15)
        setAlerts(critical)
      })
  }, [])

  const title = PAGE_TITLES[path] ?? 'Gelatto ICE CO.'

  return (
    <header className="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
      <div>
        <h1 className="font-display text-lg font-bold text-navy-950">{title}</h1>
        <p className="text-xs text-slate-400">Gelatto ICE CO. · Painel de Gestão</p>
      </div>

      <div className="flex items-center gap-4">
        <div className="relative">
          <button
            onClick={() => setOpen((v) => !v)}
            className="relative flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100"
            aria-label="Notificações"
          >
            <IconBell className="h-5 w-5" />
            {alerts.length > 0 && (
              <span className="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500" />
            )}
          </button>
          {open && (
            <div className="absolute right-0 mt-2 w-72 rounded-card border border-slate-200 bg-white shadow-card">
              <div className="border-b border-slate-100 px-4 py-3">
                <p className="font-display text-sm font-semibold text-navy-950">Notificações</p>
              </div>
              <div className="max-h-72 overflow-y-auto">
                {alerts.length === 0 ? (
                  <p className="px-4 py-6 text-center text-sm text-slate-400">
                    Nenhum alerta no momento.
                  </p>
                ) : (
                  alerts.map((p) => (
                    <div key={p.id} className="border-b border-slate-50 px-4 py-3 last:border-0">
                      <p className="text-sm font-medium text-navy-950">{p.nome}</p>
                      <p className="text-xs text-red-600">Estoque crítico — repor com urgência</p>
                    </div>
                  ))
                )}
              </div>
            </div>
          )}
        </div>

        <button
          onClick={signOut}
          className="flex items-center gap-1.5 rounded-[10px] px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100"
        >
          <IconLogout className="h-4 w-4" />
          Sair
        </button>
      </div>
    </header>
  )
}
