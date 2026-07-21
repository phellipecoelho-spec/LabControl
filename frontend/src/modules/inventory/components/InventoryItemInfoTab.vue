<template>
  <div class="card">
    <div class="grid">
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm font-medium mb-1">Nome</label>
          <p class="text-900 font-medium m-0">{{ item.name }}</p>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm font-medium mb-1">Código</label>
          <p class="text-900 m-0">{{ item.code || '-' }}</p>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm font-medium mb-1">Categoria</label>
          <p class="text-900 m-0">
            <Tag
              v-if="item.category"
              :value="item.category.name"
              severity="info"
              rounded
              size="small"
            />
            <span v-else class="text-600">-</span>
          </p>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm font-medium mb-1">Fornecedor</label>
          <p class="text-900 m-0">{{ item.supplier?.name || '-' }}</p>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm font-medium mb-1">Saldo Atual</label>
          <p class="m-0" :class="{ 'text-red-500 font-bold': item.is_critical }">
            {{ item.current_balance }} {{ item.unit }}
          </p>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm font-medium mb-1">Estoque Mínimo</label>
          <p class="text-900 m-0">{{ item.min_stock }} {{ item.unit }}</p>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm font-medium mb-1">Lote</label>
          <p class="text-900 m-0">{{ item.batch_lot || '-' }}</p>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm font-medium mb-1">Data de Validade</label>
          <p class="text-900 m-0">{{ item.expiry_date ? formatDate(item.expiry_date) : '-' }}</p>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm font-medium mb-1">Localização Física</label>
          <p class="text-900 m-0">{{ item.physical_location || '-' }}</p>
        </div>
      </div>
      <div class="col-12">
        <div class="field mb-3">
          <label class="block text-600 text-sm font-medium mb-1">Descrição</label>
          <p class="text-900 m-0">{{ item.description || '-' }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Tag from 'primevue/tag'
import type { InventoryItem } from '@/modules/inventory/types/inventory'

const props = defineProps<{
  item: InventoryItem
}>()

function formatDate(dateStr: string): string {
  const date = new Date(dateStr)
  return date.toLocaleDateString('pt-BR')
}
</script>
