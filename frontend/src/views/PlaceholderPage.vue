<template>
  <div class="placeholder-page flex flex-column align-items-center justify-content-center" style="min-height: 60vh;">
    <i :class="icon" style="font-size: 4rem; opacity: 0.3; margin-bottom: 1rem;" />
    <h2 class="text-xl font-semibold mb-2">{{ title }}</h2>
    <p class="text-sm text-500">Módulo em desenvolvimento</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const title = computed(() => (route.meta.title as string) || 'Módulo')
const icon = computed(() => {
  const icons: Record<string, string> = {
    'inventory.index': 'pi pi-warehouse',
    'movements.index': 'pi pi-arrow-right-arrow-left',
    'loans.index': 'pi pi-share-alt',
    'calibrations.index': 'pi pi-calendar-clock',
    'verifications.index': 'pi pi-check-circle',
    'maintenance.index': 'pi pi-wrench',
    'reports.index': 'pi pi-file-pdf',
  }
  const name = route.name as string
  return icons[name] || 'pi pi-cog'
})
</script>
