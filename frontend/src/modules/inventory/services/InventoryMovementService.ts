import { api } from '@/services/api'

export const inventoryMovementService = {
  async list(params?: Record<string, any>) {
    const response = await api.get('/inventory-movements', { params })
    return response.data
  },

  async create(data: Record<string, any>) {
    const response = await api.post('/inventory-movements', data)
    return response.data
  },

  async getById(id: string) {
    const response = await api.get(`/inventory-movements/${id}`)
    return response.data
  },
}
