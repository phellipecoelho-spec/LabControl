export interface InventoryCategory {
  id: string
  name: string
  slug: string
}

export interface InventoryItem {
  id: string
  name: string
  code?: string
  description?: string
  unit: string
  min_stock: number
  batch_lot?: string
  expiry_date?: string
  physical_location?: string
  current_balance: number
  is_critical: boolean
  category?: InventoryCategory
  supplier?: import('@/modules/equipment/types/equipment').Supplier
  created_at: string
  updated_at: string
}

export interface InventoryItemFormData {
  name: string
  code?: string
  description?: string
  category_id: string
  supplier_id?: string
  unit: string
  min_stock: number
  batch_lot?: string
  expiry_date?: string | Date
  physical_location?: string
  initial_quantity?: number
}

export type MovementType = 'purchase' | 'consumption' | 'adjustment' | 'disposal' | 'return'

export interface InventoryMovement {
  id: string
  item_id: string
  type: MovementType
  quantity: number
  quantity_display: number
  balance_after: number
  reason?: string
  notes?: string
  user?: { id: string; name: string }
  item?: Pick<InventoryItem, 'id' | 'name' | 'code'>
  created_at: string
}

export interface InventoryMovementFormData {
  item_id: string
  type: MovementType
  quantity: number
  reason?: string
  notes?: string
}

// Constants for the fixed unit list (D-16)
export const INVENTORY_UNITS = [
  { label: 'UN', value: 'UN' },
  { label: 'KG', value: 'KG' },
  { label: 'L', value: 'L' },
  { label: 'CX', value: 'CX' },
  { label: 'M', value: 'M' },
  { label: 'M²', value: 'M2' },
  { label: 'M³', value: 'M3' },
  { label: 'PC', value: 'PC' },
  { label: 'PCT', value: 'PCT' },
  { label: 'CJ', value: 'CJ' },
] as const

// Movement type options with labels for UI display
export const MOVEMENT_TYPE_OPTIONS = [
  { label: 'Compra', value: 'purchase' },
  { label: 'Consumo', value: 'consumption' },
  { label: 'Ajuste', value: 'adjustment' },
  { label: 'Descarte', value: 'disposal' },
  { label: 'Devolução', value: 'return' },
] as const
