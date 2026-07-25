<template>
  <div class="calibration-info-tab">
    <div v-if="calibration.is_due" class="mb-4">
      <Message severity="error" :closable="false">
        <div class="flex align-items-center gap-2">
          <i class="pi pi-exclamation-triangle" />
          <span>
            <strong>Calibração vencida.</strong>
            A próxima data de calibração era {{ formatDate(calibration.next_due_at) }}.
          </span>
        </div>
      </Message>
    </div>

    <div v-if="calibration.is_due_soon" class="mb-4">
      <Message severity="warn" :closable="false">
        <div class="flex align-items-center gap-2">
          <i class="pi pi-clock" />
          <span>
            <strong>Calibração próxima do vencimento.</strong>
            Vence em {{ daysUntilDue }} dia(s) — {{ formatDate(calibration.next_due_at) }}.
          </span>
        </div>
      </Message>
    </div>

    <div class="grid">
      <div class="col-12 md:col-6">
        <Card>
          <template #title>
            <span class="text-base font-medium">Equipamento</span>
          </template>
          <template #content>
            <div class="field mb-2">
              <label class="block text-600 text-sm mb-1">Nome</label>
              <div class="text-900 font-medium">{{ calibration.equipment?.name || '—' }}</div>
            </div>
            <div class="field mb-2">
              <label class="block text-600 text-sm mb-1">Patrimônio</label>
              <div class="text-900">{{ calibration.equipment?.patrimony_id || '—' }}</div>
            </div>
            <div class="field mb-2">
              <label class="block text-600 text-sm mb-1">Parte do Equipamento</label>
              <div class="text-900">{{ calibration.part_name || '—' }}</div>
            </div>
          </template>
        </Card>
      </div>

      <div class="col-12 md:col-6">
        <Card>
          <template #title>
            <span class="text-base font-medium">Período</span>
          </template>
          <template #content>
            <div class="field mb-2">
              <label class="block text-600 text-sm mb-1">Data Agendada</label>
              <div class="text-900">{{ formatDate(calibration.scheduled_date) }}</div>
            </div>
            <div class="field mb-2">
              <label class="block text-600 text-sm mb-1">Data de Conclusão</label>
              <div class="text-900">
                <span v-if="calibration.completed_at">{{ formatDate(calibration.completed_at) }}</span>
                <span v-else class="text-500">Pendente</span>
              </div>
            </div>
            <div class="field mb-2">
              <label class="block text-600 text-sm mb-1">Próxima Data</label>
              <div class="text-900">
                <template v-if="calibration.next_due_at">
                  {{ formatDate(calibration.next_due_at) }}
                  <Tag
                    v-if="calibration.is_due"
                    value="Vencida"
                    severity="danger"
                    size="small"
                    rounded
                    class="ml-2"
                  />
                  <Tag
                    v-else-if="calibration.is_due_soon"
                    :value="`Vence em ${daysUntilDue} dia(s)`"
                    severity="warn"
                    size="small"
                    rounded
                    class="ml-2"
                  />
                </template>
                <span v-else class="text-500">—</span>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <Divider />

    <div class="grid">
      <div class="col-12 md:col-6">
        <Card>
          <template #title>
            <span class="text-base font-medium">Periodicidade</span>
          </template>
          <template #content>
            <div class="field mb-2">
              <label class="block text-600 text-sm mb-1">Intervalo</label>
              <div class="text-900">{{ formatInterval(calibration.interval_value, calibration.interval_unit) }}</div>
            </div>
          </template>
        </Card>
      </div>

      <div class="col-12 md:col-6">
        <Card>
          <template #title>
            <span class="text-base font-medium">Status</span>
          </template>
          <template #content>
            <Tag
              :value="getStatusLabel(calibration.status)"
              :severity="getStatusSeverity(calibration.status)"
              rounded
            />
          </template>
        </Card>
      </div>
    </div>

    <Divider />

    <div class="grid">
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Responsável</label>
          <div class="text-900">{{ calibration.responsible || '—' }}</div>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Laboratório</label>
          <div class="text-900">{{ calibration.laboratory || '—' }}</div>
        </div>
      </div>
    </div>

    <div class="grid">
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Nº Certificado</label>
          <div class="text-900">{{ calibration.certificate_number || '—' }}</div>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Criado por</label>
          <div class="text-900">{{ calibration.created_by?.name || '—' }}</div>
        </div>
      </div>
    </div>

    <Divider />

    <div class="field mb-3">
      <label class="block text-600 text-sm mb-1">Observações</label>
      <div class="text-900">
        <template v-if="calibration.notes">
          <span v-if="notesExpanded || calibration.notes.length <= 200">{{ calibration.notes }}</span>
          <span v-else>{{ calibration.notes.substring(0, 200) }}...</span>
          <Button
            v-if="calibration.notes.length > 200"
            :label="notesExpanded ? 'Mostrar menos' : 'Mostrar mais'"
            link
            size="small"
            @click="notesExpanded = !notesExpanded"
          />
        </template>
        <span v-else class="text-500">Nenhuma observação registrada.</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import Card from 'primevue/card'
import Divider from 'primevue/divider'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import Button from 'primevue/button'
import type { Calibration } from '../types/calibration'

const props = defineProps<{
  calibration: Calibration
}>()

const notesExpanded = ref(false)

const daysUntilDue = computed(() => {
  if (!props.calibration.next_due_at) return 0
  const now = new Date()
  const due = new Date(props.calibration.next_due_at)
  return Math.ceil((due.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
})

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    scheduled: 'Agendada',
    completed: 'Concluída',
    cancelled: 'Cancelada',
  }
  return labels[status] || status
}

function getStatusSeverity(status: string): string {
  const severities: Record<string, string> = {
    scheduled: 'info',
    completed: 'success',
    cancelled: 'secondary',
  }
  return severities[status] || 'info'
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

function formatInterval(value: number, unit: string): string {
  const labels: Record<string, string> = {
    months: value === 1 ? 'mês' : 'meses',
    days: value === 1 ? 'dia' : 'dias',
    hours: value === 1 ? 'hora' : 'horas',
  }
  return `${value} ${labels[unit] || unit}`
}
</script>
