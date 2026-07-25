export type MaintenanceType = 'preventive' | 'corrective'
export type MaintenanceStatus = 'open' | 'in_progress' | 'completed' | 'cancelled'
export type MaintenancePriority = 'low' | 'medium' | 'high' | 'critical'

export interface MaintenanceOrderPart {
  id: string
  inventory_item_id: string
  item_name?: string
  quantity: number
  unit_cost: number | null
}

export interface MaintenanceOrder {
  id: string
  type: MaintenanceType
  type_label: string
  status: MaintenanceStatus
  status_label: string
  priority: MaintenancePriority
  priority_label: string
  description: string
  scheduled_date: string | null
  completed_at: string | null
  resolution: string | null
  time_spent: number | null
  cost: number | null
  interval_value: number | null
  interval_unit: string | null
  next_due_at: string | null
  notes: string | null
  equipment: { id: string; name: string; patrimony_id?: string } | null
  assigned_to: { id: string; name: string } | null
  opened_by: { id: string; name: string } | null
  parts: MaintenanceOrderPart[]
  created_by: { id: string; name: string } | null
  created_at: string
  updated_at: string
}

export interface OpenMaintenanceFormData {
  equipment_id: string
  type: MaintenanceType
  priority: MaintenancePriority
  description: string
  scheduled_date?: string
  interval_value?: number
  interval_unit?: string
}

export interface CloseMaintenanceFormData {
  resolution?: string
  time_spent?: number
  cost?: number
  completed_at?: string
  parts?: { inventory_item_id: string; quantity: number; unit_cost?: number }[]
}

export const MAINTENANCE_TYPE_OPTIONS = [
  { label: 'Preventiva', value: 'preventive' },
  { label: 'Corretiva', value: 'corrective' },
]

export const MAINTENANCE_STATUS_OPTIONS = [
  { label: 'Aberta', value: 'open' },
  { label: 'Em Andamento', value: 'in_progress' },
  { label: 'Concluída', value: 'completed' },
  { label: 'Cancelada', value: 'cancelled' },
]

export const MAINTENANCE_PRIORITY_OPTIONS = [
  { label: 'Baixa', value: 'low' },
  { label: 'Média', value: 'medium' },
  { label: 'Alta', value: 'high' },
  { label: 'Crítica', value: 'critical' },
]

export const INTERVAL_UNIT_OPTIONS = [
  { label: 'Meses', value: 'months' },
  { label: 'Dias', value: 'days' },
  { label: 'Horas', value: 'hours' },
]
