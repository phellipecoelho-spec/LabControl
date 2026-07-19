<template>
  <div class="audit-logs-page">
    <div class="page-header">
      <h1>Logs de Auditoria</h1>
      <p>Histórico de ações realizadas no sistema</p>
    </div>

    <div class="filter-bar">
      <SelectButton
        v-if="moduleOptions.length > 0"
        v-model="filters.module"
        :options="moduleOptions"
        optionLabel="label"
        optionValue="value"
        aria-label="Módulo"
        allowEmpty
      />

      <SelectButton
        v-model="filters.action"
        :options="actionOptions"
        optionLabel="label"
        optionValue="value"
        aria-label="Ação"
        allowEmpty
      />

      <Calendar
        v-model="dateRange"
        selectionMode="range"
        placeholder="Período"
        showIcon
        fluid
      />

      <Button
        label="Filtrar"
        icon="pi pi-search"
        severity="primary"
        @click="applyFilters"
      />

      <Button
        label="Limpar"
        icon="pi pi-times"
        severity="secondary"
        @click="clearFilters"
      />
    </div>

    <div v-if="!loading && logs.length === 0" class="empty-state">
      <Message severity="info" :closable="false">
        Nenhum log encontrado para os filtros selecionados.
      </Message>
    </div>

    <Timeline
      v-else
      :value="logs"
      align="alternate"
      layout="vertical"
    >
      <template #marker="slotProps">
        <i
          :class="getIcon(slotProps.item.action)"
          :style="{ color: getColor(slotProps.item.action), fontSize: '1.25rem' }"
        />
      </template>

      <template #content="slotProps">
        <Card>
          <template #title>
            <div class="log-header">
              <Tag :value="slotProps.item.module" severity="info" />
              <span class="log-timestamp">{{ formatDate(slotProps.item.created_at) }}</span>
            </div>
          </template>

          <template #content>
            <div class="log-body">
              <div class="log-action-label">
                <strong>{{ getActionLabel(slotProps.item.action) }}</strong>
              </div>

              <div v-if="slotProps.item.user" class="log-user">
                <i class="pi pi-user" />
                {{ slotProps.item.user.name }}
                <span class="log-user-email">({{ slotProps.item.user.email }})</span>
              </div>

              <div v-if="slotProps.item.ip_address" class="log-ip">
                <i class="pi pi-globe" />
                IP: {{ slotProps.item.ip_address }}
              </div>

              <div v-if="slotProps.item.action === 'updated' && slotProps.item.new_values" class="log-changes">
                <div
                  v-for="(change, field) in slotProps.item.new_values"
                  :key="field"
                  class="log-change-item"
                >
                  <span class="change-field">{{ field }}:</span>
                  <span class="change-old">{{ change.old }}</span>
                  <i class="pi pi-arrow-right" />
                  <span class="change-new">{{ change.new }}</span>
                </div>
              </div>

              <div v-if="slotProps.item.action === 'created' && slotProps.item.new_values" class="log-values">
                <pre>{{ formatJson(slotProps.item.new_values) }}</pre>
              </div>

              <div v-if="slotProps.item.action === 'deleted' && slotProps.item.old_values" class="log-values">
                <pre>{{ formatJson(slotProps.item.old_values) }}</pre>
              </div>
            </div>
          </template>
        </Card>
      </template>
    </Timeline>

    <Paginator
      v-if="pagination.last_page > 1"
      :first="(pagination.current_page - 1) * pagination.per_page"
      :rows="pagination.per_page"
      :totalRecords="pagination.total"
      @page="onPageChange"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useActivityLogsStore } from '@/stores/activityLogs'
import Button from 'primevue/button'
import Calendar from 'primevue/calendar'
import Card from 'primevue/card'
import Message from 'primevue/message'
import Paginator from 'primevue/paginator'
import SelectButton from 'primevue/selectbutton'
import Tag from 'primevue/tag'
import Timeline from 'primevue/timeline'

const store = useActivityLogsStore()

const logs = ref(store.logs)
const loading = ref(false)
const pagination = ref(store.pagination)
const moduleOptions = ref<Array<{ label: string; value: string }>>([])
const dateRange = ref<Date[] | null>(null)

const filters = ref<{
  module: string | null
  action: string | null
  user_id: string | null
  date_from: string | null
  date_to: string | null
}>({
  module: null,
  action: null,
  user_id: null,
  date_from: null,
  date_to: null,
})

const actionOptions = [
  { label: 'Todos', value: null },
  { label: 'Criação', value: 'created' },
  { label: 'Edição', value: 'updated' },
  { label: 'Exclusão', value: 'deleted' },
  { label: 'Login', value: 'login' },
  { label: 'Logout', value: 'logout' },
  { label: 'Falha de Login', value: 'login_failed' },
  { label: 'Email Verificado', value: 'email_verified' },
  { label: 'Redefinição de Senha', value: 'password_reset' },
]

function getIcon(action: string): string {
  const icons: Record<string, string> = {
    created: 'pi pi-plus',
    updated: 'pi pi-pencil',
    deleted: 'pi pi-trash',
    login: 'pi pi-sign-in',
    logout: 'pi pi-sign-out',
    login_failed: 'pi pi-exclamation-triangle',
    email_verified: 'pi pi-check-circle',
    password_reset: 'pi pi-key',
    password_reset_requested: 'pi pi-envelope',
    register: 'pi pi-user-plus',
  }
  return icons[action] || 'pi pi-info-circle'
}

function getColor(action: string): string {
  const colors: Record<string, string> = {
    created: '#22c55e',
    updated: '#3b82f6',
    deleted: '#ef4444',
    login: '#10b981',
    logout: '#f59e0b',
    login_failed: '#dc2626',
    email_verified: '#06b6d4',
    password_reset: '#8b5cf6',
    password_reset_requested: '#8b5cf6',
    register: '#22c55e',
  }
  return colors[action] || '#6b7280'
}

function getActionLabel(action: string): string {
  const labels: Record<string, string> = {
    created: 'Criação',
    updated: 'Edição',
    deleted: 'Exclusão',
    login: 'Login',
    logout: 'Logout',
    login_failed: 'Falha de Login',
    email_verified: 'Email Verificado',
    password_reset: 'Redefinição de Senha',
    password_reset_requested: 'Solicitação de Redefinição',
    register: 'Registro',
  }
  return labels[action] || action
}

function formatDate(dateStr: string): string {
  const d = new Date(dateStr)
  return d.toLocaleString('pt-BR')
}

function formatJson(obj: Record<string, any>): string {
  try {
    return JSON.stringify(obj, null, 2)
  } catch {
    return String(obj)
  }
}

async function applyFilters() {
  loading.value = true
  try {
    if (dateRange.value && dateRange.value.length === 2) {
      filters.value.date_from = dateRange.value[0]?.toISOString().split('T')[0] || null
      filters.value.date_to = dateRange.value[1]?.toISOString().split('T')[0] || null
    } else {
      filters.value.date_from = null
      filters.value.date_to = null
    }

    const params: Record<string, any> = {}
    if (filters.value.module) params.module = filters.value.module
    if (filters.value.action) params.action = filters.value.action
    if (filters.value.user_id) params.user_id = filters.value.user_id
    if (filters.value.date_from) params.date_from = filters.value.date_from
    if (filters.value.date_to) params.date_to = filters.value.date_to

    await store.fetchAll(params)
    logs.value = store.logs
    pagination.value = store.pagination
  } finally {
    loading.value = false
  }
}

async function clearFilters() {
  filters.value = { module: null, action: null, user_id: null, date_from: null, date_to: null }
  dateRange.value = null
  await store.fetchAll()
  logs.value = store.logs
  pagination.value = store.pagination
}

function onPageChange(event: any) {
  const page = event.page + 1
  store.fetchAll({ page })
  logs.value = store.logs
  pagination.value = store.pagination
}

async function loadModules() {
  try {
    const modules = await store.fetchModules()
    moduleOptions.value = modules.map(m => ({ label: m, value: m }))
  } catch {
    moduleOptions.value = []
  }
}

onMounted(async () => {
  loading.value = true
  try {
    await Promise.all([loadModules(), store.fetchAll()])
    logs.value = store.logs
    pagination.value = store.pagination
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.audit-logs-page {
  padding: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 1.5rem;
}

.page-header h1 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.25rem;
}

.page-header p {
  color: #94a3b8;
  margin: 0;
}

.filter-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.empty-state {
  padding: 2rem 0;
}

.log-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.log-timestamp {
  font-size: 0.875rem;
  color: #94a3b8;
}

.log-body {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.log-action-label {
  font-size: 1rem;
}

.log-user {
  font-size: 0.875rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.log-user-email {
  color: #94a3b8;
}

.log-ip {
  font-size: 0.75rem;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.log-changes {
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: #1e293b;
  border-radius: 6px;
  font-size: 0.8rem;
}

.log-change-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0;
}

.change-field {
  font-weight: 600;
  color: #e2e8f0;
  min-width: 100px;
}

.change-old {
  color: #ef4444;
  text-decoration: line-through;
}

.change-new {
  color: #22c55e;
}

.log-values pre {
  background: #1e293b;
  padding: 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  overflow-x: auto;
  margin: 0.5rem 0 0;
  color: #e2e8f0;
}
</style>
