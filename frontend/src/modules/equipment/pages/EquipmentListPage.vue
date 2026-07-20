<template>
  <div class="equipment-list-page">
    <Toast />
    <ConfirmDialog />

    <div class="flex align-items-center justify-content-between mb-4">
      <div>
        <h2 class="text-2xl font-bold m-0">Equipamentos</h2>
        <p class="text-sm text-600 mt-1">Gerencie os equipamentos do laboratório</p>
      </div>
      <Button
        v-if="authStore.hasPermission('equipamentos.create')"
        label="Novo Equipamento"
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
              placeholder="Buscar por nome ou patrimônio..."
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
              @change="fetchEquipments"
            />
            <Select
              v-model="filters.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Status"
              class="p-inputtext-sm"
              style="min-width: 140px"
              clearable
              @change="fetchEquipments"
            />
          </div>
        </template>
      </Toolbar>

      <DataTable
        :value="equipmentStore.equipments"
        :loading="equipmentStore.loading"
        paginator
        :rows="equipmentStore.pagination.per_page"
        :totalRecords="equipmentStore.pagination.total"
        :first="firstRow"
        lazy
        @page="onPage"
        sortField="created_at"
        :sortOrder="-1"
        stripedRows
        size="small"
      >
        <Column field="name" header="Nome" sortable />
        <Column field="patrimony_id" header="Patrimônio" sortable />
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
        <Column field="manufacturer.name" header="Fabricante" sortable />
        <Column field="serial_number" header="Nº Série" sortable />
        <Column field="location" header="Localização" sortable />
        <Column field="status" header="Status" sortable>
          <template #body="{ data }">
            <Tag
              :value="getStatusLabel(data.status)"
              :severity="getSeverity(data.status)"
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
                @click="viewEquipment(data)"
              />
              <Button
                v-if="authStore.hasPermission('equipamentos.edit')"
                icon="pi pi-pencil"
                severity="secondary"
                text
                rounded
                size="small"
                @click="goToEdit(data)"
              />
              <Button
                v-if="authStore.hasPermission('equipamentos.delete')"
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
import { useEquipmentStore } from '@/modules/equipment/store/EquipmentStore'
import { useAuthStore } from '@/stores/auth'
import type { Equipment } from '@/modules/equipment/types/equipment'

const router = useRouter()
const equipmentStore = useEquipmentStore()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()

const filters = ref({
  search: '',
  category_id: null as string | null,
  status: null as string | null,
})

const statusOptions = [
  { label: 'Todos', value: null },
  { label: 'Ativo', value: 'active' },
  { label: 'Inativo', value: 'inactive' },
  { label: 'Manutenção', value: 'maintenance' },
  { label: 'Baixado', value: 'retired' },
]

const categoryOptions = computed(() => [
  { label: 'Todas', value: null },
  ...equipmentStore.categories.map(c => ({ label: c.name, value: c.id })),
])

const firstRow = computed(() => {
  return (equipmentStore.pagination.current_page - 1) * equipmentStore.pagination.per_page
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

function onSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchEquipments()
  }, 400)
}

function fetchEquipments() {
  const params: Record<string, any> = {
    page: 1,
  }
  if (filters.value.search) params.search = filters.value.search
  if (filters.value.category_id) params.category_id = filters.value.category_id
  if (filters.value.status) params.status = filters.value.status
  equipmentStore.fetchAll(params)
}

function onPage(event: any) {
  const params: Record<string, any> = {
    page: event.page + 1,
  }
  if (filters.value.search) params.search = filters.value.search
  if (filters.value.category_id) params.category_id = filters.value.category_id
  if (filters.value.status) params.status = filters.value.status
  equipmentStore.fetchAll(params)
}

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

function goToCreate() {
  router.push({ name: 'equipment-create' })
}

function goToEdit(equipment: Equipment) {
  router.push({ name: 'equipment-edit', params: { id: equipment.id } })
}

function viewEquipment(equipment: Equipment) {
  router.push(`/equipments/${equipment.id}`)
}

function confirmDelete(equipment: Equipment) {
  confirm.require({
    message: `Tem certeza que deseja excluir o equipamento "${equipment.name}"?`,
    header: 'Confirmar Exclusão',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancelar',
    acceptLabel: 'Excluir',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await equipmentStore.destroy(equipment.id)
        toast.add({
          severity: 'success',
          summary: 'Equipamento excluído',
          detail: 'O equipamento foi removido com sucesso.',
          life: 3000,
        })
        fetchEquipments()
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
  fetchEquipments()
  equipmentStore.fetchCategories()
  equipmentStore.fetchManufacturers()
  equipmentStore.fetchSuppliers()
})
</script>