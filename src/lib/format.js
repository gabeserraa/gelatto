export function formatCurrency(value) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value ?? 0)
}

export function formatKg(value) {
  return `${new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 1 }).format(value ?? 0)} kg`
}

export function formatPercent(value) {
  return `${new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 1 }).format(value ?? 0)}%`
}

export function monthLabel(dateStr) {
  const d = new Date(dateStr)
  return d.toLocaleDateString('pt-BR', { month: 'short' }).replace('.', '')
}

export function pctChange(current, previous) {
  if (!previous) return current ? 100 : 0
  return ((current - previous) / previous) * 100
}
