<template>
  <AuthForm
    title="Recuperar Senha"
    description="Digite seu email para receber instruções de redefinição"
    submitLabel="Enviar instruções"
    :loading="loading"
    @submit="handleForgotPassword"
  >
    <div class="field">
      <label for="email">Email</label>
      <InputText id="email" v-model="email" type="email" class="w-full" placeholder="seu@email.com" autocomplete="email" />
    </div>

    <template #footer>
      <router-link to="/login" class="text-sm">Voltar ao login</router-link>
    </template>
  </AuthForm>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import AuthForm from '@/components/auth/AuthForm.vue'
import { useAuthStore } from '@/stores/auth'

const toast = useToast()
const auth = useAuthStore()

const email = ref('')
const loading = ref(false)

async function handleForgotPassword() {
  loading.value = true
  try {
    await auth.forgotPassword(email.value)
    toast.add({ severity: 'success', summary: 'Instruções enviadas', detail: 'Se o email existir, enviaremos instruções de redefinição.', life: 5000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Erro', detail: 'Se o email existir, enviaremos instruções de redefinição.', life: 5000 })
  } finally {
    loading.value = false
  }
}
</script>
