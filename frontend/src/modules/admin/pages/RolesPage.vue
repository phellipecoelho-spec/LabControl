<template>
  <div class="roles-page">
    <Toast />
    <ConfirmDialog />

    <div class="flex align-items-center justify-content-between mb-4">
      <div>
        <h2 class="text-2xl font-bold m-0">Perfis de Acesso</h2>
        <p class="text-sm text-600 mt-1">Gerencie os perfis e permissões do sistema</p>
      </div>
      <Button label="Novo Perfil" icon="pi pi-plus" @click="openCreateDialog" />
    </div>

    <div class="grid">
      <div class="col-5">
        <div class="card">
          <DataTable
            :value="rolesStore.roles"
            :loading="rolesStore.loading"
            selectionMode="single"
            :selection="selectedRole"
            @update:selection="onRoleSelect"
            dataKey="id"
            stripedRows
            size="small"
          >
            <Column field="name" header="Perfil" sortable>
              <template #body="{ data }">
                <div>
                  <span class="font-medium">{{ data.name }}</span>
                  <span class="block text-xs text-500">{{ data.slug }}</span>
                </div>
              </template>
            </Column>
            <Column field="description" header="Descrição">
              <template #body="{ data }">
                <span class="text-sm">{{ data.description }}</span>
              </template>
            </Column>
            <Column header="Sistema" style="width: 80px">
              <template #body="{ data }">
                <Tag
                  v-if="data.is_system"
                  value="Sistema"
                  severity="warning"
                  rounded
                />
              </template>
            </Column>
            <Column field="users_count" header="Usuários" style="width: 80px" sortable>
              <template #body="{ data }">
                <span class="font-medium">{{ data.users_count ?? 0 }}</span>
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
                    @click.stop="openEditDialog(data)"
                  />
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    rounded
                    size="small"
                    :disabled="data.slug === 'admin' || (data.users_count ?? 0) > 0"
                    @click.stop="confirmDelete(data)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>

      <div class="col-7">
        <div class="card">
          <RolePermissionEditor
            :role="selectedRole"
            :allPermissions="allPermissions"
            @save="handlePermissionsSave"
          />
        </div>
      </div>
    </div>

    <Dialog
      :visible="showFormDialog"
      :header="isEditingRole ? 'Editar Perfil' : 'Novo Perfil'"
      modal
      :style="{ width: '450px' }"
      @update:visible="closeFormDialog"
    >
      <div class="flex flex-column gap-3 p-3">
        <div class="field">
          <label for="roleName">Nome</label>
          <InputText id="roleName" v-model="roleForm.name" class="w-full" />
        </div>
        <div class="field">
          <label for="roleDescription">Descrição</label>
          <Textarea id="roleDescription" v-model="roleForm.description" class="w-full" rows="3" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="closeFormDialog" />
        <Button label="Salvar" @click="handleRoleSave" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import RolePermissionEditor from '@/modules/admin/components/RolePermissionEditor.vue'
import { useRolesStore } from '@/stores/roles'
import type { Role, Permission } from '@/stores/roles'

const rolesStore = useRolesStore()
const toast = useToast()
const confirm = useConfirm()

const selectedRole = ref<Role | null>(null)
const showFormDialog = ref(false)
const isEditingRole = ref(false)
const roleForm = ref({ name: '', description: '' })

const allPermissions = ref<Permission[]>([])

function onRoleSelect(role: Role | null) {
  selectedRole.value = role
  if (role && role.permissions) {
    allPermissions.value = role.permissions
  }
}

async function fetchRoles() {
  await rolesStore.fetchAll()
  const perms: Permission[] = []
  const seen = new Set<string>()
  for (const role of rolesStore.roles) {
    if (role.permissions) {
      for (const p of role.permissions) {
        if (!seen.has(p.id)) {
          seen.add(p.id)
          perms.push(p)
        }
      }
    }
  }
  allPermissions.value = perms
}

function openCreateDialog() {
  isEditingRole.value = false
  roleForm.value = { name: '', description: '' }
  showFormDialog.value = true
}

function openEditDialog(role: Role) {
  isEditingRole.value = true
  roleForm.value = { name: role.name, description: role.description }
  selectedRole.value = role
  showFormDialog.value = true
}

function closeFormDialog() {
  showFormDialog.value = false
}

async function handleRoleSave() {
  try {
    if (isEditingRole.value && selectedRole.value) {
      await rolesStore.update(selectedRole.value.id, roleForm.value)
      toast.add({ severity: 'success', summary: 'Perfil atualizado', detail: 'O perfil foi atualizado com sucesso.', life: 3000 })
    } else {
      await rolesStore.create(roleForm.value)
      toast.add({ severity: 'success', summary: 'Perfil criado', detail: 'O perfil foi cadastrado com sucesso.', life: 3000 })
    }
    closeFormDialog()
    await fetchRoles()
  } catch (e: any) {
    toast.add({ severity: 'error', summary: 'Erro', detail: e.response?.data?.message || 'Ocorreu um erro ao salvar.', life: 5000 })
  }
}

async function handlePermissionsSave(roleId: string, permissionIds: string[]) {
  try {
    await rolesStore.syncPermissions(roleId, permissionIds)
    toast.add({ severity: 'success', summary: 'Permissões salvas', detail: 'As permissões foram atualizadas com sucesso.', life: 3000 })
    await fetchRoles()
    const updated = rolesStore.roles.find(r => r.id === roleId)
    if (updated) selectedRole.value = updated
  } catch (e: any) {
    toast.add({ severity: 'error', summary: 'Erro', detail: e.response?.data?.message || 'Ocorreu um erro ao salvar permissões.', life: 5000 })
  }
}

function confirmDelete(role: Role) {
  confirm.require({
    message: `Tem certeza que deseja excluir o perfil ${role.name}?`,
    header: 'Confirmar Exclusão',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancelar',
    acceptLabel: 'Excluir',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await rolesStore.destroy(role.id)
        toast.add({ severity: 'success', summary: 'Perfil excluído', detail: 'O perfil foi removido com sucesso.', life: 3000 })
        if (selectedRole.value?.id === role.id) {
          selectedRole.value = null
        }
        await fetchRoles()
      } catch (e: any) {
        toast.add({ severity: 'error', summary: 'Erro', detail: e.response?.data?.message || 'Ocorreu um erro ao excluir.', life: 5000 })
      }
    },
  })
}

onMounted(() => {
  fetchRoles()
})
</script>
