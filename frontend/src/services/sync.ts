import { db, type PendingOperation, type ConflictRecord } from '@/db'

// ---------------------------------------------------------------------------
// SyncAuthError — signals session expiry during replay
// ---------------------------------------------------------------------------

export class SyncAuthError extends Error {
  constructor(message: string) {
    super(message)
    this.name = 'SyncAuthError'
  }
}

// ---------------------------------------------------------------------------
// Cookie helper — reads the XSRF-TOKEN for API requests
// ---------------------------------------------------------------------------

function getCookie(name: string): string | null {
  const match = document.cookie.match(new RegExp(`(?:^|; )${name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}=([^;]*)`))
  return match ? decodeURIComponent(match[1]) : null
}

// ---------------------------------------------------------------------------
// SyncService — replays the offline queue and resolves conflicts
// ---------------------------------------------------------------------------

export class SyncService {
  /**
   * Replay all pending operations from the sync queue.
   * Returns a summary of succeeded, failed and conflicted operations.
   *
   * - 200/201/204 → delete from queue
   * - 409         → detect and store conflict, delete from queue
   * - 401         → throw SyncAuthError (caller pauses the queue)
   * - 5xx         → increment retry, continue
   * - Network err → increment retry, break
   */
  static async replayQueue(): Promise<{ succeeded: number; failed: number; conflicts: ConflictRecord[] }> {
    const ops = await db.syncQueue.orderBy('createdAt').toArray()
    let succeeded = 0
    let failed = 0
    const conflicts: ConflictRecord[] = []

    for (const op of ops) {
      try {
        const headers: Record<string, string> = {
          'Content-Type': 'application/json',
          'X-Sync-Engine': 'true',
        }
        const xsrf = getCookie('XSRF-TOKEN')
        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf

        const fetchOptions: RequestInit = {
          method: op.method,
          headers,
          credentials: 'include',
        }

        if (op.method !== 'DELETE' && op.payload) {
          fetchOptions.body = JSON.stringify(op.payload)
        }

        const response = await fetch(op.endpoint, fetchOptions)

        if (response.status === 200 || response.status === 201 || response.status === 204) {
          await db.syncQueue.delete(op.id!)
          succeeded++
          continue
        }

        if (response.status === 409) {
          const body = await response.json().catch(() => ({}))
          const conflict = await SyncService.detectAndStoreConflict(
            op,
            body.serverVersion ?? body.server_version ?? {},
            body.baseVersion ?? body.base_version ?? {},
          )
          await db.syncQueue.delete(op.id!)
          if (conflict) conflicts.push(conflict)
          continue
        }

        if (response.status === 401) {
          throw new SyncAuthError('Session expired — re-authentication required')
        }

        if (response.status >= 500) {
          await db.syncQueue.update(op.id!, {
            retryCount: (op.retryCount ?? 0) + 1,
            lastError: `HTTP ${response.status}: ${response.statusText}`,
          })
          failed++
          continue
        }

        // Any other unexpected status
        await db.syncQueue.update(op.id!, {
          retryCount: (op.retryCount ?? 0) + 1,
          lastError: `Unexpected HTTP ${response.status}`,
        })
        failed++
      } catch (err) {
        if (err instanceof SyncAuthError) throw err

        // Network error — increment retry, then break (stop further processing)
        await db.syncQueue.update(op.id!, {
          retryCount: (op.retryCount ?? 0) + 1,
          lastError: err instanceof Error ? err.message : 'Network error',
        })
        failed++
        break
      }
    }

    return { succeeded, failed, conflicts }
  }

  /**
   * Field-level diff between local payload and server version.
   *
   * - If ALL differing fields match the base version (server changed but user didn't),
   *   auto-resolve by updating local cache with server version.
   * - If some fields genuinely conflict, create a conflict record.
   * - If no conflicting fields (different fields changed per D-21), auto-merge.
   */
  static async detectAndStoreConflict(
    op: PendingOperation,
    serverVersion: Record<string, unknown>,
    baseVersion: Record<string, unknown>,
  ): Promise<ConflictRecord | null> {
    const localVersion = op.payload
    const diffFields: { field: string; local: unknown; server: unknown; base: unknown }[] = []
    const allKeys = new Set([...Object.keys(localVersion), ...Object.keys(serverVersion)])

    for (const key of allKeys) {
      const localVal = key in localVersion ? localVersion[key] : undefined
      const serverVal = key in serverVersion ? serverVersion[key] : undefined

      // Values are equal — no conflict
      if (JSON.stringify(localVal) === JSON.stringify(serverVal)) continue

      const baseVal = key in baseVersion ? baseVersion[key] : undefined
      diffFields.push({ field: key, local: localVal, server: serverVal, base: baseVal })
    }

    if (diffFields.length === 0) {
      // No differences at all — nothing to do
      return null
    }

    // Check if ALL differences are only on the server side (local === base)
    const onlyServerChanged = diffFields.every((f) => JSON.stringify(f.local) === JSON.stringify(f.base))
    if (onlyServerChanged) {
      // Auto-resolve: update local cache with server version
      const tableName = SyncService.entityTypeToTableName(op.entityType)
      try {
        await db.table(tableName).put(serverVersion, serverVersion.id as string)
      } catch {
        // Entity cache update is best-effort
      }
      return null
    }

    // Check if there are conflicting fields (both local and server changed same field differently)
    const conflictingFields = diffFields.filter(
      (f) =>
        JSON.stringify(f.local) !== JSON.stringify(f.base) &&
        JSON.stringify(f.server) !== JSON.stringify(f.base),
    )

    if (conflictingFields.length === 0) {
      // No conflicting fields (D-21): different fields changed — auto-merge
      const merged = { ...serverVersion, ...localVersion }
      try {
        const response = await fetch(op.endpoint, {
          method: op.method,
          headers: {
            'Content-Type': 'application/json',
            'X-Sync-Engine': 'true',
            ...(getCookie('XSRF-TOKEN') ? { 'X-XSRF-TOKEN': getCookie('XSRF-TOKEN')! } : {}),
          },
          credentials: 'include',
          body: JSON.stringify(merged),
        })
        if (response.ok) {
          const tableName = SyncService.entityTypeToTableName(op.entityType)
          try {
            await db.table(tableName).put(merged, merged.id as string)
          } catch {
            // best-effort
          }
        }
      } catch {
        // Merge PUT failed best-effort
      }
      return null
    }

    // Genuine conflict — store conflict record
    const conflictRecord: ConflictRecord = {
      entityType: op.entityType,
      entityId: op.entityId,
      localVersion: { ...localVersion },
      serverVersion: { ...serverVersion },
      baseVersion: { ...baseVersion },
      detectedAt: new Date().toISOString(),
      status: 'pending',
    }

    const id = await db.conflicts.add(conflictRecord)
    return { ...conflictRecord, id }
  }

  /**
   * Auto-resolve stale conflicts older than N days (D-22).
   * Uses last-write-wins strategy (keep-server).
   */
  static async autoResolveStaleConflicts(days: number = 7): Promise<number> {
    const cutoff = new Date(Date.now() - days * 24 * 60 * 60 * 1000).toISOString()
    const stale = await db.conflicts
      .where('status')
      .equals('pending')
      .and((c) => c.detectedAt < cutoff)
      .toArray()

    for (const conflict of stale) {
      await SyncService.resolveConflict(conflict.id!, 'keep-server')
    }

    return stale.length
  }

  /**
   * Resolve a conflict by the chosen strategy (D-20).
   *
   * - keep-local:    PUT localVersion to server, update local cache
   * - keep-server:   update local cache with serverVersion
   * - manual-merge:  PUT mergedPayload to server, update local cache
   */
  static async resolveConflict(
    conflictId: number,
    resolution: 'keep-local' | 'keep-server' | 'manual-merge',
    mergedPayload?: Record<string, unknown>,
  ): Promise<void> {
    const conflict = await db.conflicts.get(conflictId)
    if (!conflict) throw new Error(`Conflict ${conflictId} not found`)

    const tableName = SyncService.entityTypeToTableName(conflict.entityType)

    if (resolution === 'keep-local') {
      // PUT local version to server
      try {
        const headers: Record<string, string> = {
          'Content-Type': 'application/json',
          'X-Sync-Engine': 'true',
        }
        const xsrf = getCookie('XSRF-TOKEN')
        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf

        const response = await fetch(`/api/v1/${conflict.entityType}/${conflict.entityId}`, {
          method: 'PUT',
          headers,
          credentials: 'include',
          body: JSON.stringify(conflict.localVersion),
        })
        if (response.ok) {
          try {
            await db.table(tableName).put(conflict.localVersion, conflict.entityId)
          } catch {
            // best-effort
          }
        }
      } catch {
        // PUT failed — keep trying later? For now, resolve anyway
      }
    } else if (resolution === 'keep-server') {
      // Update local cache with server version
      try {
        await db.table(tableName).put(conflict.serverVersion, conflict.entityId)
      } catch {
        // best-effort
      }
    } else if (resolution === 'manual-merge' && mergedPayload) {
      // PUT merged payload to server
      try {
        const headers: Record<string, string> = {
          'Content-Type': 'application/json',
          'X-Sync-Engine': 'true',
        }
        const xsrf = getCookie('XSRF-TOKEN')
        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf

        const response = await fetch(`/api/v1/${conflict.entityType}/${conflict.entityId}`, {
          method: 'PUT',
          headers,
          credentials: 'include',
          body: JSON.stringify(mergedPayload),
        })
        if (response.ok) {
          try {
            await db.table(tableName).put(mergedPayload, conflict.entityId)
          } catch {
            // best-effort
          }
        }
      } catch {
        // PUT failed
      }
    }

    // Mark conflict as resolved
    await db.conflicts.update(conflictId, {
      status: 'resolved',
      resolvedAt: new Date().toISOString(),
      resolution,
    })
  }

  /**
   * Map entity type string to Dexie table name.
   * Used for updating local cached copies after sync/conflict resolution.
   */
  static entityTypeToTableName(entityType: string): string {
    const map: Record<string, string> = {
      equipment: 'equipment',
      'inventory-items': 'inventoryItems',
      'inventory-items-categories': 'inventoryItems',
      loans: 'loans',
      calibrations: 'calibrations',
      'maintenance-orders': 'maintenanceOrders',
      verifications: 'verifications',
    }
    return map[entityType] ?? entityType
  }
}
