<template>
  <AuthForm
    title="Redefinir Senha"
    description="Crie uma nova senha para sua conta"
    submitLabel="Redefinir senha"
    :loading="loading"
    :formErrors="validationErrors"
    @submit="handleResetPassword"
  >
    <PasswordInput v-model="password" label="Nova Senha" placeholder="Mínimo 8 caracteres" autocomplete="new-password" />

    <PasswordInput v-model="passwordConfirmation" label="Confirmar Nova Senha" placeholder="Repita a senha" autocomplete="new-password" />

    <template #footer>
      <router-link to="/login" class="text-sm">Voltar ao login</router-link>
    </template>
  </AuthForm>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import AuthForm from '@/components/auth/AuthForm.vue'
import PasswordInput from '@/components/auth/PasswordInput.vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const auth = useAuthStore()

const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const validationErrors = ref<Record<string, string[]> | null>(null)

const token = route.query.token as string
const email = route.query.email as string

onMounted(() => {
  if (!token || !email) {
    toast.add({ severity: 'error', summary: 'Link inválido', detail: 'Token ou email não encontrados na URL.', life: 5000 })
  }
})

async function handleResetPassword() {
  if (!token || !email) return
  loading.value = true
  validationErrors.value = null

  try {
    await auth.resetPassword(token, email, password.value, passwordConfirmation.value)
    toast.add({ severity: 'success', summary: 'Senha redefinida', detail: 'Sua senha foi redefinida com sucesso.', life: 5000 })
    router.push({ name: 'login', query: { reset: '1' } })
  } catch (e: any) {
    if (e.response?.status === 422) {
      validationErrors.value = e.response.data.errors
    } else {
      toast.add({ severity: 'error', summary: 'Erro', detail: e.response?.data?.message || 'Token inválido ou expirado.', life: 5000 })
    }
  } finally {
    loading.value = false
  }
}
</script>
