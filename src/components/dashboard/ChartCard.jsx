export default function ChartCard({ title, children, actions }) {
  return (
    <div className="rounded-card border border-slate-200 bg-white p-5 shadow-card">
      <div className="mb-4 flex items-center justify-between">
        <h3 className="font-display text-sm font-semibold text-navy-950">{title}</h3>
        {actions}
      </div>
      {children}
    </div>
  )
}
