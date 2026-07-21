<template>
  <div class="loan-list-page">
    <Toast />
    <ConfirmDialog />

    <div class="flex align-items-center justify-content-between mb-4">
      <div>
        <h2 class="text-2xl font-bold m-0">Empréstimos</h2>
        <p class="text-sm text-600 mt-1">Gerencie os empréstimos de equipamentos</p>
      </div>
      <Button
        v-if="authStore.hasPermission('emprestimos.create')"
        label="Novo Empréstimo"
        icon="pi pi-plus"
        @click="showCreateDialog = true"
      />
    </div>

    <div class="card">
      <Toolbar class="mb-3">
        <template #start>
          <div class="flex gap-2 flex-wrap align-items-center">
            <InputText
              v-model="filters.search"
              placeholder="Buscar por tomador..."
              class="p-inputtext-sm"
              style="min-width: 200px"
              @input="onSearch"
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
            <MultiSelect
              v-model="filters.equipment_ids"
              :options="equipmentOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Filtrar por equipamento..."
              class="p-inputtext-sm"
              style="min-width: 220px"
              :maxSelectedLabels="2"
              selectedItemsLabel="{0} equipamentos selecionados"
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
        :value="store.loans"
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
        <Column header="Tomador">
          <template #body="{ data }">
            <span>{{ data.borrower?.name || '—' }}</span>
          </template>
        </Column>
        <Column header="Equipamentos">
          <template #body="{ data }">
            <div class="flex flex-wrap gap-1">
              <Tag
                v-for="eq in data.equipment"
                :key="eq.id"
                :value="eq.name"
                severity="info"
                rounded
                size="small"
              />
            </div>
          </template>
        </Column>
        <Column header="Data Retirada">
          <template #body="{ data }">
            <span>{{ formatDate(data.borrowed_at) }}</span>
          </template>
        </Column>
        <Column header="Data Prevista">
          <template #body="{ data }">
            <span>{{ formatDate(data.expected_return_at) }}</span>
          </template>
        </Column>
        <Column header="Status">
          <template #body="{ data }">
            <div class="flex align-items-center gap-1">
              <Tag
                :value="getStatusLabel(data.status)"
                :severity="getStatusSeverity(data.status)"
                rounded
                size="small"
              />
              <Tag
                v-if="data.is_overdue"
                value="Atrasado"
                severity="danger"
                rounded
                size="small"
              />
            </div>
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
                @click="viewLoan(data)"
              />
              <Button
                v-if="authStore.hasPermission('emprestimos.edit') && data.status === 'reserved'"
                icon="pi pi-pencil"
                severity="secondary"
                text
                rounded
                size="small"
                @click="editLoan(data)"
              />
              <Button
                v-if="authStore.hasPermission('emprestimos.finalizar') && (data.status === 'reserved' || data.status === 'cancelled')"
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

    <LoanCreateDialog
      v-model:visible="showCreateDialog"
      @saved="onLoanCreated"
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
import MultiSelect from 'primevue/multiselect'
import DatePicker from 'primevue/datepicker'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { useLoanStore } from '../store/LoanStore'
import { useAuthStore } from '@/stores/auth'
import { LOAN_STATUS_OPTIONS } from '../types/loan'
import type { Loan } from '../types/loan'
import LoanCreateDialog from '../components/LoanCreateDialog.vue'

const router = useRouter()
const store = useLoanStore()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()

const showCreateDialog = ref(false)

const filters = ref({
  search: '',
  status: null as string | null,
  equipment_ids: [] as string[],
  from: null as Date | null,
  to: null as Date | null,
})

const statusOptions = [
  { label: 'Todos', value: null as string | null },
  ...LOAN_STATUS_OPTIONS,
]

const equipmentOptions = computed(() =>
  store.equipment.map(eq => ({
    label: `${eq.name} - ${eq.patrimony_id || eq.id}`,
    value: eq.id,
  }))
)

const firstRow = computed(() => {
  return (store.pagination.current_page - 1) * store.pagination.per_page
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

function rowClass(data: Loan) {
  return data.is_overdue ? 'p-row-overdue' : ''
}

function onSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    handleFilterChange()
  }, 400)
}

function handleFilterChange() {
  fetchLoans(1)
}

function fetchLoans(page = 1) {
  const params: Record<string, any> = { page }
  if (filters.value.search) params.search = filters.value.search
  if (filters.value.status) params.status = filters.value.status
  if (filters.value.equipment_ids?.length) {
    params.equipment_id = filters.value.equipment_ids.join(',')
  }
  if (filters.value.from) params.from = filters.value.from.toISOString().split('T')[0]
  if (filters.value.to) params.to = filters.value.to.toISOString().split('T')[0]
  store.fetchAll(params)
}

function onPage(event: any) {
  fetchLoans(event.page + 1)
}

function viewLoan(loan: Loan) {
  router.push(`/loans/${loan.id}`)
}

function editLoan(loan: Loan) {
  router.push(`/loans/${loan.id}?edit=1`)
}

function confirmDelete(loan: Loan) {
  confirm.require({
    message: `Tem certeza que deseja excluir o empréstimo?`,
    header: 'Confirmar Exclusão',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancelar',
    acceptLabel: 'Excluir',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await store.destroy(loan.id)
        toast.add({
          severity: 'success',
          summary: 'Empréstimo excluído',
          detail: 'O empréstimo foi removido com sucesso.',
          life: 3000,
        })
        fetchLoans()
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

function onLoanCreated() {
  showCreateDialog.value = false
  toast.add({
    severity: 'success',
    summary: 'Empréstimo criado',
    detail: 'O empréstimo foi registrado com sucesso.',
    life: 3000,
  })
  fetchLoans()
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    reserved: 'Reservado',
    active: 'Ativo',
    returned: 'Devolvido',
    cancelled: 'Cancelado',
  }
  return labels[status] || status
}

function getStatusSeverity(status: string): string {
  const severities: Record<string, string> = {
    reserved: 'info',
    active: 'warning',
    returned: 'success',
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
  fetchLoans()
  store.fetchUsers()
  store.fetchEquipment({ all: true })
})
</script>

<style scoped>
.p-row-overdue {
  background-color: rgba(239, 68, 68, 0.05) !important;
}
.p-row-overdue:hover {
  background-color: rgba(239, 68, 68, 0.1) !important;
}
</style>
