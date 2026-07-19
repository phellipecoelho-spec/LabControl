<template>
  <div class="profile-info-form">
    <div class="grid formgrid p-fluid">
      <div class="field col-12 md:col-6">
        <label for="name">Nome</label>
        <InputText id="name" v-model="form.name" />
      </div>

      <div class="field col-12 md:col-6">
        <label for="email">Email</label>
        <InputText id="email" v-model="form.email" type="email" />
      </div>

      <div class="field col-12 md:col-6">
        <label for="phone">Telefone</label>
        <InputText id="phone" v-model="form.phone" />
      </div>

      <div class="field col-12 md:col-6">
        <label for="position">Cargo</label>
        <InputText id="position" v-model="form.position" />
      </div>

      <div class="field col-12 md:col-6">
        <label for="department">Departamento</label>
        <InputText id="department" v-model="form.department" />
      </div>

      <div class="field col-12">
        <label for="signature">Assinatura</label>
        <InputText id="signature" v-model="form.signature" />
      </div>
    </div>

    <div v-if="serverErrors.length" class="mb-3">
      <InlineMessage v-for="(err, i) in serverErrors" :key="i" severity="error" class="mb-1">{{ err }}</InlineMessage>
    </div>

    <Button label="Salvar" severity="primary" :loading="saving" @click="saveProfile" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import InlineMessage from 'primevue/inlinemessage'
import { api } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const toast = useToast()
const saving = ref(false)
const serverErrors = ref<string[]>([])

const form = ref({
  name: '',
  email: '',
  phone: '',
  position: '',
  department: '',
  signature: '',
})

onMounted(() => {
  if (auth.user) {
    form.value.name = auth.user.name || ''
    form.value.email = auth.user.email || ''
    form.value.phone = (auth.user as any).phone || ''
    form.value.position = (auth.user as any).position || ''
    form.value.department = (auth.user as any).department || ''
    form.value.signature = (auth.user as any).signature || ''
  }
})

async function saveProfile() {
  saving.value = true
  serverErrors.value = []

  try {
    await api.put('/profile', form.value)
    await auth.fetchUser()
    toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Perfil atualizado com sucesso.', life: 3000 })
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response.data.errors
      const messages: string[] = []
      for (const field in errors) {
        messages.push(...errors[field])
      }
      serverErrors.value = messages
    } else {
      toast.add({ severity: 'error', summary: 'Erro', detail: e.response?.data?.message || 'Erro ao salvar perfil.', life: 5000 })
    }
  } finally {
    saving.value = false
  }
}
</script>
