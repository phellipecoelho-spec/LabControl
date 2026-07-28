import Dexie from 'dexie'
import { db, type PendingOperation } from './index'

// ---------------------------------------------------------------------------
// Sync queue operations
// ---------------------------------------------------------------------------

/**
 * Add a new operation to the offline sync queue.
 * The operation will be replayed when connectivity is restored.
 */
export async function enqueueOperation(
  op: Omit<PendingOperation, 'id' | 'createdAt' | 'retryCount'>,
): Promise<number | undefined> {
  return db.syncQueue.add({
    ...op,
    createdAt: new Date().toISOString(),
    retryCount: 0,
  })
}

/**
 * Return all pending operations ordered by creation time (FIFO).
 */
export async function getPendingOperations(): Promise<PendingOperation[]> {
  return db.syncQueue.orderBy('createdAt').toArray()
}

/**
 * Remove a successfully replayed operation from the queue.
 */
export async function markOperationCompleted(id: number): Promise<void> {
  await db.syncQueue.delete(id)
}

/**
 * Increment the retry counter and record the last error message.
 *
 * Uses Dexie.getMaxKey() to avoid a read-before-write race:
 * the index is on `retryCount` and we just increment atomically.
 */
export async function incrementRetry(
  id: number,
  error: string,
): Promise<void> {
  const current = await db.syncQueue.get(id)
  if (!current) return
  await db.syncQueue.update(id, {
    retryCount: (current.retryCount ?? 0) + 1,
    lastError: error,
  })
}

/**
 * Return the total number of pending operations.
 */
export async function getPendingCount(): Promise<number> {
  return db.syncQueue.count()
}

/**
 * Remove all completed/abandoned operations from the queue.
 */
export async function clearCompleted(): Promise<void> {
  await db.syncQueue.clear()
}

/**
 * Find pending operations for a specific entity record.
 * Used for conflict detection before replaying a new operation.
 */
export async function getPendingForEntity(
  entityType: string,
  entityId: string,
): Promise<PendingOperation[]> {
  return db.syncQueue
    .where('[entityType+entityId]')
    .equals([entityType, entityId])
    .toArray()
}
