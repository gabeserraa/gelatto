import { useState } from 'react'
import { useAuth } from '../contexts/AuthContext'
import { IconSnowflake } from '../components/icons'

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
    <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4">
      <div className="w-full max-w-sm rounded-card border border-slate-200 bg-white p-8 shadow-card">
        <div className="mb-6 flex flex-col items-center gap-2">
          <span className="flex h-12 w-12 items-center justify-center rounded-full bg-cyan-500/[0.13]">
            <IconSnowflake className="h-6 w-6 text-cyan-600" />
          </span>
          <h1 className="font-display text-lg font-bold text-navy-950">Gelatto ICE CO.</h1>
          <p className="text-xs text-slate-400">Painel de Gestão</p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="mb-1 block text-xs font-medium text-slate-500">E-mail</label>
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
            />
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-slate-500">Senha</label>
            <input
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm text-navy-950 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
            />
          </div>

          {error && <p className="text-sm text-red-600">{error}</p>}

          <button
            type="submit"
            disabled={loading}
            className="w-full rounded-[10px] bg-navy-950 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-navy-800 disabled:opacity-60"
          >
            {loading ? 'Entrando...' : 'Entrar'}
          </button>
        </form>
      </div>
    </div>
  )
}
