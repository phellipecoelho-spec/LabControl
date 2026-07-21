import { api } from '@/services/api'

export const inventoryCategoryService = {
  async list(params?: Record<string, any>) {
    const response = await api.get('/inventory-categories', { params })
    return response.data
  },

  async create(data: Record<string, any>) {
    const response = await api.post('/inventory-categories', data)
    return response.data
  },

  async update(id: string, data: Record<string, any>) {
    const response = await api.put(`/inventory-categories/${id}`, data)
    return response.data
  },

  async delete(id: string) {
    await api.delete(`/inventory-categories/${id}`)
  },
}
