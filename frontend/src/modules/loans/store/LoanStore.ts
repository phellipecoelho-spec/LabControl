import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type { Loan, LoanFormData, ReturnItemFormData, UserSummary } from '../types/loan'
import type { Equipment } from '@/modules/equipment/types/equipment'

interface Pagination {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

interface FetchAllParams {
  page?: number
  search?: string
  status?: string
  equipment_id?: string
  borrower_id?: string
  per_page?: number
}

export const useLoanStore = defineStore('loan', () => {
  const loans = ref<Loan[]>([])
  const currentLoan = ref<Loan | null>(null)
  const loading = ref(false)
  const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  })
  const users = ref<UserSummary[]>([])
  const equipment = ref<Equipment[]>([])

  async function fetchAll(params?: FetchAllParams) {
    loading.value = true
    try {
      const response = await api.get('/loans', { params })
      const data = response.data
      if (Array.isArray(data)) {
        loans.value = data
      } else if (data.data) {
        loans.value = data.data
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
      const response = await api.get(`/loans/${id}`)
      currentLoan.value = response.data?.data ?? response.data
      return currentLoan.value
    } finally {
      loading.value = false
    }
  }

  async function create(data: LoanFormData) {
    const response = await api.post('/loans', data)
    return response.data
  }

  async function update(id: string, data: Partial<LoanFormData>) {
    const response = await api.put(`/loans/${id}`, data)
    return response.data
  }

  async function destroy(id: string) {
    const response = await api.delete(`/loans/${id}`)
    return response.data
  }

  async function activate(id: string) {
    const response = await api.post(`/loans/${id}/activate`)
    return response.data
  }

  async function returnItem(id: string, data: ReturnItemFormData) {
    const response = await api.post(`/loans/${id}/return`, data)
    return response.data
  }

  async function cancel(id: string) {
    const response = await api.post(`/loans/${id}/cancel`)
    return response.data
  }

  async function fetchUsers(params?: Record<string, any>) {
    const response = await api.get('/users', { params })
    users.value = response.data?.data ?? response.data ?? []
    return users.value
  }

  async function fetchEquipment(params?: Record<string, any>) {
    const response = await api.get('/equipments', { params })
    equipment.value = response.data?.data ?? response.data ?? []
    return equipment.value
  }

  return {
    loans,
    currentLoan,
    loading,
    pagination,
    users,
    equipment,
    fetchAll,
    fetchById,
    create,
    update,
    destroy,
    activate,
    returnItem,
    cancel,
    fetchUsers,
    fetchEquipment,
  }
})
