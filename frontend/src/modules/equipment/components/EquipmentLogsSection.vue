<template>
  <div class="equipment-logs-section">
    <div v-if="loading" class="card">
      <Skeleton height="4rem" class="mb-3" />
      <Skeleton height="4rem" class="mb-3" />
      <Skeleton height="4rem" class="mb-3" />
    </div>

    <div v-else-if="logs.length === 0" class="card">
      <div class="text-center py-5">
        <i class="pi pi-history text-5xl text-400 mb-3"></i>
        <p class="text-600">Nenhuma alteração registrada.</p>
      </div>
    </div>

    <Timeline v-else :value="logs" align="left">
      <template #opposite="slotProps">
        <small class="text-500">{{ formatDate(slotProps.item.created_at) }}</small>
      </template>
      <template #marker="slotProps">
        <span
          class="flex align-items-center justify-content-center w-2rem h-2rem border-circle z-1 shadow-2"
          :style="getMarkerStyle(slotProps.item.action)"
        >
          <i :class="getIcon(slotProps.item.action)" class="text-white text-sm"></i>
        </span>
      </template>
      <template #content="slotProps">
        <div class="card p-3">
          <div class="font-medium mb-1">{{ getDescription(slotProps.item) }}</div>
          <small class="text-500">{{ slotProps.item.user?.name || 'Sistema' }}</small>
        </div>
      </template>
    </Timeline>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { api } from '@/services/api'
import Skeleton from 'primevue/skeleton'
import Timeline from 'primevue/timeline'

const props = defineProps<{
  equipmentId: string
}>()

interface ActivityLog {
  id: string
  action: string
  module: string
  description?: string
  changes?: Record<string, { old: string; new: string }>
  user?: { name: string }
  created_at: string
}

const logs = ref<ActivityLog[]>([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const response = await api.get('/activity-logs', {
      params: {
        subject_type: 'equipment',
        subject_id: props.equipmentId,
      },
    })
    logs.value = response.data.data || response.data
  } finally {
    loading.value = false
  }
})

function formatDate(date: string): string {
  return new Date(date).toLocaleString('pt-BR')
}

function getIcon(action: string): string {
  const icons: Record<string, string> = {
    created: 'pi pi-plus',
    updated: 'pi pi-pencil',
    deleted: 'pi pi-trash',
  }
  return icons[action] || 'pi pi-info-circle'
}

function getMarkerStyle(action: string): Record<string, string> {
  const colors: Record<string, string> = {
    created: 'background-color: var(--green-500)',
    updated: 'background-color: var(--blue-500)',
    deleted: 'background-color: var(--red-500)',
  }
  return {
    backgroundColor: colors[action] ? undefined : 'var(--gray-500)',
    ...(colors[action] ? {} : {}),
  }
}

function getDescription(log: ActivityLog): string {
  if (log.description) return log.description

  const labels: Record<string, string> = {
    created: 'Equipamento cadastrado',
    updated: 'Equipamento atualizado',
    deleted: 'Equipamento removido',
  }
  return labels[log.action] || log.action
}
</script>
