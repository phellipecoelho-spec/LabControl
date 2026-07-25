import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { verificationService } from '../services/VerificationService'
import type { Verification, PendingEquipment, VerificationFilters } from '../types/verification'

export const useVerificationStore = defineStore('verifications', () => {
  const verifications = ref<Verification[]>([])
  const pendingEquipment = ref<PendingEquipment[]>([])
  const currentVerification = ref<Verification | null>(null)
  const loading = ref(false)
  const meta = ref<any>(null)

  const hasPending = computed(() => pendingEquipment.value.length > 0)

  async function fetchPending() {
    loading.value = true
    try {
      pendingEquipment.value = await verificationService.getPending()
    } finally {
      loading.value = false
    }
  }

  async function fetchHistory(equipmentId: string, params?: any) {
    loading.value = true
    try {
      const result = await verificationService.getHistoryByEquipment(equipmentId, params)
      verifications.value = result.data
      meta.value = result.meta
    } finally {
      loading.value = false
    }
  }

  async function create(data: any): Promise<Verification> {
    const verification = await verificationService.create(data)
    return verification
  }

  function $reset() {
    verifications.value = []
    pendingEquipment.value = []
    currentVerification.value = null
    loading.value = false
    meta.value = null
  }

  return {
    verifications,
    pendingEquipment,
    currentVerification,
    loading,
    meta,
    hasPending,
    fetchPending,
    fetchHistory,
    create,
    $reset,
  }
})
