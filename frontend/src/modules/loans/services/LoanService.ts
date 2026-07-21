import { api } from '@/services/api'
import type { Loan, LoanFormData, ReturnItemFormData } from '../types/loan'

export const loanService = {
  async list(params?: Record<string, any>) {
    const response = await api.get('/loans', { params })
    return response.data
  },

  async getById(id: string) {
    const response = await api.get(`/loans/${id}`)
    return response.data
  },

  async create(data: LoanFormData) {
    const response = await api.post('/loans', data)
    return response.data
  },

  async update(id: string, data: Partial<LoanFormData>) {
    const response = await api.put(`/loans/${id}`, data)
    return response.data
  },

  async delete(id: string) {
    await api.delete(`/loans/${id}`)
  },

  async activate(id: string) {
    const response = await api.post(`/loans/${id}/activate`)
    return response.data
  },

  async returnItem(id: string, data: ReturnItemFormData) {
    const response = await api.post(`/loans/${id}/return`, data)
    return response.data
  },

  async cancel(id: string) {
    const response = await api.post(`/loans/${id}/cancel`)
    return response.data
  },

  async listUsers(params?: Record<string, any>) {
    const response = await api.get('/users', { params })
    return response.data
  },

  async listEquipment(params?: Record<string, any>) {
    const response = await api.get('/equipments', { params })
    return response.data
  },
}
