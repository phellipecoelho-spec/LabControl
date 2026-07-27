<template>
  <div class="kpi-row">
    <KpiCard
      v-for="kpi in kpiList"
      :key="kpi.key"
      :value="kpi.value"
      :label="kpi.label"
      :subtitle="kpi.subtitle"
      :to="kpi.to"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import KpiCard from './KpiCard.vue'
import type { KpiData } from '../types/dashboard'

const props = defineProps<{
  kpis: KpiData
}>()

type RouteQuery = Record<string, string>

interface KpiItem {
  key: string
  value: number
  label: string
  subtitle: string
  to: { name: string; query?: RouteQuery }
}

const kpiList = computed<KpiItem[]>(() => {
  const k = props.kpis
  return [
    { key: 'total_equipments', value: k.total_equipments, label: 'Equipamentos', subtitle: 'Total cadastrados', to: { name: 'equipments' } },
    { key: 'calibrations_due_soon', value: k.calibrations_due_soon, label: 'Calibrações a Vencer', subtitle: 'Próximos 30 dias', to: { name: 'calibrations.index', query: { status: 'completed', due_soon: '30' } as RouteQuery } },
    { key: 'active_loans', value: k.active_loans, label: 'Empréstimos Ativos', subtitle: 'Atualmente emprestados', to: { name: 'loans.index', query: { status: 'active' } as RouteQuery } },
    { key: 'pending_verifications_today', value: k.pending_verifications_today, label: 'Aferições Pendentes', subtitle: 'Hoje', to: { name: 'verifications.index', query: { pending: 'true' } as RouteQuery } },
    { key: 'open_maintenance_orders', value: k.open_maintenance_orders, label: 'Manutenções Abertas', subtitle: 'Em aberto/andamento', to: { name: 'maintenance.index', query: { status: 'open,in_progress' } as RouteQuery } },
  ]
})
</script>

<style scoped>
.kpi-row {
  display: flex;
  gap: 1rem;
  padding: 1.5rem 1.5rem 0;
}
</style>
