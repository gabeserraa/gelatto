const STYLES = {
  critico: { label: 'Crítico', className: 'bg-red-100 text-red-700' },
  repor: { label: 'Repor em breve', className: 'bg-amber-100 text-amber-700' },
  ok: { label: 'OK', className: 'bg-emerald-100 text-emerald-700' },
  ativo: { label: 'Ativo', className: 'bg-emerald-100 text-emerald-700' },
  inativo: { label: 'Inativo', className: 'bg-slate-100 text-slate-700' },
  manutencao: { label: 'Manutenção', className: 'bg-amber-100 text-amber-700' },
}

export default function UrgencyBadge({ status }) {
  const s = STYLES[status] ?? STYLES.ok
  return (
    <span className={`inline-flex rounded-full px-2.5 py-[3px] text-[11px] font-semibold ${s.className}`}>
      {s.label}
    </span>
  )
}

export function urgencyFromRatio(currentKg, avgDailyKg) {
  if (!avgDailyKg) return 'ok'
  const daysLeft = currentKg / avgDailyKg
  if (daysLeft <= 1) return 'critico'
  if (daysLeft <= 3) return 'repor'
  return 'ok'
}
