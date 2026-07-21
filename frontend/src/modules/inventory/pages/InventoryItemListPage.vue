<template>
  <div class="inventory-list-page">
    <Toast />
    <ConfirmDialog />

    <div class="flex align-items-center justify-content-between mb-4">
      <div>
        <h2 class="text-2xl font-bold m-0">Estoque</h2>
        <p class="text-sm text-600 mt-1">Gerencie os itens do estoque do laboratório</p>
      </div>
      <Button
        v-if="authStore.hasPermission('estoque.create')"
        label="Novo Item"
        icon="pi pi-plus"
        @click="goToCreate"
      />
    </div>

    <div class="card">
      <Toolbar class="mb-3">
        <template #start>
          <div class="flex gap-2 flex-wrap">
            <InputText
              v-model="filters.search"
              placeholder="Buscar por nome ou código..."
              class="p-inputtext-sm"
              @input="onSearch"
            />
            <Select
              v-model="filters.category_id"
              :options="categoryOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Categoria"
              class="p-inputtext-sm"
              style="min-width: 160px"
              clearable
              @change="fetchItems"
            />
            <Select
              v-model="filters.unit"
              :options="unitOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Unidade"
              class="p-inputtext-sm"
              style="min-width: 120px"
              clearable
              @change="fetchItems"
            />
            <Select
              v-model="filters.critical"
              :options="criticalOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Status"
              class="p-inputtext-sm"
              style="min-width: 140px"
              clearable
              @change="fetchItems"
            />
          </div>
        </template>
      </Toolbar>

      <DataTable
        :value="store.items"
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
        <Column field="name" header="Nome" sortable />
        <Column field="code" header="Código" sortable />
        <Column field="category.name" header="Categoria" sortable>
          <template #body="{ data }">
            <Tag
              v-if="data.category"
              :value="data.category.name"
              severity="info"
              rounded
              size="small"
            />
            <span v-else class="text-600">-</span>
          </template>
        </Column>
        <Column field="current_balance" header="Quantidade Atual" sortable>
          <template #body="{ data }">
            <span :class="{ 'font-bold text-red-500': data.is_critical }">
              {{ data.current_balance }}
            </span>
          </template>
        </Column>
        <Column field="unit" header="Unidade" sortable />
        <Column field="min_stock" header="Estoque Mínimo" sortable />
        <Column field="supplier.name" header="Fornecedor" sortable />
        <Column field="is_critical" header="Status" sortable>
          <template #body="{ data }">
            <Tag
              v-if="data.is_critical"
              value="Crítico"
              severity="danger"
              rounded
              size="small"
            />
            <Tag
              v-else
              value="Normal"
              severity="success"
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
                @click="viewItem(data)"
              />
              <Button
                v-if="authStore.hasPermission('estoque.edit')"
                icon="pi pi-pencil"
                severity="secondary"
                text
                rounded
                size="small"
                @click="goToEdit(data)"
              />
              <Button
                v-if="authStore.hasPermission('estoque.delete')"
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
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { useInventoryItemStore } from '@/modules/inventory/store/InventoryItemStore'
import { useAuthStore } from '@/stores/auth'
import { INVENTORY_UNITS } from '@/modules/inventory/types/inventory'
import type { InventoryItem } from '@/modules/inventory/types/inventory'

const router = useRouter()
const store = useInventoryItemStore()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()

const filters = ref({
  search: '',
  category_id: null as string | null,
  unit: null as string | null,
  critical: null as string | null,
})

const criticalOptions = [
  { label: 'Todos', value: null },
  { label: 'Crítico', value: '1' },
  { label: 'Normal', value: '0' },
]

const categoryOptions = computed(() => [
  { label: 'Todas', value: null },
  ...store.categories.map(c => ({ label: c.name, value: c.id })),
])

const unitOptions = computed(() => [
  { label: 'Todas', value: null },
  ...INVENTORY_UNITS.map(u => ({ label: u.label, value: u.value })),
])

const firstRow = computed(() => {
  return (store.pagination.current_page - 1) * store.pagination.per_page
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

function rowClass(data: InventoryItem) {
  return data.is_critical ? 'p-row-critical' : ''
}

function onSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchItems()
  }, 400)
}

function fetchItems() {
  const params: Record<string, any> = {
    page: 1,
  }
  if (filters.value.search) params.search = filters.value.search
  if (filters.value.category_id) params.category_id = filters.value.category_id
  if (filters.value.unit) params.unit = filters.value.unit
  if (filters.value.critical) params.critical = filters.value.critical
  store.fetchAll(params)
}

function onPage(event: any) {
  const params: Record<string, any> = {
    page: event.page + 1,
  }
  if (filters.value.search) params.search = filters.value.search
  if (filters.value.category_id) params.category_id = filters.value.category_id
  if (filters.value.unit) params.unit = filters.value.unit
  if (filters.value.critical) params.critical = filters.value.critical
  store.fetchAll(params)
}

function goToCreate() {
  router.push({ name: 'inventory.create' })
}

function goToEdit(item: InventoryItem) {
  router.push({ name: 'inventory.edit', params: { id: item.id } })
}

function viewItem(item: InventoryItem) {
  router.push(`/inventory/${item.id}`)
}

function confirmDelete(item: InventoryItem) {
  confirm.require({
    message: `Tem certeza que deseja excluir o item "${item.name}"?`,
    header: 'Confirmar Exclusão',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancelar',
    acceptLabel: 'Excluir',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await store.destroy(item.id)
        toast.add({
          severity: 'success',
          summary: 'Item excluído',
          detail: 'O item foi removido com sucesso.',
          life: 3000,
        })
        fetchItems()
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

onMounted(() => {
  fetchItems()
  store.fetchCategories()
  store.fetchSuppliers()
})
</script>
