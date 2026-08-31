import { MapContainer, TileLayer, CircleMarker, Popup } from 'react-leaflet'
import 'leaflet/dist/leaflet.css'
import { urgencyFromRatio } from './UrgencyBadge'
import { formatKg } from '../../lib/format'

const URGENCY_COLORS = { critico: '#ef4444', repor: '#f59e0b', ok: '#06b6d4' }

export default function PontosMap({ pontos }) {
  const withCoords = pontos.filter((p) => p.latitude != null && p.longitude != null)

  if (withCoords.length === 0) {
    return (
      <div className="flex h-96 items-center justify-center rounded-card border border-slate-200 bg-white text-sm text-slate-400 shadow-card">
        Nenhum ponto com latitude/longitude cadastrada ainda.
      </div>
    )
  }

  const center = [
    withCoords.reduce((s, p) => s + p.latitude, 0) / withCoords.length,
    withCoords.reduce((s, p) => s + p.longitude, 0) / withCoords.length,
  ]

  return (
    <div className="h-96 overflow-hidden rounded-card border border-slate-200 shadow-card">
      <MapContainer center={center} zoom={12} className="h-full w-full">
        <TileLayer
          attribution='&copy; OpenStreetMap contributors'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        {withCoords.map((p) => {
          const urgency = urgencyFromRatio(p.estoque_atual_kg, p.consumo_medio_dia)
          return (
            <CircleMarker
              key={p.id}
              center={[p.latitude, p.longitude]}
              radius={9}
              pathOptions={{ color: URGENCY_COLORS[urgency], fillColor: URGENCY_COLORS[urgency], fillOpacity: 0.8 }}
            >
              <Popup>
                <strong>{p.nome}</strong>
                <br />
                {formatKg(p.estoque_atual_kg)} / {formatKg(p.capacidade_kg)}
              </Popup>
            </CircleMarker>
          )
        })}
      </MapContainer>
    </div>
  )
}
