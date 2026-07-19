<template>
  <div class="auth-container">
    <Card>
      <template #title>
        <h2>Verifique seu Email</h2>
      </template>
      <template #content>
        <div class="flex flex-column align-items-center gap-3 p-4 text-center">
          <i class="pi pi-envelope" style="font-size: 3rem; color: var(--p-primary-color)" />
          <p>Enviamos um link de verificação para seu email.</p>
          <p class="text-sm text-muted-color">Clique no link para ativar sua conta.</p>

          <Button label="Reenviar email" @click="resend" :loading="resending" />

          <Button label="Ir para login" severity="secondary" @click="goToLogin" />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const toast = useToast()
const auth = useAuthStore()

const resending = ref(false)

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
