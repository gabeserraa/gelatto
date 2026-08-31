import { lazy, Suspense } from 'react'
import { HashRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider, useAuth } from './contexts/AuthContext'
import DashboardLayout from './layouts/DashboardLayout'
import Login from './pages/Login'

const Dashboard = lazy(() => import('./pages/Dashboard'))
const Pontos = lazy(() => import('./pages/Pontos'))
const Estoque = lazy(() => import('./pages/Estoque'))
const Financeiro = lazy(() => import('./pages/Financeiro'))
const Relatorios = lazy(() => import('./pages/Relatorios'))
const Configuracoes = lazy(() => import('./pages/Configuracoes'))

function Protected({ children }) {
  const { user, loading } = useAuth()

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-100 text-sm text-slate-400">
        Carregando...
      </div>
    )
  }
  if (!user) return <Navigate to="/login" replace />
  return children
}

function AppRoutes() {
  const { user, loading } = useAuth()

  return (
    <Routes>
      <Route path="/login" element={!loading && user ? <Navigate to="/" replace /> : <Login />} />
      <Route
        path="/*"
        element={
          <Protected>
            <DashboardLayout>
              <Suspense fallback={<p className="text-sm text-slate-400">Carregando...</p>}>
                <Routes>
                  <Route path="/" element={<Dashboard />} />
                  <Route path="/pontos" element={<Pontos />} />
                  <Route path="/estoque" element={<Estoque />} />
                  <Route path="/financeiro" element={<Financeiro />} />
                  <Route path="/relatorios" element={<Relatorios />} />
                  <Route path="/configuracoes" element={<Configuracoes />} />
                  <Route path="*" element={<Navigate to="/" replace />} />
                </Routes>
              </Suspense>
            </DashboardLayout>
          </Protected>
        }
      />
    </Routes>
  )
}

export default function App() {
  return (
    <AuthProvider>
      <HashRouter>
        <AppRoutes />
      </HashRouter>
    </AuthProvider>
  )
}
