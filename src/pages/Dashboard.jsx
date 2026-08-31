import { useEffect, useMemo, useState } from 'react'
import {
  Area,
  AreaChart,
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
import { usePontosEstoque } from '../lib/usePontosEstoque'
import StatCard from '../components/dashboard/StatCard'
import ChartCard from '../components/dashboard/ChartCard'
import UrgencyBadge, { urgencyFromRatio } from '../components/dashboard/UrgencyBadge'
import ProgressBar from '../components/dashboard/ProgressBar'
import VendaModal from '../components/dashboard/VendaModal'
import { formatCurrency, formatKg, pctChange } from '../lib/format'

const TIPO_LABELS = { balada: 'Balada', mercado: 'Mercado', evento: 'Evento', bar: 'Bar' }
const TIPO_COLORS = ['#06b6d4', '#0891b2', '#0e7490', '#155e75']

export default function Dashboard() {
  const { pontos, loading, refresh } = usePontosEstoque()
  const [mensal, setMensal] = useState([])
  const [dailySeries, setDailySeries] = useState([])
  const [modalPontoId, setModalPontoId] = useState(null)

  useEffect(() => {
    supabase
      .from('v_financeiro_mensal')
      .select('*')
      .order('mes', { ascending: false })
      .limit(2)
      .then(({ data }) => setMensal(data ?? []))

    const since = new Date()
    since.setDate(since.getDate() - 30)
    supabase
      .from('v_movimentacoes_margem')
      .select('data, receita, custo')
      .gte('data', since.toISOString().slice(0, 10))
      .then(({ data }) => {
        const byDay = {}
        for (const row of data ?? []) {
          byDay[row.data] ??= { data: row.data, receita: 0, custo: 0 }
          byDay[row.data].receita += row.receita
          byDay[row.data].custo += row.custo
        }
        setDailySeries(Object.values(byDay).sort((a, b) => a.data.localeCompare(b.data)))
      })
  }, [])

  const [mesAtual, mesAnterior] = mensal

  const kpis = useMemo(() => {
    const pontosAtivos = pontos.filter((p) => p.status === 'ativo').length
    return {
      receita: mesAtual?.receita ?? 0,
      receitaTrend: pctChange(mesAtual?.receita, mesAnterior?.receita),
      lucro: mesAtual?.lucro ?? 0,
      lucroTrend: pctChange(mesAtual?.lucro, mesAnterior?.lucro),
      pontosAtivos,
    }
  }, [pontos, mesAtual, mesAnterior])

  const consumoPorTipo = useMemo(() => {
    const byTipo = {}
    for (const p of pontos) {
      byTipo[p.tipo] ??= 0
      byTipo[p.tipo] += Math.max(0, p.capacidade_kg - p.estoque_atual_kg)
    }
    return Object.entries(byTipo).map(([tipo, value]) => ({
      name: TIPO_LABELS[tipo] ?? tipo,
      value,
    }))
  }, [pontos])

  const reposicao = useMemo(
    () =>
      pontos
        .filter((p) => urgencyFromRatio(p.estoque_atual_kg, p.consumo_medio_dia) !== 'ok')
        .sort((a, b) => a.estoque_atual_kg / a.capacidade_kg - b.estoque_atual_kg / b.capacidade_kg),
    [pontos],
  )

  const totalKg = pontos.reduce((sum, p) => sum + Number(p.estoque_atual_kg || 0), 0)

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          label="Receita do mês"
          value={formatCurrency(kpis.receita)}
          trend={kpis.receitaTrend}
          hint="vs mês anterior"
        />
        <StatCard
          label="Lucro líquido"
          value={formatCurrency(kpis.lucro)}
          trend={kpis.lucroTrend}
          hint="vs mês anterior"
        />
        <StatCard label="Gelo em estoque" value={formatKg(totalKg)} hint="em todos os pontos" />
        <StatCard label="Pontos ativos" value={kpis.pontosAtivos} hint={`de ${pontos.length} cadastrados`} />
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <ChartCard title="Receita vs Custo — últimos 30 dias" actions={null}>
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={dailySeries}>
                <defs>
                  <linearGradient id="receitaGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#06b6d4" stopOpacity={0.3} />
                    <stop offset="95%" stopColor="#06b6d4" stopOpacity={0} />
                  </linearGradient>
                  <linearGradient id="custoGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#ef4444" stopOpacity={0.3} />
                    <stop offset="95%" stopColor="#ef4444" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                <XAxis
                  dataKey="data"
                  tick={{ fontSize: 11, fill: '#94a3b8' }}
                  tickFormatter={(v) => v.slice(8, 10) + '/' + v.slice(5, 7)}
                  axisLine={false}
                  tickLine={false}
                />
                <YAxis tick={{ fontSize: 11, fill: '#94a3b8' }} axisLine={false} tickLine={false} />
                <Tooltip formatter={(v) => formatCurrency(v)} />
                <Legend wrapperStyle={{ fontSize: 11 }} />
                <Area type="monotone" dataKey="receita" name="Receita" stroke="#06b6d4" fill="url(#receitaGradient)" strokeWidth={2} />
                <Area type="monotone" dataKey="custo" name="Custo" stroke="#ef4444" fill="url(#custoGradient)" strokeWidth={2} />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </ChartCard>

        <ChartCard title="Consumo por tipo de ponto">
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

      <div className="rounded-card border border-slate-200 bg-white shadow-card">
        <div className="border-b border-slate-100 px-5 py-4">
          <h3 className="font-display text-sm font-semibold text-navy-950">Pontos que Precisam de Reposição</h3>
        </div>
        <div className="divide-y divide-slate-100">
          {loading && <p className="px-5 py-6 text-sm text-slate-400">Carregando...</p>}
          {!loading && reposicao.length === 0 && (
            <p className="px-5 py-6 text-sm text-slate-400">Nenhum ponto precisa de reposição agora.</p>
          )}
          {reposicao.map((p) => {
            const ratio = p.estoque_atual_kg / p.capacidade_kg
            return (
              <div key={p.id} className="flex items-center gap-4 px-5 py-4">
                <div className="min-w-0 flex-1">
                  <div className="flex items-center gap-2">
                    <p className="truncate text-sm font-medium text-navy-950">{p.nome}</p>
                    <UrgencyBadge status={urgencyFromRatio(p.estoque_atual_kg, p.consumo_medio_dia)} />
                  </div>
                  <p className="mt-1 text-xs text-slate-400">{formatKg(p.estoque_atual_kg)} de {formatKg(p.capacidade_kg)}</p>
                  <div className="mt-2 w-full max-w-xs">
                    <ProgressBar ratio={ratio} />
                  </div>
                </div>
                <button
                  onClick={() => setModalPontoId(p.id)}
                  className="shrink-0 rounded-[10px] bg-navy-950 px-3 py-2 text-xs font-semibold text-white hover:bg-navy-800"
                >
                  Registrar Venda
                </button>
              </div>
            )
          })}
        </div>
      </div>

      {modalPontoId && (
        <VendaModal
          pontos={pontos}
          defaultPontoId={modalPontoId}
          onClose={() => setModalPontoId(null)}
          onSaved={refresh}
        />
      )}
    </div>
  )
}
