<template>
  <div class="equipment-info-tab">
    <div class="grid">
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Nome</label>
          <div class="text-900 font-medium">{{ equipment.name }}</div>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Patrimônio</label>
          <div class="text-900">{{ equipment.patrimony_id || 'Não informado' }}</div>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Nº Série</label>
          <div class="text-900">{{ equipment.serial_number || 'Não informado' }}</div>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Categoria</label>
          <Tag 
            v-if="equipment.category" 
            :value="equipment.category.name" 
            severity="info" 
            rounded 
          />
          <span v-else class="text-600">Não informada</span>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Fabricante</label>
          <div class="text-900">{{ equipment.manufacturer?.name || 'Não informado' }}</div>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Fornecedor</label>
          <div class="text-900">{{ equipment.supplier?.name || 'Não informado' }}</div>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Status</label>
          <Tag 
            :value="getStatusLabel(equipment.status)" 
            :severity="getSeverity(equipment.status)" 
            rounded 
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Equipment } from '../types/equipment'
import Tag from 'primevue/tag'

defineProps<{
  equipment: Equipment
}>()

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    active: 'Ativo',
    inactive: 'Inativo',
    maintenance: 'Manutenção',
    retired: 'Baixado',
  }
  return labels[status] || status
}

function getSeverity(status: string): string {
  const severities: Record<string, string> = {
    active: 'success',
    inactive: 'danger',
    maintenance: 'warning',
    retired: 'info',
  }
  return severities[status] || 'info'
}
</script>