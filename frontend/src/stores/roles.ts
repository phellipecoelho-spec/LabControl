import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'

export interface Permission {
  id: string
  name: string
  slug: string
  group: string
  description?: string
}

export interface Role {
  id: string
  name: string
  slug: string
  description: string
  is_system: boolean
  permissions: Permission[]
  users_count?: number
  created_at?: string
  updated_at?: string
}

export const useRolesStore = defineStore('roles', () => {
  const roles = ref<Role[]>([])
  const loading = ref(false)

  async function fetchAll(params?: Record<string, any>) {
    loading.value = true
    try {
      const response = await api.get('/roles', { params })
      const data = response.data
      if (Array.isArray(data)) {
        roles.value = data
      } else if (data.data) {
        roles.value = data.data
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchById(id: string) {
    const response = await api.get(`/roles/${id}`)
    return response.data
  }

  async function create(data: Partial<Role>) {
    const response = await api.post('/roles', data)
    return response.data
  }

  async function update(id: string, data: Partial<Role>) {
    const response = await api.put(`/roles/${id}`, data)
    return response.data
  }

  async function syncPermissions(id: string, permissionIds: string[]) {
    const response = await api.post(`/roles/${id}/permissions`, {
      permission_ids: permissionIds,
    })
    return response.data
  }

  async function destroy(id: string) {
    const response = await api.delete(`/roles/${id}`)
    return response.data
  }

  return {
    roles,
    loading,
    fetchAll,
    fetchById,
    create,
    update,
    syncPermissions,
    destroy,
  }
})
