import Dexie, { type EntityTable } from 'dexie'

// ---------------------------------------------------------------------------
// Interfaces
// ---------------------------------------------------------------------------

export interface PendingOperation {
  id?: number
  entityType: string
  entityId: string
  action: 'create' | 'update' | 'delete'
  endpoint: string
  method: 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  payload: Record<string, unknown>
  createdAt: string
  retryCount: number
  lastError?: string
}

export interface ConflictRecord {
  id?: number
  entityType: string
  entityId: string
  localVersion: Record<string, unknown>
  serverVersion: Record<string, unknown>
  baseVersion: Record<string, unknown>
  detectedAt: string
  status: 'pending' | 'resolved'
  resolvedAt?: string
  resolution?: 'keep-local' | 'keep-server' | 'manual-merge'
}

export interface SyncMeta {
  id: number
  key: string
  value: string
}

// ---------------------------------------------------------------------------
// Database class
// ---------------------------------------------------------------------------

export class LabControlDB extends Dexie {
  syncQueue!: EntityTable<PendingOperation, 'id'>
  conflicts!: EntityTable<ConflictRecord, 'id'>
  syncMeta!: EntityTable<SyncMeta, 'id'>

  // Entity caches — typed as generic Record until we integrate domain models
  equipment!: EntityTable<Record<string, unknown>, string>
  inventoryItems!: EntityTable<Record<string, unknown>, string>
  loans!: EntityTable<Record<string, unknown>, string>
  calibrations!: EntityTable<Record<string, unknown>, string>
  maintenanceOrders!: EntityTable<Record<string, unknown>, string>
  verifications!: EntityTable<Record<string, unknown>, string>

  constructor() {
    super('LabControl')

    this.version(1).stores({
      // Sync queue: pending offline mutations
      syncQueue: '++id, [entityType+entityId], action, createdAt, retryCount',
      // Conflict resolution records
      conflicts: '++id, [entityType+entityId], status',
      // Sync metadata (lastSyncAt, etc.)
      syncMeta: '++id, key',

      // Entity tables — all indexed on id and updatedAt for offline queries
      equipment: 'id, updatedAt',
      inventoryItems: 'id, updatedAt',
      loans: 'id, updatedAt',
      calibrations: 'id, updatedAt',
      maintenanceOrders: 'id, updatedAt',
      verifications: 'id, updatedAt',
    })
  }
}

// Singleton instance
export const db = new LabControlDB()
