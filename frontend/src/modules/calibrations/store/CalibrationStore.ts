import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type { Calibration, CalibrationFormData, CompleteCalibrationFormData } from '../types/calibration'
import type { Equipment } from '@/modules/equipment/types/equipment'

interface Pagination {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

export const useCalibrationStore = defineStore('calibration', () => {
  const calibrations = ref<Calibration[]>([])
  const currentCalibration = ref<Calibration | null>(null)
  const loading = ref(false)
  const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  })
  const equipment = ref<Equipment[]>([])

  async function fetchAll(params?: Record<string, any>) {
    loading.value = true
    try {
      const response = await api.get('/calibrations', { params })
      const data = response.data
      if (Array.isArray(data)) {
        calibrations.value = data
      } else if (data.data) {
        calibrations.value = data.data
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
    loading.value = true
    try {
      const response = await api.get(`/calibrations/${id}`)
      currentCalibration.value = response.data?.data ?? response.data
      return currentCalibration.value
    } finally {
      loading.value = false
    }
  }

  async function create(data: CalibrationFormData) {
    const response = await api.post('/calibrations', data)
    return response.data
  }

  async function update(id: string, data: Partial<CalibrationFormData>) {
    const response = await api.put(`/calibrations/${id}`, data)
    return response.data
  }

  async function destroy(id: string) {
    await api.delete(`/calibrations/${id}`)
  }

  async function complete(id: string, data: CompleteCalibrationFormData) {
    const response = await api.post(`/calibrations/${id}/complete`, data)
    return response.data
  }

  async function cancel(id: string) {
    const response = await api.post(`/calibrations/${id}/cancel`)
    return response.data
  }

  async function fetchEquipment(params?: Record<string, any>) {
    const response = await api.get('/equipments', { params })
    equipment.value = response.data?.data ?? response.data ?? []
    return equipment.value
  }

  return {
    calibrations,
    currentCalibration,
    loading,
    pagination,
    equipment,
    fetchAll,
    fetchById,
    create,
    update,
    destroy,
    complete,
    cancel,
    fetchEquipment,
  }
})
