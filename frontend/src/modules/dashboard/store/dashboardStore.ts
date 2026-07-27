import { defineStore } from 'pinia'
import { ref } from 'vue'
import { dashboardService } from '../services/dashboardService'
import type { DashboardData } from '../types/dashboard'

export const useDashboardStore = defineStore('dashboard', () => {
  const data = ref<DashboardData | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchData(params?: { start_date?: string; end_date?: string }) {
    loading.value = true
    error.value = null
    try {
      const result = await dashboardService.fetch(params)
      data.value = result
    } catch (err: any) {
      error.value = err?.response?.data?.message || err?.message || 'Erro ao carregar dashboard'
    } finally {
      loading.value = false
    }
  }

  return { data, loading, error, fetchData }
})
