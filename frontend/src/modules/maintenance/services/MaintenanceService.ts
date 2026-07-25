import { api } from '@/services/api'
import type {
  MaintenanceOrder,
  OpenMaintenanceFormData,
  CloseMaintenanceFormData,
} from '../types/maintenance'

export const maintenanceService = {
  async list(params?: Record<string, any>) {
    const response = await api.get('/maintenance-orders', { params })
    return response.data
  },

  async getById(id: string) {
    const response = await api.get(`/maintenance-orders/${id}`)
    return response.data
  },

  async create(data: OpenMaintenanceFormData) {
    const response = await api.post('/maintenance-orders', data)
    return response.data
  },

  async update(id: string, data: Partial<OpenMaintenanceFormData>) {
    const response = await api.put(`/maintenance-orders/${id}`, data)
    return response.data
  },

  async delete(id: string) {
    await api.delete(`/maintenance-orders/${id}`)
  },

  async complete(id: string, data: CloseMaintenanceFormData) {
    const response = await api.post(`/maintenance-orders/${id}/complete`, data)
    return response.data
  },

  async cancel(id: string, reason?: string) {
    const response = await api.post(`/maintenance-orders/${id}/cancel`, { reason })
    return response.data
  },

  async getHistoryByEquipment(equipmentId: string, params?: { page?: number; per_page?: number }) {
    const response = await api.get(`/equipments/${equipmentId}/maintenance`, { params })
    return response.data
  },
}
