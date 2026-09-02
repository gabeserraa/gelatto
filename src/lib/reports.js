import { supabase } from './supabaseClient'
import { monthLabel } from './format'

export const REPORT_TYPES = {
  consumo_por_ponto: {
    label: 'Consumo por Ponto',
    description: 'Total vendido (kg) por ponto no período selecionado.',
    async fetch(inicio, fim) {
      const { data } = await supabase
        .from('movimentacoes_estoque')
        .select('quantidade_kg, pontos(nome)')
        .gte('data', inicio)
        .lte('data', fim)
      const byPonto = {}
      for (const row of data ?? []) {
        const nome = row.pontos?.nome ?? '—'
        byPonto[nome] = (byPonto[nome] ?? 0) + row.quantidade_kg
      }
      const entries = Object.entries(byPonto).sort((a, b) => b[1] - a[1])
      return {
        columns: ['Ponto', 'Consumo (kg)'],
        rows: entries.map(([nome, kg]) => [nome, kg.toFixed(1)]),
        chart: entries.length
          ? {
              categories: entries.map(([nome]) => nome),
              series: [{ name: 'Consumo (kg)', values: entries.map(([, kg]) => kg), color: [6, 182, 212] }],
            }
          : null,
      }
    },
  },
  financeiro_mensal: {
    label: 'Financeiro Mensal',
    description: 'Receita, custo e lucro agregados por mês no período.',
    async fetch(inicio, fim) {
      const { data } = await supabase
        .from('v_financeiro_mensal')
        .select('*')
        .gte('mes', inicio)
        .lte('mes', fim)
        .order('mes')
      const rows = data ?? []
      return {
        columns: ['Mês', 'Receita', 'Custo', 'Lucro'],
        rows: rows.map((r) => [r.mes, r.receita.toFixed(2), r.custo.toFixed(2), r.lucro.toFixed(2)]),
        chart: rows.length
          ? {
              categories: rows.map((r) => monthLabel(r.mes)),
              series: [
                { name: 'Receita', values: rows.map((r) => r.receita), color: [6, 182, 212] },
                { name: 'Custo', values: rows.map((r) => r.custo), color: [239, 68, 68] },
                { name: 'Lucro', values: rows.map((r) => r.lucro), color: [16, 185, 129] },
              ],
            }
          : null,
      }
    },
  },
  reposicoes: {
    label: 'Vendas e Entregas',
    description: 'Histórico detalhado de vendas/entregas por ponto no período.',
    async fetch(inicio, fim) {
      const { data } = await supabase
        .from('movimentacoes_estoque')
        .select('data, quantidade_kg, preco_venda_kg, custo_kg, pontos(nome)')
        .gte('data', inicio)
        .lte('data', fim)
        .order('data')
      return {
        columns: ['Data', 'Ponto', 'Quantidade (kg)', 'Preço venda/kg', 'Custo/kg'],
        rows: (data ?? []).map((r) => [
          r.data,
          r.pontos?.nome ?? '—',
          r.quantidade_kg,
          r.preco_venda_kg.toFixed(2),
          r.custo_kg.toFixed(2),
        ]),
        chart: null,
      }
    },
  },
  estoque_consolidado: {
    label: 'Estoque Consolidado',
    description: 'Foto atual do estoque, custo médio e valor por ponto.',
    async fetch() {
      const { data } = await supabase.from('v_pontos_estoque').select('*').order('nome')
      const pontos = data ?? []
      return {
        columns: ['Ponto', 'Estoque (kg)', 'Custo médio/kg', 'Valor total'],
        rows: pontos.map((p) => [
          p.nome,
          p.estoque_atual_kg,
          p.custo_medio_kg.toFixed(2),
          (p.estoque_atual_kg * p.custo_medio_kg).toFixed(2),
        ]),
        chart: pontos.length
          ? {
              categories: pontos.map((p) => p.nome),
              series: [
                {
                  name: 'Valor em estoque (R$)',
                  values: pontos.map((p) => p.estoque_atual_kg * p.custo_medio_kg),
                  color: [6, 182, 212],
                },
              ],
            }
          : null,
      }
    },
  },
}

/**
 * Desenha um gráfico de barras agrupadas direto com os primitivos vetoriais
 * do jsPDF (sem rasterizar/capturar tela) — mais confiável e leve do que
 * gerar uma imagem à parte. Retorna o Y logo abaixo do gráfico.
 */
function drawBarChart(doc, { x, y, width, height, categories, series }) {
  const maxValue = Math.max(...series.flatMap((s) => s.values), 1)
  const axisLabelWidth = 4
  const chartX = x + axisLabelWidth
  const chartWidth = width - axisLabelWidth
  const groupWidth = chartWidth / categories.length
  const gap = 1
  const barWidth = Math.max((groupWidth - gap * (series.length + 1)) / series.length, 1)

  doc.setDrawColor(226, 232, 240)
  doc.setLineWidth(0.1)
  const steps = 4
  for (let i = 0; i <= steps; i++) {
    const ly = y + height - (height * i) / steps
    doc.line(chartX, ly, chartX + chartWidth, ly)
  }

  categories.forEach((cat, ci) => {
    series.forEach((s, si) => {
      const value = s.values[ci] ?? 0
      const barHeight = maxValue > 0 ? (Math.max(value, 0) / maxValue) * height : 0
      const bx = chartX + ci * groupWidth + gap + si * (barWidth + gap)
      const by = y + height - barHeight
      doc.setFillColor(...s.color)
      doc.rect(bx, by, barWidth, barHeight, 'F')
    })
    doc.setFontSize(6)
    doc.setTextColor(100, 116, 139)
    const label = String(cat).length > 14 ? String(cat).slice(0, 13) + '…' : String(cat)
    doc.text(label, chartX + ci * groupWidth + groupWidth / 2, y + height + 4, { align: 'center', maxWidth: groupWidth })
  })

  let legendX = x
  const legendY = y - 3
  for (const s of series) {
    doc.setFillColor(...s.color)
    doc.rect(legendX, legendY - 2.2, 2.5, 2.5, 'F')
    doc.setFontSize(7)
    doc.setTextColor(30, 41, 59)
    doc.text(s.name, legendX + 3.5, legendY)
    legendX += doc.getTextWidth(s.name) + 10
  }

  return y + height + 10
}

export async function generateReport(tipo, formato, inicio, fim) {
  const config = REPORT_TYPES[tipo]
  const { columns, rows, chart } = await config.fetch(inicio, fim)
  const filename = `${tipo}-${inicio}-a-${fim}`

  if (formato === 'pdf') {
    const [{ default: jsPDF }, { default: autoTable }] = await Promise.all([
      import('jspdf'),
      import('jspdf-autotable'),
    ])
    const doc = new jsPDF()
    doc.setFontSize(14)
    doc.text(`Gelatto ICE CO. — ${config.label}`, 14, 16)
    doc.setFontSize(10)
    doc.text(`Período: ${inicio} a ${fim}`, 14, 22)

    let startY = 28
    if (chart) {
      startY = drawBarChart(doc, { x: 14, y: 34, width: 182, height: 55, ...chart })
    }

    autoTable(doc, { head: [columns], body: rows, startY })
    doc.save(`${filename}.pdf`)
  } else {
    const XLSX = await import('xlsx')
    const sheet = XLSX.utils.aoa_to_sheet([columns, ...rows])
    const workbook = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(workbook, sheet, config.label.slice(0, 31))
    XLSX.writeFile(workbook, `${filename}.xlsx`)
  }

  await supabase.from('relatorios_gerados').insert({
    tipo,
    formato,
    periodo_inicio: inicio,
    periodo_fim: fim,
  })
}
