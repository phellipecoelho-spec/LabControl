export type ReportFormat = 'pdf' | 'xlsx' | 'csv'

export type ReportType = 'equipments' | 'calibrations' | 'inventory-movements' | 'dashboard'

export interface ReportMeta {
  type: ReportType
  label: string
  description: string
  icon: string
  formats: ReportFormat[]
  defaultFormat: ReportFormat
}

export interface ReportFilters {
  date_from?: string
  date_to?: string
  status?: string
}

export interface ReportListResponse {
  reports: ReportMeta[]
}
