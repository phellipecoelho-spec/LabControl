<template>
  <AuthForm
    title="Criar Conta"
    description="Preencha os dados para se cadastrar no LabControl"
    submitLabel="Criar conta"
    :loading="loading"
    :formErrors="validationErrors"
    @submit="handleRegister"
  >
    <div class="field">
      <label for="name">Nome</label>
      <InputText id="name" v-model="name" class="w-full" placeholder="Seu nome" autocomplete="name" />
    </div>

    <div class="field">
      <label for="email">Email</label>
      <InputText id="email" v-model="email" type="email" class="w-full" placeholder="seu@email.com" autocomplete="email" />
    </div>

    <PasswordInput v-model="password" label="Senha" placeholder="Mínimo 8 caracteres" autocomplete="new-password" />

    <PasswordInput v-model="passwordConfirmation" label="Confirmar Senha" placeholder="Repita a senha" autocomplete="new-password" />

    <template #footer>
      <router-link to="/login" class="text-sm">Já tem conta? Faça login</router-link>
    </template>
  </AuthForm>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import AuthForm from '@/components/auth/AuthForm.vue'
import PasswordInput from '@/components/auth/PasswordInput.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const toast = useToast()
const auth = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const validationErrors = ref<Record<string, string[]> | null>(null)

async function handleRegister() {
  loading.value = true
  validationErrors.value = null

  try {
    await auth.register({ name: name.value, email: email.value, password: password.value, password_confirmation: passwordConfirmation.value })
    toast.add({ severity: 'success', summary: 'Conta criada', detail: 'Email de verificação enviado. Verifique sua caixa de entrada.', life: 5000 })
    router.push({ name: 'verify-email.pending', query: { registered: '1' } })
  } catch (e: any) {
    if (e.response?.status === 422) {
      validationErrors.value = e.response.data.errors
    } else {
      toast.add({ severity: 'error', summary: 'Erro', detail: e.response?.data?.message || 'Erro ao registrar', life: 5000 })
    }
  } finally {
    loading.value = false
  }
}
</script>
