import { useState } from 'react'
import { useAuth } from '../contexts/AuthContext'
import { IconSnowflake } from '../components/icons'
import { inputClass, labelClass, primaryButtonClass } from '../lib/ui'

export default function Login() {
  const { signIn } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(false)

  async function handleSubmit(e) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    const { error } = await signIn(email, password)
    setLoading(false)
    if (error) setError('E-mail ou senha inválidos.')
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4 dark:bg-navy-950">
      <div className="w-full max-w-sm rounded-card border border-slate-200 bg-white p-8 shadow-card dark:border-navy-700 dark:bg-navy-900">
        <div className="mb-6 flex flex-col items-center gap-2">
          <span className="flex h-12 w-12 items-center justify-center rounded-full bg-cyan-500/[0.13]">
            <IconSnowflake className="h-6 w-6 text-cyan-600 dark:text-cyan-400" />
          </span>
          <h1 className="font-display text-lg font-bold text-navy-950 dark:text-white">Gelatto ICE CO.</h1>
          <p className="text-xs text-slate-400 dark:text-slate-500">Painel de Gestão</p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className={labelClass}>E-mail</label>
            <input
              type="email"
              autoComplete="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Senha</label>
            <input
              type="password"
              autoComplete="current-password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className={inputClass}
            />
          </div>

          {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}

          <button type="submit" disabled={loading} className={`w-full py-2.5 transition-colors ${primaryButtonClass}`}>
            {loading ? 'Entrando...' : 'Entrar'}
          </button>
        </form>
      </div>
    </div>
  )
}
