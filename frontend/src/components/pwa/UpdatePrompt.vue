<template>
  <!-- Fixed-bottom banner when new version is available -->
  <div v-if="showBanner" class="update-prompt">
    <Message severity="info" :closable="false">
      <div class="update-prompt__content">
        <span class="update-prompt__text">
          Nova versão disponível. Atualize para garantir o melhor funcionamento.
        </span>
        <div class="update-prompt__actions">
          <Button
            label="Atualizar agora"
            severity="info"
            size="small"
            @click="updateSW"
          />
          <Button
            label="Depois"
            severity="secondary"
            variant="text"
            size="small"
            @click="dismiss"
          />
        </div>
      </div>
    </Message>
  </div>

  <!-- Toast notifications for SW events -->
  <Toast />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'

const showBanner = ref(false)
let updateServiceWorkerFn: ((reloadPage?: boolean) => Promise<void>) | null = null
const toast = useToast()

onMounted(async () => {
  try {
    // Dynamic import to avoid issues in dev mode
    const { registerSW } = await import('virtual:pwa-register')
    const updateFn = registerSW({
      onNeedRefresh() {
        showBanner.value = true
        toast.add({
          severity: 'info',
          summary: 'Nova versão disponível',
          detail: 'Uma nova versão do LabControl está disponível.',
          life: 10000,
        })
      },
      onOfflineReady() {
        toast.add({
          severity: 'success',
          summary: 'App pronto para uso offline',
          life: 5000,
        })
      },
    })

    // Store the update function returned by registerSW
    updateServiceWorkerFn = updateFn as unknown as (reloadPage?: boolean) => Promise<void>
  } catch {
    // virtual:pwa-register not available in dev mode — silently ignore
  }
})

async function updateSW(): Promise<void> {
  if (updateServiceWorkerFn) {
    await updateServiceWorkerFn()
  }
  showBanner.value = false
}

function dismiss(): void {
  showBanner.value = false
}
</script>

<style scoped>
.update-prompt {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 9999;
  padding: 0;
  margin: 0;
}

.update-prompt :deep(.p-message) {
  border-radius: 0;
  margin: 0;
}

.update-prompt__content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.update-prompt__text {
  font-size: 0.875rem;
}

.update-prompt__actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}
</style>
