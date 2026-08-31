import { useEffect, useState } from 'react'
import { supabase } from '../lib/supabaseClient'
import { useAuth } from '../contexts/AuthContext'
import { useTheme } from '../contexts/ThemeContext'
import { inputClass, primaryButtonClass } from '../lib/ui'

const TABS = ['Perfil', 'Empresa', 'Usuários', 'Preferências', 'Integrações']

export default function Configuracoes() {
  const [tab, setTab] = useState('Perfil')

  return (
    <div className="flex flex-col gap-6 lg:flex-row">
      <nav className="flex gap-2 overflow-x-auto lg:w-48 lg:flex-col">
        {TABS.map((t) => (
          <button
            key={t}
            onClick={() => setTab(t)}
            className={`whitespace-nowrap rounded-[10px] px-3 py-2 text-left text-sm font-medium ${
              tab === t
                ? 'bg-navy-950 text-white dark:bg-cyan-600'
                : 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-navy-800'
            }`}
          >
            {t}
          </button>
        ))}
      </nav>

      <div className="flex-1 rounded-card border border-slate-200 bg-white p-6 shadow-card dark:border-navy-700 dark:bg-navy-900">
        {tab === 'Perfil' && <PerfilTab />}
        {tab === 'Empresa' && <EmpresaTab />}
        {tab === 'Usuários' && <UsuariosTab />}
        {tab === 'Preferências' && <PreferenciasTab />}
        {tab === 'Integrações' && <IntegracoesTab />}
      </div>
    </div>
  )
}

function Field({ label, children }) {
  return (
    <div>
      <label className="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">{label}</label>
      {children}
    </div>
  )
}

function SaveButton({ saving, saved }) {
  return (
    <button type="submit" disabled={saving} className={primaryButtonClass}>
      {saving ? 'Salvando...' : saved ? 'Salvo!' : 'Salvar'}
    </button>
  )
}

function PerfilTab() {
  const { user, profile } = useAuth()
  const [fullName, setFullName] = useState('')
  const [saving, setSaving] = useState(false)
  const [saved, setSaved] = useState(false)

  useEffect(() => setFullName(profile?.full_name ?? ''), [profile])

  async function handleSubmit(e) {
    e.preventDefault()
    setSaving(true)
    await supabase.from('profiles').update({ full_name: fullName }).eq('id', user.id)
    setSaving(false)
    setSaved(true)
    setTimeout(() => setSaved(false), 2000)
  }

  return (
    <form onSubmit={handleSubmit} className="max-w-sm space-y-4">
      <h2 className="font-display text-sm font-semibold text-navy-950 dark:text-white">Perfil</h2>
      <Field label="Nome completo">
        <input value={fullName} onChange={(e) => setFullName(e.target.value)} className={inputClass} />
      </Field>
      <Field label="E-mail">
        <input
          value={user?.email ?? ''}
          disabled
          className={`${inputClass} bg-slate-50 text-slate-400 dark:bg-navy-950 dark:text-slate-500`}
        />
      </Field>
      <Field label="Nível de permissão">
        <input
          value={profile?.role === 'admin' ? 'Administrador' : 'Operador'}
          disabled
          className={`${inputClass} bg-slate-50 text-slate-400 dark:bg-navy-950 dark:text-slate-500`}
        />
      </Field>
      <SaveButton saving={saving} saved={saved} />
    </form>
  )
}

function EmpresaTab() {
  const [form, setForm] = useState(null)
  const [saving, setSaving] = useState(false)
  const [saved, setSaved] = useState(false)

  useEffect(() => {
    supabase.from('app_settings').select('*').single().then(({ data }) => setForm(data))
  }, [])

  async function handleSubmit(e) {
    e.preventDefault()
    setSaving(true)
    await supabase
      .from('app_settings')
      .update({ empresa_nome: form.empresa_nome, empresa_cnpj: form.empresa_cnpj })
      .eq('id', true)
    setSaving(false)
    setSaved(true)
    setTimeout(() => setSaved(false), 2000)
  }

  if (!form) return <p className="text-sm text-slate-400 dark:text-slate-500">Carregando...</p>

  return (
    <form onSubmit={handleSubmit} className="max-w-sm space-y-4">
      <h2 className="font-display text-sm font-semibold text-navy-950 dark:text-white">Empresa</h2>
      <Field label="Nome da empresa">
        <input
          value={form.empresa_nome}
          onChange={(e) => setForm((f) => ({ ...f, empresa_nome: e.target.value }))}
          className={inputClass}
        />
      </Field>
      <Field label="CNPJ">
        <input
          value={form.empresa_cnpj ?? ''}
          onChange={(e) => setForm((f) => ({ ...f, empresa_cnpj: e.target.value }))}
          className={inputClass}
        />
      </Field>
      <SaveButton saving={saving} saved={saved} />
    </form>
  )
}

function UsuariosTab() {
  const { profile: myProfile } = useAuth()
  const [users, setUsers] = useState([])

  async function load() {
    const { data } = await supabase.from('profiles').select('*').order('full_name')
    setUsers(data ?? [])
  }

  useEffect(() => {
    load()
  }, [])

  async function changeRole(id, role) {
    await supabase.from('profiles').update({ role }).eq('id', id)
    load()
  }

  const isAdmin = myProfile?.role === 'admin'

  return (
    <div>
      <h2 className="font-display text-sm font-semibold text-navy-950 dark:text-white">Usuários</h2>
      <div className="mt-4 divide-y divide-slate-100 rounded-[10px] border border-slate-200 dark:divide-navy-700 dark:border-navy-700">
        {users.map((u) => (
          <div key={u.id} className="flex items-center justify-between px-4 py-3">
            <span className="text-sm font-medium text-navy-950 dark:text-white">{u.full_name}</span>
            {isAdmin ? (
              <select
                value={u.role}
                onChange={(e) => changeRole(u.id, e.target.value)}
                className="rounded-[10px] border border-slate-200 px-2 py-1.5 text-xs text-navy-950 dark:border-navy-700 dark:bg-navy-900 dark:text-white"
              >
                <option value="admin">Administrador</option>
                <option value="operador">Operador</option>
              </select>
            ) : (
              <span className="text-xs text-slate-400 dark:text-slate-500">
                {u.role === 'admin' ? 'Administrador' : 'Operador'}
              </span>
            )}
          </div>
        ))}
      </div>
      {!isAdmin && (
        <p className="mt-3 text-xs text-slate-400 dark:text-slate-500">
          Apenas administradores podem alterar níveis de permissão.
        </p>
      )}
    </div>
  )
}

function PreferenciasTab() {
  const { theme, setTheme } = useTheme()
  const [form, setForm] = useState(null)
  const [saving, setSaving] = useState(false)
  const [saved, setSaved] = useState(false)

  useEffect(() => {
    supabase.from('app_settings').select('*').single().then(({ data }) => setForm(data))
  }, [])

  async function handleSubmit(e) {
    e.preventDefault()
    setSaving(true)
    await supabase
      .from('app_settings')
      .update({
        moeda: form.moeda,
        fuso_horario: form.fuso_horario,
        notificacoes_email: form.notificacoes_email,
      })
      .eq('id', true)
    setSaving(false)
    setSaved(true)
    setTimeout(() => setSaved(false), 2000)
  }

  if (!form) return <p className="text-sm text-slate-400 dark:text-slate-500">Carregando...</p>

  return (
    <form onSubmit={handleSubmit} className="max-w-sm space-y-4">
      <h2 className="font-display text-sm font-semibold text-navy-950 dark:text-white">Preferências</h2>
      <Field label="Moeda">
        <select
          value={form.moeda}
          onChange={(e) => setForm((f) => ({ ...f, moeda: e.target.value }))}
          className={inputClass}
        >
          <option value="BRL">Real (R$)</option>
          <option value="USD">Dólar (US$)</option>
        </select>
      </Field>
      <Field label="Fuso horário">
        <select
          value={form.fuso_horario}
          onChange={(e) => setForm((f) => ({ ...f, fuso_horario: e.target.value }))}
          className={inputClass}
        >
          <option value="America/Sao_Paulo">América/São Paulo</option>
          <option value="America/Manaus">América/Manaus</option>
        </select>
      </Field>
      <Field label="Modo de exibição">
        <select value={theme} onChange={(e) => setTheme(e.target.value)} className={inputClass}>
          <option value="claro">Claro</option>
          <option value="escuro">Escuro</option>
        </select>
        <p className="mt-1 text-xs text-slate-400 dark:text-slate-500">
          Aplica na hora, só neste dispositivo — não precisa salvar.
        </p>
      </Field>
      <label className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
        <input
          type="checkbox"
          checked={form.notificacoes_email}
          onChange={(e) => setForm((f) => ({ ...f, notificacoes_email: e.target.checked }))}
        />
        Receber notificações por e-mail
      </label>
      <SaveButton saving={saving} saved={saved} />
    </form>
  )
}

function IntegracoesTab() {
  return (
    <div>
      <h2 className="font-display text-sm font-semibold text-navy-950 dark:text-white">Integrações</h2>
      <div className="mt-4 flex items-center justify-between rounded-[10px] border border-slate-200 px-4 py-3 dark:border-navy-700">
        <div>
          <p className="text-sm font-medium text-navy-950 dark:text-white">Supabase</p>
          <p className="text-xs text-slate-400 dark:text-slate-500">Banco de dados e autenticação em nuvem — conectado.</p>
        </div>
        <span className="rounded-full bg-emerald-100 px-2.5 py-[3px] text-[11px] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
          Ativo
        </span>
      </div>
      <p className="mt-4 text-xs text-slate-400 dark:text-slate-500">
        Novas integrações (WhatsApp, e-mail transacional, etc.) podem ser adicionadas aqui no futuro.
      </p>
    </div>
  )
}
