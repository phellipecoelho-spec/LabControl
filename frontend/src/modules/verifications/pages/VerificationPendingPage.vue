<template>
  <div class="verification-pending-page">
    <div class="flex align-items-center justify-content-between mb-4">
      <h2 class="text-2xl font-bold m-0">
        <i class="pi pi-check-circle mr-2 text-primary"></i>
        Aferições Pendentes
      </h2>
    </div>

    <Card>
      <template #content>
        <LoadingSkeleton v-if="store.loading" variant="table" :rows="3" />

        <EmptyState
          v-else-if="store.pendingEquipment.length === 0"
          icon="pi pi-check-circle"
          title="Todos os equipamentos estão em dia"
          description="Nenhum equipamento precisa de aferição no momento."
        />

        <DataTable
          v-else
          :value="store.pendingEquipment"
          stripedRows
          :rows="15"
          :paginator="store.pendingEquipment.length > 15"
        >
          <Column field="name" header="Equipamento" sortable>
            <template #body="{ data }">
              <span class="font-medium">{{ data.name }}</span>
            </template>
          </Column>
          <Column field="patrimony_id" header="Patrimônio" sortable />
          <Column field="serial_number" header="Nº Série" />
          <Column field="category" header="Categoria" sortable>
            <template #body="{ data }">
              {{ data.category?.name || '—' }}
            </template>
          </Column>
          <Column field="verification_frequency" header="Frequência" sortable>
            <template #body="{ data }">
              <Tag
                :value="frequencyLabels[data.verification_frequency] || data.verification_frequency"
                severity="info"
                rounded
              />
            </template>
          </Column>
          <Column field="last_verification_at" header="Última Aferição" sortable>
            <template #body="{ data }">
              <span v-if="data.last_verification_at" class="text-sm">
                {{ formatDate(data.last_verification_at) }}
              </span>
              <Tag v-else value="Nunca" severity="warn" rounded />
            </template>
          </Column>
          <Column header="Ações" style="width: 100px">
            <template #body="{ data }">
              <Button
                v-if="authStore.hasPermission('afericoes.create')"
                label="Aferir"
                icon="pi pi-check"
                severity="info"
                size="small"
                @click="openVerificationDialog(data.id)"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <VerificationFormDialog
      v-model:visible="verificationDialogVisible"
      :equipmentId="selectedEquipmentId"
      @saved="onVerificationSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useVerificationStore } from '../store/VerificationStore'
import VerificationFormDialog from '../components/VerificationFormDialog.vue'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import EmptyState from '@/components/EmptyState.vue'

const authStore = useAuthStore()
const store = useVerificationStore()

function formatDate(dateStr: string): string {
  const d = new Date(dateStr)
  return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const verificationDialogVisible = ref(false)
const selectedEquipmentId = ref<string | null>(null)

const frequencyLabels: Record<string, string> = {
  daily: 'Diária',
  weekly: 'Semanal',
  shift: 'Turno',
}

onMounted(() => {
  store.fetchPending()
})

function openVerificationDialog(equipmentId: string) {
  selectedEquipmentId.value = equipmentId
  verificationDialogVisible.value = true
}

function onVerificationSaved() {
  verificationDialogVisible.value = false
  selectedEquipmentId.value = null
  store.fetchPending()
}
</script>

<style scoped>
.verification-pending-page {
  max-width: 1200px;
}
</style>
