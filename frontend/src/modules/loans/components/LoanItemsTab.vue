<template>
  <div class="loan-items-tab">
    <DataTable
      :value="items"
      stripedRows
      size="small"
      :ptable="true"
    >
      <Column header="Equipamento">
        <template #body="{ data }">
          <span class="font-medium">{{ data.name }}</span>
        </template>
      </Column>
      <Column header="Patrimônio">
        <template #body="{ data }">
          <span>{{ data.patrimony_id || '—' }}</span>
        </template>
      </Column>
      <Column header="Nº Série">
        <template #body="{ data }">
          <span>{{ data.serial_number || '—' }}</span>
        </template>
      </Column>
      <Column header="Status">
        <template #body="{ data }">
          <Tag
            v-if="data.pivot?.is_returned"
            value="Devolvido"
            severity="success"
            rounded
            size="small"
          />
          <Tag
            v-else
            value="Pendente"
            severity="warn"
            rounded
            size="small"
          />
        </template>
      </Column>
      <Column header="Devolvido Em">
        <template #body="{ data }">
          <span>{{ data.pivot?.returned_at ? formatDate(data.pivot.returned_at) : '—' }}</span>
        </template>
      </Column>
      <Column header="Observações">
        <template #body="{ data }">
          <span class="text-sm">{{ data.pivot?.notes || '—' }}</span>
        </template>
      </Column>
      <Column v-if="hasUnreturnedItems" header="Ações" style="width: 100px">
        <template #body="{ data }">
          <Button
            v-if="!data.pivot?.is_returned"
            label="Devolver"
            icon="pi pi-undo"
            severity="warn"
            size="small"
            outlined
            @click="$emit('returnItem')"
          />
        </template>
      </Column>
    </DataTable>

    <div v-if="items.length === 0" class="text-center text-600 p-4">
      Nenhum equipamento vinculado a este empréstimo.
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import type { LoanedEquipment } from '../types/loan'

const props = defineProps<{
  items: LoanedEquipment[]
}>()

defineEmits<{
  returnItem: []
}>()

const hasUnreturnedItems = computed(() =>
  props.items.some(item => !item.pivot?.is_returned)
)

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
