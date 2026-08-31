const STYLES = {
  critico: { label: 'Crítico', className: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400' },
  repor: { label: 'Repor em breve', className: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400' },
  ok: { label: 'OK', className: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' },
  ativo: { label: 'Ativo', className: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' },
  inativo: { label: 'Inativo', className: 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-slate-300' },
  manutencao: { label: 'Manutenção', className: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400' },
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
