<template>
  <div class="maintenance-list-page">
    <Toast />
    <ConfirmDialog />

    <div class="flex align-items-center justify-content-between mb-4">
      <div>
        <h2 class="text-2xl font-bold m-0">Manutenções</h2>
        <p class="text-sm text-600 mt-1">Gerencie as ordens de manutenção dos equipamentos</p>
      </div>
      <Button
        v-if="authStore.hasPermission('manutencoes.create')"
        label="Nova Manutenção"
        icon="pi pi-plus"
        @click="showCreateDialog = true"
      />
    </div>

    <LoadingSkeleton v-if="loading" variant="table" />

    <EmptyState
      v-else-if="!loading && store.orders.length === 0"
      icon="pi pi-wrench"
      title="Nenhuma manutenção encontrada"
      description="Registre a primeira ordem de manutenção para acompanhar os serviços nos equipamentos."
      actionLabel="Nova Manutenção"
      actionIcon="pi pi-plus"
      @action="showCreateDialog = true"
    />

    <div v-else class="card">
      <Toolbar class="mb-3">
        <template #start>
          <div class="flex gap-2 flex-wrap align-items-center">
            <Select
              v-model="filters.equipment_id"
              :options="equipmentOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Filtrar por equipamento..."
              class="p-inputtext-sm"
              style="min-width: 220px"
              :filter="true"
              filterPlaceholder="Buscar por nome..."
              clearable
              @change="handleFilterChange"
            />
            <Select
              v-model="filters.type"
              :options="typeOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Tipo"
              class="p-inputtext-sm"
              style="min-width: 140px"
              clearable
              @change="handleFilterChange"
            />
            <Select
              v-model="filters.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Status"
              class="p-inputtext-sm"
              style="min-width: 160px"
              clearable
              @change="handleFilterChange"
            />
            <Select
              v-model="filters.priority"
              :options="priorityOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Prioridade"
              class="p-inputtext-sm"
              style="min-width: 140px"
              clearable
              @change="handleFilterChange"
            />
            <DatePicker
              v-model="filters.from"
              placeholder="Data início"
              class="p-inputtext-sm"
              dateFormat="dd/mm/yy"
              showIcon
              @date-select="handleFilterChange"
            />
            <DatePicker
              v-model="filters.to"
              placeholder="Data fim"
              class="p-inputtext-sm"
              dateFormat="dd/mm/yy"
              showIcon
              @date-select="handleFilterChange"
            />
          </div>
        </template>
      </Toolbar>

      <DataTable
        :value="store.orders"
        :loading="store.loading"
        paginator
        :rows="store.pagination.per_page"
        :totalRecords="store.pagination.total"
        :first="firstRow"
        lazy
        @page="onPage"
        sortField="created_at"
        :sortOrder="-1"
        stripedRows
        size="small"
        scrollable
        scrollHeight="flex"
      >
        <Column header="Equipamento">
          <template #body="{ data }">
            <span class="font-medium">{{ data.equipment?.name || '—' }}</span>
          </template>
        </Column>
        <Column header="Tipo">
          <template #body="{ data }">
            <Tag
              :value="data.type_label"
              :severity="data.type === 'preventive' ? 'success' : 'info'"
              rounded
              size="small"
            />
          </template>
        </Column>
        <Column header="Status">
          <template #body="{ data }">
            <Tag
              :value="data.status_label"
              :severity="getStatusSeverity(data.status)"
              rounded
              size="small"
            />
          </template>
        </Column>
        <Column header="Prioridade">
          <template #body="{ data }">
            <Tag
              :value="data.priority_label"
              :severity="getPrioritySeverity(data.priority)"
              rounded
              size="small"
            />
          </template>
        </Column>
        <Column header="Data Agendada">
          <template #body="{ data }">
            <span>{{ data.scheduled_date ? formatDate(data.scheduled_date) : '—' }}</span>
          </template>
        </Column>
        <Column header="Técnico">
          <template #body="{ data }">
            <span>{{ data.assigned_to?.name || '—' }}</span>
          </template>
        </Column>
        <Column header="Ações" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button
                icon="pi pi-eye"
                severity="info"
                text
                rounded
                size="small"
                v-tooltip.top="'Ver detalhes'"
                @click="viewOrder(data)"
              />
              <Button
                v-if="authStore.hasPermission('manutencoes.concluir') && data.status === 'in_progress'"
                icon="pi pi-check"
                severity="success"
                text
                rounded
                size="small"
                v-tooltip.top="'Concluir'"
                @click="openCompleteDialog(data)"
              />
              <Button
                v-if="authStore.hasPermission('manutencoes.concluir') && (data.status === 'open' || data.status === 'in_progress')"
                icon="pi pi-times"
                severity="danger"
                text
                rounded
                size="small"
                v-tooltip.top="'Cancelar'"
                @click="confirmCancel(data)"
              />
            </div>
          </template>
        </Column>
        <template #empty>
          <EmptyState
            icon="pi pi-wrench"
            title="Nenhuma manutenção encontrada"
            description="Nenhuma ordem de manutenção corresponde aos filtros atuais."
          />
        </template>
      </DataTable>
    </div>

    <MaintenanceOpenDialog
      v-model:visible="showCreateDialog"
      @saved="onOrderCreated"
    />

    <MaintenanceCloseDialog
      v-model:visible="showCompleteDialog"
      :orderId="selectedOrderId"
      @saved="onOrderCompleted"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import Toolbar from 'primevue/toolbar'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { useMaintenanceStore } from '../store/MaintenanceStore'
import { useAuthStore } from '@/stores/auth'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import EmptyState from '@/components/EmptyState.vue'
import {
  MAINTENANCE_TYPE_OPTIONS,
  MAINTENANCE_STATUS_OPTIONS,
  MAINTENANCE_PRIORITY_OPTIONS,
} from '../types/maintenance'
import type { MaintenanceOrder } from '../types/maintenance'
import MaintenanceOpenDialog from '../components/MaintenanceOpenDialog.vue'
import MaintenanceCloseDialog from '../components/MaintenanceCloseDialog.vue'

const router = useRouter()
const store = useMaintenanceStore()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()

const showCreateDialog = ref(false)
const showCompleteDialog = ref(false)
const selectedOrderId = ref('')
const loading = ref(true)

const filters = ref({
  equipment_id: null as string | null,
  type: null as string | null,
  status: null as string | null,
  priority: null as string | null,
  from: null as Date | null,
  to: null as Date | null,
})

const typeOptions = [
  { label: 'Todos', value: null as string | null },
  ...MAINTENANCE_TYPE_OPTIONS,
]

const statusOptions = [
  { label: 'Todos', value: null as string | null },
  ...MAINTENANCE_STATUS_OPTIONS,
]

const priorityOptions = [
  { label: 'Todos', value: null as string | null },
  ...MAINTENANCE_PRIORITY_OPTIONS,
]

const equipmentOptions = computed(() =>
  store.equipment.map(eq => ({
    label: `${eq.name}${(eq as any).patrimony_id ? ` — ${(eq as any).patrimony_id}` : ''}`,
    value: eq.id,
  }))
)

const firstRow = computed(() => {
  return (store.pagination.current_page - 1) * store.pagination.per_page
})

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

function handleFilterChange() {
  fetchOrders(1)
}

async function fetchOrders(page = 1) {
  loading.value = true
  const params: Record<string, any> = { page }
  if (filters.value.equipment_id) params.equipment_id = filters.value.equipment_id
  if (filters.value.type) params.type = filters.value.type
  if (filters.value.status) params.status = filters.value.status
  if (filters.value.priority) params.priority = filters.value.priority
  if (filters.value.from) params.from = (filters.value.from as Date).toISOString().split('T')[0]
  if (filters.value.to) params.to = (filters.value.to as Date).toISOString().split('T')[0]
  try {
    await store.fetchAll(params)
  } finally {
    loading.value = false
  }
}

function onPage(event: any) {
  fetchOrders(event.page + 1)
}

function viewOrder(order: MaintenanceOrder) {
  router.push(`/maintenance/${order.id}`)
}

function openCompleteDialog(order: MaintenanceOrder) {
  selectedOrderId.value = order.id
  showCompleteDialog.value = true
}

function confirmCancel(order: MaintenanceOrder) {
  confirm.require({
    message: `Tem certeza que deseja cancelar esta ordem de manutenção?`,
    header: 'Confirmar Cancelamento',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Não',
    acceptLabel: 'Sim, Cancelar',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await store.cancel(order.id)
        toast.add({
          severity: 'success',
          summary: 'Ordem cancelada',
          detail: 'A ordem de manutenção foi cancelada com sucesso.',
          life: 3000,
        })
        fetchOrders()
      } catch (e: any) {
        toast.add({
          severity: 'error',
          summary: 'Erro',
          detail: e.response?.data?.message || 'Ocorreu um erro ao cancelar.',
          life: 5000,
        })
      }
    },
  })
}

function onOrderCreated() {
  showCreateDialog.value = false
  toast.add({
    severity: 'success',
    summary: 'Ordem criada',
    detail: 'A ordem de manutenção foi registrada com sucesso.',
    life: 3000,
  })
  fetchOrders()
}

function onOrderCompleted() {
  showCompleteDialog.value = false
  toast.add({
    severity: 'success',
    summary: 'Ordem concluída',
    detail: 'A ordem de manutenção foi concluída com sucesso.',
    life: 3000,
  })
  fetchOrders()
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  try {
    const date = new Date(dateStr)
    return date.toLocaleDateString('pt-BR')
  } catch {
    return dateStr
  }
}

onMounted(() => {
  fetchOrders()
  store.fetchEquipment({ all: true })
})
</script>
