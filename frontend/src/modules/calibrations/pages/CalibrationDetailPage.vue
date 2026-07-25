<template>
  <div class="calibration-detail-page">
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
            Detalhes da Calibração
            <Tag
              v-if="calibration"
              :value="getStatusLabel(calibration.status)"
              :severity="getStatusSeverity(calibration.status)"
              rounded
            />
            <Tag
              v-if="calibration?.is_due"
              value="Vencida"
              severity="danger"
              rounded
            />
            <Tag
              v-else-if="calibration?.is_due_soon"
              :value="`Vence em ${daysUntilDue} dias`"
              severity="warn"
              rounded
            />
          </h2>
        </div>
      </div>
      <div v-if="calibration" class="flex gap-2">
        <Button
          v-if="calibration.status === 'scheduled' && authStore.hasPermission('calibracoes.concluir')"
          label="Concluir"
          icon="pi pi-check"
          severity="success"
          size="small"
          @click="confirmComplete"
        />
        <Button
          v-if="calibration.status === 'scheduled' && authStore.hasPermission('calibracoes.cancel')"
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

    <div v-else-if="calibration" class="card">
      <Tabs v-model:value="activeTab">
        <TabList>
          <Tab value="0">Dados da Calibração</Tab>
          <Tab value="1">Certificados</Tab>
          <Tab value="2">Timeline</Tab>
        </TabList>
        <TabPanels>
          <TabPanel value="0">
            <CalibrationInfoTab :calibration="calibration" />
          </TabPanel>
          <TabPanel value="1">
            <CalibrationCertificateTab
              :calibration-id="calibration.id"
              :certificates="calibration.certificates || []"
              @refresh="handleRefresh"
            />
          </TabPanel>
          <TabPanel value="2">
            <CalibrationTimelineTab :calibration="calibration" />
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>

    <div v-else class="card">
      <div class="text-center text-600 p-4">
        Calibração não encontrada.
      </div>
    </div>

    <CalibrationConcludeDialog
      v-model:visible="showConcludeDialog"
      :calibration="calibration"
      @saved="onCalibrationConcluded"
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
import { useCalibrationStore } from '../store/CalibrationStore'
import { useAuthStore } from '@/stores/auth'
import type { Calibration } from '../types/calibration'
import CalibrationInfoTab from '../components/CalibrationInfoTab.vue'
import CalibrationCertificateTab from '../components/CalibrationCertificateTab.vue'
import CalibrationTimelineTab from '../components/CalibrationTimelineTab.vue'
import CalibrationConcludeDialog from '../components/CalibrationConcludeDialog.vue'

const route = useRoute()
const router = useRouter()
const store = useCalibrationStore()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()

const calibration = ref<Calibration | null>(null)
const loading = ref(false)
const activeTab = ref('0')
const showConcludeDialog = ref(false)

const daysUntilDue = computed(() => {
  if (!calibration.value?.next_due_at) return 0
  const now = new Date()
  const due = new Date(calibration.value.next_due_at)
  return Math.ceil((due.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
})

onMounted(async () => {
  const id = route.params.id as string
  if (id) {
    loading.value = true
    try {
      calibration.value = await store.fetchById(id)
    } finally {
      loading.value = false
    }
  }
})

function goBack() {
  router.push('/calibrations')
}

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

async function handleRefresh() {
  const id = route.params.id as string
  calibration.value = await store.fetchById(id)
}

function confirmComplete() {
  showConcludeDialog.value = true
}

async function onCalibrationConcluded() {
  showConcludeDialog.value = false
  toast.add({
    severity: 'success',
    summary: 'Calibração concluída',
    detail: 'A calibração foi concluída com sucesso.',
    life: 3000,
  })
  const id = route.params.id as string
  calibration.value = await store.fetchById(id)
}

function confirmCancel() {
  confirm.require({
    message: 'Tem certeza que deseja cancelar esta calibração?',
    header: 'Confirmar Cancelamento',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Voltar',
    acceptLabel: 'Cancelar Calibração',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await store.cancel(calibration.value!.id)
        toast.add({
          severity: 'success',
          summary: 'Calibração cancelada',
          detail: 'A calibração foi cancelada com sucesso.',
          life: 3000,
        })
        calibration.value = await store.fetchById(calibration.value!.id)
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
