import { NavLink } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import {
  IconHome,
  IconSnowflake,
  IconBox,
  IconChart,
  IconFile,
  IconSettings,
  IconX,
} from './icons'

const NAV_ITEMS = [
  { to: '/', label: 'Visão Geral', icon: IconHome, end: true },
  { to: '/pontos', label: 'Pontos de Freezer', icon: IconSnowflake },
  { to: '/estoque', label: 'Estoque', icon: IconBox },
  { to: '/financeiro', label: 'Financeiro & Lucro', icon: IconChart },
  { to: '/relatorios', label: 'Relatórios', icon: IconFile },
  { to: '/configuracoes', label: 'Configurações', icon: IconSettings },
]

function SidebarContent({ onNavigate, onClose }) {
  const { profile, user } = useAuth()
  const name = profile?.full_name || user?.email || 'Usuário'
  const initials = name
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()

  return (
    <>
      <div className="flex items-center justify-between px-5 py-6">
        <div className="flex items-center gap-2">
          <span className="flex h-9 w-9 items-center justify-center rounded-full bg-cyan-500/[0.18]">
            <IconSnowflake className="h-5 w-5 text-cyan-400" />
          </span>
          <span className="font-display text-base font-semibold text-white">Gelatto ICE CO.</span>
        </div>
        {onClose && (
          <button
            onClick={onClose}
            className="flex h-8 w-8 items-center justify-center rounded-full text-slate-300 hover:bg-white/10 lg:hidden"
            aria-label="Fechar menu"
          >
            <IconX className="h-5 w-5" />
          </button>
        )}
      </div>

      <nav className="flex-1 space-y-1 px-3">
        {NAV_ITEMS.map(({ to, label, icon: Icon, end }) => (
          <NavLink
            key={to}
            to={to}
            end={end}
            onClick={onNavigate}
            className={({ isActive }) =>
              `flex items-center gap-3 rounded-[9px] px-3 py-2.5 text-sm font-medium transition-colors ${
                isActive
                  ? 'bg-cyan-500/[0.13] text-cyan-400'
                  : 'text-slate-300 hover:bg-white/5 hover:text-white'
              }`
            }
          >
            <Icon className="h-[18px] w-[18px]" />
            {label}
          </NavLink>
        ))}
      </nav>

      <div className="mt-auto flex items-center gap-3 border-t border-white/10 px-5 py-4">
        <span className="flex h-9 w-9 items-center justify-center rounded-full bg-navy-700 text-xs font-semibold text-white">
          {initials || '?'}
        </span>
        <div className="min-w-0">
          <p className="truncate text-sm font-medium text-white">{name}</p>
          <p className="truncate text-xs text-slate-400">
            {profile?.role === 'admin' ? 'Administrador' : 'Operador'}
          </p>
        </div>
      </div>
    </>
  )
}

export default function Sidebar({ mobileOpen, onCloseMobile }) {
  return (
    <>
      <aside className="fixed inset-y-0 left-0 z-20 hidden w-64 flex-col bg-navy-950 lg:flex">
        <SidebarContent />
      </aside>

      {mobileOpen && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <div className="absolute inset-0 bg-navy-950/50" onClick={onCloseMobile} />
          <aside className="absolute inset-y-0 left-0 flex w-64 flex-col bg-navy-950 shadow-xl">
            <SidebarContent onNavigate={onCloseMobile} onClose={onCloseMobile} />
          </aside>
        </div>
      )}
    </>
  )
}
