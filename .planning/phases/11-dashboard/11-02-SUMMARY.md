---
phase: 11-dashboard
plan: 02
subsystem: frontend
tags: [vue3, primevue, echarts, pinia, dashboard, kpi, typescript, vite]
requires:
  - phase: 11-dashboard
    plan: 01
    provides: DashboardService, DashboardController, GET /api/v1/dashboard
provides:
  - 6 interfaces TypeScript para DashboardData, KpiData, ChartData e tipos de gráfico
  - dashboardService com método fetch() para GET /api/v1/dashboard com filtro de período
  - dashboardStore (Pinia Composition API) com state loading/data/error e ação fetchData
  - KpiCard: Card PrimeVue clicável com value/label/subtitle e navegação router.push
  - KpiRow: fileira com 5 KPIs mapeados com labels PT-BR e rotas de drill-down
  - EquipmentsByCategoryChart: gráfico de rosca ECharts com drill-down por categoria
  - CalibrationsTimelineChart: barras empilhadas ECharts com drill-down por mês
  - StockMovementsChart: linha/área ECharts com drill-down por mês
  - EmptyState: onboarding com links para cadastro de equipamentos e calibrações
  - DashboardPage: toolbar (DatePicker + refresh), loading, KpiRow, grid responsivo 3 gráficos
affects: []

tech-stack:
  added: []
  patterns:
    - ECharts tree-shaking com use() individual por componente (PieChart, BarChart, LineChart + CanvasRenderer)
    - Componentes de gráfico com props tipadas, chartOption computed, tema escuro
    - Drill-down via router.push com named routes e query params
    - Grid responsivo CSS Grid com breakpoints 1024px e 768px
    - State management Pinia Composition API com fetch explícito (sem polling)

key-files:
  created:
    - frontend/src/modules/dashboard/types/dashboard.ts
    - frontend/src/modules/dashboard/services/dashboardService.ts
    - frontend/src/modules/dashboard/store/dashboardStore.ts
    - frontend/src/modules/dashboard/components/KpiCard.vue
    - frontend/src/modules/dashboard/components/KpiRow.vue
    - frontend/src/modules/dashboard/components/EquipmentsByCategoryChart.vue
    - frontend/src/modules/dashboard/components/CalibrationsTimelineChart.vue
    - frontend/src/modules/dashboard/components/StockMovementsChart.vue
    - frontend/src/modules/dashboard/components/EmptyState.vue
    - frontend/src/modules/dashboard/pages/DashboardPage.vue (replaced placeholder)
  modified:
    - frontend/src/modules/dashboard/pages/DashboardPage.vue (full rewrite)

key-decisions:
  - "ECharts tree-shaking com use() individual por componente (não plugin global) — apenas 3 componentes consomem ECharts, evita bundle desnecessário"
  - "KpiRow usa KpiItem interface tipada com type alias RouteQuery = Record<string, string> para compatibilidade com objetos de query parcial"
  - "Grid responsivo: 3 colunas >=1024px, 2 colunas 768-1023px, 1 coluna <768px — gráfico de linha ocupa grid-column: 1 / -1 por ser série temporal longa"
  - "DashboardPage usa onMounted para fetch inicial e refresh manual (sem polling automático) conforme D-15"
  - "DatePicker com selectionMode='range' e dateFormat='dd/mm/yy' para filtro de período"

requirements-completed: [DASH-01, DASH-02]

coverage:
  - id: D1
    description: Dashboard types, service and Pinia store — 6 interfaces, dashboardService.fetch(), dashboardStore com loading/data/error/fetchData
    requirement: DASH-01
    verification:
      - kind: unit
        ref: "npx vue-tsc --noEmit --strict (sem erros nos novos arquivos)"
        status: pass
      - kind: other
        ref: "npm run build (Vite build bem-sucedido)"
        status: pass
    human_judgment: false
  - id: D2
    description: KpiCard clicável com navegação router.push e KpiRow com 5 KPIs mapeados com labels PT-BR e rotas de drill-down
    requirement: DASH-01
    verification:
      - kind: unit
        ref: "npx vue-tsc --noEmit --strict (sem erros nos novos arquivos)"
        status: pass
    human_judgment: true
    rationale: "Comportamento visual (hover, clique, navegação) requer verificação humana em browser"
  - id: D3
    description: EmptyState com mensagem de onboarding e links para cadastro de equipamentos e calibrações
    requirement: DASH-02
    verification:
      - kind: unit
        ref: "npx vue-tsc --noEmit --strict (sem erros nos novos arquivos)"
        status: pass
    human_judgment: true
    rationale: "Renderização condicional e links de navegação requerem verificação visual"
  - id: D4
    description: 3 componentes ECharts (rosca, barras empilhadas, linha/área) com tree-shaking, tema escuro e drill-down click handlers
    requirement: DASH-02
    verification:
      - kind: unit
        ref: "npx vue-tsc --noEmit --strict (sem erros nos novos arquivos)"
        status: pass
      - kind: other
        ref: "npm run build (Vite build bem-sucedido, DashboardPage chunk gerada)"
        status: pass
    human_judgment: true
    rationale: "Renderização de gráficos ECharts com tema escuro, drill-down e responsividade requerem verificação visual em browser"
  - id: D5
    description: DashboardPage completa com toolbar (DatePicker + refresh), loading spinner, empty state, KpiRow, grid responsivo 3 gráficos
    requirement: DASH-02
    verification:
      - kind: unit
        ref: "npx vue-tsc --noEmit --strict (sem erros nos novos arquivos)"
        status: pass
      - kind: other
        ref: "npm run build (Vite build bem-sucedido)"
        status: pass
      - kind: other
        ref: "Placeholder 'LabControl' não mais presente no componente"
        status: pass
    human_judgment: true
    rationale: "Layout completo, responsividade, DatePicker, loading e empty state requerem verificação visual em browser"

duration: 5 min
completed: 2026-07-27
status: complete
---

# Phase 11: Dashboard — Plan 02 — Frontend Summary

**Módulo frontend do Dashboard: tipos TypeScript, serviço Axios, store Pinia, 6 componentes (KpiCard, KpiRow, 3 gráficos ECharts, EmptyState) e DashboardPage substituindo placeholder**

## Performance

- **Duration:** 5 min
- **Started:** 2026-07-27T16:17:54Z
- **Completed:** 2026-07-27T16:23:09Z
- **Tasks:** 3
- **Files modified:** 10

## Accomplishments

- **Types e Data Layer:** 6 interfaces TypeScript (DashboardData, KpiData, ChartData, ChartCategoryItem, ChartTimelineItem, ChartMovementItem), dashboardService com fetch() tipado, dashboardStore Pinia (loading/data/error/fetchData)
- **KPI Components:** KpiCard (Card PrimeVue clicável com hover effect), KpiRow (5 KPIs mapeados com labels PT-BR e rotas de drill-down para equipments, calibrations, loans, verifications, maintenance)
- **3 ECharts Charts com Tree-shaking:** EquipmentsByCategoryChart (rosca), CalibrationsTimelineChart (barras empilhadas), StockMovementsChart (linha/área) — todos com tema escuro, drill-down click handlers e registro individual de componentes via use()
- **EmptyState:** Mensagem de onboarding com links para cadastrar equipamentos e ver calibrações
- **DashboardPage:** Substitui o placeholder com toolbar (DatePicker range + botão Atualizar), ProgressSpinner loading, EmptyState condicional, KpiRow e grid responsivo de 3 gráficos (3 colunas ≥1024px, 2 colunas 768-1023px, 1 coluna <768px)
- **Build Vite:** 7.51s, sem erros de compilação TypeScript

## Task Commits

Each task was committed atomically:

1. **Task 1: Criar tipos, serviço e store do Dashboard** - `619ff65` (feat)
2. **Task 2: Criar KpiCard, KpiRow e EmptyState** - `b3bbf20` (feat)
3. **Task 3: Criar componentes ECharts e integrar DashboardPage** - `e180bb6` (feat)

## Files Created/Modified

- `frontend/src/modules/dashboard/types/dashboard.ts` - 6 interfaces TypeScript para dados do dashboard
- `frontend/src/modules/dashboard/services/dashboardService.ts` - Serviço Axios com fetch() para GET /api/v1/dashboard
- `frontend/src/modules/dashboard/store/dashboardStore.ts` - Pinia store (Composition API) com loading/data/error/fetchData
- `frontend/src/modules/dashboard/components/KpiCard.vue` - Card PrimeVue clicável com hover effect e navegação
- `frontend/src/modules/dashboard/components/KpiRow.vue` - Fileira horizontal com 5 KPIs mapeados
- `frontend/src/modules/dashboard/components/EquipmentsByCategoryChart.vue` - Gráfico de rosca ECharts com drill-down
- `frontend/src/modules/dashboard/components/CalibrationsTimelineChart.vue` - Barras empilhadas ECharts com drill-down
- `frontend/src/modules/dashboard/components/StockMovementsChart.vue` - Linha/área ECharts com drill-down
- `frontend/src/modules/dashboard/components/EmptyState.vue` - Estado vazio com onboarding e links de ação
- `frontend/src/modules/dashboard/pages/DashboardPage.vue` - Página completa do dashboard (placeholder substituído)

## Decisions Made

- **ECharts tree-shaking com use() individual por componente** — Apenas 3 componentes consomem ECharts, registro individual evita bundle desnecessário em páginas que não têm gráficos, use() é idempotente
- **KpiRow com type alias RouteQuery = Record<string, string>** — Para compatibilidade com objetos de query parcial (objetos com propriedades opcionais não podem ser atribuídos diretamente a Record<string, string>)
- **Grid responsivo 3 → 2 → 1 coluna** — Seguindo D-01, breakpoints em 1024px e 768px via CSS media queries
- **Gráfico de linha em grid-column: 1 / -1** — StockMovementsChart ocupa largura total por ser série temporal longa (D-03)
- **Fetch manual, sem polling** — Botão "Atualizar" com refresh explícito, sem polling automático (D-15)

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- **KpiRow type compatibility:** TypeScript rejeitou `Record<string, string>` para query objects com propriedades ausentes. Resolvido com type alias `RouteQuery = Record<string, string>` e cast explícito `as RouteQuery` nos objetos de query.
- **vue-tsc CLI no Windows:** O binário `node_modules/.bin/vue-tsc` é um shell script Unix. Resolvido usando `npx vue-tsc` diretamente.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Frontend do Dashboard completo: 10 arquivos criados, compilação TypeScript sem erros, build Vite bem-sucedido
- Pronto para verificação visual em browser (navegar para `/`, testar KPIs clicáveis, gráficos, DatePicker, responsividade)

## Self-Check: PASSED

---
*Phase: 11-dashboard*
*Completed: 2026-07-27*
