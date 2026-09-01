import { useCallback, useEffect, useMemo, useState } from 'react'
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
import { useTheme } from '../contexts/ThemeContext'
import StatCard from '../components/dashboard/StatCard'
import ChartCard from '../components/dashboard/ChartCard'
import { formatCurrency, formatKg, formatPercent, monthLabel, pctChange } from '../lib/format'
import { tableCardClass, tableHeaderRowClass, tbodyClass, thClass, theadClass } from '../lib/ui'
import { useRealtimeRefresh } from '../lib/useRealtimeRefresh'

const TIPO_COLORS = ['#06b6d4', '#0891b2', '#0e7490', '#155e75', '#164e63', '#0f2f3a']

export default function Financeiro() {
  const { theme } = useTheme()
  const axisColor = theme === 'escuro' ? '#64748b' : '#94a3b8'
  const gridColor = theme === 'escuro' ? '#1c304a' : '#e2e8f0'
  const [mensal, setMensal] = useState([])
  const [ranking, setRanking] = useState([])
  const [comparativo, setComparativo] = useState([])
  const [loading, setLoading] = useState(true)

  const load = useCallback(async () => {
      const inicioMes = new Date()
      inicioMes.setDate(1)
      const inicioMesStr = inicioMes.toISOString().slice(0, 10)

      const [{ data: mensalData }, { data: rankingData }, { data: pontosData }, { data: vendasMes }] =
        await Promise.all([
          supabase.from('v_financeiro_mensal').select('*').order('mes', { ascending: false }).limit(6),
          supabase.from('v_lucro_por_ponto').select('*'),
          supabase.from('v_pontos_estoque').select('id, nome, capacidade_kg, estoque_atual_kg'),
          supabase.from('movimentacoes_estoque').select('ponto_id, quantidade_kg').gte('data', inicioMesStr),
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

      const lucroMesPorPonto = Object.fromEntries((rankingData ?? []).map((r) => [r.ponto_id, r.lucro_mes_atual ?? 0]))
      const consumoPorPonto = {}
      for (const v of vendasMes ?? []) {
        consumoPorPonto[v.ponto_id] = (consumoPorPonto[v.ponto_id] ?? 0) + v.quantidade_kg
      }
      const comparativoRows = (pontosData ?? [])
        .map((p) => ({
          id: p.id,
          nome: p.nome,
          estoqueAtual: p.estoque_atual_kg,
          consumoMes: consumoPorPonto[p.id] ?? 0,
          giro: p.capacidade_kg > 0 ? ((consumoPorPonto[p.id] ?? 0) / p.capacidade_kg) * 100 : 0,
          lucroMes: lucroMesPorPonto[p.id] ?? 0,
        }))
        .sort((a, b) => b.consumoMes - a.consumoMes)
      setComparativo(comparativoRows)

      setLoading(false)
  }, [])

  useEffect(() => {
    load()
  }, [load])

  useRealtimeRefresh(['movimentacoes_estoque', 'ajustes_estoque', 'pontos'], load)

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
                <CartesianGrid strokeDasharray="3 3" stroke={gridColor} vertical={false} />
                <XAxis dataKey="mesLabel" tick={{ fontSize: 11, fill: axisColor }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 11, fill: axisColor }} axisLine={false} tickLine={false} />
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

      <div className="rounded-card border border-slate-200 bg-white shadow-card dark:border-navy-700 dark:bg-navy-900">
        <div className="border-b border-slate-100 px-5 py-4 dark:border-navy-700">
          <h3 className="font-display text-sm font-semibold text-navy-950 dark:text-white">Ranking de Lucro por Ponto</h3>
        </div>
        <div className="divide-y divide-slate-100 dark:divide-navy-700">
          {loading && <p className="px-5 py-6 text-sm text-slate-400 dark:text-slate-500">Carregando...</p>}
          {!loading &&
            ranking.map((r, i) => (
              <div key={r.nome} className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3">
                  <span className="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500 dark:bg-navy-800 dark:text-slate-400">
                    {i + 1}
                  </span>
                  <span className="text-sm font-medium text-navy-950 dark:text-white">{r.nome}</span>
                </div>
                <div className="flex items-center gap-3">
                  <span className="text-sm font-semibold text-navy-950 dark:text-white">{formatCurrency(r.lucro)}</span>
                  <span
                    className={`text-xs font-semibold ${
                      r.variacao >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'
                    }`}
                  >
                    {r.variacao >= 0 ? '+' : ''}
                    {r.variacao.toFixed(1)}%
                  </span>
                </div>
              </div>
            ))}
        </div>
      </div>

      <div className={tableCardClass}>
        <div className={tableHeaderRowClass}>
          <h3 className="font-display text-sm font-semibold text-navy-950 dark:text-white">Comparativo entre Pontos</h3>
        </div>
        <table className="w-full text-left text-sm">
          <thead className={theadClass}>
            <tr>
              {['Ponto', 'Estoque atual', 'Consumo do mês', 'Giro', 'Lucro do mês'].map((h) => (
                <th key={h} className={thClass}>
                  {h}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className={tbodyClass}>
            {loading && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
                  Carregando...
                </td>
              </tr>
            )}
            {!loading &&
              comparativo.map((c) => (
                <tr key={c.id}>
                  <td className="px-4 py-3 font-medium text-navy-950 dark:text-white">{c.nome}</td>
                  <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{formatKg(c.estoqueAtual)}</td>
                  <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{formatKg(c.consumoMes)}</td>
                  <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{formatPercent(c.giro)}</td>
                  <td className="px-4 py-3 font-medium text-emerald-700 dark:text-emerald-400">
                    {formatCurrency(c.lucroMes)}
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
