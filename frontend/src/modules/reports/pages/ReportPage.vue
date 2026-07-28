<template>
  <div class="report-page">
    <Toast />

    <div class="flex align-items-center justify-content-between mb-4">
      <div>
        <h2 class="text-2xl font-bold m-0">Relatórios</h2>
        <p class="text-sm text-600 mt-1">Exporte dados do sistema em PDF, Excel ou CSV</p>
      </div>
      <Button
        icon="pi pi-filter"
        severity="secondary"
        variant="text"
        :badge="hasActiveFilters ? '1' : undefined"
        @click="showFilters = true"
      />
    </div>

    <Drawer v-model:visible="showFilters" header="Filtros" position="left">
      <div class="filter-section">
        <label class="filter-label">Período</label>
        <DatePicker
          v-model="filters.dateRange"
          selectionMode="range"
          dateFormat="dd/mm/yy"
          placeholder="Selecionar período"
          class="w-full"
        />
      </div>
      <div class="filter-section">
        <label class="filter-label">Status</label>
        <Select
          v-model="filters.status"
          :options="statusOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="Todos"
          class="w-full"
          clearable
        />
      </div>
      <div class="filter-actions">
        <Button label="Aplicar" class="w-full mb-2" @click="applyFilters" />
        <Button label="Limpar" severity="secondary" variant="text" class="w-full" @click="clearFilters" />
      </div>
    </Drawer>

    <LoadingSkeleton v-if="loading" variant="card" :rows="4" />

    <EmptyState
      v-else-if="reports.length === 0"
      icon="pi pi-file-pdf"
      title="Nenhum relatório disponível"
      description="No momento não há relatórios configurados no sistema."
    />

    <div v-else class="report-grid">
      <div v-for="report in reports" :key="report.type" class="report-card-wrapper">
        <Card>
          <template #title>
            <div class="flex align-items-center gap-2">
              <i :class="report.icon"></i>
              <span class="font-semibold">{{ report.label }}</span>
            </div>
          </template>
          <template #content>
            <p class="report-description">{{ report.description }}</p>
          </template>
          <template #footer>
            <SplitButton
              :label="getDefaultFormatLabel(report)"
              icon="pi pi-download"
              :loading="downloading[report.type]"
              @click="triggerDownload(report, report.defaultFormat)"
              :model="formatMenuItems(report)"
            />
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import Toast from 'primevue/toast'
import Button from 'primevue/button'
import Drawer from 'primevue/drawer'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Card from 'primevue/card'
import SplitButton from 'primevue/splitbutton'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import EmptyState from '@/components/EmptyState.vue'
import { reportService } from '../services/ReportService'
import { useDownload } from '@/composables/useDownload'
import { useToast } from 'primevue/usetoast'
import type { ReportMeta, ReportFormat, ReportFilters } from '../types/report'

const { downloading, downloadReport } = useDownload()
const toast = useToast()

const reports = ref<ReportMeta[]>([])
const loading = ref(true)
const showFilters = ref(false)

const filters = reactive({
  dateRange: null as (Date | null)[] | null,
  status: null as string | null,
})

const statusOptions = [
  { label: 'Todos', value: null as string | null },
  { label: 'Ativo', value: 'active' },
  { label: 'Inativo', value: 'inactive' },
  { label: 'Pendente', value: 'pending' },
  { label: 'Concluído', value: 'completed' },
]

const hasActiveFilters = computed(() => {
  return filters.dateRange !== null || filters.status !== null
})

const FORMAT_LABELS: Record<ReportFormat, string> = {
  pdf: 'PDF',
  xlsx: 'Excel',
  csv: 'CSV',
}

const FORMAT_ICONS: Record<ReportFormat, string> = {
  pdf: 'pi pi-file-pdf',
  xlsx: 'pi pi-file-excel',
  csv: 'pi pi-file',
}

function getDefaultFormatLabel(report: ReportMeta): string {
  return FORMAT_LABELS[report.defaultFormat] || 'PDF'
}

function formatMenuItems(report: ReportMeta) {
  const otherFormats = report.formats.filter(f => f !== report.defaultFormat)
  return otherFormats.map(format => ({
    label: FORMAT_LABELS[format] || format.toUpperCase(),
    icon: FORMAT_ICONS[format],
    command: () => triggerDownload(report, format),
  }))
}

async function triggerDownload(report: ReportMeta, format: ReportFormat) {
  const filtersPayload: ReportFilters = {}
  if (filters.dateRange?.[0]) {
    filtersPayload.date_from = (filters.dateRange[0] as Date).toISOString().split('T')[0]
  }
  if (filters.dateRange?.[1]) {
    filtersPayload.date_to = (filters.dateRange[1] as Date).toISOString().split('T')[0]
  }
  if (filters.status) {
    filtersPayload.status = filters.status
  }

  const url = reportService.getDownloadUrl(report.type, format, filtersPayload)
  const filename = `${report.type}_${new Date().toISOString().split('T')[0]}.${format}`
  await downloadReport(url, filename, `${report.type}-${format}`)
}

async function fetchReports() {
  loading.value = true
  try {
    const response = await reportService.getReportList()
    reports.value = response.reports || []
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Erro',
      detail: 'Não foi possível carregar a lista de relatórios.',
      life: 5000,
    })
    reports.value = []
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  showFilters.value = false
}

function clearFilters() {
  filters.dateRange = null
  filters.status = null
  showFilters.value = false
}

onMounted(() => {
  fetchReports()
})
</script>

<style scoped>
.report-page {
  padding: 1.5rem;
}

.report-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

@media (min-width: 640px) {
  .report-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .report-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

.report-description {
  margin: 0;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
  line-height: 1.5;
}

.filter-section {
  margin-bottom: 1.5rem;
}

.filter-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: var(--p-text-color);
}

.filter-actions {
  margin-top: 2rem;
}
</style>
