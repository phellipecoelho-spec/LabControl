<template>
  <div class="equipment-location-tab">
    <div class="grid">
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Localização</label>
          <div class="text-900 font-medium">{{ equipment.location || 'Não informada' }}</div>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Data de Aquisição</label>
          <div class="text-900">{{ formatDate(equipment.acquisition_date) }}</div>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Fim da Garantia</label>
          <div class="flex align-items-center gap-2">
            <span class="text-900">{{ formatDate(equipment.warranty_end) }}</span>
            <Tag 
              v-if="isWarrantyExpired" 
              value="Vencida" 
              severity="danger" 
              size="small" 
              rounded 
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Equipment } from '../types/equipment'
import Tag from 'primevue/tag'

const props = defineProps<{
  equipment: Equipment
}>()

const isWarrantyExpired = computed(() => {
  if (!props.equipment.warranty_end) return false
  const warrantyEnd = new Date(props.equipment.warranty_end)
  const today = new Date()
  return warrantyEnd < today
})

function formatDate(date: string | undefined): string {
  if (!date) return 'Não informada'
  return new Date(date).toLocaleDateString('pt-BR')
}
</script>