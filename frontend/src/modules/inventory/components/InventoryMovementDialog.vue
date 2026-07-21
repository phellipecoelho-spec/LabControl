<template>
  <Dialog
    :visible="visible"
    @update:visible="emitClose"
    header="Nova Movimentação"
    :modal="true"
    :style="{ width: '500px' }"
    :closable="!saving"
  >
    <div class="grid">
      <div class="col-12">
        <div class="field mb-3">
          <label class="block text-900 font-medium mb-2">
            Item <span class="text-red-500">*</span>
          </label>
          <div v-if="itemId">
            <InputText
              :value="selectedItemName"
              disabled
              class="w-full"
            />
          </div>
          <Select
            v-else
            v-model="form.item_id"
            :options="itemOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Selecione um item"
            :class="{ 'p-invalid': errors.item_id }"
            filter
            :disabled="saving"
            class="w-full"
          />
          <small v-if="errors.item_id" class="p-error">Item é obrigatório</small>
        </div>
      </div>
      <div class="col-12">
        <div class="field mb-3">
          <label class="block text-900 font-medium mb-2">
            Tipo <span class="text-red-500">*</span>
          </label>
          <Select
            v-model="form.type"
            :options="movementTypeOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Selecione o tipo"
            :class="{ 'p-invalid': errors.type }"
            :disabled="saving"
            class="w-full"
          />
          <small v-if="errors.type" class="p-error">Tipo é obrigatório</small>
        </div>
      </div>
      <div class="col-12">
        <div class="field mb-3">
          <label class="block text-900 font-medium mb-2">
            Quantidade <span class="text-red-500">*</span>
          </label>
          <InputNumber
            v-model="form.quantity"
            placeholder="Quantidade"
            :class="{ 'p-invalid': errors.quantity }"
            :disabled="saving"
            class="w-full"
            :min="1"
          />
          <small v-if="errors.quantity" class="p-error">Quantidade deve ser maior que zero</small>
        </div>
      </div>
      <div class="col-12" v-if="showReasonField">
        <div class="field mb-3">
          <label class="block text-900 font-medium mb-2">
            Motivo <span class="text-red-500">*</span>
          </label>
          <InputText
            v-model="form.reason"
            placeholder="Informe o motivo da movimentação"
            :class="{ 'p-invalid': errors.reason }"
            :disabled="saving"
            class="w-full"
          />
          <small v-if="errors.reason" class="p-error">Motivo é obrigatório para ajuste e descarte</small>
        </div>
      </div>
      <div class="col-12">
        <div class="field mb-3">
          <label class="block text-900 font-medium mb-2">
            Observações
          </label>
          <Textarea
            v-model="form.notes"
            placeholder="Observações adicionais..."
            :disabled="saving"
            class="w-full"
            rows="3"
          />
        </div>
      </div>
    </div>

    <template #footer>
      <Button
        label="Cancelar"
        severity="secondary"
        @click="emitClose"
        :disabled="saving"
      />
      <Button
        label="Salvar"
        :loading="saving"
        :disabled="!canSave"
        @click="handleSubmit"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import { useInventoryItemStore } from '@/modules/inventory/store/InventoryItemStore'
import { useInventoryMovementStore } from '@/modules/inventory/store/InventoryMovementStore'
import { MOVEMENT_TYPE_OPTIONS } from '@/modules/inventory/types/inventory'
import type { InventoryMovementFormData } from '@/modules/inventory/types/inventory'

const props = defineProps<{
  visible: boolean
  itemId?: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'created', data: { movement: any; is_critical: boolean }): void
}>()

const itemStore = useInventoryItemStore()
const movementStore = useInventoryMovementStore()
const toast = useToast()

const saving = ref(false)
const movementTypeOptions = [...MOVEMENT_TYPE_OPTIONS]

const form = reactive<InventoryMovementFormData>({
  item_id: '',
  type: '' as any,
  quantity: 0,
  reason: '',
  notes: '',
})

const errors = reactive({
  item_id: false,
  type: false,
  quantity: false,
  reason: false,
})

const showReasonField = computed(() => {
  return form.type === 'adjustment' || form.type === 'disposal'
})

const canSave = computed(() => {
  return (
    (props.itemId || form.item_id) &&
    form.type &&
    form.quantity > 0 &&
    (showReasonField.value ? !!form.reason : true) &&
    !saving.value
  )
})

const itemOptions = computed(() => {
  return itemStore.items.map(item => ({
    label: `${item.name}${item.code ? ` (${item.code})` : ''}`,
    value: item.id,
  }))
})

const selectedItemName = computed(() => {
  if (!props.itemId) return ''
  const item = itemStore.items.find(i => i.id === props.itemId)
  return item ? `${item.name}${item.code ? ` (${item.code})` : ''}` : 'Carregando...'
})

function resetForm() {
  form.item_id = props.itemId || ''
  form.type = '' as any
  form.quantity = 0
  form.reason = ''
  form.notes = ''
  errors.item_id = false
  errors.type = false
  errors.quantity = false
  errors.reason = false
}

function emitClose() {
  if (!saving.value) {
    emit('close')
  }
}

function validateForm(): boolean {
  errors.item_id = !props.itemId && !form.item_id
  errors.type = !form.type
  errors.quantity = !form.quantity || form.quantity <= 0
  errors.reason = showReasonField.value && !form.reason

  return !Object.values(errors).some(Boolean)
}

async function handleSubmit() {
  if (!validateForm()) {
    toast.add({
      severity: 'warn',
      summary: 'Campos obrigatórios',
      detail: 'Preencha todos os campos obrigatórios',
      life: 3000,
    })
    return
  }

  saving.value = true
  try {
    const result = await movementStore.create({
      item_id: props.itemId || form.item_id,
      type: form.type,
      quantity: form.quantity,
      reason: form.reason || undefined,
      notes: form.notes || undefined,
    })

    const isCritical = movementStore.lastCreatedCritical

    toast.add({
      severity: 'success',
      summary: 'Movimentação registrada',
      detail: 'A movimentação foi registrada com sucesso.',
      life: 3000,
    })

    emit('created', { movement: result, is_critical: isCritical })
  } catch (e: any) {
    const message = e.response?.data?.message || 'Ocorreu um erro ao registrar a movimentação.'

    toast.add({
      severity: 'error',
      summary: 'Erro',
      detail: message,
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}

watch(() => props.visible, (newVal) => {
  if (newVal) {
    resetForm()
    // Ensure items are loaded for the dropdown
    if (itemStore.items.length === 0) {
      itemStore.fetchAll({ per_page: 100 })
    }
  }
})
</script>
