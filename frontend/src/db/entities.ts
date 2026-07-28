import { db } from './index'

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

/** All entity table names supported by the offline cache. */
export type EntityName =
  | 'equipment'
  | 'inventoryItems'
  | 'loans'
  | 'calibrations'
  | 'maintenanceOrders'
  | 'verifications'

// ---------------------------------------------------------------------------
// Generic CRUD helpers
// ---------------------------------------------------------------------------

/**
 * Upsert (bulk put) a batch of records into the given entity table.
 * Records are matched by their `id` field — existing records are updated,
 * new records are inserted.
 */
export async function cacheEntity(
  tableName: EntityName,
  records: Record<string, unknown>[],
): Promise<void> {
  await db.table(tableName).bulkPut(records)
}

/**
 * Retrieve a single cached record by id.
 */
export async function getCachedEntity<T = Record<string, unknown>>(
  tableName: EntityName,
  id: string,
): Promise<T | undefined> {
  return (await db.table(tableName).get(id)) as T | undefined
}

/**
 * Retrieve all cached records from a given entity table.
 */
export async function getAllCached<T = Record<string, unknown>>(
  tableName: EntityName,
): Promise<T[]> {
  return (await db.table(tableName).toArray()) as T[]
}

/**
 * Retrieve records updated after an ISO timestamp (inclusive).
 */
export async function getCachedSince<T = Record<string, unknown>>(
  tableName: EntityName,
  since: string,
): Promise<T[]> {
  return (await db
    .table(tableName)
    .where('updatedAt')
    .aboveOrEqual(since)
    .toArray()) as T[]
}

/**
 * Clear all records from a given entity table.
 */
export async function clearEntityCache(tableName: EntityName): Promise<void> {
  await db.table(tableName).clear()
}

// ---------------------------------------------------------------------------
// API response helpers
// ---------------------------------------------------------------------------

/**
 * Convenience helper that accepts the raw API response array and caches it.
 * Useful right after fetching a paginated list from the server.
 */
export async function cacheApiListResponse(
  entityName: EntityName,
  items: Record<string, unknown>[],
): Promise<void> {
  if (items.length === 0) return
  await cacheEntity(entityName, items)
}
