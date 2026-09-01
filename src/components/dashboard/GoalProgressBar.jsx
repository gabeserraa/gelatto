export default function GoalProgressBar({ ratio }) {
  const pct = Math.max(0, Math.min(100, Math.round(ratio * 100)))

  return (
    <div className="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-navy-800">
      <div
        className={`h-full rounded-full ${pct >= 100 ? 'bg-emerald-500' : 'bg-cyan-500'}`}
        style={{ width: `${pct}%` }}
      />
    </div>
  )
}
