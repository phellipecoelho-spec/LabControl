<template>
  <div class="calibration-list-page">
    <Toast />
    <ConfirmDialog />

    <div class="flex align-items-center justify-content-between mb-4">
      <div>
        <h2 class="text-2xl font-bold m-0">Calibrações</h2>
        <p class="text-sm text-600 mt-1">Gerencie as calibrações periódicas dos equipamentos</p>
      </div>
      <Button
        v-if="authStore.hasPermission('calibracoes.create')"
        label="Nova Calibração"
        icon="pi pi-plus"
        @click="showCreateDialog = true"
      />
    </div>

    <LoadingSkeleton v-if="loading" variant="table" />

    <EmptyState
      v-else-if="!loading && store.calibrations.length === 0"
      icon="pi pi-verified"
      title="Nenhuma calibração encontrada"
      description="Agende a primeira calibração de equipamento para manter a rastreabilidade."
      actionLabel="Nova Calibração"
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
            <InputText
              v-model="filters.laboratory"
              placeholder="Buscar por laboratório..."
              class="p-inputtext-sm"
              style="min-width: 200px"
              @input="onSearch"
            />
          </div>
        </template>
      </Toolbar>

      <DataTable
        :value="store.calibrations"
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
        :rowClass="rowClass"
      >
        <Column header="Equipamento">
          <template #body="{ data }">
            <span class="font-medium">{{ data.equipment?.name || '—' }}</span>
          </template>
        </Column>
        <Column header="Parte">
          <template #body="{ data }">
            <span>{{ data.part_name || '—' }}</span>
          </template>
        </Column>
        <Column header="Data Agendada">
          <template #body="{ data }">
            <span>{{ formatDate(data.scheduled_date) }}</span>
          </template>
        </Column>
        <Column header="Data Conclusão">
          <template #body="{ data }">
            <span>{{ data.completed_at ? formatDate(data.completed_at) : '—' }}</span>
          </template>
        </Column>
        <Column header="Próxima Data">
          <template #body="{ data }">
            <div class="flex align-items-center gap-1">
              <span>{{ data.next_due_at ? formatDate(data.next_due_at) : '—' }}</span>
              <Tag
                v-if="data.is_due"
                value="Vencida"
                severity="danger"
                rounded
                size="small"
              />
              <Tag
                v-else-if="data.is_due_soon"
                :value="`Vence em ${daysUntilDue(data.next_due_at)} dias`"
                severity="warn"
                rounded
                size="small"
              />
            </div>
          </template>
        </Column>
        <Column header="Laboratório">
          <template #body="{ data }">
            <span>{{ data.laboratory || '—' }}</span>
          </template>
        </Column>
        <Column header="Status">
          <template #body="{ data }">
            <Tag
              :value="getStatusLabel(data.status)"
              :severity="getStatusSeverity(data.status)"
              rounded
              size="small"
            />
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
                @click="viewCalibration(data)"
              />
              <Button
                v-if="authStore.hasPermission('calibracoes.edit') && data.status === 'scheduled'"
                icon="pi pi-pencil"
                severity="secondary"
                text
                rounded
                size="small"
                @click="editCalibration(data)"
              />
              <Button
                v-if="authStore.hasPermission('calibracoes.edit') && (data.status === 'scheduled' || data.status === 'cancelled')"
                icon="pi pi-trash"
                severity="danger"
                text
                rounded
                size="small"
                @click="confirmDelete(data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <CalibrationCreateDialog
      v-model:visible="showCreateDialog"
      @saved="onCalibrationCreated"
    />

    <CalibrationConcludeDialog
      v-model:visible="showConcludeDialog"
      :calibration="selectedCalibration"
      @saved="onCalibrationConcluded"
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
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { useCalibrationStore } from '../store/CalibrationStore'
import { useAuthStore } from '@/stores/auth'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import EmptyState from '@/components/EmptyState.vue'
import { CALIBRATION_STATUS_OPTIONS } from '../types/calibration'
import type { Calibration } from '../types/calibration'
import CalibrationCreateDialog from '../components/CalibrationCreateDialog.vue'
import CalibrationConcludeDialog from '../components/CalibrationConcludeDialog.vue'

const router = useRouter()
const store = useCalibrationStore()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()

const showCreateDialog = ref(false)
const showConcludeDialog = ref(false)
const selectedCalibration = ref<Calibration | null>(null)
const loading = ref(true)

const filters = ref({
  equipment_id: null as string | null,
  status: null as string | null,
  from: null as Date | null,
  to: null as Date | null,
  laboratory: '',
})

const statusOptions = [
  { label: 'Todos', value: null as string | null },
  ...CALIBRATION_STATUS_OPTIONS,
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

let searchTimeout: ReturnType<typeof setTimeout> | null = null

function rowClass(data: Calibration) {
  return data.is_due ? 'p-row-due' : ''
}

function daysUntilDue(dateStr: string | null): number {
  if (!dateStr) return 0
  const now = new Date()
  const due = new Date(dateStr)
  return Math.ceil((due.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
}

function onSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    handleFilterChange()
  }, 300)
}

function handleFilterChange() {
  fetchCalibrations(1)
}

async function fetchCalibrations(page = 1) {
  loading.value = true
  const params: Record<string, any> = { page }
  if (filters.value.equipment_id) params.equipment_id = filters.value.equipment_id
  if (filters.value.status) params.status = filters.value.status
  if (filters.value.from) params.from = filters.value.from.toISOString().split('T')[0]
  if (filters.value.to) params.to = filters.value.to.toISOString().split('T')[0]
  if (filters.value.laboratory) params.laboratory = filters.value.laboratory
  try {
    await store.fetchAll(params)
  } finally {
    loading.value = false
  }
}

function onPage(event: any) {
  fetchCalibrations(event.page + 1)
}

function viewCalibration(calibration: Calibration) {
  router.push(`/calibrations/${calibration.id}`)
}

function editCalibration(calibration: Calibration) {
  router.push(`/calibrations/${calibration.id}?edit=1`)
}

function confirmDelete(calibration: Calibration) {
  confirm.require({
    message: `Tem certeza que deseja excluir a calibração?`,
    header: 'Confirmar Exclusão',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancelar',
    acceptLabel: 'Excluir',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await store.destroy(calibration.id)
        toast.add({
          severity: 'success',
          summary: 'Calibração excluída',
          detail: 'A calibração foi removida com sucesso.',
          life: 3000,
        })
        fetchCalibrations()
      } catch (e: any) {
        toast.add({
          severity: 'error',
          summary: 'Erro',
          detail: e.response?.data?.message || 'Ocorreu um erro ao excluir.',
          life: 5000,
        })
      }
    },
  })
}

function onCalibrationCreated() {
  showCreateDialog.value = false
  toast.add({
    severity: 'success',
    summary: 'Calibração criada',
    detail: 'A calibração foi registrada com sucesso.',
    life: 3000,
  })
  fetchCalibrations()
}

function onCalibrationConcluded() {
  showConcludeDialog.value = false
  toast.add({
    severity: 'success',
    summary: 'Calibração concluída',
    detail: 'A calibração foi concluída com sucesso.',
    life: 3000,
  })
  fetchCalibrations()
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    scheduled: 'Agendada',
    completed: 'Concluída',
    cancelled: 'Cancelada',
  }
  return labels[status] || status
}

function getStatusSeverity(status: string): string {
  const severities: Record<string, string> = {
    scheduled: 'info',
    completed: 'success',
    cancelled: 'secondary',
  }
  return severities[status] || 'info'
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
  fetchCalibrations()
  store.fetchEquipment({ all: true })
})
</script>

<style scoped>
.p-row-due {
  background-color: rgba(239, 68, 68, 0.05) !important;
}
.p-row-due:hover {
  background-color: rgba(239, 68, 68, 0.1) !important;
}
</style>
