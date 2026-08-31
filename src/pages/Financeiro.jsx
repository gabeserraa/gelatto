import { useEffect, useMemo, useState } from 'react'
import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Legend,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { supabase } from '../lib/supabaseClient'
import StatCard from '../components/dashboard/StatCard'
import ChartCard from '../components/dashboard/ChartCard'
import { formatCurrency, formatPercent, monthLabel, pctChange } from '../lib/format'

const TIPO_COLORS = ['#06b6d4', '#0891b2', '#0e7490', '#155e75', '#164e63', '#0f2f3a']

export default function Financeiro() {
  const [mensal, setMensal] = useState([])
  const [ranking, setRanking] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    async function load() {
      const [{ data: mensalData }, { data: rankingData }, { data: pontosData }] = await Promise.all([
        supabase.from('v_financeiro_mensal').select('*').order('mes', { ascending: false }).limit(6),
        supabase.from('v_lucro_por_ponto').select('*'),
        supabase.from('pontos').select('id, nome'),
      ])

      setMensal((mensalData ?? []).slice().reverse())

      const nomes = Object.fromEntries((pontosData ?? []).map((p) => [p.id, p.nome]))
      const rows = (rankingData ?? [])
        .map((r) => ({
          nome: nomes[r.ponto_id] ?? '—',
          lucro: r.lucro_mes_atual ?? 0,
          variacao: pctChange(r.lucro_mes_atual, r.lucro_mes_anterior),
        }))
        .sort((a, b) => b.lucro - a.lucro)
      setRanking(rows)
      setLoading(false)
    }
    load()
  }, [])

  const [mesAtual, mesAnterior] = useMemo(() => [...mensal].reverse(), [mensal])

  const margemAtual = mesAtual?.receita ? (mesAtual.lucro / mesAtual.receita) * 100 : 0
  const margemAnterior = mesAnterior?.receita ? (mesAnterior.lucro / mesAnterior.receita) * 100 : 0

  const projecao = useMemo(() => {
    if (!mesAtual) return 0
    const now = new Date()
    const dayOfMonth = now.getDate()
    const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate()
    if (dayOfMonth === 0) return mesAtual.lucro
    return (mesAtual.lucro / dayOfMonth) * daysInMonth
  }, [mesAtual])

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <StatCard
          label="Margem de lucro"
          value={formatPercent(margemAtual)}
          trend={pctChange(margemAtual, margemAnterior)}
          hint="vs mês anterior"
        />
        <StatCard label="Lucro do mês" value={formatCurrency(mesAtual?.lucro)} />
        <StatCard label="Projeção do mês" value={formatCurrency(projecao)} hint="no ritmo atual" />
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <ChartCard title="Receita x Custo x Lucro — últimos 6 meses">
          <div className="h-72 lg:col-span-2">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={mensal.map((m) => ({ ...m, mesLabel: monthLabel(m.mes) }))}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                <XAxis dataKey="mesLabel" tick={{ fontSize: 11, fill: '#94a3b8' }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 11, fill: '#94a3b8' }} axisLine={false} tickLine={false} />
                <Tooltip formatter={(v) => formatCurrency(v)} />
                <Legend wrapperStyle={{ fontSize: 11 }} />
                <Bar dataKey="receita" name="Receita" fill="#06b6d4" radius={[4, 4, 0, 0]} />
                <Bar dataKey="custo" name="Custo" fill="#ef4444" radius={[4, 4, 0, 0]} />
                <Bar dataKey="lucro" name="Lucro" fill="#10b981" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </ChartCard>

        <ChartCard title="Participação no lucro total">
          <div className="h-72">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie data={ranking} dataKey="lucro" nameKey="nome" innerRadius={50} outerRadius={80} paddingAngle={2}>
                  {ranking.map((entry, i) => (
                    <Cell key={entry.nome} fill={TIPO_COLORS[i % TIPO_COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip formatter={(v) => formatCurrency(v)} />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </ChartCard>
      </div>

      <div className="rounded-card border border-slate-200 bg-white shadow-card">
        <div className="border-b border-slate-100 px-5 py-4">
          <h3 className="font-display text-sm font-semibold text-navy-950">Ranking de Lucro por Ponto</h3>
        </div>
        <div className="divide-y divide-slate-100">
          {loading && <p className="px-5 py-6 text-sm text-slate-400">Carregando...</p>}
          {!loading &&
            ranking.map((r, i) => (
              <div key={r.nome} className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3">
                  <span className="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">
                    {i + 1}
                  </span>
                  <span className="text-sm font-medium text-navy-950">{r.nome}</span>
                </div>
                <div className="flex items-center gap-3">
                  <span className="text-sm font-semibold text-navy-950">{formatCurrency(r.lucro)}</span>
                  <span className={`text-xs font-semibold ${r.variacao >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                    {r.variacao >= 0 ? '+' : ''}
                    {r.variacao.toFixed(1)}%
                  </span>
                </div>
              </div>
            ))}
        </div>
      </div>
    </div>
  )
}
