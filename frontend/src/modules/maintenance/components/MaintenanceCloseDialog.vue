<template>
  <Dialog
    :visible="visible"
    @update:visible="$emit('update:visible', $event)"
    header="Concluir Manutenção"
    :style="{ width: '600px' }"
    :modal="true"
    class="p-fluid"
  >
    <div class="grid">
      <div class="col-12">
        <div class="field mb-4">
          <label for="resolution" class="font-medium mb-2 block">
            Parecer Técnico <span class="text-red-500">*</span>
          </label>
          <Textarea
            v-model="form.resolution"
            placeholder="Descreva o parecer técnico da manutenção realizada..."
            :autoResize="true"
            rows="3"
            :maxlength="5000"
            :class="{ 'p-invalid': submitted && !form.resolution }"
          />
          <small v-if="submitted && !form.resolution" class="p-error">
            Informe o parecer técnico.
          </small>
        </div>
      </div>

      <div class="col-12 md:col-6">
        <div class="field mb-4">
          <label for="time_spent" class="font-medium mb-2 block">Tempo Gasto (horas)</label>
          <InputNumber
            v-model="form.time_spent"
            placeholder="Horas gastas"
            :min="0"
            :step="0.5"
            suffix=" h"
          />
        </div>
      </div>

      <div class="col-12 md:col-6">
        <div class="field mb-4">
          <label for="cost" class="font-medium mb-2 block">Custo (R$)</label>
          <InputNumber
            v-model="form.cost"
            placeholder="Custo total"
            :min="0"
            :minFractionDigits="2"
            :maxFractionDigits="2"
            prefix="R$ "
          />
        </div>
      </div>

      <div class="col-12 md:col-6">
        <div class="field mb-4">
          <label for="completed_at" class="font-medium mb-2 block">Data de Conclusão</label>
          <DatePicker
            v-model="form.completed_at"
            dateFormat="dd/mm/yy"
            placeholder="Selecione a data..."
            showIcon
          />
        </div>
      </div>

      <!-- Parts Section -->
      <div class="col-12">
        <div class="flex align-items-center justify-content-between mb-2">
          <label class="font-medium">Peças Utilizadas</label>
          <Button
            label="Adicionar Peça"
            icon="pi pi-plus"
            size="small"
            severity="info"
            text
            @click="addPart"
          />
        </div>

        <div
          v-for="(part, index) in form.parts"
          :key="index"
          class="grid mb-2 p-2 surface-ground border-round"
        >
          <div class="col-5">
            <Select
              v-model="part.inventory_item_id"
              :options="inventoryOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Selecione o item..."
              :filter="true"
              filterPlaceholder="Buscar por nome ou código..."
              :class="{ 'p-invalid': submitted && !part.inventory_item_id }"
            />
          </div>
          <div class="col-3">
            <InputNumber
              v-model="part.quantity"
              placeholder="Qtd"
              :min="0.0001"
              :step="1"
              :minFractionDigits="0"
              :maxFractionDigits="4"
              :class="{ 'p-invalid': submitted && !part.quantity }"
            />
          </div>
          <div class="col-3">
            <InputNumber
              v-model="part.unit_cost"
              placeholder="Custo unit."
              :min="0"
              :minFractionDigits="2"
              :maxFractionDigits="2"
              prefix="R$ "
            />
          </div>
          <div class="col-1 flex align-items-center">
            <Button
              icon="pi pi-trash"
              severity="danger"
              text
              rounded
              size="small"
              @click="removePart(index)"
            />
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
        label="Concluir"
        icon="pi pi-check"
        @click="handleSave"
        :loading="saving"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import { api } from '@/services/api'
import { useMaintenanceStore } from '../store/MaintenanceStore'
import type { CloseMaintenanceFormData } from '../types/maintenance'

const props = defineProps<{
  visible: boolean
  orderId: string
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  'saved': []
}>()

const store = useMaintenanceStore()
const toast = useToast()

const saving = ref(false)
const submitted = ref(false)
const inventoryItems = ref<{ id: string; name: string; code?: string }[]>([])

interface PartEntry {
  inventory_item_id: string
  quantity: number | null
  unit_cost: number | null
}

const form = ref<{
  resolution: string
  time_spent: number | null
  cost: number | null
  completed_at: any
  parts: PartEntry[]
}>({
  resolution: '',
  time_spent: null,
  cost: null,
  completed_at: new Date(),
  parts: [],
})

const inventoryOptions = computed(() =>
  inventoryItems.value.map(item => ({
    label: `${item.name}${item.code ? ` (${item.code})` : ''}`,
    value: item.id,
  }))
)

function addPart() {
  form.value.parts.push({
    inventory_item_id: '',
    quantity: null,
    unit_cost: null,
  })
}

function removePart(index: number) {
  form.value.parts.splice(index, 1)
}

function resetForm() {
  form.value = {
    resolution: '',
    time_spent: null,
    cost: null,
    completed_at: new Date(),
    parts: [],
  }
  submitted.value = false
}

async function handleSave() {
  submitted.value = true

  if (!form.value.resolution) {
    return
  }

  saving.value = true
  try {
    const completedAt = form.value.completed_at instanceof Date
      ? form.value.completed_at.toISOString().split('T')[0]
      : form.value.completed_at

    const payload: CloseMaintenanceFormData = {
      resolution: form.value.resolution,
      completed_at: completedAt,
    }
    if (form.value.time_spent !== null) payload.time_spent = form.value.time_spent
    if (form.value.cost !== null) payload.cost = form.value.cost

    // Build parts payload, filtering out empty rows
    const validParts = form.value.parts.filter(p => p.inventory_item_id && p.quantity)
    if (validParts.length > 0) {
      payload.parts = validParts.map(p => ({
        inventory_item_id: p.inventory_item_id,
        quantity: p.quantity!,
        ...(p.unit_cost !== null ? { unit_cost: p.unit_cost } : {}),
      }))
    }

    await store.complete(props.orderId, payload)
    resetForm()
    emit('saved')
  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary: 'Erro ao concluir ordem',
      detail: e.response?.data?.message || 'Ocorreu um erro ao processar a solicitação.',
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}

async function fetchInventoryItems() {
  try {
    const response = await api.get('/inventory-items', { params: { all: true } })
    inventoryItems.value = response.data?.data ?? response.data ?? []
  } catch {
    // Silently fail — inventory items loading is optional
  }
}

onMounted(() => {
  fetchInventoryItems()
})
</script>
