<template>
  <div class="unauthorized-container">
    <Card class="unauthorized-card">
      <template #title>
        <div class="flex items-center gap-2">
          <i class="pi pi-lock text-2xl text-red-500" />
          <h2>Acesso Negado</h2>
        </div>
      </template>
      <template #content>
        <p class="text-center text-muted-color mb-4">
          Você não tem permissão para acessar esta página.
        </p>
        <div class="flex gap-2 justify-center">
          <Button label="Voltar ao Dashboard" icon="pi pi-home" @click="goDashboard" />
          <Button label="Sair" icon="pi pi-sign-out" severity="secondary" @click="logout" />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Card from 'primevue/card'
import Button from 'primevue/button'

const router = useRouter()
const auth = useAuthStore()

function goDashboard() {
  router.push({ name: 'dashboard' })
}

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<style scoped>
.unauthorized-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.unauthorized-card {
  max-width: 24rem;
  width: 100%;
}
</style>