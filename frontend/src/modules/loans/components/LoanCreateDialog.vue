<template>
  <Dialog
    :visible="visible"
    @update:visible="$emit('update:visible', $event)"
    header="Novo Empréstimo"
    :style="{ width: '600px' }"
    :modal="true"
    class="p-fluid"
  >
    <div class="grid">
      <div class="col-12">
        <div class="field mb-4">
          <label for="borrower" class="font-medium mb-2 block">
            Tomador <span class="text-red-500">*</span>
          </label>
          <Select
            v-model="form.borrower_id"
            :options="userOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Selecione o tomador..."
            :filter="true"
            filterPlaceholder="Buscar por nome..."
            :class="{ 'p-invalid': submitted && !form.borrower_id }"
          />
          <small v-if="submitted && !form.borrower_id" class="p-error">
            Selecione um tomador.
          </small>
        </div>
      </div>

      <div class="col-12">
        <div class="field mb-4">
          <label for="equipment" class="font-medium mb-2 block">
            Equipamentos <span class="text-red-500">*</span>
          </label>
          <MultiSelect
            v-model="form.equipment_ids"
            :options="equipmentOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Selecione os equipamentos..."
            :filter="true"
            filterPlaceholder="Buscar por nome..."
            :maxSelectedLabels="3"
            selectedItemsLabel="{0} equipamentos selecionados"
            :class="{ 'p-invalid': submitted && form.equipment_ids.length === 0 }"
          />
          <small v-if="submitted && form.equipment_ids.length === 0" class="p-error">
            Selecione ao menos um equipamento.
          </small>
        </div>
      </div>

      <div class="col-12 md:col-6">
        <div class="field mb-4">
          <label for="borrowed_at" class="font-medium mb-2 block">
            Data de Retirada <span class="text-red-500">*</span>
          </label>
          <DatePicker
            v-model="form.borrowed_at"
            dateFormat="dd/mm/yy"
            placeholder="Selecione a data..."
            showIcon
            :class="{ 'p-invalid': submitted && !form.borrowed_at }"
          />
          <small v-if="submitted && !form.borrowed_at" class="p-error">
            Informe a data de retirada.
          </small>
        </div>
      </div>

      <div class="col-12 md:col-6">
        <div class="field mb-4">
          <label for="expected_return_at" class="font-medium mb-2 block">
            Previsão de Devolução <span class="text-red-500">*</span>
          </label>
          <DatePicker
            v-model="form.expected_return_at"
            dateFormat="dd/mm/yy"
            placeholder="Selecione a data..."
            showIcon
            :class="{ 'p-invalid': submitted && !form.expected_return_at }"
          />
          <small v-if="submitted && !form.expected_return_at" class="p-error">
            Informe a previsão de devolução.
          </small>
        </div>
      </div>

      <div class="col-12">
        <div class="field mb-4">
          <label for="reason" class="font-medium mb-2 block">Motivo</label>
          <Textarea
            v-model="form.reason"
            placeholder="Motivo do empréstimo (opcional)"
            :autoResize="true"
            rows="2"
          />
        </div>
      </div>

      <div class="col-12 md:col-6">
        <div class="field mb-4">
          <label for="destination" class="font-medium mb-2 block">Destino / Setor</label>
          <InputText
            v-model="form.destination"
            placeholder="Ex: Laboratório de Análises"
          />
        </div>
      </div>

      <div class="col-12 md:col-6">
        <div class="field mb-4">
          <label for="contact" class="font-medium mb-2 block">Contato</label>
          <InputText
            v-model="form.contact"
            placeholder="Telefone ou e-mail do tomador"
          />
        </div>
      </div>

      <div class="col-12">
        <div class="field mb-4">
          <label for="approved_by" class="font-medium mb-2 block">Aprovador</label>
          <Select
            v-model="form.approved_by"
            :options="approverOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Selecione o aprovador (opcional)"
            :filter="true"
            filterPlaceholder="Buscar por nome..."
            :showClear="true"
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
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import { useLoanStore } from '../store/LoanStore'
import type { LoanFormData } from '../types/loan'

const props = defineProps<{
  visible: boolean
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  'saved': []
}>()

const store = useLoanStore()
const toast = useToast()

const saving = ref(false)
const submitted = ref(false)

const form = ref<LoanFormData>({
  borrower_id: '',
  equipment_ids: [],
  borrowed_at: '',
  expected_return_at: '',
  reason: '',
  destination: '',
  contact: '',
  notes: '',
  approved_by: '',
})

const userOptions = computed(() =>
  store.users.map(u => ({
    label: u.name,
    value: u.id,
  }))
)

const equipmentOptions = computed(() =>
  store.equipment.map(eq => ({
    label: `${eq.name} - ${(eq as any).patrimony_id || eq.id}`,
    value: eq.id,
  }))
)

const approverOptions = computed(() =>
  store.users
    .filter(u => {
      const roles = (u as any).roles || []
      return roles.some((r: any) =>
        ['admin', 'supervisor'].includes(r.slug)
      )
    })
    .map(u => ({
      label: u.name,
      value: u.id,
    }))
)

function resetForm() {
  form.value = {
    borrower_id: '',
    equipment_ids: [],
    borrowed_at: '',
    expected_return_at: '',
    reason: '',
    destination: '',
    contact: '',
    notes: '',
    approved_by: '',
  }
  submitted.value = false
}

async function handleSave() {
  submitted.value = true

  if (!form.value.borrower_id || form.value.equipment_ids.length === 0 || !form.value.borrowed_at || !form.value.expected_return_at) {
    return
  }

  saving.value = true
  try {
    const payload: LoanFormData = {
      ...form.value,
      borrowed_at: form.value.borrowed_at instanceof Date
        ? form.value.borrowed_at.toISOString().split('T')[0]
        : form.value.borrowed_at,
      expected_return_at: form.value.expected_return_at instanceof Date
        ? form.value.expected_return_at.toISOString().split('T')[0]
        : form.value.expected_return_at,
    }
    if (!payload.reason) delete payload.reason
    if (!payload.destination) delete payload.destination
    if (!payload.contact) delete payload.contact
    if (!payload.notes) delete payload.notes
    if (!payload.approved_by) delete payload.approved_by

    await store.create(payload)
    resetForm()
    emit('saved')
  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary: 'Erro ao criar empréstimo',
      detail: e.response?.data?.message || 'Ocorreu um erro ao processar a solicitação.',
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}
</script>
