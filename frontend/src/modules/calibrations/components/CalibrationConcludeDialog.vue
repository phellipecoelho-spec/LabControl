<template>
  <Dialog
    :visible="visible"
    @update:visible="$emit('update:visible', $event)"
    header="Concluir Calibração"
    :style="{ width: '600px' }"
    :modal="true"
    class="p-fluid"
  >
    <div v-if="calibration" class="mb-4">
      <div class="bg-primary-50 p-3 border-round mb-4">
        <p class="text-sm text-600 m-0">
          <span class="font-medium">Equipamento:</span>
          {{ calibration.equipment?.name || '—' }}
          <span v-if="calibration.part_name" class="text-500">
            — {{ calibration.part_name }}
          </span>
        </p>
        <p v-if="calibration.interval_value" class="text-sm text-600 m-0 mt-1">
          <span class="font-medium">Periodicidade:</span>
          {{ formatInterval(calibration.interval_value, calibration.interval_unit) }}
        </p>
      </div>
    </div>

    <div class="grid">
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

      <div class="col-12 md:col-6">
        <div class="field mb-4">
          <label for="certificate_number" class="font-medium mb-2 block">Nº Certificado</label>
          <InputText
            v-model="form.certificate_number"
            placeholder="Número do certificado"
          />
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
        label="Confirmar Conclusão"
        icon="pi pi-check"
        @click="handleSave"
        :loading="saving"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import { useCalibrationStore } from '../store/CalibrationStore'
import type { Calibration, CompleteCalibrationFormData } from '../types/calibration'

const props = defineProps<{
  visible: boolean
  calibration: Calibration | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  'saved': []
}>()

const store = useCalibrationStore()
const toast = useToast()

const saving = ref(false)

const form = ref<CompleteCalibrationFormData>({
  completed_at: new Date(),
  certificate_number: '',
  responsible: '',
  laboratory: '',
  notes: '',
})

function resetForm() {
  form.value = {
    completed_at: new Date(),
    certificate_number: '',
    responsible: props.calibration?.responsible || '',
    laboratory: props.calibration?.laboratory || '',
    notes: '',
  }
}

// Populate form fields when calibration changes
watch(() => props.calibration, (cal) => {
  if (cal) {
    form.value.responsible = cal.responsible || ''
    form.value.laboratory = cal.laboratory || ''
  }
})

// Reset form when dialog opens
watch(() => props.visible, (newVal) => {
  if (newVal) {
    resetForm()
  }
})

function formatInterval(value: number, unit: string): string {
  const labels: Record<string, string> = {
    months: value === 1 ? 'mês' : 'meses',
    days: value === 1 ? 'dia' : 'dias',
    hours: value === 1 ? 'hora' : 'horas',
  }
  return `${value} ${labels[unit] || unit}`
}

async function handleSave() {
  if (!props.calibration) return

  saving.value = true
  try {
    const payload: Record<string, any> = {}
    if (form.value.completed_at) {
      payload.completed_at = form.value.completed_at instanceof Date
        ? form.value.completed_at.toISOString().split('T')[0]
        : form.value.completed_at
    }
    if (form.value.certificate_number) payload.certificate_number = form.value.certificate_number
    if (form.value.responsible) payload.responsible = form.value.responsible
    if (form.value.laboratory) payload.laboratory = form.value.laboratory
    if (form.value.notes) payload.notes = form.value.notes

    await store.complete(props.calibration.id, payload)
    emit('saved')
  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary: 'Erro ao concluir calibração',
      detail: e.response?.data?.message || 'Ocorreu um erro ao processar a conclusão.',
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}
</script>
