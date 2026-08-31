import { useLocation } from 'react-router-dom'
import Sidebar from '../components/Sidebar'
import Header from '../components/Header'

export default function DashboardLayout({ children }) {
  const { pathname } = useLocation()

  return (
    <div className="min-h-screen bg-slate-100">
      <Sidebar />
      <div className="lg:pl-64">
        <Header path={pathname} />
        <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{children}</main>
      </div>
    </div>
  )
}
