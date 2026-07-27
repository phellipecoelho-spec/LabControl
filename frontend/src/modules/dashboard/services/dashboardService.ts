import { api } from '@/services/api'
import type { DashboardData } from '../types/dashboard'

export const dashboardService = {
  async fetch(params?: { start_date?: string; end_date?: string }): Promise<DashboardData> {
    const response = await api.get('/dashboard', { params })
    return response.data
  },
}
