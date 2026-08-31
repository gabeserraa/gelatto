export default function ProgressBar({ ratio }) {
  const pct = Math.max(0, Math.min(100, Math.round(ratio * 100)))
  const color = pct <= 15 ? 'bg-red-500' : pct <= 35 ? 'bg-amber-500' : 'bg-cyan-500'

  return (
    <div className="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
      <div className={`h-full rounded-full ${color}`} style={{ width: `${pct}%` }} />
    </div>
  )
}
