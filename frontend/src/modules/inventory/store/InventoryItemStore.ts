import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type { InventoryItem, InventoryCategory } from '../types/inventory'
import type { Supplier } from '@/modules/equipment/types/equipment'

interface Pagination {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

interface FetchAllParams {
  page?: number
  search?: string
  category_id?: string
  unit?: string
  critical?: string
  per_page?: number
}

export const useInventoryItemStore = defineStore('inventoryItem', () => {
  const items = ref<InventoryItem[]>([])
  const loading = ref(false)
  const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  })
  const categories = ref<InventoryCategory[]>([])
  const suppliers = ref<Supplier[]>([])

  async function fetchAll(params?: FetchAllParams) {
    loading.value = true
    try {
      const response = await api.get('/inventory-items', { params })
      const data = response.data
      if (Array.isArray(data)) {
        items.value = data
      } else if (data.data) {
        items.value = data.data
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
    const response = await api.get(`/inventory-items/${id}`)
    return response.data
  }

  async function create(data: Record<string, any>) {
    const response = await api.post('/inventory-items', data)
    return response.data
  }

  async function update(id: string, data: Record<string, any>) {
    const response = await api.put(`/inventory-items/${id}`, data)
    return response.data
  }

  async function destroy(id: string) {
    const response = await api.delete(`/inventory-items/${id}`)
    return response.data
  }

  async function fetchCategories(params?: Record<string, any>) {
    const response = await api.get('/inventory-categories', { params })
    categories.value = response.data.data || response.data || []
    return categories.value
  }

  async function fetchSuppliers(params?: Record<string, any>) {
    const response = await api.get('/suppliers', { params })
    suppliers.value = response.data.data || response.data || []
    return suppliers.value
  }

  async function fetchItemMovements(itemId: string, params?: Record<string, any>) {
    const response = await api.get(`/inventory-items/${itemId}/movements`, { params })
    return response.data
  }

  return {
    items,
    loading,
    pagination,
    categories,
    suppliers,
    fetchAll,
    fetchById,
    create,
    update,
    destroy,
    fetchCategories,
    fetchSuppliers,
    fetchItemMovements,
  }
})
