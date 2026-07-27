<template>
  <Card>
    <template #title>Calibrações — Próximos 6 Meses</template>
    <template #content>
      <VChart
        v-if="data.length > 0"
        :option="chartOption"
        :theme="'dark'"
        autoresize
        style="height: 320px"
        @click="onChartClick"
      />
      <p v-else class="chart-empty">Nenhuma calibração agendada</p>
    </template>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { use } from 'echarts/core'
import { BarChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, LegendComponent, GridComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
import VChart from 'vue-echarts'
import Card from 'primevue/card'
import { useRouter } from 'vue-router'
import type { ChartTimelineItem } from '../types/dashboard'

use([BarChart, TitleComponent, TooltipComponent, LegendComponent, GridComponent, CanvasRenderer])

const props = defineProps<{
  data: ChartTimelineItem[]
}>()

const router = useRouter()

const chartOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  legend: { bottom: '0', textStyle: { color: '#94a3b8' } },
  grid: { left: '3%', right: '4%', bottom: '15%', containLabel: true },
  xAxis: {
    type: 'category',
    data: props.data.map((d) => d.month),
    axisLabel: { color: '#94a3b8' },
  },
  yAxis: {
    type: 'value',
    axisLabel: { color: '#94a3b8' },
  },
  series: [
    {
      name: 'Agendadas',
      type: 'bar',
      stack: 'total',
      data: props.data.map((d) => d.scheduled),
      itemStyle: { color: '#6366f1' },
    },
    {
      name: 'Concluídas',
      type: 'bar',
      stack: 'total',
      data: props.data.map((d) => d.completed),
      itemStyle: { color: '#22c55e' },
    },
  ],
}))

function onChartClick(params: any) {
  if (params.name) {
    router.push({ name: 'calibrations.index', query: { month: params.name } })
  }
}
</script>

<style scoped>
.chart-empty {
  text-align: center;
  color: var(--p-text-muted-color, #94a3b8);
  padding: 4rem 0;
}
</style>
