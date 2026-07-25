<template>
  <Dialog
    :visible="visible"
    @update:visible="$emit('update:visible', $event)"
    header="Nova Calibração"
    :style="{ width: '600px' }"
    :modal="true"
    class="p-fluid"
  >
    <div class="grid">
      <div class="col-12 md:col-8">
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
          />
          <small v-if="submitted && !form.equipment_id" class="p-error">
            Selecione um equipamento.
          </small>
        </div>
      </div>

      <div class="col-12 md:col-4">
        <div class="field mb-4">
          <label for="part_name" class="font-medium mb-2 block">Parte do Equipamento</label>
          <InputText
            v-model="form.part_name"
            placeholder="Ex: Sensor de temp."
          />
        </div>
      </div>

      <div class="col-12 md:col-4">
        <div class="field mb-4">
          <label for="scheduled_date" class="font-medium mb-2 block">
            Data Agendada <span class="text-red-500">*</span>
          </label>
          <DatePicker
            v-model="form.scheduled_date"
            dateFormat="dd/mm/yy"
            placeholder="Selecione a data..."
            showIcon
            :class="{ 'p-invalid': submitted && !form.scheduled_date }"
          />
          <small v-if="submitted && !form.scheduled_date" class="p-error">
            Informe a data agendada.
          </small>
        </div>
      </div>

      <div class="col-12 md:col-4">
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

      <div class="col-12 md:col-4">
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

      <div class="col-12 md:col-6">
        <div class="field mb-4">
          <label for="responsible" class="font-medium mb-2 block">Responsável</label>
          <InputText
            v-model="form.responsible"
            placeholder="Responsável pela calibração"
          />
        </div>
      </div>

      <div class="col-12 md:col-6">
        <div class="field mb-4">
          <label for="laboratory" class="font-medium mb-2 block">Laboratório</label>
          <InputText
            v-model="form.laboratory"
            placeholder="Laboratório responsável"
          />
        </div>
      </div>

      <div class="col-12">
        <div class="field mb-3">
          <label for="notes" class="font-medium mb-2 block">Observações</label>
          <Textarea
            v-model="form.notes"
            placeholder="Observações adicionais (opcional)"
            :autoResize="true"
            rows="2"
          />
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
        label="Salvar"
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
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import { useCalibrationStore } from '../store/CalibrationStore'
import { INTERVAL_UNIT_OPTIONS } from '../types/calibration'
import type { CalibrationFormData } from '../types/calibration'

const props = defineProps<{
  visible: boolean
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  'saved': []
}>()

const store = useCalibrationStore()
const toast = useToast()

const saving = ref(false)
const submitted = ref(false)

const form = ref<{
  equipment_id: string
  scheduled_date: any
  interval_value: number
  interval_unit: 'months' | 'days' | 'hours'
  part_name: string
  responsible: string
  laboratory: string
  notes: string
}>({
  equipment_id: '',
  scheduled_date: '',
  interval_value: 0,
  interval_unit: '' as 'months' | 'days' | 'hours',
  part_name: '',
  responsible: '',
  laboratory: '',
  notes: '',
})

const equipmentOptions = computed(() =>
  store.equipment.map(eq => ({
    label: `${eq.name}${(eq as any).patrimony_id ? ` — ${(eq as any).patrimony_id}` : ''}`,
    value: eq.id,
  }))
)

function resetForm() {
  form.value = {
    equipment_id: '',
    scheduled_date: '',
    interval_value: 0,
    interval_unit: '' as 'months' | 'days' | 'hours',
    part_name: '',
    responsible: '',
    laboratory: '',
    notes: '',
  }
  submitted.value = false
}

async function handleSave() {
  submitted.value = true

  if (!form.value.equipment_id || !form.value.scheduled_date || !form.value.interval_value || !form.value.interval_unit) {
    return
  }

  saving.value = true
  try {
    const scheduledDate = form.value.scheduled_date instanceof Date
      ? form.value.scheduled_date.toISOString().split('T')[0]
      : form.value.scheduled_date
    const payload: CalibrationFormData = {
      equipment_id: form.value.equipment_id,
      scheduled_date: scheduledDate,
      interval_value: form.value.interval_value,
      interval_unit: form.value.interval_unit,
    }
    if (form.value.part_name) payload.part_name = form.value.part_name
    if (form.value.responsible) payload.responsible = form.value.responsible
    if (form.value.laboratory) payload.laboratory = form.value.laboratory
    if (form.value.notes) payload.notes = form.value.notes

    await store.create(payload)
    resetForm()
    emit('saved')
  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary: 'Erro ao criar calibração',
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
