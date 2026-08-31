export default function StatCard({ label, value, hint, trend }) {
  const trendColor =
    trend > 0
      ? 'text-emerald-600 dark:text-emerald-400'
      : trend < 0
        ? 'text-red-600 dark:text-red-400'
        : 'text-slate-400 dark:text-slate-500'
  const trendLabel = trend != null ? `${trend > 0 ? '+' : ''}${trend.toFixed(1)}%` : null

  return (
    <div className="rounded-card border border-slate-200 bg-white p-5 shadow-card dark:border-navy-700 dark:bg-navy-900">
      <p className="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">{label}</p>
      <p className="mt-2 font-display text-[28px] font-bold leading-none text-navy-950 dark:text-white">{value}</p>
      <div className="mt-2 flex items-center gap-2">
        {trendLabel && <span className={`text-xs font-semibold ${trendColor}`}>{trendLabel}</span>}
        {hint && <span className="text-xs text-slate-400 dark:text-slate-500">{hint}</span>}
      </div>
    </div>
  )
}
