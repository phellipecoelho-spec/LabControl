import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type { Equipment, Category, Manufacturer, Supplier } from '../types/equipment'

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
  status?: string
  per_page?: number
}

export const useEquipmentStore = defineStore('equipment', () => {
  const equipments = ref<Equipment[]>([])
  const loading = ref(false)
  const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  })
  const categories = ref<Category[]>([])
  const manufacturers = ref<Manufacturer[]>([])
  const suppliers = ref<Supplier[]>([])

  async function fetchAll(params?: FetchAllParams) {
    loading.value = true
    try {
      const response = await api.get('/equipments', { params })
      const data = response.data
      if (Array.isArray(data)) {
        equipments.value = data
      } else if (data.data) {
        equipments.value = data.data
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
    const response = await api.get(`/equipments/${id}`)
    return response.data
  }

  async function create(data: Record<string, any>) {
    const response = await api.post('/equipments', data)
    return response.data
  }

  async function update(id: string, data: Record<string, any>) {
    const response = await api.put(`/equipments/${id}`, data)
    return response.data
  }

  async function destroy(id: string) {
    const response = await api.delete(`/equipments/${id}`)
    return response.data
  }

  async function fetchCategories(params?: Record<string, any>) {
    const response = await api.get('/categories', { params })
    categories.value = response.data.data || response.data || []
    return categories.value
  }

  async function fetchManufacturers(params?: Record<string, any>) {
    const response = await api.get('/manufacturers', { params })
    manufacturers.value = response.data.data || response.data || []
    return manufacturers.value
  }

  async function fetchSuppliers(params?: Record<string, any>) {
    const response = await api.get('/suppliers', { params })
    suppliers.value = response.data.data || response.data || []
    return suppliers.value
  }

  return {
    equipments,
    loading,
    pagination,
    categories,
    manufacturers,
    suppliers,
    fetchAll,
    fetchById,
    create,
    update,
    destroy,
    fetchCategories,
    fetchManufacturers,
    fetchSuppliers,
  }
})