export interface KpiData {
  total_equipments: number
  calibrations_due_soon: number
  active_loans: number
  pending_verifications_today: number
  open_maintenance_orders: number
}

export interface ChartCategoryItem {
  name: string
  value: number
}

export interface ChartTimelineItem {
  month: string
  scheduled: number
  completed: number
  due: number
}

export interface ChartMovementItem {
  month: string
  incoming: number
  outgoing: number
}

export interface ChartData {
  equipments_by_category: ChartCategoryItem[]
  calibrations_timeline: ChartTimelineItem[]
  stock_movements: ChartMovementItem[]
}

export interface DashboardData {
  kpis: KpiData
  charts: ChartData
}
