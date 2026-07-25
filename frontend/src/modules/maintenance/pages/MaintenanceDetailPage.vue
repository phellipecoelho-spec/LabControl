<template>
  <div class="maintenance-detail-page">
    <Toast />
    <ConfirmDialog />

    <div class="flex align-items-center justify-content-between mb-4">
      <div class="flex align-items-center gap-3">
        <Button
          icon="pi pi-arrow-left"
          text
          rounded
          severity="secondary"
          @click="goBack"
        />
        <div class="flex align-items-center gap-2">
          <h2 class="text-2xl font-bold m-0">Ordem de Manutenção #{{ order?.id?.slice(0, 8) || '...' }}</h2>
          <Tag v-if="order" :value="order.status_label" :severity="getStatusSeverity(order.status)" rounded />
          <Tag v-if="order" :value="order.priority_label" :severity="getPrioritySeverity(order.priority)" rounded />
        </div>
      </div>
      <div class="flex gap-2">
        <Button
          v-if="authStore.hasPermission('manutencoes.edit') && order && order.status !== 'completed' && order.status !== 'cancelled'"
          label="Editar"
          icon="pi pi-pencil"
          severity="secondary"
          size="small"
          @click="editOrder"
        />
        <Button
          v-if="authStore.hasPermission('manutencoes.concluir') && order?.status === 'in_progress'"
          label="Concluir"
          icon="pi pi-check"
          severity="success"
          size="small"
          @click="showCompleteDialog = true"
        />
        <Button
          v-if="authStore.hasPermission('manutencoes.concluir') && order && (order.status === 'open' || order.status === 'in_progress')"
          label="Cancelar"
          icon="pi pi-times"
          severity="danger"
          size="small"
          @click="confirmCancel"
        />
      </div>
    </div>

    <div v-if="store.loading" class="card">
      <Skeleton height="4rem" class="mb-3" />
      <Skeleton height="4rem" class="mb-3" />
      <Skeleton height="4rem" class="mb-3" />
    </div>

    <div v-else-if="order">
      <Tabs v-model:value="activeTab">
        <TabList>
          <Tab value="0">Dados da Manutenção</Tab>
          <Tab value="1">Peças Utilizadas</Tab>
        </TabList>
        <TabPanels>
          <TabPanel value="0">
            <div class="grid">
              <div class="col-12 md:col-6">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Equipamento</label>
                  <p class="m-0 mt-1">{{ order.equipment?.name || '—' }}</p>
                </div>
              </div>
              <div class="col-12 md:col-6">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Tipo</label>
                  <p class="m-0 mt-1">{{ order.type_label }}</p>
                </div>
              </div>
              <div class="col-12 md:col-6">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Status</label>
                  <p class="m-0 mt-1">
                    <Tag :value="order.status_label" :severity="getStatusSeverity(order.status)" rounded />
                  </p>
                </div>
              </div>
              <div class="col-12 md:col-6">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Prioridade</label>
                  <p class="m-0 mt-1">
                    <Tag :value="order.priority_label" :severity="getPrioritySeverity(order.priority)" rounded />
                  </p>
                </div>
              </div>
              <div class="col-12">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Descrição</label>
                  <p class="m-0 mt-1 whitespace-pre-wrap">{{ order.description }}</p>
                </div>
              </div>
              <div class="col-12 md:col-6">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Responsável (Técnico)</label>
                  <p class="m-0 mt-1">{{ order.assigned_to?.name || '—' }}</p>
                </div>
              </div>
              <div class="col-12 md:col-6">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Aberto por</label>
                  <p class="m-0 mt-1">{{ order.opened_by?.name || '—' }}</p>
                </div>
              </div>
              <div class="col-12 md:col-4">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Data Agendada</label>
                  <p class="m-0 mt-1">{{ order.scheduled_date ? formatDate(order.scheduled_date) : '—' }}</p>
                </div>
              </div>
              <div class="col-12 md:col-4">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Data Conclusão</label>
                  <p class="m-0 mt-1">{{ order.completed_at ? formatDate(order.completed_at) : '—' }}</p>
                </div>
              </div>
              <div class="col-12 md:col-4">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Próxima Data</label>
                  <p class="m-0 mt-1">{{ order.next_due_at ? formatDate(order.next_due_at) : '—' }}</p>
                </div>
              </div>
              <div v-if="order.resolution" class="col-12">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Parecer Técnico</label>
                  <p class="m-0 mt-1 whitespace-pre-wrap">{{ order.resolution }}</p>
                </div>
              </div>
              <div v-if="order.time_spent" class="col-12 md:col-4">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Tempo Gasto</label>
                  <p class="m-0 mt-1">{{ order.time_spent }} horas</p>
                </div>
              </div>
              <div v-if="order.cost" class="col-12 md:col-4">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Custo</label>
                  <p class="m-0 mt-1">R$ {{ Number(order.cost).toFixed(2) }}</p>
                </div>
              </div>
              <div v-if="order.notes" class="col-12">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Observações</label>
                  <p class="m-0 mt-1 whitespace-pre-wrap">{{ order.notes }}</p>
                </div>
              </div>
              <div v-if="order.interval_value" class="col-12 md:col-4">
                <div class="field">
                  <label class="font-medium text-sm text-color-secondary">Intervalo</label>
                  <p class="m-0 mt-1">{{ order.interval_value }} {{ order.interval_unit }}</p>
                </div>
              </div>
            </div>
          </TabPanel>

          <TabPanel value="1">
            <DataTable
              :value="order.parts || []"
              stripedRows
              size="small"
              :paginator="false"
            >
              <Column field="item_name" header="Item">
                <template #body="{ data }">
                  {{ data.item_name || 'Item #' + data.inventory_item_id?.slice(0, 8) }}
                </template>
              </Column>
              <Column field="quantity" header="Quantidade" />
              <Column field="unit_cost" header="Custo Unitário">
                <template #body="{ data }">
                  {{ data.unit_cost ? 'R$ ' + Number(data.unit_cost).toFixed(2) : '—' }}
                </template>
              </Column>
              <Column header="Total">
                <template #body="{ data }">
                  {{ data.unit_cost && data.quantity ? 'R$ ' + (Number(data.quantity) * Number(data.unit_cost)).toFixed(2) : '—' }}
                </template>
              </Column>
              <template #empty>
                <div class="flex flex-column align-items-center py-4 text-color-secondary">
                  <i class="pi pi-box text-3xl mb-2" style="opacity: 0.4"></i>
                  <p class="m-0">Nenhuma peça registrada.</p>
                </div>
              </template>
            </DataTable>

            <!-- Total cost footer -->
            <div v-if="order.parts && order.parts.length > 0" class="flex justify-content-end mt-2 p-2 surface-ground border-round">
              <strong>Custo Total: R$ {{ calculateTotalCost(order.parts).toFixed(2) }}</strong>
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>

    <MaintenanceCloseDialog
      v-model:visible="showCompleteDialog"
      :orderId="order?.id || ''"
      @saved="onOrderCompleted"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Skeleton from 'primevue/skeleton'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useMaintenanceStore } from '../store/MaintenanceStore'
import { useAuthStore } from '@/stores/auth'
import type { MaintenanceOrder, MaintenanceOrderPart } from '../types/maintenance'
import MaintenanceCloseDialog from '../components/MaintenanceCloseDialog.vue'

const route = useRoute()
const router = useRouter()
const store = useMaintenanceStore()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()

const activeTab = ref('0')
const showCompleteDialog = ref(false)

const order = computed(() => store.currentOrder)

onMounted(async () => {
  const id = route.params.id as string
  if (id) {
    await store.fetchById(id)
  }
})

function goBack() {
  router.push('/maintenance')
}

function getStatusSeverity(status: string): string {
  const map: Record<string, string> = {
    open: 'info',
    in_progress: 'warn',
    completed: 'success',
    cancelled: 'secondary',
  }
  return map[status] || 'info'
}

function getPrioritySeverity(priority: string): string {
  const map: Record<string, string> = {
    low: 'info',
    medium: 'warn',
    high: 'danger',
    critical: 'danger',
  }
  return map[priority] || 'info'
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  try {
    const date = new Date(dateStr)
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    })
  } catch {
    return dateStr
  }
}

function calculateTotalCost(parts: MaintenanceOrderPart[]): number {
  return parts.reduce((sum, part) => {
    if (part.unit_cost && part.quantity) {
      return sum + Number(part.quantity) * Number(part.unit_cost)
    }
    return sum
  }, 0)
}

function editOrder() {
  router.push(`/maintenance/${route.params.id}?edit=1`)
}

function confirmCancel() {
  confirm.require({
    message: `Tem certeza que deseja cancelar esta ordem de manutenção?`,
    header: 'Confirmar Cancelamento',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Não',
    acceptLabel: 'Sim, Cancelar',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await store.cancel(order.value!.id)
        toast.add({
          severity: 'success',
          summary: 'Ordem cancelada',
          detail: 'A ordem de manutenção foi cancelada com sucesso.',
          life: 3000,
        })
        await store.fetchById(order.value!.id)
      } catch (e: any) {
        toast.add({
          severity: 'error',
          summary: 'Erro',
          detail: e.response?.data?.message || 'Ocorreu um erro ao cancelar.',
          life: 5000,
        })
      }
    },
  })
}

function onOrderCompleted() {
  showCompleteDialog.value = false
  toast.add({
    severity: 'success',
    summary: 'Ordem concluída',
    detail: 'A ordem de manutenção foi concluída com sucesso.',
    life: 3000,
  })
  // Reload the order detail
  if (order.value) {
    store.fetchById(order.value.id)
  }
}
</script>

<style scoped>
.whitespace-pre-wrap {
  white-space: pre-wrap;
}
</style>
