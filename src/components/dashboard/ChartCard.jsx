export default function ChartCard({ title, children, actions }) {
  return (
    <div className="rounded-card border border-slate-200 bg-white p-5 shadow-card dark:border-navy-700 dark:bg-navy-900">
      <div className="mb-4 flex items-center justify-between">
        <h3 className="font-display text-sm font-semibold text-navy-950 dark:text-white">{title}</h3>
        {actions}
      </div>
      {children}
    </div>
  )
}
