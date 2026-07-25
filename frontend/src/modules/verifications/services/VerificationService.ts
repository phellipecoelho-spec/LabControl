import { api } from '@/services/api'
import type {
  Verification, VerificationFormData, VerificationFilters,
  PendingEquipment, VerificationTemplate,
} from '../types/verification'

export const verificationService = {
  async getPending(): Promise<PendingEquipment[]> {
    const { data } = await api.get('/verifications/pending')
    return data.data
  },

  async create(data: VerificationFormData): Promise<Verification> {
    const response = await api.post('/verifications', data)
    return response.data.data
  },

  async getHistoryByEquipment(equipmentId: string, params?: { page?: number; per_page?: number }): Promise<{ data: Verification[]; meta: any }> {
    const { data } = await api.get(`/equipments/${equipmentId}/verifications`, { params })
    return data
  },

  async getTemplatesByCategory(categoryId: string): Promise<VerificationTemplate[]> {
    const { data } = await api.get('/verification-templates', { params: { equipment_category_id: categoryId } })
    return data.data ?? data
  },

  async getTemplatesByEquipment(equipmentId: string): Promise<VerificationTemplate[]> {
    const { data } = await api.get(`/verification-templates/by-equipment/${equipmentId}`)
    return data.data ?? data
  },
}
