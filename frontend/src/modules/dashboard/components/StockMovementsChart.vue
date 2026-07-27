<template>
  <Card>
    <template #title>Movimentações de Estoque por Mês</template>
    <template #content>
      <VChart
        v-if="data.length > 0"
        :option="chartOption"
        :theme="'dark'"
        autoresize
        style="height: 320px"
        @click="onChartClick"
      />
      <p v-else class="chart-empty">Nenhuma movimentação registrada</p>
    </template>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { use } from 'echarts/core'
import { LineChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, LegendComponent, GridComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
import VChart from 'vue-echarts'
import Card from 'primevue/card'
import { useRouter } from 'vue-router'
import type { ChartMovementItem } from '../types/dashboard'

use([LineChart, TitleComponent, TooltipComponent, LegendComponent, GridComponent, CanvasRenderer])

const props = defineProps<{
  data: ChartMovementItem[]
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
      name: 'Entradas',
      type: 'line',
      areaStyle: { opacity: 0.3 },
      data: props.data.map((d) => d.incoming),
      itemStyle: { color: '#22c55e' },
      smooth: true,
    },
    {
      name: 'Saídas',
      type: 'line',
      areaStyle: { opacity: 0.3 },
      data: props.data.map((d) => d.outgoing),
      itemStyle: { color: '#ef4444' },
      smooth: true,
    },
  ],
}))

function onChartClick(params: any) {
  if (params.name) {
    router.push({ name: 'movements.index', query: { month: params.name } })
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
