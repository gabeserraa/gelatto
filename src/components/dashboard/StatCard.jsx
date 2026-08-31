export default function StatCard({ label, value, hint, trend }) {
  const trendColor = trend > 0 ? 'text-emerald-600' : trend < 0 ? 'text-red-600' : 'text-slate-400'
  const trendLabel = trend != null ? `${trend > 0 ? '+' : ''}${trend.toFixed(1)}%` : null

  return (
    <div className="rounded-card border border-slate-200 bg-white p-5 shadow-card">
      <p className="text-xs font-medium uppercase tracking-wide text-slate-400">{label}</p>
      <p className="mt-2 font-display text-[28px] font-bold leading-none text-navy-950">{value}</p>
      <div className="mt-2 flex items-center gap-2">
        {trendLabel && <span className={`text-xs font-semibold ${trendColor}`}>{trendLabel}</span>}
        {hint && <span className="text-xs text-slate-400">{hint}</span>}
      </div>
    </div>
  )
}
