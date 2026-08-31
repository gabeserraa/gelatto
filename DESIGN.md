# Design

<!-- impeccable:design-schema 1 -->

## World

Deep-navy authority + cyan-accent operate dashboard. Pinned exactly to the client's own Figma reference (`https://churn-space-56833911.figma.site/`) — every token below was read from that prototype's computed styles, not invented. Near-black navy sidebar with a soft cyan pill marking the active route; a slate canvas holding white, hairline-bordered, whisper-shadowed cards; Outfit for headings and KPI numbers, Inter for body copy; pastel-pill status badges; solid navy buttons.

## Palette

- **Canvas**: `slate-100` (`#f1f5f9`) — page background.
- **Sidebar**: `navy-950` (`#0a1628`) — bg. Custom scale in `tailwind.config.js` (`navy.50` through `navy.950`) for hover/active sidebar surfaces (`navy-800`, `navy-900`) and primary buttons.
- **Accent**: `cyan-500` (`#06b6d4`) — active nav pill (`bg-cyan-500/[0.13]` + `text-cyan-400`/`text-cyan-700` depending on surface), focus rings, links, chart primary series.
- **Text**: `navy-950` for primary/headings/KPI values; `slate-500`/`slate-400` for secondary/labels; `slate-300`/`slate-400` for sidebar inactive text on the dark surface.
- **Status** (pastel pill: `bg-{color}-100` + `text-{color}-700` for AA contrast — the prototype's own `text-{color}-500` measured under 4.5:1 on small pill text, so this build darkens the text one step while keeping the same hue family):
  - success/ativo/OK: `emerald`
  - warning/repor em breve/manutenção: `amber`
  - critical/crítico: `red`
  - neutral/inativo: `slate`
- **Charts**: cyan (`#06b6d4`) for revenue/primary, `#ef4444` red for cost, `#10b981` emerald for profit; a 10-step cyan ramp (`#06b6d4` → `#164e63`) for categorical breakdowns with many series (stock-by-point, profit-share).

## Typography

- **Display / headings / KPI numbers**: Outfit, 600–700 weight (`font-display` Tailwind key). KPI values are 28px/700.
- **Body**: Inter, 400–700 (Tailwind default `font-sans`). Loaded together via one Google Fonts request in both `layouts/app.blade.php` and `layouts/guest.blade.php`.
- Section headings inside cards: `font-display text-sm font-semibold`. Page `<h1>`: `font-display text-lg font-bold`.
- Table headers: 11px, semibold, uppercase, `tracking-wide`, `text-slate-400`.

## Shape & Elevation

- Cards: `rounded-card` (16px, custom Tailwind key), `border border-slate-200`, `shadow-card` (`0 1px 4px 0 rgb(0 0 0 / 0.06)` — a whisper shadow, not a drop shadow), `p-5` (20px/22px in the prototype).
- Buttons and inputs: `rounded-[10px]`.
- Sidebar nav items: `rounded-[9px]`.
- Badges: fully pill (`rounded-full`), `px-2.5 py-[3px]`, `text-[11px] font-semibold`.

## Components

- `x-dashboard.kpi-card` — label (uppercase, 12px, slate-400) / value (Outfit 700, 28px, navy-950) / optional hint (12px, slate-400).
- `x-dashboard.status-badge` — point operational status (ativo/inativo/manutenção); pastel pill.
- Inline urgency badges (crítico/repor em breve/OK) follow the same pastel-pill recipe but aren't componentized (three call sites: Visão Executiva restock list, Pontos, Estoque) — extract to a shared component if a fourth call site appears.
- `x-dashboard.data-table` — white card shell, `slate-50/60` header row, 11px uppercase headers, `divide-slate-100` rows.
- `x-dashboard.chart-card` — card shell + `font-display` title + canvas; Chart.js legend/font defaults are set once globally in `resources/js/app.js` (`usePointStyle`, 11px Inter labels) rather than per chart.
- `x-toggle-switch` — real CSS toggle (peer-checked pattern), replacing the old plain checkbox that couldn't render as a switch. Used across Configurações → Preferências.
- `x-primary-button` / `x-secondary-button` / `x-danger-button` / `x-text-input` — Breeze's generic form components, restyled to the same radius/weight/color tokens so Profile and auth screens match the dashboards instead of keeping Breeze's stock indigo/gray look.

## Layout

- Sidebar: fixed 256px, `navy-950`, droplet-in-cyan-chip logo + wordmark at top, nav list, user identity block (initials avatar + name + role) pinned at the bottom via `mt-auto`/flex-column — this last part is new relative to the old sidebar and mirrors the prototype exactly.
- Header: white, `border-b border-slate-200`, page title (`font-display`) + one-line "Gelatto ICE CO. · Painel de Gestão" subtitle, Perfil/Sair on the right.
- Main content: `slate-100` canvas, `max-w-7xl` centered, standard `px-4 py-6 sm:px-6 lg:px-8` padding — unchanged from before the redesign, only the tokens riding on top of it changed.

## Patterns Established

- Every page-level "card" (KPI, chart, ad-hoc info panel, report card, settings panel) uses the same three classes together: `rounded-card border border-slate-200 bg-white shadow-card`. Never mix this with a plain `rounded-lg shadow` — that was the pre-redesign default and is the one thing to grep for if a future addition looks inconsistent.
- Any new status/urgency concept should reuse the pastel-pill recipe (`bg-{hue}-100 text-{hue}-700`, pill radius, 11px/600) rather than inventing a new badge treatment.
- Chart.js color usage: cyan = primary/revenue, red = cost/negative, emerald = profit/positive, amber = warning, slate = neutral/other. The 10-step cyan ramp is reserved for categorical breakdowns (many series, one hue family) — don't reach for a rainbow palette.
- New dashboards still follow the pre-existing `config/dashboards.php` + controller + view contract; the redesign did not touch that architecture, only the visual layer riding on top of it.
