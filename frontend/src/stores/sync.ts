import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { db } from '@/db'
import { SyncService } from '@/services/sync'

export const useSyncStore = defineStore('sync', () => {
  // ---------------------------------------------------------------------------
  // State
  // ---------------------------------------------------------------------------

  /** Number of pending operations in the sync queue (D-06, D-08). */
  const pendingCount = ref(0)

  /** ISO timestamp of the last successful sync (D-10). */
  const lastSyncAt = ref<string | null>(null)

  /** Whether a manual sync is currently in progress (prevents concurrent runs). */
  const isSyncing = ref(false)

  /** Whether the browser currently reports online status. */
  const isOnline = ref(navigator.onLine)

  /** Whether there are unresolved conflicts that need attention. */
  const hasConflicts = ref(false)

  // ---------------------------------------------------------------------------
  // Getters
  // ---------------------------------------------------------------------------

  /** True when there are pending operations to sync (D-08). */
  const hasPending = computed(() => pendingCount.value > 0)

  /** Human-readable pending label (D-06). */
  const pendingLabel = computed(() => `${pendingCount.value} operações pendentes`)

  // ---------------------------------------------------------------------------
  // Actions
  // ---------------------------------------------------------------------------

  /**
   * Refresh pendingCount from Dexie.
   */
  async function refreshPendingCount(): Promise<void> {
    pendingCount.value = await db.syncQueue.count()
  }

  /**
   * Load the lastSyncAt timestamp from Dexie syncMeta.
   */
  async function loadLastSyncAt(): Promise<void> {
    const meta = await db.syncMeta.get({ key: 'lastSyncAt' })
    if (meta?.value) {
      lastSyncAt.value = meta.value
    }
  }

  /**
   * Trigger a full manual sync cycle (D-07).
   *
   * 1. Replays all pending operations via SyncService.replayQueue()
   * 2. Auto-resolves stale conflicts older than 7 days (D-22)
   * 3. Refreshes pending count
   * 4. Persists the sync timestamp
   */
  async function manualSync(): Promise<void> {
    if (isSyncing.value) return

    isSyncing.value = true

    try {
      const result = await SyncService.replayQueue()

      // Auto-resolve conflicts older than 7 days (D-22)
      const autoResolved = await SyncService.autoResolveStaleConflicts(7)

      // Update conflict status
      if (result.conflicts.length > 0) {
        hasConflicts.value = true
      }

      // Refresh pending count (should be 0 if all succeeded)
      await refreshPendingCount()

      // Persist sync timestamp
      const now = new Date().toISOString()
      lastSyncAt.value = now
      await db.syncMeta.put({ key: 'lastSyncAt', value: now })

      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      const _ = { result, autoResolved } // used for future notification integration
    } catch (err) {
      // SyncAuthError means session expired — caller should handle
      if ((err as Error)?.name === 'SyncAuthError') {
        // Re-throw so App.vue can notify user
        throw err
      }
      // Other errors are logged but don't prevent future syncs
    } finally {
      isSyncing.value = false
    }
  }

  /**
   * Convenience wrapper to dismiss a conflict with a simple resolution.
   * For manual-merge, use SyncService.resolveConflict directly.
   */
  async function dismissConflict(
    conflictId: number,
    resolution: 'keep-local' | 'keep-server',
  ): Promise<void> {
    await SyncService.resolveConflict(conflictId, resolution)
    // Check if any pending conflicts remain
    const remaining = await db.conflicts.where('status').equals('pending').count()
    if (remaining === 0) {
      hasConflicts.value = false
    }
  }

  /**
   * Update isOnline state (called from App.vue's useOnline subscription).
   */
  function setOnlineStatus(online: boolean): void {
    isOnline.value = online
  }

  /**
   * Check and update hasConflicts from the Dexie table.
   */
  async function refreshConflicts(): Promise<void> {
    const count = await db.conflicts.where('status').equals('pending').count()
    hasConflicts.value = count > 0
  }

  // ---------------------------------------------------------------------------
  // Initialize — called once when the store is first created
  // ---------------------------------------------------------------------------

  refreshPendingCount()
  loadLastSyncAt()
  refreshConflicts()

  return {
    // State
    pendingCount,
    lastSyncAt,
    isSyncing,
    isOnline,
    hasConflicts,
    // Getters
    hasPending,
    pendingLabel,
    // Actions
    refreshPendingCount,
    loadLastSyncAt,
    manualSync,
    dismissConflict,
    setOnlineStatus,
    refreshConflicts,
  }
})
