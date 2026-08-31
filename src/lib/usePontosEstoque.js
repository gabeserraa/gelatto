import { useCallback, useEffect, useState } from 'react'
import { supabase } from './supabaseClient'

export function usePontosEstoque() {
  const [pontos, setPontos] = useState([])
  const [loading, setLoading] = useState(true)

  const refresh = useCallback(async () => {
    setLoading(true)
    const { data } = await supabase.from('v_pontos_estoque').select('*').order('nome')
    setPontos(data ?? [])
    setLoading(false)
  }, [])

  useEffect(() => {
    refresh()
  }, [refresh])

  return { pontos, loading, refresh }
}
