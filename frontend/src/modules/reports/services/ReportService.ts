import { api } from '@/services/api'
import type { ReportListResponse, ReportType, ReportFormat, ReportFilters } from '../types/report'

export const reportService = {
  async getReportList(): Promise<ReportListResponse> {
    const response = await api.get('/reports')
    return response.data
  },

  getDownloadUrl(type: ReportType, format: ReportFormat, filters?: ReportFilters): string {
    const params = new URLSearchParams({ format })
    if (filters?.date_from) params.append('date_from', filters.date_from)
    if (filters?.date_to) params.append('date_to', filters.date_to)
    if (filters?.status) params.append('status', filters.status)
    return `/api/v1/reports/${type}?${params}`
  }
}
