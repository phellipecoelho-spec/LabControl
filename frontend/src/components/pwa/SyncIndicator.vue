<template>
  <div v-if="syncStore.hasPending || syncStore.lastSyncAt" class="sync-indicator" :class="[`sync-indicator--${position}`]">
    <!-- Pending indicator -->
    <template v-if="syncStore.hasPending">
      <Tag
        severity="warn"
        :icon="syncStore.isSyncing ? 'pi pi-spin pi-spinner' : 'pi pi-sync'"
        :value="syncStore.pendingCount"
      />
      <span v-if="!syncStore.isSyncing" class="sync-indicator__label">
        {{ syncStore.pendingLabel }}
      </span>
      <span v-else class="sync-indicator__label">
        Sincronizando...
      </span>
      <Button
        v-if="!syncStore.isSyncing"
        icon="pi pi-upload"
        label="Sincronizar"
        severity="secondary"
        variant="text"
        size="small"
        @click="syncStore.manualSync()"
      />
    </template>

    <!-- Synced indicator -->
    <template v-else-if="syncStore.lastSyncAt">
      <Tag
        severity="success"
        icon="pi pi-check"
        :value="syncedLabel"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import { useSyncStore } from '@/stores/sync'

withDefaults(defineProps<{
  position?: 'topbar' | 'inline'
}>(), {
  position: 'topbar',
})

const syncStore = useSyncStore()

function formatRelativeTime(iso: string): string {
  const now = Date.now()
  const then = new Date(iso).getTime()
  const diffMs = now - then
  const diffMin = Math.floor(diffMs / 60000)
  const diffHour = Math.floor(diffMin / 60)
  const diffDay = Math.floor(diffHour / 24)

  if (diffMin < 1) return 'agora mesmo'
  if (diffMin < 60) return `há ${diffMin} min`
  if (diffHour < 24) return `há ${diffHour}h`
  if (diffDay < 30) return `há ${diffDay}d`
  return new Date(iso).toLocaleDateString('pt-BR')
}

const syncedLabel = computed(() => `Sincronizado: ${formatRelativeTime(syncStore.lastSyncAt!)}`)
</script>

<style scoped>
.sync-indicator {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: var(--p-text-sm, 0.875rem);
}

.sync-indicator--topbar {
  margin-right: 0.5rem;
}

.sync-indicator__label {
  color: var(--p-text-muted-color);
  white-space: nowrap;
}
</style>
