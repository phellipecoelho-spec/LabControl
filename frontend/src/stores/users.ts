import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type { User } from '@/stores/auth'

interface Pagination {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

interface FetchAllParams {
  page?: number
  search?: string
  role?: string
  is_active?: boolean
  per_page?: number
}

export const useUsersStore = defineStore('users', () => {
  const users = ref<User[]>([])
  const loading = ref(false)
  const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  })

  async function fetchAll(params?: FetchAllParams) {
    loading.value = true
    try {
      const response = await api.get('/users', { params })
      const data = response.data
      if (Array.isArray(data)) {
        users.value = data
      } else if (data.data) {
        users.value = data.data
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
    const response = await api.get(`/users/${id}`)
    return response.data
  }

  async function create(data: Partial<User> & { password?: string; password_confirmation?: string; roles?: string[] }) {
    const response = await api.post('/users', data)
    return response.data
  }

  async function update(id: string, data: Partial<User> & { password?: string; password_confirmation?: string; roles?: string[] }) {
    const response = await api.put(`/users/${id}`, data)
    return response.data
  }

  async function destroy(id: string) {
    const response = await api.delete(`/users/${id}`)
    return response.data
  }

  return {
    users,
    loading,
    pagination,
    fetchAll,
    fetchById,
    create,
    update,
    destroy,
  }
})
