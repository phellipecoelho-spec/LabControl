import { api } from '@/services/api'
import type { Equipment } from '../types/equipment'

export const equipmentService = {
  async list(params?: Record<string, any>) {
    const response = await api.get('/equipments', { params })
    return response.data
  },

  async getById(id: string) {
    const response = await api.get(`/equipments/${id}`)
    return response.data
  },

  async create(data: Record<string, any>) {
    const response = await api.post('/equipments', data)
    return response.data
  },

  async update(id: string, data: Record<string, any>) {
    const response = await api.put(`/equipments/${id}`, data)
    return response.data
  },

  async delete(id: string) {
    await api.delete(`/equipments/${id}`)
  },

  async listCategories(params?: Record<string, any>) {
    const response = await api.get('/categories', { params })
    return response.data
  },

  async listManufacturers(params?: Record<string, any>) {
    const response = await api.get('/manufacturers', { params })
    return response.data
  },

  async listSuppliers(params?: Record<string, any>) {
    const response = await api.get('/suppliers', { params })
    return response.data
  },
}