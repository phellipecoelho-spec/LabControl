<template>
  <div class="calibration-timeline-tab">
    <div v-if="timelineEvents.length > 0">
      <Timeline :value="timelineEvents" align="left">
        <template #marker="{ item }">
          <span
            class="flex align-items-center justify-content-center w-2rem h-2rem border-circle z-1 shadow-2"
            :style="{ backgroundColor: item.color, color: '#fff' }"
          >
            <i :class="item.icon" class="text-sm" />
          </span>
        </template>
        <template #content="{ item }">
          <Card>
            <template #title>
              <span class="text-sm font-medium">{{ item.title }}</span>
            </template>
            <template #subtitle>
              <span class="text-xs text-600">{{ item.date }}</span>
            </template>
            <template #content>
              <p v-if="item.description" class="text-sm m-0 text-700">
                {{ item.description }}
              </p>
            </template>
          </Card>
        </template>
      </Timeline>
    </div>
    <div v-else class="text-center text-600 p-4">
      <i class="pi pi-info-circle text-xl mb-2 block" />
      <p>Histórico disponível em breve</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Timeline from 'primevue/timeline'
import Card from 'primevue/card'
import type { Calibration } from '../types/calibration'

const props = defineProps<{
  calibration: Calibration
}>()

interface TimelineEvent {
  title: string
  date: string
  description?: string
  icon: string
  color: string
}

const timelineEvents = computed<TimelineEvent[]>(() => {
  const events: TimelineEvent[] = []
  const cal = props.calibration

  if (!cal) return events

  // 1. Calibração agendada (criação)
  if (cal.created_at) {
    const equipmentName = cal.equipment?.name || 'Equipamento'
    const description = cal.part_name
      ? `${equipmentName} — ${cal.part_name}`
      : equipmentName
    events.push({
      title: 'Calibração agendada',
      date: formatDateTime(cal.created_at),
      description: `Agendada para ${formatDate(cal.scheduled_date)} — ${description}`,
      icon: 'pi pi-plus-circle',
      color: '#3B82F6',
    })
  }

  // 2. Calibração concluída
  if (cal.completed_at) {
    const nextDueText = cal.next_due_at
      ? `Próxima calibração: ${formatDate(cal.next_due_at)}`
      : undefined
    events.push({
      title: 'Calibração concluída',
      date: formatDateTime(cal.completed_at),
      description: nextDueText,
      icon: 'pi pi-check-circle',
      color: '#10B981',
    })
  }

  // 3. Calibração cancelada
  if (cal.status === 'cancelled') {
    events.push({
      title: 'Calibração cancelada',
      date: formatDateTime(cal.updated_at),
      description: 'A calibração foi cancelada',
      icon: 'pi pi-times-circle',
      color: '#EF4444',
    })
  }

  return events
})

function formatDateTime(dateStr: string | null): string {
  if (!dateStr) return ''
  try {
    const date = new Date(dateStr)
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return dateStr
  }
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  try {
    const date = new Date(dateStr)
    return date.toLocaleDateString('pt-BR')
  } catch {
    return dateStr
  }
}
</script>
