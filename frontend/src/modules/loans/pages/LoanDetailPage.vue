<template>
  <div class="loan-detail-page">
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
        <div>
          <h2 class="text-2xl font-bold m-0 flex align-items-center gap-2">
            Empréstimo #{{ loan?.id?.substring(0, 8) || 'Carregando...' }}
            <Tag
              v-if="loan"
              :value="getStatusLabel(loan.status)"
              :severity="getStatusSeverity(loan.status)"
              rounded
            />
            <Tag
              v-if="loan?.is_overdue"
              value="Atrasado"
              severity="danger"
              rounded
            />
          </h2>
        </div>
      </div>
      <div v-if="loan" class="flex gap-2">
        <Button
          v-if="loan.status === 'reserved' && authStore.hasPermission('emprestimos.edit')"
          label="Ativar"
          icon="pi pi-play"
          severity="success"
          size="small"
          @click="confirmActivate"
        />
        <Button
          v-if="loan.status === 'active' && authStore.hasPermission('emprestimos.edit')"
          label="Devolver Itens"
          icon="pi pi-undo"
          severity="warning"
          size="small"
          @click="showReturnDialog = true"
        />
        <Button
          v-if="loan.status === 'reserved' && authStore.hasPermission('emprestimos.finalizar')"
          label="Cancelar"
          icon="pi pi-times"
          severity="danger"
          size="small"
          @click="confirmCancel"
        />
      </div>
    </div>

    <div v-if="loading" class="card">
      <Skeleton height="3rem" class="mb-3" />
      <Skeleton height="3rem" class="mb-3" />
      <Skeleton height="3rem" class="mb-3" />
    </div>

    <div v-else-if="loan" class="card">
      <div class="flex align-items-center gap-3 mb-3">
        <Tag
          :value="getStatusLabel(loan.status)"
          :severity="getStatusSeverity(loan.status)"
          rounded
        />
        <Tag
          v-if="loan.is_overdue"
          value="Atrasado"
          severity="danger"
          rounded
        />
        <span class="text-sm text-600">
          {{ loan.returned_items_count }}/{{ loan.items_count }} itens devolvidos
        </span>
      </div>
      <ProgressBar
        :value="loan.progress"
        :class="loan.progress === 100 ? 'progress-complete' : ''"
      />

      <Divider />

      <Tabs v-model:value="activeTab">
        <TabList>
          <Tab value="0">Dados do Empréstimo</Tab>
          <Tab value="1">Itens</Tab>
          <Tab value="2">Timeline</Tab>
        </TabList>
        <TabPanels>
          <TabPanel value="0">
            <LoanInfoTab :loan="loan" />
          </TabPanel>
          <TabPanel value="1">
            <LoanItemsTab
              :items="loan.equipment"
              @return-item="onReturnItem"
            />
          </TabPanel>
          <TabPanel value="2">
            <LoanTimelineTab :loan="loan" />
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>

    <div v-else class="card">
      <div class="text-center text-600 p-4">
        Empréstimo não encontrado.
      </div>
    </div>

    <LoanReturnDialog
      v-model:visible="showReturnDialog"
      :loan-id="loan?.id || ''"
      :items="loan?.equipment || []"
      @returned="onItemsReturned"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
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
import Divider from 'primevue/divider'
import ProgressBar from 'primevue/progressbar'
import { useLoanStore } from '../store/LoanStore'
import { useAuthStore } from '@/stores/auth'
import type { Loan } from '../types/loan'
import LoanInfoTab from '../components/LoanInfoTab.vue'
import LoanItemsTab from '../components/LoanItemsTab.vue'
import LoanTimelineTab from '../components/LoanTimelineTab.vue'
import LoanReturnDialog from '../components/LoanReturnDialog.vue'

const route = useRoute()
const router = useRouter()
const store = useLoanStore()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()

const loan = ref<Loan | null>(null)
const loading = ref(false)
const activeTab = ref('0')
const showReturnDialog = ref(false)

onMounted(async () => {
  const id = route.params.id as string
  if (id) {
    loading.value = true
    try {
      loan.value = await store.fetchById(id)
    } finally {
      loading.value = false
    }
  }
})

function goBack() {
  router.push('/loans')
}

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

function onReturnItem() {
  showReturnDialog.value = true
}

async function onItemsReturned() {
  showReturnDialog.value = false
  toast.add({
    severity: 'success',
    summary: 'Devolução registrada',
    detail: 'A devolução foi registrada com sucesso.',
    life: 3000,
  })
  const id = route.params.id as string
  loan.value = await store.fetchById(id)
}

function confirmActivate() {
  confirm.require({
    message: 'Tem certeza que deseja ativar este empréstimo?',
    header: 'Confirmar Ativação',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancelar',
    acceptLabel: 'Ativar',
    accept: async () => {
      try {
        await store.activate(loan.value!.id)
        toast.add({
          severity: 'success',
          summary: 'Empréstimo ativado',
          detail: 'O empréstimo foi ativado com sucesso.',
          life: 3000,
        })
        loan.value = await store.fetchById(loan.value!.id)
      } catch (e: any) {
        toast.add({
          severity: 'error',
          summary: 'Erro',
          detail: e.response?.data?.message || 'Ocorreu um erro ao ativar.',
          life: 5000,
        })
      }
    },
  })
}

function confirmCancel() {
  confirm.require({
    message: 'Tem certeza que deseja cancelar este empréstimo?',
    header: 'Confirmar Cancelamento',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Voltar',
    acceptLabel: 'Cancelar Empréstimo',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await store.cancel(loan.value!.id)
        toast.add({
          severity: 'success',
          summary: 'Empréstimo cancelado',
          detail: 'O empréstimo foi cancelado com sucesso.',
          life: 3000,
        })
        loan.value = await store.fetchById(loan.value!.id)
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
</script>

<style scoped>
.progress-complete :deep(.p-progressbar-value) {
  background: var(--p-green-500);
}
</style>
