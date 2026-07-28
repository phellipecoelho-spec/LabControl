<template>
  <Toast position="top-right" />
  <AppLayout v-if="route.meta.requiresAuth">
    <div class="app-content-wrapper">
      <SyncIndicator position="inline" class="sync-inline-bar" />
      <router-view />
    </div>
  </AppLayout>
  <router-view v-else />
  <UpdatePrompt />
  <ConflictDialog
    v-model:visible="conflictDialogVisible"
    :conflict="currentConflict"
    @resolve="handleConflictResolve"
    @update:visible="conflictDialogVisible = $event"
  />
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { liveQuery } from 'dexie'
import Toast from 'primevue/toast'
import AppLayout from '@/components/layout/AppLayout.vue'
import { useTheme } from '@/composables/useTheme'
import { useOnline } from '@/composables/useOnline'
import { useSyncStore } from '@/stores/sync'
import { SyncService } from '@/services/sync'
import { db } from '@/db'
import type { ConflictRecord } from '@/db'
import SyncIndicator from '@/components/pwa/SyncIndicator.vue'
import UpdatePrompt from '@/components/pwa/UpdatePrompt.vue'
import ConflictDialog from '@/components/pwa/ConflictDialog.vue'

const route = useRoute()
const syncStore = useSyncStore()

// Re-aplica o tema após Vue montar para garantir que PrimeVue
// detecte a classe .app-dark corretamente
useTheme()

// ---------------------------------------------------------------------------
// Connectivity monitoring — auto-sync when coming back online (D-05, D-12)
// ---------------------------------------------------------------------------

const online = useOnline()

// Auto-sync when connectivity is restored
online.on('online', () => {
  syncStore.setOnlineStatus(true)
  syncStore.manualSync().catch(() => {
    // SyncAuthError or network error — silently handled by the store
  })
})

// Track offline state
online.on('offline', () => {
  syncStore.setOnlineStatus(false)
})

// Also check for pending operations when user returns to the tab
online.on('visibility-change', () => {
  syncStore.refreshPendingCount()
})

// ---------------------------------------------------------------------------
// Conflict detection via Dexie liveQuery (D-19)
// ---------------------------------------------------------------------------

const conflictDialogVisible = ref(false)
const currentConflict = ref<ConflictRecord | null>(null)
let conflictSubscription: { unsubscribe: () => void } | null = null

onMounted(() => {
  const sub = liveQuery(
    () => db.conflicts.where('status').equals('pending').toArray(),
  ).subscribe({
    next: (conflicts: ConflictRecord[]) => {
      if (conflicts.length > 0 && !conflictDialogVisible.value) {
        currentConflict.value = conflicts[0]
        conflictDialogVisible.value = true
      } else if (conflicts.length === 0) {
        currentConflict.value = null
      }
      syncStore.hasConflicts = conflicts.length > 0
    },
  })
  conflictSubscription = sub as unknown as { unsubscribe: () => void }
})

onUnmounted(() => {
  conflictSubscription?.unsubscribe()
})

// ---------------------------------------------------------------------------
// Conflict resolution handler (D-20)
// ---------------------------------------------------------------------------

async function handleConflictResolve(payload: { conflictId: number; resolution: 'keep-local' | 'keep-server' }): Promise<void> {
  await SyncService.resolveConflict(payload.conflictId, payload.resolution)
  conflictDialogVisible.value = false
  currentConflict.value = null

  // Load the next pending conflict if any
  const remaining = await db.conflicts.where('status').equals('pending').toArray()
  if (remaining.length > 0) {
    currentConflict.value = remaining[0]
    conflictDialogVisible.value = true
  } else {
    syncStore.hasConflicts = false
  }
}
</script>

<style scoped>
.app-content-wrapper {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.sync-inline-bar {
  padding: 0.5rem 1.5rem;
  border-bottom: 1px solid var(--p-content-border-color);
  background: var(--p-surface-ground);
}
</style>
