import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type {
  MaintenanceOrder,
  OpenMaintenanceFormData,
  CloseMaintenanceFormData,
} from '../types/maintenance'
import type { Equipment } from '@/modules/equipment/types/equipment'

interface Pagination {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

export const useMaintenanceStore = defineStore('maintenance', () => {
  const orders = ref<MaintenanceOrder[]>([])
  const currentOrder = ref<MaintenanceOrder | null>(null)
  const loading = ref(false)
  const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  })
  const equipment = ref<Equipment[]>([])

  async function fetchAll(params?: Record<string, any>) {
    loading.value = true
    try {
      const response = await api.get('/maintenance-orders', { params })
      const data = response.data
      if (Array.isArray(data)) {
        orders.value = data
      } else if (data.data) {
        orders.value = data.data
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

  async function fetchById(id: string) {
    loading.value = true
    try {
      const response = await api.get(`/maintenance-orders/${id}`)
      currentOrder.value = response.data?.data ?? response.data
      return currentOrder.value
    } finally {
      loading.value = false
    }
  }

  async function create(data: OpenMaintenanceFormData) {
    const response = await api.post('/maintenance-orders', data)
    // Dispatch custom event for tab refresh
    window.dispatchEvent(new CustomEvent('maintenance-saved', {
      detail: { equipmentId: data.equipment_id },
    }))
    return response.data
  }

  async function update(id: string, data: Partial<OpenMaintenanceFormData>) {
    const response = await api.put(`/maintenance-orders/${id}`, data)
    return response.data
  }

  async function destroy(id: string) {
    await api.delete(`/maintenance-orders/${id}`)
  }

  async function complete(id: string, data: CloseMaintenanceFormData) {
    const response = await api.post(`/maintenance-orders/${id}/complete`, data)
    // Dispatch custom event for tab refresh
    window.dispatchEvent(new CustomEvent('maintenance-saved', {
      detail: { equipmentId: response.data?.data?.equipment?.id },
    }))
    return response.data
  }

  async function cancel(id: string, reason?: string) {
    const response = await api.post(`/maintenance-orders/${id}/cancel`, { reason })
    return response.data
  }

  async function fetchEquipment(params?: Record<string, any>) {
    const response = await api.get('/equipments', { params })
    equipment.value = response.data?.data ?? response.data ?? []
    return equipment.value
  }

  async function fetchHistoryByEquipment(equipmentId: string, params?: { page?: number; per_page?: number }) {
    const response = await api.get(`/equipments/${equipmentId}/maintenance`, { params })
    return response.data
  }

  return {
    orders,
    currentOrder,
    loading,
    pagination,
    equipment,
    fetchAll,
    fetchById,
    create,
    update,
    destroy,
    complete,
    cancel,
    fetchEquipment,
    fetchHistoryByEquipment,
  }
})
