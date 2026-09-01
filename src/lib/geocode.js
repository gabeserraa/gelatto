/**
 * Busca latitude/longitude a partir de um endereço via Nominatim
 * (OpenStreetMap) — mesmo provedor já usado pro mapa em Pontos, gratuito e
 * sem chave de API. Retorna null se não encontrar nada.
 */
export async function geocodeAddress(query) {
  if (!query?.trim()) return null

  const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`
  const res = await fetch(url, { headers: { Accept: 'application/json' } })
  if (!res.ok) throw new Error('Não foi possível buscar o endereço.')

  const results = await res.json()
  if (!results?.length) return null

  return { latitude: Number(results[0].lat), longitude: Number(results[0].lon) }
}
