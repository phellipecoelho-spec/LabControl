<template>
  <AuthForm
    title="Entrar no LabControl"
    submitLabel="Entrar"
    :loading="loading"
    :formErrors="validationErrors"
    @submit="handleLogin"
  >
    <div class="field">
      <label for="email">Email</label>
      <InputText
        id="email"
        v-model="email"
        type="email"
        class="w-full"
        placeholder="seu@email.com"
        autocomplete="email"
      />
    </div>

    <div class="field">
      <PasswordInput
        v-model="password"
        label="Senha"
        placeholder="Sua senha"
        autocomplete="current-password"
      />
    </div>

    <div class="field-checkbox">
      <Checkbox v-model="remember" inputId="remember" :binary="true" />
      <label for="remember" class="ml-2">Lembrar-me</label>
    </div>

    <template #footer>
      <div class="flex flex-column gap-2">
        <router-link to="/forgot-password" class="text-sm">Esqueci a senha</router-link>
        <router-link to="/register" class="text-sm">Criar conta</router-link>
      </div>
    </template>
  </AuthForm>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Checkbox from 'primevue/checkbox'
import AuthForm from '@/components/auth/AuthForm.vue'
import PasswordInput from '@/components/auth/PasswordInput.vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const remember = ref(false)
const loading = ref(false)
const validationErrors = ref<Record<string, string[]> | null>(null)

async function handleLogin() {
  loading.value = true
  validationErrors.value = null

  try {
    await auth.login({ email: email.value, password: password.value, remember: remember.value })
    const redirect = (route.query.redirect as string) || '/'
    router.push(redirect)
  } catch (e: any) {
    if (e.response?.status === 422) {
      validationErrors.value = e.response.data.errors
    } else if (e.response?.status === 403) {
      toast.add({ severity: 'warn', summary: 'Email não verificado', detail: 'Verifique seu email antes de acessar o sistema.', life: 5000 })
      router.push({ name: 'verify-email.pending' })
    } else {
      toast.add({ severity: 'error', summary: 'Erro', detail: e.response?.data?.message || 'Erro ao fazer login', life: 5000 })
    }
  } finally {
    loading.value = false
  }
}
</script>
