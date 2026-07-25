<template>
  <div class="verification-history-tab">
    <div class="flex align-items-center justify-content-between mb-3">
      <h3 class="text-lg font-semibold m-0">Histórico de Aferições</h3>
      <Button
        v-if="authStore.hasPermission('afericoes.create')"
        label="Aferir"
        icon="pi pi-check"
        severity="info"
        size="small"
        @click="$emit('start-verification')"
      />
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex flex-column gap-2">
      <Skeleton height="3rem" v-for="n in 3" :key="n" />
    </div>

    <!-- History table -->
    <DataTable
      v-else-if="verifications.length > 0"
      :value="verifications"
      stripedRows
      :rows="perPage"
      :totalRecords="totalRecords"
      :lazy="true"
      :paginator="totalRecords > perPage"
      :first="first"
      @page="onPageChange"
      dataKey="id"
      :expandedRows="expandedRows"
      @rowToggle="onRowToggle"
    >
      <Column :expander="true" style="width: 3rem" />
      <Column field="verified_at" header="Data" sortable>
        <template #body="{ data }">
          {{ formatDate(data.verified_at) }}
        </template>
      </Column>
      <Column field="operator" header="Operador">
        <template #body="{ data }">
          {{ data.operator?.name || '—' }}
        </template>
      </Column>
      <Column header="# Parâmetros">
        <template #body="{ data }">
          {{ data.params?.length || 0 }}
        </template>
      </Column>
      <Column header="Fora do Intervalo">
        <template #body="{ data }">
          <Tag
            v-if="data.is_outside_range"
            value="Sim"
            severity="danger"
            rounded
          />
          <Tag v-else value="Não" severity="success" rounded />
        </template>
      </Column>
      <Column header="Status">
        <template #body="{ data }">
          <i
            v-if="!data.is_outside_range"
            class="pi pi-check-circle text-green-500 text-xl"
            title="Dentro do intervalo"
          ></i>
          <i
            v-else
            class="pi pi-times-circle text-red-500 text-xl"
            title="Fora do intervalo"
          ></i>
        </template>
      </Column>
      <Column header="Ações" style="width: 100px">
        <template #body="{ data }">
          <Button
            icon="pi pi-eye"
            text
            rounded
            severity="secondary"
            v-tooltip.top="'Ver detalhes'"
            @click="expandedRows = expandedRows === data.id ? null : data.id"
          />
        </template>
      </Column>

      <!-- Expanded row: param details -->
      <template #expansion="{ data }">
        <div class="p-3 surface-ground border-round">
          <div v-if="data.params?.length" class="flex flex-wrap gap-2">
            <Tag
              v-for="param in data.params"
              :key="param.id"
            >
              <div class="flex align-items-center gap-1">
                <span>{{ param.template?.parameter_name || '—' }}:</span>
                <span class="font-semibold">{{ param.value }}</span>
                <i
                  v-if="param.result === 'within_range'"
                  class="pi pi-check-circle text-green-500 ml-1"
                ></i>
                <i
                  v-else-if="param.result === 'outside_range'"
                  class="pi pi-times-circle text-red-500 ml-1"
                ></i>
                <i
                  v-else
                  class="pi pi-minus text-gray-500 ml-1"
                ></i>
              </div>
            </Tag>
          </div>
          <div v-else class="text-sm text-color-secondary">
            Nenhum parâmetro registrado.
          </div>
          <div v-if="data.notes" class="mt-2 text-sm text-color-secondary">
            <i class="pi pi-comment mr-1"></i>
            {{ data.notes }}
          </div>
        </div>
      </template>
    </DataTable>

    <!-- Empty state -->
    <div v-else class="flex flex-column align-items-center py-5 text-color-secondary">
      <i class="pi pi-inbox text-4xl mb-2" style="opacity: 0.4"></i>
      <p class="m-0">Nenhuma aferição registrada para este equipamento.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { verificationService } from '../services/VerificationService'
import type { Verification } from '../types/verification'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import Button from 'primevue/button'

const props = defineProps<{
  equipmentId: string
}>()

const emit = defineEmits<{
  'start-verification': []
}>()

const authStore = useAuthStore()

const verifications = ref<Verification[]>([])
const loading = ref(false)
const totalRecords = ref(0)
const perPage = ref(15)
const first = ref(0)
const currentPage = ref(1)
const expandedRows = ref<any>(null)

onMounted(() => {
  fetchHistory()
  window.addEventListener('verification-saved', onVerificationSavedEvent)
})

onUnmounted(() => {
  window.removeEventListener('verification-saved', onVerificationSavedEvent)
})

watch(() => props.equipmentId, () => {
  currentPage.value = 1
  first.value = 0
  fetchHistory()
})

function formatDate(dateStr: string): string {
  const d = new Date(dateStr)
  return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function fetchHistory() {
  loading.value = true
  try {
    const result = await verificationService.getHistoryByEquipment(props.equipmentId, {
      page: currentPage.value,
      per_page: perPage.value,
    })
    verifications.value = result.data
    if (result.meta) {
      totalRecords.value = result.meta.total ?? 0
    }
  } finally {
    loading.value = false
  }
}

function onPageChange(event: any) {
  currentPage.value = event.page + 1
  first.value = event.first
  fetchHistory()
}

function onRowToggle(event: any) {
  expandedRows.value = expandedRows.value === event.data.id ? null : event.data.id
}

function onVerificationSavedEvent(event: Event) {
  const detail = (event as CustomEvent).detail
  if (detail?.equipmentId === props.equipmentId) {
    fetchHistory()
  }
}
</script>
