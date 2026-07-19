import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'

export interface ActivityLog {
  id: string
  user_id: string | null
  action: string
  module: string
  table_name: string | null
  record_id: string | null
  old_values: Record<string, any> | null
  new_values: Record<string, any> | null
  ip_address: string | null
  user_agent: string | null
  created_at: string
  user?: { id: string; name: string; email: string }
}

export interface ActivityLogFilters {
  module?: string
  action?: string
  user_id?: string
  date_from?: string
  date_to?: string
  page?: number
  per_page?: number
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

export const useActivityLogsStore = defineStore('activityLogs', () => {
  const logs = ref<ActivityLog[]>([])
  const loading = ref(false)
  const pagination = ref<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 50,
  })

  async function fetchAll(params?: ActivityLogFilters) {
    loading.value = true
    try {
      const response = await api.get('/logs', { params })
      const data = response.data
      if (Array.isArray(data)) {
        logs.value = data
      } else if (data.data) {
        logs.value = data.data
        pagination.value = {
          current_page: data.current_page,
          last_page: data.last_page,
          total: data.total,
          per_page: data.per_page,
        }
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchModules(): Promise<string[]> {
    const response = await api.get('/logs/modules/list')
    return response.data
  }

  return {
    logs,
    loading,
    pagination,
    fetchAll,
    fetchModules,
  }
})
