<template>
  <div class="inventory-movements-page">
    <Toast />

    <div class="flex align-items-center justify-content-between mb-4">
      <div>
        <h2 class="text-2xl font-bold m-0">Movimentações</h2>
        <p class="text-sm text-600 mt-1">Registre e consulte todas as movimentações de estoque</p>
      </div>
      <Button
        label="Nova Movimentação"
        icon="pi pi-plus"
        @click="showDialog = true"
      />
    </div>

    <div class="card">
      <Toolbar class="mb-3">
        <template #start>
          <div class="flex gap-2 flex-wrap align-items-center">
            <InputText
              v-model="filters.search"
              placeholder="Buscar por item..."
              class="p-inputtext-sm"
              @input="onSearch"
            />
            <Select
              v-model="filters.type"
              :options="typeOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Tipo"
              class="p-inputtext-sm"
              style="min-width: 150px"
              clearable
              @change="fetchMovements"
            />
            <DatePicker
              v-model="filters.from"
              placeholder="Data inicial"
              dateFormat="dd/mm/yy"
              showIcon
              iconDisplay="input"
              class="p-inputtext-sm"
              style="width: 150px"
              @date-select="fetchMovements"
            />
            <DatePicker
              v-model="filters.to"
              placeholder="Data final"
              dateFormat="dd/mm/yy"
              showIcon
              iconDisplay="input"
              class="p-inputtext-sm"
              style="width: 150px"
              @date-select="fetchMovements"
            />
            <InputText
              v-model="filters.user_name"
              placeholder="Responsável"
              class="p-inputtext-sm"
              style="width: 160px"
              @input="onSearchUser"
            />
            <Button
              label="Limpar Filtros"
              severity="secondary"
              size="small"
              text
              @click="clearFilters"
            />
          </div>
        </template>
      </Toolbar>

      <DataTable
        :value="store.movements"
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
      >
        <Column field="created_at" header="Data/Hora" sortable>
          <template #body="{ data }">
            {{ formatDateTime(data.created_at) }}
          </template>
        </Column>
        <Column field="item" header="Item" sortable>
          <template #body="{ data }">
            <div v-if="data.item">
              <span class="font-medium">{{ data.item.name }}</span>
              <span v-if="data.item.code" class="text-600 text-sm ml-1">({{ data.item.code }})</span>
            </div>
            <span v-else class="text-600">-</span>
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
            <span
              :class="{
                'text-green-500 font-bold': data.quantity_display > 0,
                'text-red-500 font-bold': data.quantity_display < 0,
              }"
            >
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
        <Column header="Ações" style="width: 80px">
          <template #body="{ data }">
            <Button
              v-if="data.item"
              icon="pi pi-eye"
              severity="info"
              text
              rounded
              size="small"
              @click="viewItem(data.item.id)"
              v-tooltip.left="'Ver item'"
            />
          </template>
        </Column>
      </DataTable>
    </div>

    <InventoryMovementDialog
      :visible="showDialog"
      @close="showDialog = false"
      @created="onMovementCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import Toolbar from 'primevue/toolbar'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import InventoryMovementDialog from '@/modules/inventory/components/InventoryMovementDialog.vue'
import { useInventoryMovementStore } from '@/modules/inventory/store/InventoryMovementStore'
import { MOVEMENT_TYPE_OPTIONS } from '@/modules/inventory/types/inventory'

const router = useRouter()
const store = useInventoryMovementStore()
const toast = useToast()

const showDialog = ref(false)

const filters = ref({
  search: '',
  type: null as string | null,
  from: null as Date | null,
  to: null as Date | null,
  user_name: '',
})

const typeOptions = [
  { label: 'Todos os tipos', value: null },
  ...MOVEMENT_TYPE_OPTIONS.map(o => ({ label: o.label, value: o.value })),
]

const firstRow = computed(() => {
  return (store.pagination.current_page - 1) * store.pagination.per_page
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null
let userSearchTimeout: ReturnType<typeof setTimeout> | null = null

function onSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchMovements()
  }, 400)
}

function onSearchUser() {
  if (userSearchTimeout) clearTimeout(userSearchTimeout)
  userSearchTimeout = setTimeout(() => {
    fetchMovements()
  }, 400)
}

function buildParams(page = 1): Record<string, any> {
  const params: Record<string, any> = { page }
  if (filters.value.search) params.search = filters.value.search
  if (filters.value.type) params.type = filters.value.type
  if (filters.value.from) params.from = filters.value.from.toISOString().split('T')[0]
  if (filters.value.to) params.to = filters.value.to.toISOString().split('T')[0]
  if (filters.value.user_name) params.user_name = filters.value.user_name
  return params
}

function fetchMovements() {
  store.fetchAll(buildParams(1))
}

function onPage(event: any) {
  store.fetchAll(buildParams(event.page + 1))
}

function clearFilters() {
  filters.value = {
    search: '',
    type: null,
    from: null,
    to: null,
    user_name: '',
  }
  fetchMovements()
}

function onMovementCreated(data: any) {
  showDialog.value = false
  fetchMovements()

  // Show toast if movement caused critical stock (D-11)
  if (data.is_critical) {
    toast.add({
      severity: 'warn',
      summary: 'Estoque Crítico',
      detail: 'O item atingiu o estoque mínimo.',
      life: 6000,
    })
  }
}

function viewItem(itemId: string) {
  router.push(`/inventory/${itemId}`)
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
</script>
