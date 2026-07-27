<template>
  <Card class="kpi-card" @click="navigate">
    <template #content>
      <div class="kpi-card__value">{{ value }}</div>
      <div class="kpi-card__label">{{ label }}</div>
      <div v-if="subtitle" class="kpi-card__subtitle">{{ subtitle }}</div>
    </template>
  </Card>
</template>

<script setup lang="ts">
import Card from 'primevue/card'
import { useRouter } from 'vue-router'

const props = defineProps<{
  value: number | string
  label: string
  subtitle?: string
  to?: { name: string; query?: Record<string, string> }
}>()

const router = useRouter()
function navigate() {
  if (props.to) router.push(props.to)
}
</script>

<style scoped>
.kpi-card {
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  flex: 1;
  min-width: 0;
}

.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.kpi-card__value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--p-primary-color);
}

.kpi-card__label {
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
  margin-top: 0.25rem;
}

.kpi-card__subtitle {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  opacity: 0.7;
}
</style>
