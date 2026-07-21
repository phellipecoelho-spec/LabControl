<template>
  <div class="loan-info-tab">
    <div v-if="loan.is_overdue" class="mb-4">
      <Message severity="error" :closable="false">
        <div class="flex align-items-center gap-2">
          <i class="pi pi-exclamation-triangle" />
          <span>
            <strong>Empréstimo em atraso.</strong>
            A previsão de devolução era {{ formatDate(loan.expected_return_at) }}.
          </span>
        </div>
      </Message>
    </div>

    <div class="grid">
      <div class="col-12 md:col-6">
        <Card>
          <template #title>
            <span class="text-base font-medium">Tomador</span>
          </template>
          <template #content>
            <div class="field mb-2">
              <label class="block text-600 text-sm mb-1">Nome</label>
              <div class="text-900 font-medium">{{ loan.borrower?.name || '—' }}</div>
            </div>
            <div class="field mb-2">
              <label class="block text-600 text-sm mb-1">Contato</label>
              <div class="text-900">{{ loan.contact || 'Não informado' }}</div>
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
              <label class="block text-600 text-sm mb-1">Data de Retirada</label>
              <div class="text-900">{{ formatDate(loan.borrowed_at) }}</div>
            </div>
            <div class="field mb-2">
              <label class="block text-600 text-sm mb-1">Previsão de Devolução</label>
              <div class="text-900">{{ formatDate(loan.expected_return_at) }}</div>
            </div>
            <div class="field mb-2">
              <label class="block text-600 text-sm mb-1">Devolução Real</label>
              <div class="text-900">{{ loan.returned_at ? formatDate(loan.returned_at) : 'Pendente' }}</div>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <Divider />

    <div class="grid">
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Destino / Setor</label>
          <div class="text-900">{{ loan.destination || 'Não informado' }}</div>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Motivo</label>
          <div class="text-900">{{ loan.reason || 'Não informado' }}</div>
        </div>
      </div>
    </div>

    <div class="grid">
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Aprovador</label>
          <div class="text-900">{{ loan.approved_by?.name || 'Não informado' }}</div>
        </div>
      </div>
      <div class="col-12 md:col-6">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Criado por</label>
          <div class="text-900">{{ loan.created_by?.name || '—' }}</div>
        </div>
      </div>
    </div>

    <Divider />

    <div class="field mb-3">
      <label class="block text-600 text-sm mb-1">Observações</label>
      <div class="text-900">{{ loan.notes || 'Nenhuma observação registrada.' }}</div>
    </div>

    <Divider />

    <div class="grid">
      <div class="col-12 md:col-4">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Progresso de Devolução</label>
          <div class="flex align-items-center gap-2">
            <ProgressBar
              :value="loan.progress"
              style="height: 1.5rem; flex: 1"
              :class="loan.progress === 100 ? 'progress-complete' : ''"
            />
            <span class="text-sm font-medium">{{ loan.returned_items_count }}/{{ loan.items_count }}</span>
          </div>
        </div>
      </div>
      <div class="col-12 md:col-4">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Status</label>
          <Tag
            :value="getStatusLabel(loan.status)"
            :severity="getStatusSeverity(loan.status)"
            rounded
          />
        </div>
      </div>
      <div class="col-12 md:col-4">
        <div class="field mb-3">
          <label class="block text-600 text-sm mb-1">Criado em</label>
          <div class="text-900">{{ formatDate(loan.created_at) }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Card from 'primevue/card'
import Divider from 'primevue/divider'
import Tag from 'primevue/tag'
import ProgressBar from 'primevue/progressbar'
import Message from 'primevue/message'
import type { Loan } from '../types/loan'

defineProps<{
  loan: Loan
}>()

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    reserved: 'Reservado',
    active: 'Ativo',
    returned: 'Devolvido',
    cancelled: 'Cancelado',
  }
  return labels[status] || status
}

function getStatusSeverity(status: string): string {
  const severities: Record<string, string> = {
    reserved: 'info',
    active: 'warning',
    returned: 'success',
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
</script>

<style scoped>
.progress-complete :deep(.p-progressbar-value) {
  background: var(--p-green-500);
}
</style>
