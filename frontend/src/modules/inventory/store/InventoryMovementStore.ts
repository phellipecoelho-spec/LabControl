import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type { InventoryMovement } from '../types/inventory'

interface Pagination {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

interface FetchAllParams {
  page?: number
  search?: string
  type?: string
  from?: string
  to?: string
  user_id?: string
  item_id?: string
  per_page?: number
}

export const useInventoryMovementStore = defineStore('inventoryMovement', () => {
  const movements = ref<InventoryMovement[]>([])
  const loading = ref(false)
  const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  })
  const lastCreatedCritical = ref(false)

  async function fetchAll(params?: FetchAllParams) {
    loading.value = true
    try {
      const response = await api.get('/inventory-movements', { params })
      const data = response.data
      if (Array.isArray(data)) {
        movements.value = data
      } else if (data.data) {
        movements.value = data.data
        pagination.value = {
          current_page: data.current_page ?? 1,
          last_page: data.last_page ?? 1,
          total: data.total ?? 0,
          per_page: data.per_page ?? 15,
        }
      }
    } finally {
      loading.value = false
    }
  }

  async function create(data: Record<string, any>) {
    const response = await api.post('/inventory-movements', data)
    const result = response.data
    lastCreatedCritical.value = result.meta?.is_critical ?? false
    return result
  }

  async function getById(id: string) {
    const response = await api.get(`/inventory-movements/${id}`)
    return response.data
  }

  return {
    movements,
    loading,
    pagination,
    lastCreatedCritical,
    fetchAll,
    create,
    getById,
  }
})
