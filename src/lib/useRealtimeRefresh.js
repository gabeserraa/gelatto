import { useEffect } from 'react'
import { supabase } from './supabaseClient'

/**
 * Subscribes to postgres_changes on the given tables and calls `onChange`
 * (debounced) whenever any row is inserted/updated/deleted — so both users
 * see each other's edits without a manual refresh. `onChange` should be a
 * stable reference (e.g. wrapped in useCallback) since it's only read on
 * the initial subscribe, not re-subscribed on every render.
 */
export function useRealtimeRefresh(tables, onChange) {
  const tablesKey = tables.join(',')

  useEffect(() => {
    let timeout = null
    const debouncedRefresh = () => {
      clearTimeout(timeout)
      timeout = setTimeout(onChange, 300)
    }

    const channel = supabase.channel(`realtime:${tablesKey}`)
    for (const table of tables) {
      channel.on('postgres_changes', { event: '*', schema: 'public', table }, debouncedRefresh)
    }
    channel.subscribe()

    return () => {
      clearTimeout(timeout)
      supabase.removeChannel(channel)
    }
  }, [tablesKey])
}
