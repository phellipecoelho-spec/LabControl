<template>
  <div class="loan-timeline-tab">
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
import type { Loan } from '../types/loan'

const props = defineProps<{
  loan: Loan
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
  const loan = props.loan

  if (!loan) return events

  // 1. Empréstimo criado
  if (loan.created_at) {
    events.push({
      title: 'Empréstimo criado',
      date: formatDateTime(loan.created_at),
      description: loan.created_by
        ? `Por ${loan.created_by.name}`
        : undefined,
      icon: 'pi pi-plus-circle',
      color: '#3B82F6',
    })
  }

  // 2. Reservado (se borrowed_at existe, representa o início)
  if (loan.borrowed_at) {
    events.push({
      title: 'Status: Reservado',
      date: formatDateTime(loan.borrowed_at),
      description: 'Empréstimo registrado como reservado',
      icon: 'pi pi-calendar',
      color: '#8B5CF6',
    })
  }

  // 3. Ativado (status active)
  if (loan.status === 'active' || loan.status === 'returned') {
    // Infer activation from the loan data (active means items are out)
    const activationDate = loan.borrowed_at || loan.created_at
    events.push({
      title: 'Empréstimo ativado',
      date: formatDateTime(activationDate),
      description: 'Empréstimo ativado — equipamentos retirados',
      icon: 'pi pi-play',
      color: '#F59E0B',
    })
  }

  // 4. Itens devolvidos
  if (loan.equipment && loan.equipment.length > 0) {
    loan.equipment.forEach(eq => {
      if (eq.pivot?.returned_at) {
        events.push({
          title: `Item "${eq.name}" devolvido`,
          date: formatDateTime(eq.pivot.returned_at),
          description: eq.pivot.notes ? `Observações: ${eq.pivot.notes}` : undefined,
          icon: 'pi pi-undo',
          color: '#10B981',
        })
      }
    })
  }

  // 5. Empréstimo concluído
  if (loan.status === 'returned' && loan.returned_at) {
    events.push({
      title: 'Empréstimo concluído',
      date: formatDateTime(loan.returned_at),
      description: 'Todos os itens foram devolvidos',
      icon: 'pi pi-check-circle',
      color: '#10B981',
    })
  }

  // 6. Empréstimo cancelado
  if (loan.status === 'cancelled') {
    events.push({
      title: 'Empréstimo cancelado',
      date: formatDateTime(loan.updated_at),
      description: 'O empréstimo foi cancelado',
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
</script>
