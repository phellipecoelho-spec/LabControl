<template>
  <Dialog
    :visible="visible"
    @update:visible="$emit('update:visible', $event)"
    header="Nova Ordem de Manutenção"
    :style="{ width: '600px' }"
    :modal="true"
    class="p-fluid"
  >
    <div class="grid">
      <div class="col-12">
        <div class="field mb-4">
          <label for="equipment" class="font-medium mb-2 block">
            Equipamento <span class="text-red-500">*</span>
          </label>
          <Select
            v-model="form.equipment_id"
            :options="equipmentOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Selecione o equipamento..."
            :filter="true"
            filterPlaceholder="Buscar por nome..."
            :class="{ 'p-invalid': submitted && !form.equipment_id }"
            :disabled="!!equipmentId"
          />
          <small v-if="submitted && !form.equipment_id" class="p-error">
            Selecione um equipamento.
          </small>
        </div>
      </div>

      <div class="col-12 md:col-6">
        <div class="field mb-4">
          <label for="type" class="font-medium mb-2 block">
            Tipo <span class="text-red-500">*</span>
          </label>
          <Select
            v-model="form.type"
            :options="MAINTENANCE_TYPE_OPTIONS"
            optionLabel="label"
            optionValue="value"
            placeholder="Selecione o tipo..."
            :class="{ 'p-invalid': submitted && !form.type }"
          />
          <small v-if="submitted && !form.type" class="p-error">
            Selecione o tipo de manutenção.
          </small>
        </div>
      </div>

      <div class="col-12 md:col-6">
        <div class="field mb-4">
          <label for="priority" class="font-medium mb-2 block">
            Prioridade <span class="text-red-500">*</span>
          </label>
          <Select
            v-model="form.priority"
            :options="MAINTENANCE_PRIORITY_OPTIONS"
            optionLabel="label"
            optionValue="value"
            placeholder="Selecione a prioridade..."
            :class="{ 'p-invalid': submitted && !form.priority }"
          />
          <small v-if="submitted && !form.priority" class="p-error">
            Selecione a prioridade.
          </small>
        </div>
      </div>

      <div class="col-12">
        <div class="field mb-4">
          <label for="description" class="font-medium mb-2 block">
            Descrição <span class="text-red-500">*</span>
          </label>
          <Textarea
            v-model="form.description"
            placeholder="Descreva o serviço a ser realizado..."
            :autoResize="true"
            rows="3"
            :maxlength="5000"
            :class="{ 'p-invalid': submitted && !form.description }"
          />
          <small v-if="submitted && !form.description" class="p-error">
            Informe a descrição da manutenção.
          </small>
        </div>
      </div>

      <div class="col-12 md:col-6">
        <div class="field mb-4">
          <label for="scheduled_date" class="font-medium mb-2 block">Data Agendada</label>
          <DatePicker
            v-model="form.scheduled_date"
            dateFormat="dd/mm/yy"
            placeholder="Selecione a data..."
            showIcon
          />
        </div>
      </div>

      <!-- Interval fields — shown only when type is preventive -->
      <template v-if="form.type === 'preventive'">
        <div class="col-12 md:col-6">
          <div class="field mb-4">
            <label for="interval_value" class="font-medium mb-2 block">
              Intervalo <span class="text-red-500">*</span>
            </label>
            <InputNumber
              v-model="form.interval_value"
              placeholder="Valor"
              :min="1"
              :class="{ 'p-invalid': submitted && !form.interval_value }"
            />
            <small v-if="submitted && !form.interval_value" class="p-error">
              Informe o valor do intervalo.
            </small>
          </div>
        </div>

        <div class="col-12 md:col-6">
          <div class="field mb-4">
            <label for="interval_unit" class="font-medium mb-2 block">
              Unidade <span class="text-red-500">*</span>
            </label>
            <Select
              v-model="form.interval_unit"
              :options="INTERVAL_UNIT_OPTIONS"
              optionLabel="label"
              optionValue="value"
              placeholder="Selecione..."
              :class="{ 'p-invalid': submitted && !form.interval_unit }"
            />
            <small v-if="submitted && !form.interval_unit" class="p-error">
              Selecione a unidade do intervalo.
            </small>
          </div>
        </div>
      </template>
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
        label="Salvar"
        icon="pi pi-check"
        @click="handleSave"
        :loading="saving"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import { useMaintenanceStore } from '../store/MaintenanceStore'
import { MAINTENANCE_TYPE_OPTIONS, MAINTENANCE_PRIORITY_OPTIONS, INTERVAL_UNIT_OPTIONS } from '../types/maintenance'
import type { MaintenanceType, OpenMaintenanceFormData } from '../types/maintenance'

const props = defineProps<{
  visible: boolean
  equipmentId?: string | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  'saved': []
}>()

const store = useMaintenanceStore()
const toast = useToast()

const saving = ref(false)
const submitted = ref(false)

const form = ref<{
  equipment_id: string
  type: MaintenanceType | ''
  priority: string
  description: string
  scheduled_date: any
  interval_value: number | null
  interval_unit: string
}>({
  equipment_id: '',
  type: '',
  priority: '',
  description: '',
  scheduled_date: null,
  interval_value: null,
  interval_unit: '',
})

const equipmentOptions = computed(() =>
  store.equipment.map(eq => ({
    label: `${eq.name}${(eq as any).patrimony_id ? ` — ${(eq as any).patrimony_id}` : ''}`,
    value: eq.id,
  }))
)

// Pre-select equipment when equipmentId prop is provided
watch(() => props.equipmentId, (newId) => {
  if (newId) {
    form.value.equipment_id = newId
  }
}, { immediate: true })

function resetForm() {
  form.value = {
    equipment_id: props.equipmentId || '',
    type: '',
    priority: '',
    description: '',
    scheduled_date: null,
    interval_value: null,
    interval_unit: '',
  }
  submitted.value = false
}

async function handleSave() {
  submitted.value = true

  if (!form.value.equipment_id || !form.value.type || !form.value.priority || !form.value.description) {
    return
  }

  // Validate interval fields for preventive type
  if (form.value.type === 'preventive' && (!form.value.interval_value || !form.value.interval_unit)) {
    return
  }

  saving.value = true
  try {
    const payload: OpenMaintenanceFormData = {
      equipment_id: form.value.equipment_id,
      type: form.value.type as MaintenanceType,
      priority: form.value.priority as any,
      description: form.value.description,
    }
    if (form.value.scheduled_date) {
      payload.scheduled_date = form.value.scheduled_date instanceof Date
        ? form.value.scheduled_date.toISOString().split('T')[0]
        : form.value.scheduled_date
    }
    if (form.value.interval_value) payload.interval_value = form.value.interval_value
    if (form.value.interval_unit) payload.interval_unit = form.value.interval_unit

    await store.create(payload)
    resetForm()
    emit('saved')
  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary: 'Erro ao criar ordem',
      detail: e.response?.data?.message || 'Ocorreu um erro ao processar a solicitação.',
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  if (store.equipment.length === 0) {
    store.fetchEquipment({ all: true })
  }
})
</script>
