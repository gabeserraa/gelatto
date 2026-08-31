import { supabase } from './supabaseClient'

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
      return {
        columns: ['Ponto', 'Consumo (kg)'],
        rows: Object.entries(byPonto).map(([nome, kg]) => [nome, kg.toFixed(1)]),
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
      return {
        columns: ['Mês', 'Receita', 'Custo', 'Lucro'],
        rows: (data ?? []).map((r) => [r.mes, r.receita.toFixed(2), r.custo.toFixed(2), r.lucro.toFixed(2)]),
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
      }
    },
  },
  estoque_consolidado: {
    label: 'Estoque Consolidado',
    description: 'Foto atual do estoque, custo médio e valor por ponto.',
    async fetch() {
      const { data } = await supabase.from('v_pontos_estoque').select('*').order('nome')
      return {
        columns: ['Ponto', 'Estoque (kg)', 'Custo médio/kg', 'Valor total'],
        rows: (data ?? []).map((p) => [
          p.nome,
          p.estoque_atual_kg,
          p.custo_medio_kg.toFixed(2),
          (p.estoque_atual_kg * p.custo_medio_kg).toFixed(2),
        ]),
      }
    },
  },
}

export async function generateReport(tipo, formato, inicio, fim) {
  const config = REPORT_TYPES[tipo]
  const { columns, rows } = await config.fetch(inicio, fim)
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
    autoTable(doc, { head: [columns], body: rows, startY: 28 })
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
