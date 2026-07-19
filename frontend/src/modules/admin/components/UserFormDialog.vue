<template>
  <Dialog
    :visible="visible"
    :header="isEditing ? 'Editar Usuário' : 'Novo Usuário'"
    modal
    :style="{ width: '520px' }"
    class="user-form-dialog"
    @update:visible="onCancel"
  >
    <div class="flex flex-column gap-3 p-3">
      <div class="field">
        <label for="name">Nome</label>
        <InputText id="name" v-model="form.name" class="w-full" />
      </div>

      <div class="field">
        <label for="email">Email</label>
        <InputText id="email" v-model="form.email" type="email" class="w-full" />
      </div>

      <div class="field">
        <label for="phone">Telefone</label>
        <InputText id="phone" v-model="form.phone" class="w-full" />
      </div>

      <div class="flex gap-3">
        <div class="field flex-1">
          <label for="position">Cargo</label>
          <InputText id="position" v-model="form.position" class="w-full" />
        </div>
        <div class="field flex-1">
          <label for="department">Departamento</label>
          <InputText id="department" v-model="form.department" class="w-full" />
        </div>
      </div>

      <div v-if="!isEditing" class="flex gap-3">
        <div class="field flex-1">
          <label for="password">Senha</label>
          <InputText id="password" v-model="form.password" type="password" class="w-full" />
        </div>
        <div class="field flex-1">
          <label for="password_confirmation">Confirmar Senha</label>
          <InputText id="password_confirmation" v-model="form.password_confirmation" type="password" class="w-full" />
        </div>
      </div>

      <div class="field">
        <label>Perfis</label>
        <MultiSelect
          v-model="form.roles"
          :options="roles"
          optionLabel="name"
          optionValue="id"
          placeholder="Selecione os perfis"
          class="w-full"
        />
      </div>

      <div class="field">
        <label>Ativo</label>
        <SelectButton
          v-model="form.is_active"
          :options="activeOptions"
          optionLabel="label"
          optionValue="value"
        />
      </div>
    </div>

    <template #footer>
      <Button label="Cancelar" severity="secondary" @click="onCancel" />
      <Button label="Salvar" @click="onSave" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import MultiSelect from 'primevue/multiselect'
import SelectButton from 'primevue/selectbutton'
import Button from 'primevue/button'
import type { User } from '@/stores/auth'
import type { Role } from '@/stores/roles'

const props = defineProps<{
  user: User | null
  roles: Role[]
  visible: boolean
}>()

const emit = defineEmits<{
  save: [data: Record<string, any>]
  cancel: []
}>()

const isEditing = computed(() => props.user !== null)

const activeOptions = [
  { label: 'Ativo', value: true },
  { label: 'Inativo', value: false },
]

const form = ref<Record<string, any>>({
  name: '',
  email: '',
  phone: '',
  position: '',
  department: '',
  password: '',
  password_confirmation: '',
  roles: [],
  is_active: true,
})

watch(() => props.visible, (visible) => {
  if (visible) {
    if (props.user) {
      form.value = {
        name: props.user.name || '',
        email: props.user.email || '',
        phone: (props.user as any).phone || '',
        position: (props.user as any).position || '',
        department: (props.user as any).department || '',
        password: '',
        password_confirmation: '',
        roles: props.user.roles?.map(r => r.id) || [],
        is_active: props.user.is_active ?? true,
      }
    } else {
      form.value = {
        name: '',
        email: '',
        phone: '',
        position: '',
        department: '',
        password: '',
        password_confirmation: '',
        roles: [],
        is_active: true,
      }
    }
  }
})

function onCancel() {
  emit('cancel')
}

function onSave() {
  const data: Record<string, any> = { ...form.value }
  if (isEditing.value) {
    if (!data.password) {
      delete data.password
      delete data.password_confirmation
    }
  }
  emit('save', data)
}
</script>
