import { api } from '@/services/api'
import type { Calibration, CalibrationFormData, CompleteCalibrationFormData } from '../types/calibration'

export const calibrationService = {
  async list(params?: Record<string, any>) {
    const response = await api.get('/calibrations', { params })
    return response.data
  },

  async getById(id: string) {
    const response = await api.get(`/calibrations/${id}`)
    return response.data
  },

  async create(data: CalibrationFormData) {
    const response = await api.post('/calibrations', data)
    return response.data
  },

  async update(id: string, data: Partial<CalibrationFormData>) {
    const response = await api.put(`/calibrations/${id}`, data)
    return response.data
  },

  async delete(id: string) {
    await api.delete(`/calibrations/${id}`)
  },

  async complete(id: string, data: CompleteCalibrationFormData) {
    const response = await api.post(`/calibrations/${id}/complete`, data)
    return response.data
  },

  async cancel(id: string) {
    const response = await api.post(`/calibrations/${id}/cancel`)
    return response.data
  },

  async listEquipment(params?: Record<string, any>) {
    const response = await api.get('/equipments', { params })
    return response.data
  },

  // Certificates
  async listCertificates(calibrationId: string) {
    const response = await api.get(`/calibrations/${calibrationId}/certificates`)
    return response.data
  },

  async uploadCertificate(calibrationId: string, file: File) {
    const formData = new FormData()
    formData.append('certificate', file)
    const response = await api.post(`/calibrations/${calibrationId}/certificates`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  },

  async deleteCertificate(calibrationId: string, certificateId: string) {
    await api.delete(`/calibrations/${calibrationId}/certificates/${certificateId}`)
  },
}
