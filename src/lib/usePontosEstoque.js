import { useCallback, useEffect, useState } from 'react'
import { supabase } from './supabaseClient'
import { useRealtimeRefresh } from './useRealtimeRefresh'

export function usePontosEstoque() {
  const [pontos, setPontos] = useState([])
  const [loading, setLoading] = useState(true)

  const fetchPontos = useCallback(async () => {
    const { data } = await supabase.from('v_pontos_estoque').select('*').order('nome')
    setPontos(data ?? [])
  }, [])

  const refresh = useCallback(async () => {
    setLoading(true)
    await fetchPontos()
    setLoading(false)
  }, [fetchPontos])

  useEffect(() => {
    refresh()
  }, [refresh])

  useRealtimeRefresh(['pontos', 'movimentacoes_estoque', 'ajustes_estoque'], fetchPontos)

  return { pontos, loading, refresh }
}
