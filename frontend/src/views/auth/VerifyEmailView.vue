<template>
  <div class="auth-container">
    <Card>
      <template #title>
        <h2>Verificando Email</h2>
      </template>
      <template #content>
        <div class="flex flex-column align-items-center gap-3 p-4">
          <i v-if="status === 'loading'" class="pi pi-spin pi-spinner" style="font-size: 3rem" />
          <i v-else-if="status === 'success'" class="pi pi-check-circle text-green-500" style="font-size: 3rem" />
          <i v-else-if="status === 'error'" class="pi pi-times-circle text-red-500" style="font-size: 3rem" />

          <p v-if="status === 'loading'">Verificando seu email...</p>
          <p v-if="status === 'success'">Email verificado com sucesso!</p>
          <p v-if="status === 'error'">{{ errorMessage }}</p>

          <Button v-if="status === 'error'" label="Reenviar email" @click="resend" :loading="resending" />
          <Button v-if="status === 'error'" label="Ir para login" severity="secondary" @click="goToLogin" />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const auth = useAuthStore()

const status = ref<'loading' | 'success' | 'error'>('loading')
const errorMessage = ref('')
const resending = ref(false)

const id = route.params.id as string
const hash = route.params.hash as string

onMounted(async () => {
  if (!id || !hash) {
    status.value = 'error'
    errorMessage.value = 'Link de verificação inválido.'
    return
  }

  try {
    await auth.verifyEmail(id, hash)
    status.value = 'success'
    toast.add({ severity: 'success', summary: 'Email verificado', detail: 'Seu email foi verificado com sucesso!', life: 5000 })
    setTimeout(() => router.push('/login?verified=1'), 2000)
  } catch (e: any) {
    status.value = 'error'
    errorMessage.value = e.response?.data?.message || 'Link inválido ou expirado.'
  }
})

async function resend() {
  resending.value = true
  try {
    await auth.resendVerification()
    toast.add({ severity: 'success', summary: 'Reenviado', detail: 'Novo link de verificação enviado.', life: 5000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível reenviar. Tente novamente mais tarde.', life: 5000 })
  } finally {
    resending.value = false
  }
}

function goToLogin() {
  router.push('/login')
}
</script>

<style scoped>
.auth-container {
  max-width: 28rem;
  margin: 4rem auto;
  padding: 0 1rem;
}
</style>
