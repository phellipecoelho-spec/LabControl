<template>
  <Dialog
    :visible="visible"
    @update:visible="$emit('update:visible', $event)"
    header="Devolução de Itens"
    :style="{ width: '600px' }"
    :modal="true"
    class="p-fluid"
  >
    <div v-if="unreturnedItems.length === 0" class="text-center text-600 p-4">
      <i class="pi pi-check-circle text-3xl mb-2 block text-green-500" />
      <p>Todos os itens já foram devolvidos.</p>
    </div>

    <div v-else>
      <p class="text-sm text-600 mb-3">
        Selecione os itens que estão sendo devolvidos e informe a data e observações.
      </p>

      <div
        v-for="item in unreturnedItems"
        :key="item.id"
        class="return-item-card mb-3 p-3 border-round surface-ground"
      >
        <div class="flex align-items-center gap-2 mb-2">
          <Checkbox
            :inputId="'return_' + item.id"
            v-model="selectedItems"
            :value="item.id"
          />
          <label :for="'return_' + item.id" class="font-medium cursor-pointer">
            {{ item.name }}
            <span v-if="item.patrimony_id" class="text-sm text-600">
              — {{ item.patrimony_id }}
            </span>
          </label>
        </div>

        <div v-if="isSelected(item.id)" class="ml-3 pl-3 border-left-2 border-primary">
          <div class="grid mt-2">
            <div class="col-12 md:col-6">
              <div class="field mb-2">
                <label :for="'returned_at_' + item.id" class="block text-600 text-sm mb-1">
                  Data de Devolução
                </label>
                <DatePicker
                  v-model="returnDates[item.id]"
                  dateFormat="dd/mm/yy"
                  placeholder="Selecione a data..."
                  showIcon
                  class="p-inputtext-sm"
                />
              </div>
            </div>
            <div class="col-12 md:col-6">
              <div class="field mb-2">
                <label :for="'notes_' + item.id" class="block text-600 text-sm mb-1">
                  Observações
                </label>
                <InputText
                  v-model="returnNotes[item.id]"
                  placeholder="Observações (opcional)"
                  class="p-inputtext-sm"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <Button
        label="Cancelar"
        icon="pi pi-times"
        severity="secondary"
        @click="$emit('update:visible', false)"
        :disabled="saving"
      />
      <Button
        label="Confirmar Devolução"
        icon="pi pi-check"
        @click="handleConfirm"
        :loading="saving"
        :disabled="selectedItems.length === 0"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, reactive, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import { useLoanStore } from '../store/LoanStore'
import type { LoanedEquipment } from '../types/loan'

const props = defineProps<{
  visible: boolean
  loanId: string
  items: LoanedEquipment[]
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  'returned': []
}>()

const store = useLoanStore()
const toast = useToast()

const saving = ref(false)
const selectedItems = ref<string[]>([])
const returnDates = reactive<Record<string, Date>>({})
const returnNotes = reactive<Record<string, string>>({})

const unreturnedItems = computed(() =>
  props.items.filter(item => !item.pivot?.is_returned)
)

function isSelected(itemId: string): boolean {
  return selectedItems.value.includes(itemId)
}

function resetForm() {
  selectedItems.value = []
  Object.keys(returnDates).forEach(key => delete returnDates[key])
  Object.keys(returnNotes).forEach(key => delete returnNotes[key])
}

// Initialize dates when dialog opens
watch(() => props.visible, (newVal) => {
  if (newVal) {
    resetForm()
  }
})

async function handleConfirm() {
  if (selectedItems.value.length === 0) return

  saving.value = true
  try {
    for (const equipmentId of selectedItems.value) {
      const date = returnDates[equipmentId]
      const notes = returnNotes[equipmentId]

      await store.returnItem(props.loanId, {
        equipment_id: equipmentId,
        returned_at: date ? date.toISOString().split('T')[0] : undefined,
        notes: notes || undefined,
      })
    }

    emit('returned')
  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary: 'Erro na devolução',
      detail: e.response?.data?.message || 'Ocorreu um erro ao processar a devolução.',
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.return-item-card {
  border: 1px solid var(--p-surface-200);
  border-radius: var(--p-border-radius-md);
}
</style>
