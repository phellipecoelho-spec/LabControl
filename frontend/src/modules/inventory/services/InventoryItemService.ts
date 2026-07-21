import { api } from '@/services/api'

export const inventoryItemService = {
  async list(params?: Record<string, any>) {
    const response = await api.get('/inventory-items', { params })
    return response.data
  },

  async getById(id: string) {
    const response = await api.get(`/inventory-items/${id}`)
    return response.data
  },

  async create(data: Record<string, any>) {
    const response = await api.post('/inventory-items', data)
    return response.data
  },

  async update(id: string, data: Record<string, any>) {
    const response = await api.put(`/inventory-items/${id}`, data)
    return response.data
  },

  async delete(id: string) {
    await api.delete(`/inventory-items/${id}`)
  },

  async getMovements(itemId: string, params?: Record<string, any>) {
    const response = await api.get(`/inventory-items/${itemId}/movements`, { params })
    return response.data
  },
}
