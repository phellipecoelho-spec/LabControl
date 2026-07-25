<template>
  <Dialog
    :visible="visible"
    @update:visible="$emit('update:visible', $event)"
    header="Nova Aferição"
    modal
    :style="{ width: '700px' }"
    :closable="!submitting"
    :draggable="false"
  >
    <div class="flex flex-column gap-4">
      <!-- Equipment selector (only when no equipmentId pre-filled) -->
      <div v-if="!equipmentId" class="field">
        <label for="equipment" class="font-medium block mb-2">Equipamento</label>
        <Select
          id="equipment"
          v-model="formEquipmentId"
          :options="equipmentOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="Selecione um equipamento..."
          class="w-full"
          :disabled="submitting"
          @change="onEquipmentChange"
        />
      </div>

      <!-- Selected equipment info -->
      <div v-if="selectedEquipment" class="surface-ground p-3 border-round">
        <div class="flex align-items-center gap-2 mb-2">
          <i class="pi pi-box text-primary"></i>
          <span class="font-semibold">{{ selectedEquipment.name }}</span>
        </div>
        <div class="grid text-sm text-color-secondary">
          <div class="col-6" v-if="selectedEquipment.patrimony_id">
            Patrimônio: {{ selectedEquipment.patrimony_id }}
          </div>
          <div class="col-6" v-if="selectedEquipment.category">
            Categoria: {{ selectedEquipment.category.name }}
          </div>
          <div class="col-6">
            Frequência: {{ frequencyLabels[selectedEquipment.verification_frequency] || selectedEquipment.verification_frequency }}
          </div>
          <div class="col-6" v-if="selectedEquipment.last_verification_at">
            Última: {{ formatDate(selectedEquipment.last_verification_at) }}
          </div>
        </div>
      </div>

      <!-- Template param fields (dynamic) -->
      <div v-if="templates.length > 0" class="flex flex-column gap-4">
        <div class="font-semibold text-md border-bottom-1 pb-2">Parâmetros</div>
        <div
          v-for="tpl in templates"
          :key="tpl.id"
          class="field"
        >
          <label :for="'param-' + tpl.id" class="font-medium block mb-2">
            {{ tpl.parameter_name }}
            <small v-if="tpl.parameter_unit" class="text-color-secondary"> ({{ tpl.parameter_unit }})</small>
            <small
              v-if="tpl.tolerance_min !== null || tpl.tolerance_max !== null"
              class="text-color-secondary ml-2"
            >
              — Tolerância: {{ tpl.tolerance_min ?? '—' }} ~ {{ tpl.tolerance_max ?? '—' }}
            </small>
          </label>
          <InputNumber
            :id="'param-' + tpl.id"
            :modelValue="parseFloat(params[tpl.id]) || null"
            @update:modelValue="(val) => onParamChange(tpl.id, val)"
            :minFractionDigits="0"
            :maxFractionDigits="6"
            :placeholder="'Valor' + (tpl.parameter_unit ? ' (' + tpl.parameter_unit + ')' : '')"
            class="w-full"
            :disabled="submitting"
          />
        </div>
      </div>
      <div v-else-if="formEquipmentId && !loadingTemplates" class="text-color-secondary text-sm p-3 surface-ground border-round">
        <i class="pi pi-info-circle mr-2"></i>
        Nenhum parâmetro configurado para a categoria deste equipamento.
      </div>
      <div v-else-if="loadingTemplates" class="flex align-items-center gap-2 text-color-secondary">
        <i class="pi pi-spinner pi-spin"></i>
        <span>Carregando parâmetros...</span>
      </div>

      <!-- Notes -->
      <div class="field">
        <label for="notes" class="font-medium block mb-2">Observações</label>
        <Textarea
          id="notes"
          v-model="notes"
          rows="3"
          class="w-full"
          placeholder="Observações opcionais..."
          :disabled="submitting"
          :maxlength="2000"
        />
      </div>
    </div>

    <template #footer>
      <Button
        label="Cancelar"
        severity="secondary"
        text
        @click="$emit('update:visible', false)"
        :disabled="submitting"
      />
      <Button
        label="Salvar"
        icon="pi pi-check"
        severity="success"
        @click="submit"
        :loading="submitting"
        :disabled="!canSubmit"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { verificationService } from '../services/VerificationService'
import { useVerificationStore } from '../store/VerificationStore'
import type { VerificationTemplate, Verification } from '../types/verification'
import type { PendingEquipment } from '../types/verification'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'

const props = defineProps<{
  visible: boolean
  equipmentId: string | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  saved: [verification: Verification]
}>()

const toast = useToast()
const store = useVerificationStore()

const formEquipmentId = ref<string | null>(props.equipmentId)
const notes = ref('')
const params = ref<Record<string, string>>({})
const templates = ref<VerificationTemplate[]>([])
const selectedEquipment = ref<PendingEquipment | null>(null)
const equipmentOptions = ref<Array<{ label: string; value: string }>>([])
const submitting = ref(false)
const loadingTemplates = ref(false)

const frequencyLabels: Record<string, string> = {
  daily: 'Diária',
  weekly: 'Semanal',
  shift: 'Turno',
}

const canSubmit = computed(() => {
  return formEquipmentId.value !== null && templates.value.length > 0 && !submitting.value
})

// Watch equipmentId prop changes (when coming from EquipmentDetailPage)
watch(() => props.equipmentId, (newId) => {
  if (newId && newId !== formEquipmentId.value) {
    formEquipmentId.value = newId
    loadTemplates()
  }
})

// Watch dialog visibility — reset form when opening
watch(() => props.visible, (isVisible) => {
  if (isVisible) {
    resetForm()
    if (props.equipmentId) {
      formEquipmentId.value = props.equipmentId
      loadTemplates()
    } else {
      loadEquipmentOptions()
    }
  }
})

function resetForm() {
  notes.value = ''
  params.value = {}
  templates.value = []
  selectedEquipment.value = null
  submitting.value = false
  loadingTemplates.value = false
}

function formatDate(dateStr: string): string {
  const d = new Date(dateStr)
  return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

async function loadEquipmentOptions() {
  if (store.pendingEquipment.length > 0) {
    equipmentOptions.value = store.pendingEquipment.map(eq => ({
      label: `${eq.name} (${eq.patrimony_id || eq.serial_number || eq.id})`,
      value: eq.id,
    }))
  }
}

async function onEquipmentChange() {
  selectedEquipment.value = null
  templates.value = []
  params.value = {}
  if (formEquipmentId.value) {
    const found = store.pendingEquipment.find(eq => eq.id === formEquipmentId.value)
    if (found) {
      selectedEquipment.value = found
    }
    await loadTemplates()
  }
}

async function loadTemplates() {
  if (!formEquipmentId.value) return
  loadingTemplates.value = true
  try {
    templates.value = await verificationService.getTemplatesByEquipment(formEquipmentId.value)
    // CRITICAL: Initialize all param keys at once to preserve Vue 3 reactivity (Pitfall 1)
    const p: Record<string, string> = {}
    for (const tpl of templates.value) {
      p[tpl.id] = ''
    }
    params.value = p
  } finally {
    loadingTemplates.value = false
  }
}

function onParamChange(templateId: string, value: number | null) {
  params.value[templateId] = value !== null ? String(value) : ''
}

async function submit() {
  if (!formEquipmentId.value) return
  submitting.value = true
  try {
    const result = await store.create({
      equipment_id: formEquipmentId.value,
      verified_at: new Date().toISOString(),
      notes: notes.value || undefined,
      params: params.value,
    })
    toast.add({
      severity: 'success',
      summary: 'Aferição registrada',
      detail: 'Aferição salva com sucesso.',
      life: 3000,
    })
    // Show warning if tolerance exceeded
    if (result.is_outside_range) {
      toast.add({
        severity: 'warn',
        summary: 'Tolerância excedida',
        detail: 'Um ou mais parâmetros estão fora da tolerância permitida.',
        life: 6000,
      })
    }
    emit('saved', result)
    emit('update:visible', false)
  } catch (e: any) {
    const msg = e.response?.data?.message || 'Erro ao salvar aferição.'
    toast.add({
      severity: 'error',
      summary: 'Erro',
      detail: msg,
      life: 5000,
    })
  } finally {
    submitting.value = false
  }
}
</script>
