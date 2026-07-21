<template>
  <div class="inventory-detail-page">
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
          <h2 class="text-2xl font-bold m-0">{{ item?.name || 'Carregando...' }}</h2>
          <div class="flex align-items-center gap-2 mt-1">
            <Tag
              v-if="item"
              :value="`Saldo: ${item.current_balance} ${item.unit}`"
              severity="info"
              rounded
            />
            <Tag
              v-if="item?.is_critical"
              value="Estoque Crítico"
              severity="danger"
              rounded
            />
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="card">
      <Skeleton height="4rem" class="mb-3" />
      <Skeleton height="4rem" class="mb-3" />
      <Skeleton height="4rem" class="mb-3" />
    </div>

    <Tabs v-model:value="activeTab" v-else-if="item">
      <TabList>
        <Tab value="0">Dados do Item</Tab>
        <Tab value="1">Movimentações</Tab>
      </TabList>
      <TabPanels>
        <TabPanel value="0">
          <InventoryItemInfoTab :item="item" />
        </TabPanel>
        <TabPanel value="1">
          <InventoryMovementTab :itemId="item.id" @movement-created="onMovementCreated" />
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
import { useToast } from 'primevue/usetoast'
import InventoryItemInfoTab from '@/modules/inventory/components/InventoryItemInfoTab.vue'
import InventoryMovementTab from '@/modules/inventory/components/InventoryMovementTab.vue'
import { useInventoryItemStore } from '@/modules/inventory/store/InventoryItemStore'
import type { InventoryItem } from '@/modules/inventory/types/inventory'

const route = useRoute()
const router = useRouter()
const store = useInventoryItemStore()
const toast = useToast()

const item = ref<InventoryItem | null>(null)
const loading = ref(false)
const activeTab = ref('0')

onMounted(async () => {
  const id = route.params.id as string
  if (id) {
    loading.value = true
    try {
      item.value = await store.fetchById(id)
    } catch (e: any) {
      toast.add({
        severity: 'error',
        summary: 'Erro',
        detail: 'Erro ao carregar detalhes do item.',
        life: 5000,
      })
    } finally {
      loading.value = false
    }
  }
})

function goBack() {
  router.push('/inventory')
}

function onMovementCreated() {
  // Refresh item data after movement
  const id = route.params.id as string
  if (id) {
    store.fetchById(id).then(data => {
      item.value = data
    })
  }
}
</script>
