<template>
  <div class="maintenance-history-tab">
    <div class="flex align-items-center justify-content-between mb-3">
      <h3 class="text-lg font-semibold m-0">Histórico de Manutenções</h3>
      <Button
        v-if="authStore.hasPermission('manutencoes.create')"
        label="Nova Manutenção"
        icon="pi pi-plus"
        severity="info"
        size="small"
        @click="$emit('start-maintenance')"
      />
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="flex flex-column gap-2">
      <Skeleton height="3rem" v-for="n in 3" :key="n" />
    </div>

    <!-- History table -->
    <DataTable
      v-else-if="orders.length > 0"
      :value="orders"
      stripedRows
      :rows="perPage"
      :totalRecords="totalRecords"
      :lazy="true"
      :paginator="totalRecords > perPage"
      :first="first"
      @page="onPageChange"
      dataKey="id"
      :expandedRows="expandedRows"
      @rowToggle="onRowToggle"
      size="small"
    >
      <Column :expander="true" style="width: 3rem" />
      <Column header="Data Abertura">
        <template #body="{ data }">
          {{ formatDate(data.created_at) }}
        </template>
      </Column>
      <Column header="Tipo">
        <template #body="{ data }">
          <Tag :value="data.type_label" :severity="getTypeSeverity(data.type)" rounded size="small" />
        </template>
      </Column>
      <Column header="Status">
        <template #body="{ data }">
          <Tag :value="data.status_label" :severity="getStatusSeverity(data.status)" rounded size="small" />
        </template>
      </Column>
      <Column header="Prioridade">
        <template #body="{ data }">
          <Tag :value="data.priority_label" :severity="getPrioritySeverity(data.priority)" rounded size="small" />
        </template>
      </Column>
      <Column header="Técnico">
        <template #body="{ data }">
          {{ data.assigned_to?.name || '—' }}
        </template>
      </Column>
      <Column header="Conclusão">
        <template #body="{ data }">
          {{ data.completed_at ? formatDate(data.completed_at) : '—' }}
        </template>
      </Column>
      <Column header="Ações">
        <template #body="{ data }">
          <Button
            icon="pi pi-eye"
            text
            rounded
            severity="secondary"
            size="small"
            @click="viewOrder(data)"
          />
        </template>
      </Column>

      <!-- Expanded row: details -->
      <template #expansion="{ data }">
        <div class="p-3 surface-ground border-round">
          <div class="grid">
            <div class="col-12 mb-2">
              <strong>Descrição:</strong>
              <p class="m-0 mt-1">{{ data.description }}</p>
            </div>
            <div v-if="data.resolution" class="col-12 mb-2">
              <strong>Parecer Técnico:</strong>
              <p class="m-0 mt-1">{{ data.resolution }}</p>
            </div>
            <div class="col-6" v-if="data.time_spent">
              <strong>Tempo Gasto:</strong> {{ data.time_spent }}h
            </div>
            <div class="col-6" v-if="data.cost">
              <strong>Custo:</strong> R$ {{ Number(data.cost).toFixed(2) }}
            </div>
            <div v-if="data.parts && data.parts.length > 0" class="col-12 mt-2">
              <strong>Peças Utilizadas:</strong>
              <div v-for="part in data.parts" :key="part.id" class="flex gap-2 mt-1">
                <Tag severity="info" rounded>
                  {{ part.item_name || 'Item #' + part.inventory_item_id }} — Qtd: {{ part.quantity }}
                </Tag>
              </div>
            </div>
          </div>
        </div>
      </template>
    </DataTable>

    <!-- Empty state -->
    <div v-else class="flex flex-column align-items-center py-5 text-color-secondary">
      <i class="pi pi-wrench text-4xl mb-2" style="opacity: 0.4"></i>
      <p class="m-0">Nenhuma manutenção registrada para este equipamento.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useMaintenanceStore } from '../store/MaintenanceStore'
import type { MaintenanceOrder } from '../types/maintenance'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import Button from 'primevue/button'

const props = defineProps<{
  equipmentId: string
}>()

const emit = defineEmits<{
  'start-maintenance': []
}>()

const router = useRouter()
const authStore = useAuthStore()
const store = useMaintenanceStore()

const orders = ref<MaintenanceOrder[]>([])
const loading = ref(false)
const totalRecords = ref(0)
const perPage = ref(15)
const first = ref(0)
const currentPage = ref(1)
const expandedRows = ref<any>(null)

onMounted(() => {
  fetchHistory()
  window.addEventListener('maintenance-saved', onMaintenanceSavedEvent)
})

onUnmounted(() => {
  window.removeEventListener('maintenance-saved', onMaintenanceSavedEvent)
})

watch(() => props.equipmentId, () => {
  currentPage.value = 1
  first.value = 0
  fetchHistory()
})

function formatDate(dateStr: string): string {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function getTypeSeverity(type: string): string {
  const map: Record<string, string> = { preventive: 'success', corrective: 'info' }
  return map[type] || 'info'
}

function getStatusSeverity(status: string): string {
  const map: Record<string, string> = {
    open: 'info',
    in_progress: 'warn',
    completed: 'success',
    cancelled: 'secondary',
  }
  return map[status] || 'info'
}

function getPrioritySeverity(priority: string): string {
  const map: Record<string, string> = {
    low: 'info',
    medium: 'warn',
    high: 'danger',
    critical: 'danger',
  }
  return map[priority] || 'info'
}

async function fetchHistory() {
  loading.value = true
  try {
    const result = await store.fetchHistoryByEquipment(props.equipmentId, {
      page: currentPage.value,
      per_page: perPage.value,
    })
    orders.value = result.data
    if (result.meta) {
      totalRecords.value = result.meta.total ?? 0
    }
  } finally {
    loading.value = false
  }
}

function onPageChange(event: any) {
  currentPage.value = event.page + 1
  first.value = event.first
  fetchHistory()
}

function onRowToggle(event: any) {
  expandedRows.value = expandedRows.value === event.data.id ? null : event.data.id
}

function viewOrder(order: MaintenanceOrder) {
  router.push(`/maintenance/${order.id}`)
}

function onMaintenanceSavedEvent(event: Event) {
  const detail = (event as CustomEvent).detail
  if (detail?.equipmentId === props.equipmentId) {
    fetchHistory()
  }
}
</script>
