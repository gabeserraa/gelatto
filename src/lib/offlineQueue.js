import { supabase } from './supabaseClient'

const STORAGE_KEY = 'gelatto-offline-queue'
const MAX_ATTEMPTS = 8

function readQueue() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]')
  } catch {
    return []
  }
}

function writeQueue(queue) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(queue))
  } catch {
    // Storage full/unavailable — the queued item just made is lost. Nothing
    // more we can do client-side; the user still sees the normal error path.
  }
  for (const fn of listeners) fn(queue.length)
}

const listeners = new Set()

export function onQueueChange(fn) {
  listeners.add(fn)
  return () => listeners.delete(fn)
}

export function getQueueSize() {
  return readQueue().length
}

/**
 * Called when a save fails because the device is offline. Stores the
 * write so it can be retried automatically once the connection is back —
 * so a venda/ajuste typed at a point with bad signal isn't lost.
 */
export function enqueueOffline({ table, operation, rowId, payload }) {
  const queue = readQueue()
  queue.push({
    id: crypto.randomUUID(),
    table,
    operation,
    rowId: rowId ?? null,
    payload,
    attempts: 0,
    createdAt: new Date().toISOString(),
  })
  writeQueue(queue)
}

let flushing = false

export async function flushOfflineQueue() {
  if (flushing || !navigator.onLine) return
  const queue = readQueue()
  if (queue.length === 0) return

  flushing = true
  try {
    const remaining = []
    for (const item of queue) {
      try {
        const table = supabase.from(item.table)
        const { error } =
          item.operation === 'update'
            ? await table.update(item.payload).eq('id', item.rowId)
            : await table.insert(item.payload)
        if (error) throw error
      } catch {
        item.attempts += 1
        if (item.attempts < MAX_ATTEMPTS) {
          remaining.push(item)
        }
        // After repeated failures (likely invalid data, not a connectivity
        // issue) the item is dropped rather than blocking the queue forever.
      }
    }
    writeQueue(remaining)
  } finally {
    flushing = false
  }
}
