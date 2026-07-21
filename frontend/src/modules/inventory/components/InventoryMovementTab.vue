<template>
  <div class="inventory-movement-tab">
    <div class="flex justify-content-end mb-3">
      <Button
        label="Nova Movimentação"
        icon="pi pi-plus"
        size="small"
        @click="openDialog"
      />
    </div>

    <DataTable
      :value="movements"
      :loading="loading"
      paginator
      :rows="pagination.per_page"
      :totalRecords="pagination.total"
      :first="firstRow"
      lazy
      @page="onPage"
      stripedRows
      size="small"
    >
      <Column field="created_at" header="Data/Hora" sortable>
        <template #body="{ data }">
          {{ formatDateTime(data.created_at) }}
        </template>
      </Column>
      <Column field="type" header="Tipo" sortable>
        <template #body="{ data }">
          <Tag
            :value="getMovementTypeLabel(data.type)"
            :severity="getMovementSeverity(data.type)"
            rounded
            size="small"
          />
        </template>
      </Column>
      <Column field="quantity_display" header="Quantidade" sortable>
        <template #body="{ data }">
          <span :class="{ 'text-green-500 font-bold': data.quantity_display > 0, 'text-red-500 font-bold': data.quantity_display < 0 }">
            {{ data.quantity_display > 0 ? '+' : '' }}{{ data.quantity_display }}
          </span>
        </template>
      </Column>
      <Column field="balance_after" header="Saldo Resultante" sortable />
      <Column field="user.name" header="Responsável" sortable />
      <Column field="reason" header="Motivo" sortable>
        <template #body="{ data }">
          {{ data.reason || '-' }}
        </template>
      </Column>
    </DataTable>

    <InventoryMovementDialog
      :visible="showDialog"
      :itemId="itemId"
      @close="closeDialog"
      @created="onMovementCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import InventoryMovementDialog from '@/modules/inventory/components/InventoryMovementDialog.vue'
import { useInventoryMovementStore } from '@/modules/inventory/store/InventoryMovementStore'
import { MOVEMENT_TYPE_OPTIONS } from '@/modules/inventory/types/inventory'
import type { InventoryMovement } from '@/modules/inventory/types/inventory'

const props = defineProps<{
  itemId: string
}>()

const emit = defineEmits<{
  (e: 'movement-created'): void
}>()

const store = useInventoryMovementStore()
const toast = useToast()

const showDialog = ref(false)
const movements = computed(() => store.movements)
const loading = computed(() => store.loading)
const pagination = computed(() => store.pagination)

const firstRow = computed(() => {
  return (pagination.value.current_page - 1) * pagination.value.per_page
})

function fetchMovements(page = 1) {
  store.fetchAll({
    item_id: props.itemId,
    page,
    per_page: 10,
  })
}

function onPage(event: any) {
  fetchMovements(event.page + 1)
}

function openDialog() {
  showDialog.value = true
}

function closeDialog() {
  showDialog.value = false
}

function onMovementCreated(data: any) {
  showDialog.value = false
  fetchMovements()
  emit('movement-created')

  // Check for critical stock toast
  if (data.is_critical) {
    toast.add({
      severity: 'warn',
      summary: 'Estoque Crítico',
      detail: `O item atingiu o estoque mínimo.`,
      life: 6000,
    })
  }
}

function getMovementTypeLabel(type: string): string {
  const option = MOVEMENT_TYPE_OPTIONS.find(o => o.value === type)
  return option?.label || type
}

function getMovementSeverity(type: string): string {
  const severities: Record<string, string> = {
    purchase: 'success',
    consumption: 'info',
    adjustment: 'warning',
    disposal: 'danger',
    return: 'info',
  }
  return severities[type] || 'info'
}

function formatDateTime(dateStr: string): string {
  const date = new Date(dateStr)
  return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
  fetchMovements()
})

watch(() => props.itemId, () => {
  fetchMovements()
})
</script>
