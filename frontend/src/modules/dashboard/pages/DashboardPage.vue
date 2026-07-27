<template>
  <div class="dashboard-page">
    <div class="dashboard-toolbar">
      <DatePicker
        v-model="dateRange"
        selectionMode="range"
        dateFormat="dd/mm/yy"
        placeholder="Selecionar período"
        class="dashboard-toolbar__datepicker"
      />
      <Button
        icon="pi pi-refresh"
        label="Atualizar"
        severity="secondary"
        variant="text"
        :loading="store.loading"
        @click="refresh"
      />
    </div>

    <div v-if="store.loading" class="dashboard-loading">
      <ProgressSpinner />
      <p>Carregando dados do dashboard...</p>
    </div>

    <EmptyState v-else-if="!store.data" />

    <template v-else>
      <KpiRow :kpis="store.data.kpis" />
      <div class="dashboard-grid">
        <EquipmentsByCategoryChart
          :data="store.data.charts.equipments_by_category"
          class="dashboard-grid__chart"
        />
        <CalibrationsTimelineChart
          :data="store.data.charts.calibrations_timeline"
          class="dashboard-grid__chart"
        />
        <StockMovementsChart
          :data="store.data.charts.stock_movements"
          class="dashboard-grid__full"
        />
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DatePicker from 'primevue/datepicker'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'
import { useDashboardStore } from '../store/dashboardStore'
import KpiRow from '../components/KpiRow.vue'
import EquipmentsByCategoryChart from '../components/EquipmentsByCategoryChart.vue'
import CalibrationsTimelineChart from '../components/CalibrationsTimelineChart.vue'
import StockMovementsChart from '../components/StockMovementsChart.vue'
import EmptyState from '../components/EmptyState.vue'

const store = useDashboardStore()
const dateRange = ref<(Date | null)[] | null>(null)

function refresh() {
  const params: Record<string, string> = {}
  if (dateRange.value?.[0]) params.start_date = (dateRange.value[0] as Date).toISOString().split('T')[0]
  if (dateRange.value?.[1]) params.end_date = (dateRange.value[1] as Date).toISOString().split('T')[0]
  store.fetchData(params)
}

onMounted(() => refresh())
</script>

<style scoped>
.dashboard-toolbar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
}

.dashboard-toolbar__datepicker {
  max-width: 280px;
}

.dashboard-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  gap: 1rem;
  color: var(--p-text-muted-color);
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  padding: 1.5rem;
}

.dashboard-grid__chart {
  min-width: 0;
}

.dashboard-grid__full {
  grid-column: 1 / -1;
  min-width: 0;
}

@media (max-width: 1023px) {
  .dashboard-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 767px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}
</style>
