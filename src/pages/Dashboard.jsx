import { useCallback, useEffect, useMemo, useState } from 'react'
import { Bar, BarChart, CartesianGrid, Cell, Legend, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { supabase } from '../lib/supabaseClient'
import { usePontosEstoque } from '../lib/usePontosEstoque'
import { useRealtimeRefresh } from '../lib/useRealtimeRefresh'
import { useTheme } from '../contexts/ThemeContext'
import StatCard from '../components/dashboard/StatCard'
import ChartCard from '../components/dashboard/ChartCard'
import { formatCurrency, formatCurrencyCompact, formatKg, pctChange } from '../lib/format'
import { tableCardClass, tableHeaderRowClass } from '../lib/ui'

const TIPO_LABELS = { balada: 'Balada', mercado: 'Mercado', evento: 'Evento', bar: 'Bar' }
const TIPO_COLORS = ['#06b6d4', '#0891b2', '#0e7490', '#155e75']
const MESES_ABREV = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ']
const ANO_TABELA = 2026

function formatUltimaAtualizacao(ts) {
  if (!ts) return 'sem movimentações ainda'
  const d = new Date(ts)
  return `atualizado em ${d.toLocaleDateString('pt-BR')} às ${d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}`
}

export default function Dashboard() {
  const { pontos } = usePontosEstoque()
  const { theme } = useTheme()
  const axisColor = theme === 'escuro' ? '#64748b' : '#94a3b8'
  const gridColor = theme === 'escuro' ? '#1c304a' : '#e2e8f0'

  const [margem, setMargem] = useState([])
  const [fabrica, setFabrica] = useState(null)
  const [loading, setLoading] = useState(true)
  const [selectedMonth, setSelectedMonth] = useState(null)

  const load = useCallback(async () => {
    setLoading(true)
    const [{ data: margemData }, { data: fabricaData }] = await Promise.all([
      supabase.from('v_movimentacoes_margem').select('data, ponto_id, quantidade_kg, receita, custo, lucro'),
      supabase.from('v_estoque_fabrica').select('*').maybeSingle(),
    ])
    setMargem(margemData ?? [])
    setFabrica(fabricaData)
    setLoading(false)
  }, [])

  useEffect(() => {
    load()
  }, [load])

  useRealtimeRefresh(['movimentacoes_estoque', 'movimentacoes_fabrica'], load)

  const nomesPorPonto = useMemo(() => Object.fromEntries(pontos.map((p) => [p.id, p.nome])), [pontos])
  const tipoPorPonto = useMemo(() => Object.fromEntries(pontos.map((p) => [p.id, p.tipo])), [pontos])

  const monthlyData = useMemo(() => {
    const arr = MESES_ABREV.map((label, i) => ({ mes: i, label, receita: 0 }))
    for (const row of margem) {
      const [y, m] = row.data.split('-')
      if (Number(y) !== ANO_TABELA) continue
      arr[Number(m) - 1].receita += row.receita
    }
    return arr
  }, [margem])

  const monthlyTotalsAll = useMemo(() => {
    const map = {}
    for (const row of margem) {
      const key = row.data.slice(0, 7)
      map[key] ??= { receita: 0, lucro: 0 }
      map[key].receita += row.receita
      map[key].lucro += row.lucro
    }
    return map
  }, [margem])

  const { receitaMes, receitaMesAnterior, lucroMes, lucroMesAnterior } = useMemo(() => {
    const now = new Date()
    const nowKey = now.toISOString().slice(0, 7)
    const prev = new Date(now)
    prev.setMonth(prev.getMonth() - 1)
    const prevKey = prev.toISOString().slice(0, 7)
    return {
      receitaMes: monthlyTotalsAll[nowKey]?.receita ?? 0,
      receitaMesAnterior: monthlyTotalsAll[prevKey]?.receita ?? 0,
      lucroMes: monthlyTotalsAll[nowKey]?.lucro ?? 0,
      lucroMesAnterior: monthlyTotalsAll[prevKey]?.lucro ?? 0,
    }
  }, [monthlyTotalsAll])

  const filteredRows = useMemo(() => {
    if (selectedMonth == null) return margem
    return margem.filter((row) => {
      const [y, m] = row.data.split('-')
      return Number(y) === ANO_TABELA && Number(m) - 1 === selectedMonth
    })
  }, [margem, selectedMonth])

  const clientChartData = useMemo(() => {
    const byPonto = {}
    for (const row of filteredRows) {
      byPonto[row.ponto_id] ??= 0
      byPonto[row.ponto_id] += row.receita
    }
    return Object.entries(byPonto)
      .map(([id, receita]) => ({ nome: nomesPorPonto[id] ?? '—', receita }))
      .sort((a, b) => b.receita - a.receita)
  }, [filteredRows, nomesPorPonto])

  const pieData = useMemo(() => {
    const custo = filteredRows.reduce((sum, r) => sum + r.custo, 0)
    const lucro = filteredRows.reduce((sum, r) => sum + r.lucro, 0)
    return [
      { name: 'Lucro', value: Math.max(lucro, 0), color: '#10b981' },
      { name: 'Custo', value: Math.max(custo, 0), color: '#ef4444' },
    ]
  }, [filteredRows])

  const consumoPorTipo = useMemo(() => {
    const byTipo = {}
    for (const row of filteredRows) {
      const tipo = tipoPorPonto[row.ponto_id]
      if (!tipo) continue
      byTipo[tipo] ??= 0
      byTipo[tipo] += row.quantidade_kg
    }
    return Object.entries(byTipo).map(([tipo, value]) => ({
      name: TIPO_LABELS[tipo] ?? tipo,
      value,
    }))
  }, [filteredRows, tipoPorPonto])

  const periodoLabel = selectedMonth != null ? `${MESES_ABREV[selectedMonth]}/${ANO_TABELA}` : 'Total Geral'

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <StatCard
          label="Receita do mês"
          value={formatCurrency(receitaMes)}
          trend={pctChange(receitaMes, receitaMesAnterior)}
          hint="vs mês anterior"
        />
        <StatCard
          label="Lucro líquido"
          value={formatCurrency(lucroMes)}
          trend={pctChange(lucroMes, lucroMesAnterior)}
          hint="vs mês anterior"
        />
        <StatCard
          label="Gelo em estoque (fábrica)"
          value={formatKg(fabrica?.estoque_atual_kg)}
          hint={formatUltimaAtualizacao(fabrica?.ultima_atualizacao)}
        />
      </div>

      <div className={tableCardClass}>
        <div className={tableHeaderRowClass}>
          <h3 className="font-display text-sm font-semibold text-navy-950 dark:text-white">
            Faturamento por Mês — {ANO_TABELA}
          </h3>
          <p className="mt-0.5 text-xs text-slate-400 dark:text-slate-500">
            Clica num mês pra ver o faturamento por cliente e o lucro só daquele período.
          </p>
        </div>
        <div className="grid grid-cols-12 divide-x divide-slate-100 dark:divide-navy-700">
          {monthlyData.map((m) => (
            <button
              key={m.mes}
              onClick={() => setSelectedMonth((cur) => (cur === m.mes ? null : m.mes))}
              className={`flex flex-col items-center gap-1 px-1 py-3 transition-colors ${
                selectedMonth === m.mes
                  ? 'bg-cyan-50 dark:bg-cyan-500/10'
                  : 'hover:bg-slate-50 dark:hover:bg-navy-800'
              }`}
            >
              <span className="text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                {m.label}
              </span>
              <span
                className={`text-[11px] font-semibold sm:text-xs ${
                  selectedMonth === m.mes ? 'text-cyan-700 dark:text-cyan-400' : 'text-navy-950 dark:text-white'
                }`}
              >
                {formatCurrencyCompact(m.receita)}
              </span>
            </button>
          ))}
        </div>
        {selectedMonth != null && (
          <div className="border-t border-slate-100 px-5 py-3 dark:border-navy-700">
            <button
              onClick={() => setSelectedMonth(null)}
              className="text-xs font-medium text-cyan-600 hover:underline dark:text-cyan-400"
            >
              ← Limpar seleção (voltar pro total geral)
            </button>
          </div>
        )}
      </div>

      <ChartCard title={`Faturamento por Cliente — ${periodoLabel}`}>
        {loading && <p className="py-8 text-center text-sm text-slate-400 dark:text-slate-500">Carregando...</p>}
        {!loading && clientChartData.length === 0 && (
          <p className="py-8 text-center text-sm text-slate-400 dark:text-slate-500">Sem vendas nesse período.</p>
        )}
        {!loading && clientChartData.length > 0 && (
          <div style={{ height: Math.max(clientChartData.length * 34, 140) }}>
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={clientChartData} layout="vertical" margin={{ left: 8, right: 16 }}>
                <CartesianGrid strokeDasharray="3 3" stroke={gridColor} horizontal={false} />
                <XAxis
                  type="number"
                  tick={{ fontSize: 11, fill: axisColor }}
                  axisLine={false}
                  tickLine={false}
                  tickFormatter={(v) => formatCurrencyCompact(v)}
                />
                <YAxis
                  type="category"
                  dataKey="nome"
                  width={110}
                  tick={{ fontSize: 11, fill: axisColor }}
                  axisLine={false}
                  tickLine={false}
                />
                <Tooltip formatter={(v) => formatCurrency(v)} />
                <Bar dataKey="receita" name="Faturamento" fill="#06b6d4" radius={[0, 4, 4, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        )}
      </ChartCard>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <ChartCard title={`Faturamento x Lucro — ${periodoLabel}`}>
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie data={pieData} dataKey="value" nameKey="name" innerRadius={55} outerRadius={85} paddingAngle={2}>
                  {pieData.map((entry) => (
                    <Cell key={entry.name} fill={entry.color} />
                  ))}
                </Pie>
                <Tooltip formatter={(v) => formatCurrency(v)} />
                <Legend wrapperStyle={{ fontSize: 11 }} />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </ChartCard>

        <ChartCard title={`Consumo por Tipo de Ponto — ${periodoLabel}`}>
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={consumoPorTipo}
                  dataKey="value"
                  nameKey="name"
                  innerRadius={55}
                  outerRadius={85}
                  paddingAngle={2}
                >
                  {consumoPorTipo.map((entry, i) => (
                    <Cell key={entry.name} fill={TIPO_COLORS[i % TIPO_COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip formatter={(v) => formatKg(v)} />
                <Legend wrapperStyle={{ fontSize: 11 }} />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </ChartCard>
      </div>
    </div>
  )
}
