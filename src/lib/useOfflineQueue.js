import { useEffect, useState } from 'react'
import { flushOfflineQueue, getQueueSize, onQueueChange } from './offlineQueue'

/** Flushes the offline queue on mount and whenever the connection comes back, and returns the current pending count. */
export function useOfflineQueue() {
  const [size, setSize] = useState(getQueueSize)

  useEffect(() => {
    const unsubscribe = onQueueChange(setSize)
    flushOfflineQueue()

    function handleOnline() {
      flushOfflineQueue()
    }
    window.addEventListener('online', handleOnline)

    return () => {
      unsubscribe()
      window.removeEventListener('online', handleOnline)
    }
  }, [])

  return size
}
