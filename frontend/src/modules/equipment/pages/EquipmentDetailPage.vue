<template>
  <div class="equipment-detail-page">
    <div class="flex align-items-center justify-content-between mb-4">
      <div class="flex align-items-center gap-3">
        <Button 
          icon="pi pi-arrow-left" 
          text 
          rounded 
          severity="secondary"
          @click="goBack"
        />
        <div>
          <h2 class="text-2xl font-bold m-0">{{ equipment?.name || 'Carregando...' }}</h2>
          <Tag 
            v-if="equipment" 
            :value="getStatusLabel(equipment.status)" 
            :severity="getSeverity(equipment.status)" 
            rounded 
            class="ml-2"
          />
        </div>
      </div>
    </div>

    <div v-if="loading" class="card">
      <Skeleton height="4rem" class="mb-3" />
      <Skeleton height="4rem" class="mb-3" />
      <Skeleton height="4rem" class="mb-3" />
    </div>

    <Tabs v-model:value="activeTab" v-else-if="equipment">
      <TabList>
        <Tab value="0">Principal</Tab>
        <Tab value="1">Localização</Tab>
        <Tab value="2">Técnica</Tab>
        <Tab value="3">Arquivos</Tab>
        <Tab value="4">Logs</Tab>
      </TabList>
      <TabPanels>
        <TabPanel value="0">
          <EquipmentInfoTab :equipment="equipment" />
        </TabPanel>
        <TabPanel value="1">
          <EquipmentLocationTab :equipment="equipment" />
        </TabPanel>
        <TabPanel value="2">
          <EquipmentTechnicalTab :equipment="equipment" />
        </TabPanel>
        <TabPanel value="3">
          <div class="card">
            <div class="text-center py-5">
              <i class="pi pi-images text-5xl text-400 mb-3"></i>
              <p class="text-600">Fotos do equipamento serão disponibilizadas em breve.</p>
            </div>
          </div>
        </TabPanel>
        <TabPanel value="4">
          <div class="card">
            <div class="text-center py-5">
              <i class="pi pi-history text-5xl text-400 mb-3"></i>
              <p class="text-600">Histórico de alterações será disponibilizado em breve.</p>
            </div>
          </div>
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Skeleton from 'primevue/skeleton'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import EquipmentInfoTab from '@/modules/equipment/components/EquipmentInfoTab.vue'
import EquipmentLocationTab from '@/modules/equipment/components/EquipmentLocationTab.vue'
import EquipmentTechnicalTab from '@/modules/equipment/components/EquipmentTechnicalTab.vue'
import { useEquipmentStore } from '@/modules/equipment/store/EquipmentStore'
import type { Equipment } from '@/modules/equipment/types/equipment'

const route = useRoute()
const router = useRouter()
const equipmentStore = useEquipmentStore()

const equipment = ref<Equipment | null>(null)
const loading = ref(false)
const activeTab = ref('0')

onMounted(async () => {
  const id = route.params.id as string
  if (id) {
    loading.value = true
    try {
      equipment.value = await equipmentStore.fetchById(id)
    } finally {
      loading.value = false
    }
  }
})

function goBack() {
  router.push('/equipments')
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    active: 'Ativo',
    inactive: 'Inativo',
    maintenance: 'Manutenção',
    retired: 'Baixado',
  }
  return labels[status] || status
}

function getSeverity(status: string): string {
  const severities: Record<string, string> = {
    active: 'success',
    inactive: 'danger',
    maintenance: 'warning',
    retired: 'info',
  }
  return severities[status] || 'info'
}
</script>