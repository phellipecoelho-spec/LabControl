<template>
  <div class="inventory-form-page">
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
          <h1 class="text-2xl font-bold m-0">{{ isEditing ? `Editando: ${formData.name}` : 'Novo Item' }}</h1>
          <p class="text-600 text-sm mt-1">Preencha os dados do item de estoque</p>
        </div>
      </div>
    </div>

    <form @submit.prevent="handleSubmit">
      <Tabs v-model:value="activeTab">
        <TabList>
          <Tab value="0">Principal</Tab>
          <Tab value="1">Armazenamento</Tab>
        </TabList>
        <TabPanels>
          <TabPanel value="0">
            <div class="card">
              <div class="grid">
                <div class="col-12 md:col-6">
                  <div class="field mb-3">
                    <label for="name" class="block text-900 font-medium mb-2">
                      Nome <span class="text-red-500">*</span>
                    </label>
                    <InputText
                      id="name"
                      v-model="formData.name"
                      placeholder="Ex: Luvas de Procedimento"
                      :class="{ 'p-invalid': errors.name }"
                      :disabled="saving"
                    />
                    <small v-if="errors.name" class="p-error">Nome é obrigatório</small>
                  </div>
                </div>
                <div class="col-12 md:col-6">
                  <div class="field mb-3">
                    <label for="code" class="block text-900 font-medium mb-2">
                      Código
                    </label>
                    <InputText
                      id="code"
                      v-model="formData.code"
                      placeholder="Ex: LV-001"
                      :disabled="saving"
                    />
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
                      :options="store.categories"
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
                    <label for="supplier_id" class="block text-900 font-medium mb-2">
                      Fornecedor
                    </label>
                    <Select
                      id="supplier_id"
                      v-model="formData.supplier_id"
                      :options="store.suppliers"
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
                    <label for="unit" class="block text-900 font-medium mb-2">
                      Unidade <span class="text-red-500">*</span>
                    </label>
                    <Select
                      id="unit"
                      v-model="formData.unit"
                      :options="unitOptions"
                      optionLabel="label"
                      optionValue="value"
                      placeholder="Selecione a unidade"
                      :class="{ 'p-invalid': errors.unit }"
                      :disabled="saving"
                      class="w-full"
                    />
                    <small v-if="errors.unit" class="p-error">Unidade é obrigatória</small>
                  </div>
                </div>
                <div class="col-12 md:col-6">
                  <div class="field mb-3">
                    <label for="min_stock" class="block text-900 font-medium mb-2">
                      Estoque Mínimo <span class="text-red-500">*</span>
                    </label>
                    <InputNumber
                      id="min_stock"
                      v-model="formData.min_stock"
                      placeholder="Quantidade mínima"
                      :class="{ 'p-invalid': errors.min_stock }"
                      :disabled="saving"
                      class="w-full"
                      :min="0"
                    />
                    <small v-if="errors.min_stock" class="p-error">Estoque mínimo é obrigatório</small>
                  </div>
                </div>
                <div v-if="!isEditing" class="col-12 md:col-6">
                  <div class="field mb-3">
                    <label for="initial_quantity" class="block text-900 font-medium mb-2">
                      Quantidade Inicial
                    </label>
                    <InputNumber
                      id="initial_quantity"
                      v-model="formData.initial_quantity"
                      placeholder="Quantidade inicial em estoque"
                      :disabled="saving"
                      class="w-full"
                      :min="0"
                    />
                  </div>
                </div>
              </div>
            </div>
          </TabPanel>
          <TabPanel value="1">
            <div class="card">
              <div class="grid">
                <div class="col-12 md:col-6">
                  <div class="field mb-3">
                    <label for="batch_lot" class="block text-900 font-medium mb-2">
                      Lote
                    </label>
                    <InputText
                      id="batch_lot"
                      v-model="formData.batch_lot"
                      placeholder="Número do lote"
                      :disabled="saving"
                    />
                  </div>
                </div>
                <div class="col-12 md:col-6">
                  <div class="field mb-3">
                    <label for="expiry_date" class="block text-900 font-medium mb-2">
                      Data de Validade
                    </label>
                    <DatePicker
                      id="expiry_date"
                      v-model="formData.expiry_date"
                      dateFormat="dd/mm/yy"
                      showIcon
                      iconDisplay="input"
                      :disabled="saving"
                      class="w-full"
                    />
                  </div>
                </div>
                <div class="col-12 md:col-6">
                  <div class="field mb-3">
                    <label for="physical_location" class="block text-900 font-medium mb-2">
                      Localização Física
                    </label>
                    <InputText
                      id="physical_location"
                      v-model="formData.physical_location"
                      placeholder="Ex: Prateleira A3 - Sala de Estoque"
                      :disabled="saving"
                    />
                  </div>
                </div>
                <div class="col-12">
                  <div class="field mb-3">
                    <label for="description" class="block text-900 font-medium mb-2">
                      Descrição
                    </label>
                    <Textarea
                      id="description"
                      v-model="formData.description"
                      rows="4"
                      placeholder="Descreva o item..."
                      :disabled="saving"
                      class="w-full"
                    />
                  </div>
                </div>
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
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import { useInventoryItemStore } from '@/modules/inventory/store/InventoryItemStore'
import { INVENTORY_UNITS } from '@/modules/inventory/types/inventory'
import type { InventoryItemFormData } from '@/modules/inventory/types/inventory'

const route = useRoute()
const router = useRouter()
const store = useInventoryItemStore()
const toast = useToast()

const activeTab = ref('0')
const saving = ref(false)

const formData = reactive<Omit<InventoryItemFormData, 'expiry_date'> & {
  expiry_date: Date | null
}>({
  name: '',
  code: '',
  description: '',
  category_id: '',
  supplier_id: '',
  unit: '',
  min_stock: 0,
  batch_lot: '',
  expiry_date: null,
  physical_location: '',
  initial_quantity: 0,
})

const errors = reactive<Record<string, boolean>>({
  name: false,
  category_id: false,
  unit: false,
  min_stock: false,
})

const unitOptions = [...INVENTORY_UNITS]

const isEditing = computed(() => {
  return !!route.params.id
})

const canSave = computed(() => {
  return (
    formData.name &&
    formData.category_id &&
    formData.unit &&
    (formData.min_stock > 0 || formData.min_stock === 0) &&
    !saving.value
  )
})

onMounted(async () => {
  await Promise.all([
    store.fetchCategories(),
    store.fetchSuppliers(),
  ])

  if (isEditing.value) {
    const id = route.params.id as string
    try {
      const item = await store.fetchById(id)
      Object.assign(formData, {
        name: item.name,
        code: item.code || '',
        description: item.description || '',
        category_id: item.category?.id || '',
        supplier_id: item.supplier?.id || '',
        unit: item.unit,
        min_stock: item.min_stock,
        batch_lot: item.batch_lot || '',
        expiry_date: item.expiry_date ? new Date(item.expiry_date) : null,
        physical_location: item.physical_location || '',
        initial_quantity: 0,
      })
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Erro',
        detail: 'Erro ao carregar item de estoque',
        life: 5000,
      })
    }
  }
})

function validateForm(): boolean {
  errors.name = !formData.name
  errors.category_id = !formData.category_id
  errors.unit = !formData.unit
  errors.min_stock = formData.min_stock === null || formData.min_stock === undefined

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
      expiry_date: formData.expiry_date
        ? new Date(formData.expiry_date).toISOString().split('T')[0]
        : null,
    }

    // Only include initial_quantity in create mode
    if (isEditing.value) {
      delete submitData.initial_quantity
    }

    if (isEditing.value) {
      await store.update(route.params.id as string, submitData)
      toast.add({
        severity: 'success',
        summary: 'Item atualizado',
        detail: 'Os dados foram salvos com sucesso.',
        life: 3000,
      })
    } else {
      await store.create(submitData)
      toast.add({
        severity: 'success',
        summary: 'Item criado',
        detail: 'O item foi cadastrado com sucesso.',
        life: 3000,
      })
    }
    router.push('/inventory')
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
  router.push('/inventory')
}
</script>
