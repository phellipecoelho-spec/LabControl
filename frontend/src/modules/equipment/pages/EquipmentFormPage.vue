<template>
  <div class="equipment-form-page">
    <div class="page-header mb-4">
      <div class="flex align-items-center gap-3">
        <Button 
          icon="pi pi-arrow-left" 
          text 
          rounded 
          severity="secondary"
          @click="goBack"
        />
        <div>
          <h1 class="text-2xl font-bold m-0">{{ isEditing ? `Editar: ${equipment.name}` : 'Novo Equipamento' }}</h1>
          <p class="text-600 text-sm mt-1">Preencha os dados do equipamento</p>
        </div>
      </div>
    </div>

    <form @submit.prevent="handleSubmit">
      <Tabs v-model:value="activeTab">
        <TabList>
          <Tab value="0">Principal</Tab>
          <Tab value="1">Localização</Tab>
          <Tab value="2">Técnica</Tab>
        </TabList>
        <TabPanels>
          <TabPanel value="0">
            <div class="card">
              <div class="grid">
                <div class="col-12 md:col-8">
                  <div class="field mb-3">
                    <label for="name" class="block text-900 font-medium mb-2">
                      Nome <span class="text-red-500">*</span>
                    </label>
                    <InputText 
                      id="name"
                      v-model="formData.name"
                      placeholder="Ex: Microscópio Óptico"
                      :class="{ 'p-invalid': errors.name }"
                      :disabled="saving"
                    />
                    <small v-if="errors.name" class="p-error">Nome é obrigatório</small>
                  </div>
                </div>
                <div class="col-12 md:col-4">
                  <div class="field mb-3">
                    <label for="patrimony_id" class="block text-900 font-medium mb-2">
                      Patrimônio
                    </label>
                    <InputText 
                      id="patrimony_id"
                      v-model="formData.patrimony_id"
                      placeholder="Ex: 12345"
                      :disabled="saving"
                    />
                  </div>
                </div>
                <div class="col-12 md:col-6">
                  <div class="field mb-3">
                    <label for="serial_number" class="block text-900 font-medium mb-2">
                      Nº Série <span class="text-red-500">*</span>
                    </label>
                    <InputText 
                      id="serial_number"
                      v-model="formData.serial_number"
                      placeholder="Número de série do fabricante"
                      :class="{ 'p-invalid': errors.serial_number }"
                      :disabled="saving"
                    />
                    <small v-if="errors.serial_number" class="p-error">Número de série é obrigatório</small>
                  </div>
                </div>
                <div class="col-12 md:col-6">
                  <div class="field mb-3">
                    <label for="category_id" class="block text-900 font-medium mb-2">
                      Categoria <span class="text-red-500">*</span>
                    </label>
                    <Select 
                      id="category_id"
                      v-model="formData.category_id"
                      :options="equipmentStore.categories"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Selecione uma categoria"
                      :class="{ 'p-invalid': errors.category_id }"
                      :disabled="saving"
                      class="w-full"
                    />
                    <small v-if="errors.category_id" class="p-error">Categoria é obrigatória</small>
                  </div>
                </div>
                <div class="col-12 md:col-6">
                  <div class="field mb-3">
                    <label for="manufacturer_id" class="block text-900 font-medium mb-2">
                      Fabricante <span class="text-red-500">*</span>
                    </label>
                    <Select 
                      id="manufacturer_id"
                      v-model="formData.manufacturer_id"
                      :options="equipmentStore.manufacturers"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Selecione um fabricante"
                      :class="{ 'p-invalid': errors.manufacturer_id }"
                      :disabled="saving"
                      class="w-full"
                    />
                    <small v-if="errors.manufacturer_id" class="p-error">Fabricante é obrigatório</small>
                  </div>
                </div>
                <div class="col-12 md:col-6">
                  <div class="field mb-3">
                    <label for="supplier_id" class="block text-900 font-medium mb-2">
                      Fornecedor
                    </label>
                    <Select 
                      id="supplier_id"
                      v-model="formData.supplier_id"
                      :options="equipmentStore.suppliers"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Selecione um fornecedor"
                      :disabled="saving"
                      class="w-full"
                      clearable
                    />
                  </div>
                </div>
                <div class="col-12 md:col-6">
                  <div class="field mb-3">
                    <label for="status" class="block text-900 font-medium mb-2">
                      Status
                    </label>
                    <SelectButton 
                      id="status"
                      v-model="formData.status"
                      :options="statusOptions"
                      optionLabel="label"
                      optionValue="value"
                      :disabled="saving"
                    />
                  </div>
                </div>
              </div>
            </div>
          </TabPanel>
          <TabPanel value="1">
            <div class="card">
              <div class="grid">
                <div class="col-12 md:col-8">
                  <div class="field mb-3">
                    <label for="location" class="block text-900 font-medium mb-2">
                      Localização <span class="text-red-500">*</span>
                    </label>
                    <InputText 
                      id="location"
                      v-model="formData.location"
                      placeholder="Ex: Laboratório de Metrologia - Sala 2"
                      :class="{ 'p-invalid': errors.location }"
                      :disabled="saving"
                    />
                    <small v-if="errors.location" class="p-error">Localização é obrigatória</small>
                  </div>
                </div>
                <div class="col-12 md:col-4">
                  <div class="field mb-3">
                    <label for="acquisition_date" class="block text-900 font-medium mb-2">
                      Data de Aquisição
                    </label>
                    <DatePicker 
                      id="acquisition_date"
                      v-model="formData.acquisition_date"
                      dateFormat="dd/mm/yy"
                      showIcon
                      iconDisplay="input"
                      :disabled="saving"
                      class="w-full"
                    />
                  </div>
                </div>
                <div class="col-12 md:col-4">
                  <div class="field mb-3">
                    <label for="warranty_end" class="block text-900 font-medium mb-2">
                      Fim da Garantia
                    </label>
                    <DatePicker 
                      id="warranty_end"
                      v-model="formData.warranty_end"
                      dateFormat="dd/mm/yy"
                      showIcon
                      iconDisplay="input"
                      :disabled="saving"
                      class="w-full"
                    />
                  </div>
                </div>
              </div>
            </div>
          </TabPanel>
          <TabPanel value="2">
            <div class="card">
              <div class="field mb-3">
                <label for="description" class="block text-900 font-medium mb-2">
                  Descrição
                </label>
                <Textarea 
                  id="description"
                  v-model="formData.description"
                  rows="4"
                  placeholder="Descreva o equipamento..."
                  :disabled="saving"
                  class="w-full"
                />
              </div>
              <div class="field mb-3">
                <label for="technical_specs" class="block text-900 font-medium mb-2">
                  Especificações Técnicas
                </label>
                <Textarea 
                  id="technical_specs"
                  v-model="formData.technical_specs"
                  rows="6"
                  placeholder="Especificações técnicas detalhadas..."
                  :disabled="saving"
                  class="w-full"
                />
              </div>
              <div class="field mb-3">
                <label for="notes" class="block text-900 font-medium mb-2">
                  Observações
                </label>
                <Textarea 
                  id="notes"
                  v-model="formData.notes"
                  rows="3"
                  placeholder="Observações adicionais..."
                  :disabled="saving"
                  class="w-full"
                />
              </div>
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>

      <div class="flex justify-content-end gap-2 mt-4">
        <Button 
          label="Cancelar" 
          severity="secondary" 
          @click="goBack"
          :disabled="saving"
        />
        <Button 
          label="Salvar" 
          type="submit"
          :loading="saving"
          :disabled="!canSave"
        />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import SelectButton from 'primevue/selectbutton'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import { useEquipmentStore } from '@/modules/equipment/store/EquipmentStore'
import type { EquipmentFormData } from '@/modules/equipment/types/equipment'

const route = useRoute()
const router = useRouter()
const equipmentStore = useEquipmentStore()
const toast = useToast()

const activeTab = ref('0')
const saving = ref(false)
const equipment = ref<any>(null)

const formData = reactive<Omit<EquipmentFormData, 'acquisition_date' | 'warranty_end'> & {
  acquisition_date: Date | null
  warranty_end: Date | null
}>({
  name: '',
  patrimony_id: '',
  serial_number: '',
  category_id: '',
  manufacturer_id: '',
  supplier_id: '',
  location: '',
  acquisition_date: null,
  warranty_end: null,
  status: 'active',
  description: '',
  technical_specs: '',
  notes: '',
})

const errors = reactive<Record<string, boolean>>({
  name: false,
  serial_number: false,
  category_id: false,
  manufacturer_id: false,
  location: false,
})

const statusOptions = [
  { label: 'Ativo', value: 'active' },
  { label: 'Inativo', value: 'inactive' },
  { label: 'Manutenção', value: 'maintenance' },
  { label: 'Baixado', value: 'retired' },
]

const isEditing = computed(() => {
  return !!route.params.id
})

const canSave = computed(() => {
  return (
    formData.name &&
    formData.serial_number &&
    formData.category_id &&
    formData.manufacturer_id &&
    formData.location &&
    !saving.value
  )
})

onMounted(async () => {
  await Promise.all([
    equipmentStore.fetchCategories(),
    equipmentStore.fetchManufacturers(),
    equipmentStore.fetchSuppliers(),
  ])

  if (isEditing.value) {
    const id = route.params.id as string
    try {
      equipment.value = await equipmentStore.fetchById(id)
      Object.assign(formData, {
        name: equipment.value.name,
        patrimony_id: equipment.value.patrimony_id,
        serial_number: equipment.value.serial_number,
        category_id: equipment.value.category?.id,
        manufacturer_id: equipment.value.manufacturer?.id,
        supplier_id: equipment.value.supplier?.id,
        location: equipment.value.location,
        acquisition_date: equipment.value.acquisition_date ? new Date(equipment.value.acquisition_date) : null,
        warranty_end: equipment.value.warranty_end ? new Date(equipment.value.warranty_end) : null,
        status: equipment.value.status,
        description: equipment.value.description,
        technical_specs: equipment.value.technical_specs,
        notes: equipment.value.notes,
      })
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Erro',
        detail: 'Erro ao carregar equipamento',
        life: 5000,
      })
    }
  }
})

function validateForm(): boolean {
  errors.name = !formData.name
  errors.serial_number = !formData.serial_number
  errors.category_id = !formData.category_id
  errors.manufacturer_id = !formData.manufacturer_id
  errors.location = !formData.location

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
    const submitData: Record<string, any> = {
      ...formData,
      acquisition_date: formData.acquisition_date ? new Date(formData.acquisition_date).toISOString().split('T')[0] : null,
      warranty_end: formData.warranty_end ? new Date(formData.warranty_end).toISOString().split('T')[0] : null,
    }
    
    if (isEditing.value) {
      await equipmentStore.update(route.params.id as string, submitData)
      toast.add({
        severity: 'success',
        summary: 'Equipamento atualizado',
        detail: 'Os dados foram salvos com sucesso.',
        life: 3000,
      })
    } else {
      await equipmentStore.create(submitData)
      toast.add({
        severity: 'success',
        summary: 'Equipamento criado',
        detail: 'O equipamento foi cadastrado com sucesso.',
        life: 3000,
      })
    }
    router.push('/equipments')
  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary: 'Erro',
      detail: e.response?.data?.message || 'Ocorreu um erro ao salvar.',
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}

function goBack() {
  router.push('/equipments')
}
</script>