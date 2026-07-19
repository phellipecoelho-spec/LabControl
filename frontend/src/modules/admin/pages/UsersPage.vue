<template>
  <div class="users-page">
    <Toast />
    <ConfirmDialog />

    <div class="flex align-items-center justify-content-between mb-4">
      <div>
        <h2 class="text-2xl font-bold m-0">Usuários</h2>
        <p class="text-sm text-600 mt-1">Gerencie os usuários do sistema</p>
      </div>
      <Button
        v-if="canCreate"
        label="Novo Usuário"
        icon="pi pi-plus"
        @click="openCreateDialog"
      />
    </div>

    <div class="card">
      <Toolbar class="mb-3">
        <template #start>
          <div class="flex gap-2 flex-wrap">
            <InputText
              v-model="filters.search"
              placeholder="Buscar por nome ou email..."
              class="p-inputtext-sm"
              @input="onSearch"
            />
            <Select
              v-model="filters.role"
              :options="roleOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Perfil"
              class="p-inputtext-sm"
              style="min-width: 160px"
              clearable
              @change="fetchUsers"
            />
            <SelectButton
              v-model="filters.is_active"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              allowEmpty
              @change="fetchUsers"
            />
          </div>
        </template>
      </Toolbar>

      <DataTable
        :value="usersStore.users"
        :loading="usersStore.loading"
        paginator
        :rows="usersStore.pagination.per_page"
        :totalRecords="usersStore.pagination.total"
        :first="firstRow"
        lazy
        @page="onPage"
        sortField="created_at"
        :sortOrder="-1"
        stripedRows
        size="small"
      >
        <Column header="Usuário" sortable sortField="name">
          <template #body="{ data }">
            <div class="flex align-items-center gap-2">
              <Avatar :label="getInitials(data.name)" size="small" shape="circle" />
              <div>
                <span class="font-medium">{{ data.name }}</span>
                <span class="block text-xs text-600">{{ data.email }}</span>
              </div>
            </div>
          </template>
        </Column>
        <Column field="phone" header="Telefone" sortable />
        <Column header="Perfis">
          <template #body="{ data }">
            <div class="flex gap-1 flex-wrap">
              <Tag
                v-for="role in data.roles"
                :key="role.id"
                :value="role.name"
                :severity="role.slug === 'admin' ? 'danger' : 'info'"
                rounded
              />
            </div>
          </template>
        </Column>
        <Column field="is_active" header="Status" sortable>
          <template #body="{ data }">
            <Tag
              :value="data.is_active ? 'Ativo' : 'Inativo'"
              :severity="data.is_active ? 'success' : 'danger'"
              rounded
            />
          </template>
        </Column>
        <Column field="created_at" header="Criado em" sortable>
          <template #body="{ data }">
            {{ formatDate(data.created_at) }}
          </template>
        </Column>
        <Column header="Ações" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button
                icon="pi pi-pencil"
                severity="secondary"
                text
                rounded
                size="small"
                @click="openEditDialog(data)"
              />
              <Button
                icon="pi pi-trash"
                severity="danger"
                text
                rounded
                size="small"
                :disabled="hasAdminRole(data)"
                @click="confirmDelete(data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <UserFormDialog
      :user="selectedUser"
      :roles="rolesStore.roles"
      :visible="showFormDialog"
      @save="handleSave"
      @cancel="closeFormDialog"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import Toolbar from 'primevue/toolbar'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import SelectButton from 'primevue/selectbutton'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import UserFormDialog from '@/modules/admin/components/UserFormDialog.vue'
import { useUsersStore } from '@/stores/users'
import { useRolesStore } from '@/stores/roles'
import { useAuthStore } from '@/stores/auth'
import type { User } from '@/stores/auth'

const usersStore = useUsersStore()
const rolesStore = useRolesStore()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()

const selectedUser = ref<User | null>(null)
const showFormDialog = ref(false)

const filters = ref({
  search: '',
  role: null as string | null,
  is_active: null as boolean | null,
})

const statusOptions = [
  { label: 'Todos', value: null },
  { label: 'Ativo', value: true },
  { label: 'Inativo', value: false },
]

const roleOptions = computed(() => [
  ...rolesStore.roles.map(r => ({ label: r.name, value: r.slug })),
])

const firstRow = computed(() => {
  return (usersStore.pagination.current_page - 1) * usersStore.pagination.per_page
})

const canCreate = computed(() => {
  return authStore.hasPermission('usuarios.create')
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

function onSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchUsers()
  }, 400)
}

function fetchUsers() {
  const params: Record<string, any> = {
    page: 1,
  }
  if (filters.value.search) params.search = filters.value.search
  if (filters.value.role) params.role = filters.value.role
  if (filters.value.is_active !== null) params.is_active = filters.value.is_active
  usersStore.fetchAll(params)
}

function onPage(event: any) {
  const params: Record<string, any> = {
    page: event.page + 1,
  }
  if (filters.value.search) params.search = filters.value.search
  if (filters.value.role) params.role = filters.value.role
  if (filters.value.is_active !== null) params.is_active = filters.value.is_active
  usersStore.fetchAll(params)
}

function getInitials(name: string): string {
  return name
    .split(' ')
    .map(w => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

function formatDate(date: string): string {
  if (!date) return ''
  return new Date(date).toLocaleDateString('pt-BR')
}

function hasAdminRole(user: User): boolean {
  return user.roles?.some(r => r.slug === 'admin') ?? false
}

function openCreateDialog() {
  selectedUser.value = null
  showFormDialog.value = true
}

function openEditDialog(user: User) {
  selectedUser.value = user
  showFormDialog.value = true
}

function closeFormDialog() {
  showFormDialog.value = false
  selectedUser.value = null
}

async function handleSave(data: Record<string, any>) {
  try {
    if (selectedUser.value) {
      await usersStore.update(selectedUser.value.id, data)
      toast.add({ severity: 'success', summary: 'Usuário atualizado', detail: 'Os dados foram salvos com sucesso.', life: 3000 })
    } else {
      await usersStore.create(data)
      toast.add({ severity: 'success', summary: 'Usuário criado', detail: 'O usuário foi cadastrado com sucesso.', life: 3000 })
    }
    closeFormDialog()
    fetchUsers()
  } catch (e: any) {
    toast.add({ severity: 'error', summary: 'Erro', detail: e.response?.data?.message || 'Ocorreu um erro ao salvar.', life: 5000 })
  }
}

function confirmDelete(user: User) {
  confirm.require({
    message: `Tem certeza que deseja excluir o usuário ${user.name}?`,
    header: 'Confirmar Exclusão',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancelar',
    acceptLabel: 'Excluir',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await usersStore.destroy(user.id)
        toast.add({ severity: 'success', summary: 'Usuário excluído', detail: 'O usuário foi removido com sucesso.', life: 3000 })
        fetchUsers()
      } catch (e: any) {
        toast.add({ severity: 'error', summary: 'Erro', detail: e.response?.data?.message || 'Ocorreu um erro ao excluir.', life: 5000 })
      }
    },
  })
}

onMounted(() => {
  usersStore.fetchAll()
  rolesStore.fetchAll()
})
</script>
