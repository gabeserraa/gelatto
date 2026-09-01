import { useCallback, useEffect, useState } from 'react'
import { useAuth } from '../contexts/AuthContext'
import { supabase } from '../lib/supabaseClient'
import { useRealtimeRefresh } from '../lib/useRealtimeRefresh'
import { useOfflineQueue } from '../lib/useOfflineQueue'
import { IconBell, IconLogout, IconMenu } from './icons'

const PAGE_TITLES = {
  '/': 'Visão Geral',
  '/pontos': 'Pontos de Freezer',
  '/estoque': 'Estoque',
  '/fabrica': 'Estoque da Fábrica',
  '/financeiro': 'Financeiro & Lucro',
  '/relatorios': 'Relatórios',
  '/configuracoes': 'Configurações',
}

export default function Header({ path, onOpenMenu }) {
  const { signOut } = useAuth()
  const [open, setOpen] = useState(false)
  const [alerts, setAlerts] = useState([])
  const pendingSync = useOfflineQueue()

  const loadAlerts = useCallback(async () => {
    const { data } = await supabase.from('v_pontos_estoque').select('id, nome, estoque_atual_kg, capacidade_kg')
    if (!data) return
    const critical = data.filter((p) => p.estoque_atual_kg / p.capacidade_kg <= 0.15)
    setAlerts(critical)
  }, [])

  useEffect(() => {
    loadAlerts()
  }, [loadAlerts])

  useRealtimeRefresh(['pontos', 'movimentacoes_estoque', 'ajustes_estoque'], loadAlerts)

  const title = PAGE_TITLES[path] ?? (path?.startsWith('/pontos/') ? 'Detalhe do Ponto' : 'Gelatto ICE CO.')

  return (
    <header className="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8 dark:border-navy-700 dark:bg-navy-900">
      <div className="flex items-center gap-3">
        <button
          onClick={onOpenMenu}
          className="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 lg:hidden dark:text-slate-400 dark:hover:bg-navy-800"
          aria-label="Abrir menu"
        >
          <IconMenu className="h-5 w-5" />
        </button>
        <div>
          <h1 className="font-display text-lg font-bold text-navy-950 dark:text-white">{title}</h1>
          <p className="text-xs text-slate-400 dark:text-slate-500">Gelatto ICE CO. · Painel de Gestão</p>
        </div>
      </div>

      <div className="flex items-center gap-4">
        {pendingSync > 0 && (
          <span className="hidden items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700 sm:inline-flex dark:bg-amber-500/15 dark:text-amber-400">
            <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500" />
            {pendingSync} pendente{pendingSync > 1 ? 's' : ''} pra sincronizar
          </span>
        )}
        <div className="relative">
          <button
            onClick={() => setOpen((v) => !v)}
            className="relative flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-navy-800"
            aria-label="Notificações"
          >
            <IconBell className="h-5 w-5" />
            {alerts.length > 0 && (
              <span className="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500" />
            )}
          </button>
          {open && (
            <div className="absolute right-0 mt-2 w-72 rounded-card border border-slate-200 bg-white shadow-card dark:border-navy-700 dark:bg-navy-900">
              <div className="border-b border-slate-100 px-4 py-3 dark:border-navy-700">
                <p className="font-display text-sm font-semibold text-navy-950 dark:text-white">Notificações</p>
              </div>
              <div className="max-h-72 overflow-y-auto">
                {alerts.length === 0 ? (
                  <p className="px-4 py-6 text-center text-sm text-slate-400 dark:text-slate-500">
                    Nenhum alerta no momento.
                  </p>
                ) : (
                  alerts.map((p) => (
                    <div key={p.id} className="border-b border-slate-50 px-4 py-3 last:border-0 dark:border-navy-800">
                      <p className="text-sm font-medium text-navy-950 dark:text-white">{p.nome}</p>
                      <p className="text-xs text-red-600 dark:text-red-400">Estoque crítico — repor com urgência</p>
                    </div>
                  ))
                )}
              </div>
            </div>
          )}
        </div>

        <button
          onClick={signOut}
          className="flex items-center gap-1.5 rounded-[10px] px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-navy-800"
        >
          <IconLogout className="h-4 w-4" />
          Sair
        </button>
      </div>
    </header>
  )
}
