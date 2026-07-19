<template>
  <div class="password-change-form">
    <div class="grid formgrid p-fluid">
      <div class="field col-12 md:col-6">
        <label for="current_password">Senha Atual</label>
        <Password id="current_password" v-model="currentPassword" :feedback="false" toggleMask />
      </div>

      <div class="field col-12 md:col-6">
        <label for="new_password">Nova Senha</label>
        <Password id="new_password" v-model="newPassword" :feedback="true" toggleMask />
      </div>

      <div class="field col-12 md:col-6">
        <label for="password_confirmation">Confirmar Nova Senha</label>
        <Password id="password_confirmation" v-model="confirmPassword" :feedback="false" toggleMask />
      </div>
    </div>

    <div v-if="serverErrors.length" class="mb-3">
      <InlineMessage v-for="(err, i) in serverErrors" :key="i" severity="error" class="mb-1">{{ err }}</InlineMessage>
    </div>

    <p class="text-sm text-muted-color mb-3">
      Após alterar a senha, sua sessão atual permanecerá ativa.
    </p>

    <Button label="Alterar Senha" severity="warning" icon="pi pi-lock" :loading="saving" @click="changePassword" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Password from 'primevue/password'
import Button from 'primevue/button'
import InlineMessage from 'primevue/inlinemessage'
import { api } from '@/services/api'

const toast = useToast()
const saving = ref(false)
const serverErrors = ref<string[]>([])

const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')

async function changePassword() {
  serverErrors.value = []

  if (newPassword.value !== confirmPassword.value) {
    serverErrors.value = ['As senhas não conferem.']
    return
  }

  saving.value = true

  try {
    await api.put('/profile/password', {
      current_password: currentPassword.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    })

    toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Senha alterada com sucesso.', life: 3000 })

    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response.data.errors
      const messages: string[] = []
      for (const field in errors) {
        messages.push(...errors[field])
      }
      serverErrors.value = messages
    } else {
      toast.add({ severity: 'error', summary: 'Erro', detail: e.response?.data?.message || 'Erro ao alterar senha.', life: 5000 })
    }
  } finally {
    saving.value = false
  }
}
</script>
