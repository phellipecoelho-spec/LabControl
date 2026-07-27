<template>
  <Card>
    <template #title>Equipamentos por Categoria</template>
    <template #content>
      <VChart
        v-if="data.length > 0"
        :option="chartOption"
        :theme="'dark'"
        autoresize
        style="height: 320px"
        @click="onChartClick"
      />
      <p v-else class="chart-empty">Nenhum equipamento cadastrado</p>
    </template>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { use } from 'echarts/core'
import { PieChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, LegendComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
import VChart from 'vue-echarts'
import Card from 'primevue/card'
import { useRouter } from 'vue-router'
import type { ChartCategoryItem } from '../types/dashboard'

use([PieChart, TitleComponent, TooltipComponent, LegendComponent, CanvasRenderer])

const props = defineProps<{
  data: ChartCategoryItem[]
}>()

const router = useRouter()

const palette = ['#6366f1', '#8b5cf6', '#f59e0b', '#22c55e', '#ef4444', '#3b82f6', '#06b6d4']

const chartOption = computed(() => ({
  tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
  legend: { bottom: '0', textStyle: { color: '#94a3b8' } },
  series: [
    {
      type: 'pie',
      radius: ['50%', '70%'],
      data: props.data,
      label: { color: '#e2e8f0' },
      emphasis: { itemStyle: { shadowBlur: 10, shadowColor: 'rgba(0,0,0,0.3)' } },
      color: palette,
    },
  ],
}))

function onChartClick(params: any) {
  if (params.name) {
    router.push({ name: 'equipments', query: { category: params.name } })
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
