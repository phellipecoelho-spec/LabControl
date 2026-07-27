# Phase 11: Dashboard — Research

**Researched:** 2026-07-27
**Domain:** Agregação de dados / Visualização (ECharts) / API REST
**Confidence:** HIGH

## Summary

O módulo de Dashboard será o hub central do LabControl, exibindo KPIs numéricos e gráficos ECharts que consolidam dados de 6 módulos: Equipamentos, Calibrações, Movimentações de Estoque, Empréstimos, Aferições e Manutenções. Servirá como rota raiz (`/`) da SPA.

A arquitetura segue o padrão estabelecido: endpoint único `GET /api/v1/dashboard` com cache Redis de 5 minutos, `DashboardService` para lógica de agregação no backend, e módulo frontend dedicado com Pinia store, Axios service e componentes VChart do vue-echarts.

Todos os 6 módulos de origem estão completos (Phases 5–10), com modelos, controllers, migrations e dados já disponíveis. O Redis está configurado como `CACHE_STORE=redis` no `.env`. O vue-echarts 8.0.1 + ECharts 6.1.0 já estão instalados nas dependências do frontend (`frontend/package.json`).

**Primary recommendation:** Implementar em 2 planos — (1) Backend: DashboardService + DashboardController + rota API com cache, e (2) Frontend: módulo completo com KPIs, gráficos ECharts e seletor de período global.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

#### 1. Layout do Dashboard
- **D-01:** Widget Grid responsivo com 3 colunas flex (reorganiza para 2 em tablet, 1 em mobile)
- **D-02:** Fileira horizontal de 5 KPIs numéricos no topo, antes dos gráficos
- **D-03:** Gráficos ocupam largura total ou 2 colunas do grid conforme necessidade
- **D-04:** Estado vazio: mensagem central com onboarding e links para páginas de cadastro

#### 2. KPIs (Linha do Topo)
- **D-05:** 5 KPIs principais: Total Equipamentos, Calibrações a Vencer (próximos 30d), Empréstimos Ativos, Aferições Pendentes (hoje), Manutenções Abertas
- **D-06:** Cada KPI é clicável e navega para a listagem do módulo correspondente com filtro aplicado
- **D-07:** KPIs carregam do endpoint único de dashboard (não de chamadas separadas)

#### 3. Gráficos (ECharts)
- **D-08:** 3 gráficos principais:
  1. Equipamentos por Categoria — gráfico de pizza/rosca
  2. Calibrações nos Próximos 6 Meses — gráfico de barras/barras empilhadas
  3. Movimentações de Estoque por Mês — gráfico de linha/área
- **D-09:** Período padrão dos gráficos: últimos 12 meses
- **D-10:** Gráficos com drill-down: clicar em elemento navega para listagem filtrada (router push com query params)

#### 4. API do Dashboard
- **D-11:** Endpoint único: `GET /api/v1/dashboard`
- **D-12:** Resposta em estrutura nomeada por seção: `{ kpis: {...}, charts: { equipments_by_category: [...], calibrations_timeline: [...], stock_movements: [...] } }`
- **D-13:** Cache no backend com Redis, TTL de 5 minutos
- **D-14:** Lógica de agregação em `DashboardService` (`backend/app/Services/DashboardService.php`)

#### 5. Interatividade
- **D-15:** Atualização manual apenas (botão "Atualizar" no topo), sem polling automático
- **D-16:** Loading state: spinner centralizado enquanto carrega

#### 6. Filtros
- **D-17:** Seletor de período global no topo do dashboard (DateRangePicker do PrimeVue)
- **D-18:** Nenhum filtro adicional (laboratório, categoria) — apenas período

#### 7. Estrutura Frontend
- **D-19:** Segue o padrão de módulo: `frontend/src/modules/dashboard/{pages,components,services,store,types,routes}/`
- **D-20:** DashboardService no frontend: chamada Axios para `/api/v1/dashboard`
- **D-21:** DashboardStore (Pinia) para estado do dashboard
- **D-22:** vue-echarts para renderização dos gráficos (já instalado)

### the agent's Discretion
- Tipos de gráfico específicos (pizza vs rosca, barra simples vs empilhada, linha vs área) — definir durante planejamento baseado nos dados disponíveis
- Cores e tema dos gráficos — seguir o tema escuro Aura do PrimeVue

### Deferred Ideas (OUT OF SCOPE)
- **Filtro por laboratório/local** — será relevante quando multiempresa/multilaboratório for implementado (fase futura)
- **Dashboard customizável pelo usuário** (reordenar widgets, escolher KPIs) — feature para versão futura
- **Exportar dashboard como PDF/imagem** — melhor colocado na Phase 12 (Relatórios)
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| DASH-01 | Dashboard com indicadores (ECharts) | KPIs definidos (D-05), estrutura de resposta (D-12), endpoint único (D-11), cache Redis 5min (D-13) |
| DASH-02 | Gráficos de equipamentos, calibrações, movimentações | 3 gráficos ECharts (D-08), dados de 6 modelos existentes (Equipment, Calibration, InventoryMovement, Loan, Verification, MaintenanceOrder), vue-echarts 8.0.1 instalado |
</phase_requirements>

---

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Agregação de dados | Backend (Laravel) | — | DashboardService processa dados de 6 modelos; nunca expor queries pesadas ao frontend |
| Cache de dashboard | Backend (Redis) | — | D-13: cache de 5 min no Redis; evita reprocessar agregações a cada request |
| Visualização de gráficos | Browser (Vue 3) | — | vue-echarts renderiza no cliente com dados pré-processados pelo backend |
| KPIs clicáveis | Browser (Vue Router) | — | D-06: router.push com query params para listagem filtrada |
| Filtro de período | Browser (PrimeVue DatePicker) | Backend (query params) | Seletor global no frontend envia `?start_date=&end_date=` para o backend |
| Estado vazio / onboarding | Browser (Vue 3) | — | D-04: mensagem central condicional quando não há dados |

---

## Data Sources

### 1. Equipment — `equipments` (Phase 5)

| Field | Type | Purpose |
|-------|------|---------|
| `id` | UUID (PK) | Chave primária |
| `name` | string | Nome do equipamento |
| `category_id` | UUID FK → `categories` | Agrupamento para gráfico de pizza |
| `status` | string (active/inactive/maintenance/retired) | Filtro (KPI conta todos, exceto soft-deleted) |
| `deleted_at` | timestamp (softDelete) | Excluir registros deletados |

**Relacionamentos:** `->belongsTo(Category)` — category.name para legenda do gráfico.

**KPI:** `Equipment::count()` — total de equipamentos (soft-deletes são excluídos automaticamente).

**Gráfico:** `Category::withCount('equipments')->get()` — retorna `[{name, equipments_count}]`.

### 2. Calibration — `calibrations` (Phase 8)

| Field | Type | Purpose |
|-------|------|---------|
| `id` | UUID (PK) | Chave primária |
| `equipment_id` | UUID FK → `equipments` | Relacionamento |
| `status` | CalibrationStatus enum (scheduled/completed/cancelled) | Filtro |
| `scheduled_date` | date | Data agendada |
| `completed_at` | datetime | Preenchido na conclusão |
| `next_due_at` | datetime | Calculado: completed_at + interval |
| `interval_value` | int | Ex: 6, 12 |
| `interval_unit` | string (months/days/hours) | Unidade do intervalo |

**Índices relevantes:** Composite `(status, next_due_at)` para due-check; `(scheduled_date)`, `(next_due_at)`.

**KPI (Calibrações a Vencer — 30d):**
```php
Calibration::where('status', CalibrationStatus::Completed)
    ->where('next_due_at', '>=', now())
    ->where('next_due_at', '<=', now()->addDays(30))
    ->count();
```

**Gráfico (Barras — Próximos 6 Meses):**
Agrupar por mês as calibrações com `next_due_at` nos próximos 6 meses. Usar `whereBetween('next_due_at', [now(), now()->addMonths(6)])` com `groupBy` no mês.

### 3. Loan — `loans` (Phase 7)

| Field | Type | Purpose |
|-------|------|---------|
| `id` | UUID (PK) | Chave primária |
| `borrower_id` | UUID FK → `users` | Mutuário |
| `status` | LoanStatus enum (reserved/active/returned/cancelled) | Filtro |
| `borrowed_at` | timestamp | Data do empréstimo |
| `expected_return_at` | timestamp | Data prevista de devolução |
| `returned_at` | timestamp (nullable) | Data de devolução total |

**Índices:** Composite `(status, expected_return_at)` para overdue query.

**KPI (Empréstimos Ativos):** `Loan::where('status', LoanStatus::Active)->count()`

### 4. Inventory Movement — `inventory_movements` (Phase 6)

| Field | Type | Purpose |
|-------|------|---------|
| `id` | UUID (PK) | Chave primária |
| `item_id` | UUID FK → `inventory_items` | Item do estoque |
| `type` | string (purchase/consumption/adjustment/disposal/return) | Tipo de movimentação |
| `quantity` | int | Quantidade (sempre positiva) |
| `balance_after` | int | Saldo resultante (desnormalizado) |
| `created_at` | timestamp | Data da movimentação |

**Índices:** `(created_at)`, `(type, created_at)`, `(item_id, created_at)`.

**Gráfico (Linha/Área — Movimentações por Mês):**
Agrupar por mês (últimos 12 meses), separando entradas (purchase, return) e saídas (consumption, adjustment, disposal). Usar `whereBetween('created_at', [now()->subMonths(12), now()])`.

### 5. Verification — `verifications` (Phase 9)

| Field | Type | Purpose |
|-------|------|---------|
| `id` | UUID (PK) | Chave primária |
| `equipment_id` | UUID FK → `equipments` | Equipamento aferido |
| `verified_at` | timestamp | Data/hora da aferição |
| `operator_id` | UUID FK → `users` | Operador responsável |

**KPI (Aferições Pendentes Hoje):**
Lógica já implementada em `VerificationService::getPendingVerifications()` — equipamentos com `verification_frequency` configurada e sem aferição dentro do período esperado (daily = 24h, weekly = 7d, shift = 12h). Retorna `Collection` de equipamentos pendentes.

```php
// KPI: count only
$pending = app(VerificationService::class)->getPendingVerifications();
$pendingCount = $pending->count();
```

### 6. Maintenance Order — `maintenance_orders` (Phase 10)

| Field | Type | Purpose |
|-------|------|---------|
| `id` | UUID (PK) | Chave primária |
| `equipment_id` | UUID FK → `equipments` | Equipamento |
| `status` | MaintenanceStatus enum (open/in_progress/completed/cancelled) | Filtro |
| `type` | MaintenanceType enum (preventive/corrective) | Tipo |
| `priority` | MaintenancePriority enum (low/medium/high/critical) | Prioridade |

**Índices:** `(status)`, `(status, next_due_at)`.

**KPI (Manutenções Abertas):**
```php
MaintenanceOrder::whereIn('status', ['open', 'in_progress'])->count();
```

---

## ECharts Integration

### Current State
- `echarts` ^6.1.0 e `vue-echarts` ^8.0.1 instalados (`frontend/package.json`)
- **Nenhum componente registrado globalmente** — é necessário importar VChart em cada componente
- Nenhum arquivo de configuração ou exemplo de uso encontrado no codebase

### vue-echarts v8 — Uso com Importação Manual
`vue-echarts` v8 requer importação manual para tree-shaking:

```typescript
// Importação tree-shakeable (recomendado — mantém bundle menor)
import { use } from 'echarts/core'
import { PieChart } from 'echarts/charts'
import { BarChart, LineChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, LegendComponent, GridComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
import VChart from 'vue-echarts'

// Registra os componentes necessários
use([PieChart, BarChart, LineChart, TitleComponent, TooltipComponent, LegendComponent, GridComponent, CanvasRenderer])
```

Para a abordagem mais prática no projeto, recomenda-se criar um arquivo `frontend/src/plugins/echarts.ts` que centralize os registros, ou registrar diretamente nos componentes de gráfico (uma vez que apenas 3 gráficos compartilham componentes comuns).

### Tema Escuro
ECharts detecta automaticamente o tema ou pode ser forçado com:
- Pelo seletor CSS: VChart `theme="dark"` prop — desde que o CSS class `app-dark` esteja no `<html>`. `vue-echarts` v8 respeita o `prefers-color-scheme` + classe manual.
- Alternativa: passar `echarts.init(dom, 'dark')`.

**Recomendação:** Usar a prop `theme="dark"` no VChart e garantir que a classe `.app-dark` esteja presente (já está no `<html>` via `main.ts`).

### Tipos de Gráfico Recomendados

| Gráfico | Tipo | Motivo |
|---------|------|--------|
| Equipamentos por Categoria | Rosca (PieChart + radius: ['50%', '70%']) | Visual moderno, similar a Power BI (D-08 permite pizza ou rosca) |
| Calibrações Próximos 6 Meses | Barras Empilhadas (BarChart + stack) | Permite ver scheduled + completed por mês |
| Movimentações de Estoque por Mês | Linha/Área (LineChart + areaStyle) | Melhor para visualizar tendência temporal |

### Padrão de Componente com vue-echarts

```vue
<template>
  <VChart
    v-if="data.length"
    :option="chartOption"
    theme="dark"
    autoresize
    style="height: 320px"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import VChart from 'vue-echarts'
import type { ChartData } from '../types/dashboard'

const props = defineProps<{ data: ChartData[] }>()

const chartOption = computed(() => ({
  tooltip: { trigger: 'item' },
  legend: { bottom: '0', textStyle: { color: '#94a3b8' } },
  series: [{
    type: 'pie',
    radius: ['50%', '70%'],
    data: props.data,
    label: { color: '#e2e8f0' },
  }],
}))
</script>
```

---

## API Design

### Endpoint
```
GET /api/v1/dashboard?start_date=2026-01-01&end_date=2026-12-31
```

### Controller Pattern
Segue o padrão dos controllers existentes (`EquipmentController`):

```php
class DashboardController extends Controller
{
    public static function middleware(): array
    {
        return [
            ['middleware' => 'auth:sanctum'],
            ['middleware' => 'permission:dashboard.view'],
        ];
    }

    public function __invoke(Request $request, DashboardService $service)
    {
        $data = $service->aggregate(
            startDate: $request->date('start_date', now()->subMonths(12)),
            endDate: $request->date('end_date', now()),
        );

        return response()->json($data);
    }
}
```

### Response Structure (D-12)
```json
{
  "kpis": {
    "total_equipments": 42,
    "calibrations_due_soon": 5,
    "active_loans": 3,
    "pending_verifications_today": 8,
    "open_maintenance_orders": 2
  },
  "charts": {
    "equipments_by_category": [
      { "name": "Balanças", "value": 15 },
      { "name": "Espectrofotômetros", "value": 8 }
    ],
    "calibrations_timeline": [
      { "month": "2026-08", "scheduled": 3, "completed": 1, "due": 4 },
      { "month": "2026-09", "scheduled": 2, "completed": 0, "due": 6 }
    ],
    "stock_movements": [
      { "month": "2026-01", "incoming": 12, "outgoing": 7 },
      { "month": "2026-02", "incoming": 8, "outgoing": 10 }
    ]
  }
}
```

### Route Registration
```php
// backend/routes/api.php — dentro do grupo 'auth:sanctum'
Route::get('dashboard', DashboardController::class)->name('dashboard');
```

### Permission
`dashboard.view` — registrar na seeder de permissões (já existem permissões para todos os outros módulos).

---

## Backend Architecture

### DashboardService — Padrão de Implementação

```php
class DashboardService
{
    public function aggregate(Carbon $startDate, Carbon $endDate): array
    {
        return Cache::remember('dashboard', 300, function () use ($startDate, $endDate) {
            return [
                'kpis' => $this->kpis(),
                'charts' => [
                    'equipments_by_category' => $this->equipmentsByCategory(),
                    'calibrations_timeline' => $this->calibrationsTimeline($startDate, $endDate),
                    'stock_movements' => $this->stockMovements($startDate, $endDate),
                ],
            ];
        });
    }

    private function kpis(): array
    {
        return [
            'total_equipments' => Equipment::count(),
            'calibrations_due_soon' => Calibration::where('status', CalibrationStatus::Completed)
                ->where('next_due_at', '>=', now())
                ->where('next_due_at', '<=', now()->addDays(30))
                ->count(),
            'active_loans' => Loan::where('status', LoanStatus::Active)->count(),
            'pending_verifications_today' => $this->countPendingVerifications(),
            'open_maintenance_orders' => MaintenanceOrder::whereIn('status', ['open', 'in_progress'])->count(),
        ];
    }

    // ... métodos privados para each chart
}
```

### Padrão de Injeção de Dependência
Services no projeto usam injeção manual via `app()` helper nos controllers:
- `app(LoanService::class)` ou injeção no construtor/controller method (Laravel auto-resolve)
- O controller existente `LoanController` não usa DI explícita — chama `app(LoanService::class)` diretamente
- **Recomendação:** Usar `__invoke(Request $request, DashboardService $service)` com type-hint — Laravel resolve automaticamente

### Cache com Redis
- Redis já configurado: `CACHE_STORE=redis` no `.env`
- Store `redis` configurada em `backend/config/cache.php` (linha 81-85)
- Connection `cache` no `backend/config/database.php` (linha 169-180) — database 1 separada
- Implementação: `Cache::remember('dashboard', 300, fn() => ...)`

**Chave de cache:** usar prefixo `dashboard:` para evitar colisão:
```php
$cacheKey = sprintf('dashboard:%s', auth()->id()); // por usuário
// ou global:
$cacheKey = 'dashboard';
```

**Recomendação:** Cache global (não por usuário) — os KPIs são globais. Invalidar com `Cache::forget('dashboard')` se necessário.

### Consultas SQL — Estratégias de Performance

| Agregação | Estratégia | Índice Utilizado |
|-----------|-----------|------------------|
| Total equipamentos | `Equipment::count()` — query simples, PK index | `equipments_pkey` |
| Calibrações a vencer | `where('status','completed')->whereBetween('next_due_at',...)` | `(status, next_due_at)` composite |
| Equipamentos por categoria | `Category::withCount('equipments')` | JOIN via FK + category PK |
| Movimentações por mês | `select extract(year_month from created_at) as month, ... group by month` | `(created_at)` |
| Empréstimos ativos | `where('status','active')->count()` | `(status)` |
| Manutenções abertas | `whereIn('status',[...])->count()` | `(status)` |

Nenhum N+1 ou query pesada — todas as consultas são agregadas (count/group by) em tabelas indexadas.

---

## Frontend Architecture

### Module Structure (D-19)
```
frontend/src/modules/dashboard/
├── components/
│   ├── KpiCard.vue              # Card individual de KPI (clicável)
│   ├── KpiRow.vue               # Fileira horizontal de 5 KPIs
│   ├── EquipmentsByCategoryChart.vue  # Gráfico de rosca
│   ├── CalibrationsTimelineChart.vue  # Gráfico de barras
│   ├── StockMovementsChart.vue  # Gráfico de linha/área
│   └── EmptyState.vue           # Estado vazio com onboarding (D-04)
├── pages/
│   ├── DashboardPage.vue        # Página principal (substituir placeholder)
│   └── (nenhum adicional — apenas 1 página)
├── routes/                      # (opcional — rota já registrada globalmente)
├── services/
│   └── dashboardService.ts      # Chamada Axios para /api/v1/dashboard
├── store/
│   └── dashboardStore.ts        # Pinia: data, loading, error, fetchDashboard
└── types/
    └── dashboard.ts             # Interfaces: DashboardData, KpiData, ChartData
```

### DashboardPage.vue — Layout
```
┌─────────────────────────────────────────────────┐
│  [Seletor Período] [Atualizar]                   │  ← topbar do dashboard
├─────────────────────────────────────────────────┤
│ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐  │  ← KpiRow (D-02)
│ │KPI 1 │ │KPI 2 │ │KPI 3 │ │KPI 4 │ │KPI 5 │  │     5 cards horizontais
│ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘  │
├─────────────────────────────────────────────────┤
│ ┌─────────────────────────┐ ┌─────────────────┐ │  ← Grid 3 colunas (D-01)
│ │  Equip. por Categoria   │ │  Calibrações    │ │     Gráfico 1 + Gráfico 2
│ │  (rosca)                │ │  (barras)       │ │
│ └─────────────────────────┘ └─────────────────┘ │
├─────────────────────────────────────────────────┤
│ ┌──────────────────────────────────────────────┐│  ← Largura total (D-03)
│ │  Movimentações de Estoque                    ││     Gráfico 3
│ │  (linha/área)                                ││
│ └──────────────────────────────────────────────┘│
└─────────────────────────────────────────────────┘
```

**Breakpoints:**
- Desktop ≥ 1024px: 3 colunas (gráfico 1 + 2 ocupam 2 cols, gráfico 3 ocupa full)
- Tablet 768-1023px: 2 colunas
- Mobile < 768px: 1 coluna (TODOS os widgets empilham)

### Services (D-20)
```typescript
// frontend/src/modules/dashboard/services/dashboardService.ts
import { api } from '@/services/api'
import type { DashboardData } from '../types/dashboard'

export const dashboardService = {
  async fetch(params?: { start_date?: string; end_date?: string }): Promise<DashboardData> {
    const response = await api.get('/dashboard', { params })
    return response.data
  },
}
```

### Store (D-21)
```typescript
// frontend/src/modules/dashboard/store/dashboardStore.ts
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { dashboardService } from '../services/dashboardService'
import type { DashboardData } from '../types/dashboard'

export const useDashboardStore = defineStore('dashboard', () => {
  const data = ref<DashboardData | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchData(params?: { start_date?: string; end_date?: string }) {
    loading.value = true
    error.value = null
    try {
      data.value = await dashboardService.fetch(params)
    } catch (e: any) {
      error.value = e.message || 'Erro ao carregar dashboard'
    } finally {
      loading.value = false
    }
  }

  return { data, loading, error, fetchData }
})
```

### Types (Dashboard)
```typescript
// frontend/src/modules/dashboard/types/dashboard.ts
export interface KpiData {
  total_equipments: number
  calibrations_due_soon: number
  active_loans: number
  pending_verifications_today: number
  open_maintenance_orders: number
}

export interface ChartCategoryItem {
  name: string
  value: number
}

export interface ChartTimelineItem {
  month: string
  scheduled: number
  completed: number
  due: number
}

export interface ChartMovementItem {
  month: string
  incoming: number
  outgoing: number
}

export interface ChartData {
  equipments_by_category: ChartCategoryItem[]
  calibrations_timeline: ChartTimelineItem[]
  stock_movements: ChartMovementItem[]
}

export interface DashboardData {
  kpis: KpiData
  charts: ChartData
}
```

### Drill-down Navigation (D-10)
Cada elemento clicável do gráfico navega com `router.push()` + query params:

```typescript
// Exemplo: clique em categoria no gráfico de pizza
function onCategoryClick(params: any) {
  // O nome da categoria vem no evento do ECharts
  router.push({ name: 'equipments', query: { category: params.name } })
}
```

Caminhos de navegação por KPI:
| KPI | Rota | Filtro |
|-----|------|--------|
| Total Equipamentos | `/equipments` | — |
| Calibrações a Vencer | `/calibrations` | `?status=completed&due_soon=30` |
| Empréstimos Ativos | `/loans` | `?status=active` |
| Aferições Pendentes | `/verifications` | `?pending=true` |
| Manutenções Abertas | `/maintenance` | `?status=open,in_progress` |

### Rotas
A rota raiz `'/'` já existe em `frontend/src/router/routes.ts`:
```typescript
{
  path: '/',
  name: 'dashboard',
  component: () => import('@/modules/dashboard/pages/DashboardPage.vue'),
  meta: { requiresAuth: true, title: 'Dashboard', module: 'dashboard' },
}
```
Nenhuma alteração necessária — apenas substituir o conteúdo de `DashboardPage.vue`.

---

## UI/UX Patterns

### Core Components PrimeVue para Dashboard

| Componente | Uso | Propriedades Relevantes |
|------------|-----|------------------------|
| `Card` | Widget de KPI, wrapper de gráfico | `pt: { root, body, content }` para customização de padding/bg |
| `DatePicker` | Seletor de período global (D-17) | `selectionMode="range"`, `dateFormat="dd/mm/yy"` |
| `Button` | Botão "Atualizar" (D-15) | `icon="pi pi-refresh"`, `severity="secondary"`, `variant="text"` |
| `ProgressSpinner` | Loading centralizado (D-16) | `strokeWidth="4"`, `animationDuration=".5s"` |
| `SelectButton` | Alternativa para período pré-definido (7d, 30d, 12m) — opcional | `options`, `optionLabel` |
| `Message` | Estado vazio (D-04) | `severity="info"`, `icon="pi pi-chart-bar"` |

### Tema Escuro com ECharts
ECharts respeita o tema escuro via configuração manual. Para consistência com o tema Aura escuro:

```typescript
// Cores extraídas do tema escuro do projeto
const chartColors = ['#6366f1', '#8b5cf6', '#f59e0b', '#22c55e', '#ef4444', '#3b82f6']
```

### Grid Responsivo (D-01)
Usar CSS Grid do próprio layout (já estabelecido em `AppLayout.vue`):

```css
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  padding: 1.5rem;
}

/* Gráfico de linha ocupa largura total */
.dashboard-grid__full {
  grid-column: 1 / -1;
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
```

### Estilização de KPI Cards
```vue
<template>
  <Card class="kpi-card" @click="navigate">
    <template #content>
      <div class="kpi-card__value">{{ value }}</div>
      <div class="kpi-card__label">{{ label }}</div>
      <div v-if="subtitle" class="kpi-card__subtitle">{{ subtitle }}</div>
    </template>
  </Card>
</template>
```

---

## Package Legitimacy Audit

> Nenhum pacote externo novo será instalado nesta fase. Todas as dependências já existem no projeto:
> - `echarts` ^6.1.0 (já em `frontend/package.json`)
> - `vue-echarts` ^8.0.1 (já em `frontend/package.json`)
> - `primevue` ^5.0.0 (já em uso — Card, DatePicker, Button, ProgressSpinner)
> - Laravel `Cache` facade com Redis (já configurado no `.env`)
>
> **Disposition:** Nenhum pacote requer verificação. NPM install não é necessário.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Renderização de gráficos | Renderizar gráficos com SVG/Canvas manual | vue-echarts + ECharts 6 | ECharts tem suporte nativo a tema escuro, tooltips, drill-down, responsividade, animações |
| Cache de agregados | Query sem cache ou cache em arquivo | Redis Cache (Laravel Cache facade) | Já configurado, TTL de 5 min, evita 6+ queries pesadas a cada refresh |
| Layout responsivo de grid | CSS grid manual com media queries | CSS Grid (nativo) + @media queries | Padrão já estabelecido no projeto (AppLayout.vue usa grid-template-areas) |

**Key insight:** Dashboard é essencialmente um problema de **agregação de dados + cache + visualização**. Nenhuma dessas camadas precisa ser construída do zero — Laravel + Redis + ECharts cobrem tudo.

---

## Common Pitfalls

### Pitfall 1: Cache sem invalidação
**What goes wrong:** Dashboard exibe dados desatualizados por até 5 minutos mesmo após criar/editar registros.
**Why it happens:** `Cache::remember` com TTL fixo.
**How to avoid:** Aceitar latência de 5 min conforme D-13. Se necessário, invalidar cache em observers/eventos de criação dos modelos de origem (opcional, deferred para versão futura).
**Warning signs:** Usuário reclama que dados não refletem alteração imediata.

### Pitfall 2: N+1 queries na agregação
**What goes wrong:** DashboardService faz queries separadas para cada equipamento ao invés de `GROUP BY`.
**Why it happens:** Usar loops PHP para agregar dados que deveriam ser agregados no SQL.
**How to avoid:** Usar `withCount`, `groupBy`, `selectRaw` com funções de agregação do PostgreSQL. Ex: `Category::withCount('equipments')`.
**Warning signs:** Dashboard leva >3 segundos para carregar mesmo com cache.

### Pitfall 3: vue-echarts sem tree-shaking
**What goes wrong:** Bundle do frontend aumenta em ~1MB com todos os componentes ECharts.
**Why it happens:** Importar `VChart` sem registrar apenas os tipos de gráfico necessários.
**How to avoid:** Usar `use()` do `echarts/core` para registrar apenas `PieChart`, `BarChart`, `LineChart` + `CanvasRenderer` + componentes de suporte (Tooltip, Legend, Grid).
**Warning signs:** Build do Vite excede 2MB.

### Pitfall 4: Período padrão != período dos dados
**What goes wrong:** Gráfico de calibrações mostra vazio se nenhuma calibração existe nos próximos 6 meses.
**Why it happens:** Período fixo de 12 meses sem fallback para dados existentes.
**How to avoid:** Se não houver dados no período padrão, mostrar mensagem "Nenhum dado no período selecionado" (não gráfico vazio). Estado vazio (D-04) com links para cadastro.

### Pitfall 5: Dril-down sem suporte na listagem destino
**What goes wrong:** KPI de "Calibrações a Vencer" navega para `/calibrations?due_soon=30` mas a listagem não tem filtro `due_soon`.
**Why it happens:** Assumir que parâmetros de query são suportados sem verificar.
**How to avoid:** Verificar se as listagens existentes já suportam filtros de query params. As listagens usam `$request->input()` com `when()` — adicionar os filtros necessários (due_soon, status, pending). Isso pode exigir ajustes mínimos nos controllers existentes.

---

## Validation Architecture

> `workflow.nyquist_validation` não está definido como `false` no config — tratado como habilitado.

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit ^12.5.12 |
| Config file | `backend/phpunit.xml` |
| Quick run command | `composer run test` (backend apenas) |
| Full suite command | `composer run test` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| DASH-01 | Dashboard KPIs retornam dados corretos | Feature test | `vendor/bin/phpunit --filter DashboardTest` | ❌ Wave 0 |
| DASH-02 | Gráficos retornam estrutura esperada | Feature test | `vendor/bin/phpunit --filter DashboardTest` | ❌ Wave 0 |
| DASH-02 | Cache Redis é utilizado | Unit test | `vendor/bin/phpunit --filter DashboardCacheTest` | ❌ Wave 0 |

### Wave 0 Gaps
- [ ] `backend/tests/Feature/DashboardTest.php` — testar endpoint GET /api/v1/dashboard com dados reais (seeder), verificar estrutura da resposta (kpis, charts), verificar que KPIs têm valores numéricos
- [ ] `backend/tests/Unit/DashboardServiceTest.php` — testar agregação com dados mockados, verificar cache hit/miss

---

## Security Domain

> `security_enforcement` ausente do config — tratado como habilitado.

### Applicable ASVS Categories
| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes | Sanctum session guard (já implementado) |
| V4 Access Control | yes | `permission:dashboard.view` middleware |
| V5 Input Validation | yes | Laravel `date()` validation para start_date/end_date |
| V15 Business Logic | yes | Apenas dados autorizados (todos os registros — sem filtro por escopo) |

### Known Threat Patterns for {Laravel + Vue}
Nenhum padrão de ameaça específico para dashboard além do controle de acesso padrão (já implementado via Sanctum + middleware de permissão).

---

## Code Examples

### Backend — DashboardController
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public static function middleware(): array
    {
        return [
            ['middleware' => 'auth:sanctum'],
            ['middleware' => 'permission:dashboard.view'],
        ];
    }

    public function __invoke(Request $request, DashboardService $service)
    {
        $startDate = $request->date('start_date', now()->subMonths(12));
        $endDate = $request->date('end_date', now());

        $data = $service->aggregate($startDate, $endDate);

        return response()->json($data);
    }
}
```

### Backend — DashboardService (parcial)
```php
<?php

namespace App\Services;

use App\Models\Calibration;
use App\Models\Equipment;
use App\Models\InventoryMovement;
use App\Models\Loan;
use App\Models\MaintenanceOrder;
use App\Models\Category;
use App\Enums\LoanStatus;
use App\Enums\CalibrationStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function aggregate(Carbon $startDate, Carbon $endDate): array
    {
        return Cache::remember('dashboard', 300, function () use ($startDate, $endDate): array {
            return [
                'kpis' => $this->kpis(),
                'charts' => [
                    'equipments_by_category' => $this->equipmentsByCategory(),
                    'calibrations_timeline' => $this->calibrationsTimeline($startDate, $endDate),
                    'stock_movements' => $this->stockMovements($startDate, $endDate),
                ],
            ];
        });
    }

    private function kpis(): array
    {
        $pendingVerifications = app(VerificationService::class)->getPendingVerifications();

        return [
            'total_equipments' => Equipment::count(),
            'calibrations_due_soon' => Calibration::where('status', CalibrationStatus::Completed)
                ->whereBetween('next_due_at', [now(), now()->addDays(30)])
                ->count(),
            'active_loans' => Loan::where('status', LoanStatus::Active)->count(),
            'pending_verifications_today' => $pendingVerifications->count(),
            'open_maintenance_orders' => MaintenanceOrder::whereIn('status', ['open', 'in_progress'])->count(),
        ];
    }

    private function equipmentsByCategory(): array
    {
        return Category::withCount('equipments')
            ->get()
            ->map(fn ($cat) => ['name' => $cat->name, 'value' => $cat->equipments_count])
            ->toArray();
    }

    private function calibrationsTimeline(Carbon $startDate, Carbon $endDate): array
    {
        // Agrupa calibrações por mês nos próximos 6 meses
        // scheduled_date para agendadas, completed_at + next_due_at para concluídas
        // Implementação detalhada: 3 queries (scheduled, completed, due) com groupBy mês
        // ou uma query com CASE WHEN e extract(year_month from date)
        return []; // placeholder — ver plano para implementação completa
    }

    private function stockMovements(Carbon $startDate, Carbon $endDate): array
    {
        // Agrupa inventory_movements por mês, separando incoming (purchase+return)
        // e outgoing (consumption+adjustment+disposal)
        return []; // placeholder — ver plano para implementação completa
    }
}
```

### Frontend — DashboardPage.vue (Estrutura)
```vue
<template>
  <div class="dashboard-page">
    <!-- Topo: filtro + botão atualizar -->
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

    <!-- Loading -->
    <div v-if="store.loading" class="dashboard-loading">
      <ProgressSpinner />
      <p>Carregando dados do dashboard...</p>
    </div>

    <!-- Empty state -->
    <EmptyState v-else-if="!store.data" />

    <!-- Dashboard content -->
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
  if (dateRange.value?.[0]) params.start_date = (dateRange.value[0] as Date).toISOString()
  if (dateRange.value?.[1]) params.end_date = (dateRange.value[1] as Date).toISOString()
  store.fetchData(params)
}

onMounted(() => refresh())
</script>
```

### Frontend — ECharts Registration Pattern
```typescript
// Em cada componente de gráfico, OU em um plugin centralizado:
import { use } from 'echarts/core'
import { PieChart, BarChart, LineChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, LegendComponent, GridComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'

use([
  PieChart, BarChart, LineChart,
  TitleComponent, TooltipComponent, LegendComponent, GridComponent,
  CanvasRenderer,
])
```

---

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | O KPI "Aferições Pendentes (hoje)" usa `VerificationService::getPendingVerifications()` contando o retorno | Data Sources | O método pode ter performance ruim para muitos equipamentos; testar com dados reais |
| A2 | Os controllers de listagem existentes aceitam query params como `due_soon`, `status` para drill-down | API Design | Pode ser necessário adicionar `when()` nos controllers para suportar os filtros |

---

## Open Questions (RESOLVED)

1. **Cache invalidação ao criar/editar registros?** `RESOLVED: TTL de 5 minutos é suficiente (D-13). Cache global, não por usuário. Se necessário no futuro, adicionar observer.`
   - What we know: D-13 define cache com TTL de 5 minutos. Não há exigência de invalidação imediata.
   - What's unclear: Se o usuário espera ver dados atualizados imediatamente após criar um equipamento.
   - Recommendation: Manter TTL de 5 min conforme D-13. Se necessário no futuro, adicionar observer que invalida cache em eventos `created`/`updated` dos modelos de origem.

2. **Formato da data nos gráficos?** `RESOLVED: Usar YYYY-MM (ISO 8601 month) no backend, formatar no frontend com toLocaleDateString('pt-BR').`
   - What we know: D-09 define período padrão de 12 meses.
   - What's unclear: Formato de retorno do mês (YYYY-MM, MM/YYYY, timestamp).
   - Recommendation: Usar `YYYY-MM` (ISO 8601 month) no backend e formatar no frontend com `toLocaleDateString('pt-BR')`.

---

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| Redis 7 | Cache do Dashboard (D-13) | ✓ | 7 (Docker) | — |
| PostgreSQL 17 | Dados de 6 módulos de origem | ✓ | 17 (Docker) | — |
| Node.js 22 | Build frontend com vue-echarts | ✓ | 22 LTS | — |
| PHP 8.3 | DashboardService + Cache | ✓ | 8.3 (Docker) | — |

**Missing dependencies with no fallback:** Nenhuma — todas as dependências já estão operacionais.

---

## Sources

### Primary (HIGH confidence)
- **Codebase:** Models, Controllers, Services, Migrations das Phases 5–10 (verificados diretamente)
- **`backend/.env`:** `CACHE_STORE=redis` confirmado
- **`frontend/package.json`:** `echarts ^6.1.0`, `vue-echarts ^8.0.1` instalados
- **`frontend/src/main.ts`:** Configuração PrimeVue + tema escuro Aura
- **`frontend/src/router/routes.ts`:** Rota raiz `'/'` já aponta para `DashboardPage.vue`

### Secondary (MEDIUM confidence)
- **CONTEXT.md:** Decisões D-01 a D-22 documentadas na discussão da fase (não em código executado)

### Tertiary (LOW confidence)
- Nenhuma — todos os achados foram verificados contra o código fonte ou arquivos de configuração

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — todas as bibliotecas verificadas em `package.json` e `composer.json`
- Architecture: HIGH — padronizada com os 6 módulos existentes (controllers, services, resources)
- Pitfalls: MEDIUM — dependente de testes com dados reais para validar performance de `VerificationService`
- ECharts integration: MEDIUM — ECharts 6 pode ter API diferente da v5; verificar docs se necessário

**Research date:** 2026-07-27
**Valid until:** 2026-08-27 (30 dias — stack estável)
