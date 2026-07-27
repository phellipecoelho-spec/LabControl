# Phase 11: Dashboard - Pattern Map

**Mapped:** 2026-07-27
**Files analyzed:** 15 (6 create / 2 modify / 7 new scaffolded)
**Analogs found:** 12 / 15

## File Classification

| New/Modified File | Role | Data Flow | Closest Analog | Match Quality |
|---|---|---|---|---|
| `backend/app/Http/Controllers/Api/V1/DashboardController.php` | controller | request-response | `backend/app/Http/Controllers/Api/V1/VerificationController.php` | role-match |
| `backend/app/Services/DashboardService.php` | service | CRUD (aggregation) | `backend/app/Services/VerificationService.php` | role-match |
| `backend/routes/api.php` | config | — | itself (modify existing) | exact |
| `backend/tests/Feature/DashboardTest.php` | test | request-response | `backend/tests/Feature/EquipmentApiTest.php` | role-match |
| `backend/tests/Unit/DashboardServiceTest.php` | test | CRUD (aggregation) | `backend/tests/Unit/Services/MaintenanceServiceTest.php` | role-match |
| `frontend/src/modules/dashboard/pages/DashboardPage.vue` | page | request-response | `frontend/src/modules/dashboard/pages/DashboardPage.vue` (placeholder) | exact |
| `frontend/src/modules/dashboard/components/KpiCard.vue` | component | presentational | `frontend/src/modules/equipment/components/EquipmentInfoTab.vue` | partial |
| `frontend/src/modules/dashboard/components/KpiRow.vue` | component | presentational | `frontend/src/modules/equipment/components/EquipmentInfoTab.vue` | partial |
| `frontend/src/modules/dashboard/components/EquipmentsByCategoryChart.vue` | component | presentational | (new pattern — no existing ECharts components) | no-analog |
| `frontend/src/modules/dashboard/components/CalibrationsTimelineChart.vue` | component | presentational | (new pattern — no existing ECharts components) | no-analog |
| `frontend/src/modules/dashboard/components/StockMovementsChart.vue` | component | presentational | (new pattern — no existing ECharts components) | no-analog |
| `frontend/src/modules/dashboard/components/EmptyState.vue` | component | presentational | `frontend/src/modules/equipment/components/EquipmentInfoTab.vue` | partial |
| `frontend/src/modules/dashboard/services/dashboardService.ts` | service | request-response | `frontend/src/modules/equipment/services/EquipmentService.ts` | role-match |
| `frontend/src/modules/dashboard/store/dashboardStore.ts` | store | CRUD | `frontend/src/modules/equipment/store/EquipmentStore.ts` | role-match |
| `frontend/src/modules/dashboard/types/dashboard.ts` | types | — | `frontend/src/modules/equipment/types/equipment.ts` | role-match |

## Pattern Assignments

---

### `backend/app/Http/Controllers/Api/V1/DashboardController.php` (controller, request-response)

**Analog:** `backend/app/Http/Controllers/Api/V1/VerificationController.php`

**Imports pattern** (lines 1-18):
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
```

**Auth/Middleware pattern** (lines 26-36):
```php
public static function middleware(): array
{
    return [
        ['middleware' => 'auth:sanctum'],
        ['middleware' => 'permission:dashboard.view'],
    ];
}
```

**Core single-action pattern** (lines 56-78 from VerificationController pending method + Research.md):
Since the codebase has no `__invoke` controllers yet, use a dedicated `__invoke` method on an otherwise empty controller. The `app()` resolution pattern for services is well established:

```php
public function __invoke(Request $request)
{
    $startDate = $request->date('start_date', now()->subMonths(12));
    $endDate = $request->date('end_date', now());

    $data = app(DashboardService::class)->aggregate($startDate, $endDate);

    return response()->json($data);
}
```

**Error handling pattern:** Follow VerificationController pattern (lines 58-68) — wrap service calls in try/catch only when custom exceptions are thrown. Since DashboardService does not throw custom exceptions (aggregation only), no try/catch is needed, matching the simpler `index()` pattern in VerificationController (lines 41-51).

---

### `backend/app/Services/DashboardService.php` (service, CRUD/aggregation)

**Analog:** `backend/app/Services/VerificationService.php`

**Imports pattern** (lines 1-12):
```php
<?php

namespace App\Services;

use App\Enums\CalibrationStatus;
use App\Enums\LoanStatus;
use App\Models\Calibration;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\InventoryMovement;
use App\Models\Loan;
use App\Models\MaintenanceOrder;
use App\Services\VerificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
```

**Core pattern — aggregation with Cache** (from Research.md lines 361-391):
```php
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
```

**KPI aggregation pattern** (from Research.md lines 374-386):
```php
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
```

**Chart aggregation — Equipments by Category** (from Research.md lines 841-847):
```php
private function equipmentsByCategory(): array
{
    return Category::withCount('equipments')
        ->get()
        ->map(fn ($cat) => ['name' => $cat->name, 'value' => $cat->equipments_count])
        ->toArray();
}
```

**Chart aggregation — Stock Movements** pattern (InventoryMovement model lines 14-24 — use `selectRaw` with `CASE WHEN`):
```php
private function stockMovements(Carbon $startDate, Carbon $endDate): array
{
    return InventoryMovement::selectRaw("
        to_char(created_at, 'YYYY-MM') as month,
        SUM(CASE WHEN type IN ('purchase', 'return') THEN quantity ELSE 0 END) as incoming,
        SUM(CASE WHEN type IN ('consumption', 'adjustment', 'disposal') THEN quantity ELSE 0 END) as outgoing
    ")
    ->whereBetween('created_at', [$startDate, $endDate])
    ->groupBy('month')
    ->orderBy('month')
    ->get()
    ->toArray();
}
```

**Service resolution pattern:** `app(VerificationService::class)` — already established in VerificationController (line 59) and LoanController (line 83). DashboardService uses `app(VerificationService::class)` to delegate `getPendingVerifications()`.

---

### `backend/routes/api.php` (config — modify existing)

**Route registration pattern** (lines 345-346 from Research.md, following existing route patterns lines 92-93 for single-endpoint resources):

```php
// Dashboard — single endpoint
Route::get('dashboard', DashboardController::class)->name('dashboard');
```

Add this line inside the `Route::middleware('auth:sanctum')->group(function () { ... })` block (line 49), after the existing route groups. Add the import at line 20:

```php
use App\Http\Controllers\Api\V1\DashboardController;
```

---

### `backend/tests/Feature/DashboardTest.php` (test, request-response)

**Analog:** `backend/tests/Feature/EquipmentApiTest.php`

**Test setup pattern** (lines 1-117):
```php
<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Equipment;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('slug', 'admin')->value('id'));
    }
```

**Test — unauthenticated access** (lines 29-34):
```php
public function test_unauthenticated_user_cannot_access_dashboard(): void
{
    $response = $this->getJson('/api/v1/dashboard');

    $response->assertStatus(401);
}
```

**Test — authenticated success** (lines 36-44):
```php
public function test_dashboard_returns_kpis_and_charts_structure(): void
{
    // Create test data
    Equipment::factory(3)->create();

    $response = $this->actingAs($this->user)->getJson('/api/v1/dashboard');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'kpis' => [
                'total_equipments',
                'calibrations_due_soon',
                'active_loans',
                'pending_verifications_today',
                'open_maintenance_orders',
            ],
            'charts' => [
                'equipments_by_category',
                'calibrations_timeline',
                'stock_movements',
            ],
        ]);
}
```

**Test — date filter** (following pattern from EquipmentApiTest lines 98-111):
```php
public function test_dashboard_respects_date_filter(): void
{
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/dashboard?start_date=2026-01-01&end_date=2026-06-30');

    $response->assertStatus(200)
        ->assertJsonStructure(['kpis', 'charts']);
}
```

---

### `backend/tests/Unit/DashboardServiceTest.php` (test, CRUD/aggregation)

**Analog:** `backend/tests/Unit/Services/MaintenanceServiceTest.php`

**Test setup pattern** (lines 1-41):
```php
<?php

namespace Tests\Unit\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\Equipment;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('slug', 'admin')->value('id'));

        $this->actingAs($this->user);
        $this->service = app(DashboardService::class);
    }
```

**Test — kpis return correct values** (following pattern from MaintenanceServiceTest lines 43-63):
```php
public function test_kpis_return_correct_values(): void
{
    Equipment::factory(5)->create();

    $result = $this->service->aggregate(now()->subYear(), now());

    $this->assertEquals(5, $result['kpis']['total_equipments']);
    $this->assertIsInt($result['kpis']['calibrations_due_soon']);
    $this->assertIsInt($result['kpis']['active_loans']);
    $this->assertIsInt($result['kpis']['open_maintenance_orders']);
}
```

**Test — cache is used** (new pattern — use `Cache::spy()`):
```php
public function test_cache_is_used_on_subsequent_calls(): void
{
    Cache::shouldReceive('remember')
        ->once()
        ->with(\Mockery::type('string'), 300, \Mockery::type('Closure'))
        ->andReturn(['kpis' => [], 'charts' => []]);

    $this->service->aggregate(now()->subYear(), now());
}
```

---

### `frontend/src/modules/dashboard/services/dashboardService.ts` (service, request-response)

**Analog:** `frontend/src/modules/equipment/services/EquipmentService.ts`

**Imports + Singleton pattern** (lines 1-8):
```typescript
import { api } from '@/services/api'
import type { DashboardData } from '../types/dashboard'

export const dashboardService = {
  async fetch(params?: { start_date?: string; end_date?: string }): Promise<DashboardData> {
    const response = await api.get('/dashboard', { params })
    return response.data
  },
}
```

**Key differences from EquipmentService:** Only one method (`fetch`) since dashboard is a single-endpoint read-only aggregate.

---

### `frontend/src/modules/dashboard/store/dashboardStore.ts` (store, CRUD)

**Analog:** `frontend/src/modules/equipment/store/EquipmentStore.ts`

**Imports + Store pattern** (lines 1-18):
```typescript
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

**Key differences from EquipmentStore:** Simpler state (just `data`, `loading`, `error` — no pagination, no lists). Uses the module's own service instead of `api` directly.

---

### `frontend/src/modules/dashboard/types/dashboard.ts` (types)

**Analog:** `frontend/src/modules/equipment/types/equipment.ts`

**Interfaces pattern** (lines 1-60):
```typescript
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

---

### `frontend/src/modules/dashboard/pages/DashboardPage.vue` (page, request-response)

**Analog:** Current placeholder + Research.md specification

**Full structure** (from Research.md lines 868-943, replacing existing placeholder):
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
```

**Script setup pattern** (from Research.md lines 920-943):
```typescript
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

**CSS Grid layout pattern** (from Research.md lines 621-644):
```css
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  padding: 1.5rem;
}

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

---

### `frontend/src/modules/dashboard/components/KpiCard.vue` (component, presentational)

**Analog (style pattern):** `frontend/src/modules/equipment/components/EquipmentInfoTab.vue`

**Core pattern** (from Research.md lines 647-657 + project styling conventions):
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
  if (props.to) {
    router.push(props.to)
  }
}
</script>
```

---

### `frontend/src/modules/dashboard/components/KpiRow.vue` (component, presentational)

**Composition pattern:** Wraps 5 KpiCard components horizontally.

```vue
<template>
  <div class="kpi-row">
    <KpiCard
      v-for="(kpi, key) in kpis"
      :key="key"
      :value="kpi.value"
      :label="kpi.label"
      :subtitle="kpi.subtitle"
      :to="kpi.to"
    />
  </div>
</template>

<script setup lang="ts">
import type { KpiData } from '../types/dashboard'
import { computed } from 'vue'
import KpiCard from './KpiCard.vue'

const props = defineProps<{ kpis: KpiData }>()

const kpiList = computed(() => [
  { value: props.kpis.total_equipments, label: 'Equipamentos', subtitle: 'Total cadastrados', to: { name: 'equipments' } },
  { value: props.kpis.calibrations_due_soon, label: 'Calibrações a Vencer', subtitle: 'Próximos 30 dias', to: { name: 'calibrations.index', query: { status: 'completed', due_soon: '30' } } },
  { value: props.kpis.active_loans, label: 'Empréstimos Ativos', subtitle: 'Atualmente emprestados', to: { name: 'loans.index', query: { status: 'active' } } },
  { value: props.kpis.pending_verifications_today, label: 'Aferições Pendentes', subtitle: 'Hoje', to: { name: 'verifications.index', query: { pending: 'true' } } },
  { value: props.kpis.open_maintenance_orders, label: 'Manutenções Abertas', subtitle: 'Em aberto/andamento', to: { name: 'maintenance.index', query: { status: 'open,in_progress' } } },
])
</script>
```

---

### `frontend/src/modules/dashboard/components/EmptyState.vue` (component, presentational)

**Pattern** (from Research.md D-04):
```vue
<template>
  <div class="empty-state">
    <Message severity="info" icon="pi pi-chart-bar">
      <div class="empty-state__content">
        <h3>Bem-vindo ao LabControl</h3>
        <p>Seu dashboard ainda não possui dados. Comece cadastrando equipamentos, calibrações e movimentações.</p>
        <div class="empty-state__actions">
          <Button label="Cadastrar Equipamento" icon="pi pi-plus" @click="$router.push({ name: 'equipment-create' })" />
          <Button label="Ver Calibrações" icon="pi pi-calendar" severity="secondary" variant="text" @click="$router.push({ name: 'calibrations.index' })" />
        </div>
      </div>
    </Message>
  </div>
</template>

<script setup lang="ts">
import Message from 'primevue/message'
import Button from 'primevue/button'
</script>
```

---

### ECharts Chart Components — No Existing Analog

All three chart components (`EquipmentsByCategoryChart.vue`, `CalibrationsTimelineChart.vue`, `StockMovementsChart.vue`) share the same pattern with no existing analog in the codebase. They follow the pattern from Research.md lines 249-278:

**Registration pattern** (centralized in each component or in a shared `frontend/src/plugins/echarts.ts`):
```typescript
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

**EquipmentsByCategoryChart.vue — Donut chart pattern:**
```vue
<template>
  <Card>
    <template #title>Equipamentos por Categoria</template>
    <template #content>
      <VChart
        v-if="data.length"
        :option="chartOption"
        theme="dark"
        autoresize
        style="height: 320px"
        @click="onChartClick"
      />
      <p v-else class="text-600 text-sm">Nenhum equipamento cadastrado.</p>
    </template>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import VChart from 'vue-echarts'
import Card from 'primevue/card'
import type { ChartCategoryItem } from '../types/dashboard'

const props = defineProps<{ data: ChartCategoryItem[] }>()
const router = useRouter()

const chartOption = computed(() => ({
  tooltip: { trigger: 'item' },
  legend: { bottom: '0', textStyle: { color: '#94a3b8' } },
  series: [{
    type: 'pie',
    radius: ['50%', '70%'],
    data: props.data,
    label: { color: '#e2e8f0' },
    emphasis: {
      itemStyle: { shadowBlur: 10, shadowColor: 'rgba(0,0,0,0.3)' },
    },
  }],
}))

function onChartClick(params: any) {
  router.push({ name: 'equipments', query: { category: params.name } })
}
</script>
```

**CalibrationsTimelineChart.vue — Stacked bar pattern:**
```vue
<template>
  <Card>
    <template #title>Calibrações — Próximos 6 Meses</template>
    <template #content>
      <VChart
        v-if="data.length"
        :option="chartOption"
        theme="dark"
        autoresize
        style="height: 320px"
      />
      <p v-else class="text-600 text-sm">Nenhuma calibração no período.</p>
    </template>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import VChart from 'vue-echarts'
import Card from 'primevue/card'
import type { ChartTimelineItem } from '../types/dashboard'

const props = defineProps<{ data: ChartTimelineItem[] }>()

const chartOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  legend: { bottom: '0', textStyle: { color: '#94a3b8' } },
  grid: { left: '3%', right: '4%', bottom: '15%', containLabel: true },
  xAxis: {
    type: 'category',
    data: props.data.map(d => d.month),
    axisLabel: { color: '#94a3b8' },
  },
  yAxis: {
    type: 'value',
    axisLabel: { color: '#94a3b8' },
  },
  series: [
    { name: 'Agendadas', type: 'bar', stack: 'total', data: props.data.map(d => d.scheduled), itemStyle: { color: '#6366f1' } },
    { name: 'Concluídas', type: 'bar', stack: 'total', data: props.data.map(d => d.completed), itemStyle: { color: '#22c55e' } },
  ],
}))
</script>
```

**StockMovementsChart.vue — Area line pattern:**
```vue
<template>
  <Card>
    <template #title>Movimentações de Estoque por Mês</template>
    <template #content>
      <VChart
        v-if="data.length"
        :option="chartOption"
        theme="dark"
        autoresize
        style="height: 320px"
      />
      <p v-else class="text-600 text-sm">Nenhuma movimentação no período.</p>
    </template>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import VChart from 'vue-echarts'
import Card from 'primevue/card'
import type { ChartMovementItem } from '../types/dashboard'

const props = defineProps<{ data: ChartMovementItem[] }>()

const chartOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  legend: { bottom: '0', textStyle: { color: '#94a3b8' } },
  grid: { left: '3%', right: '4%', bottom: '15%', containLabel: true },
  xAxis: {
    type: 'category',
    data: props.data.map(d => d.month),
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
      data: props.data.map(d => d.incoming),
      itemStyle: { color: '#22c55e' },
    },
    {
      name: 'Saídas',
      type: 'line',
      areaStyle: { opacity: 0.3 },
      data: props.data.map(d => d.outgoing),
      itemStyle: { color: '#ef4444' },
    },
  ],
}))
</script>
```

---

## Shared Patterns

### Authentication
**Source:** `backend/app/Http/Controllers/Api/V1/LoanController.php` (lines 24-36)
**Apply to:** DashboardController
```php
public static function middleware(): array
{
    return [
        ['middleware' => 'auth:sanctum'],
        ['middleware' => 'permission:dashboard.view'],
    ];
}
```

### Error Handling
**Source:** `backend/app/Http/Controllers/Api/V1/VerificationController.php` (lines 58-69)
**Apply to:** DashboardController (if try/catch needed)
```php
try {
    $data = $service->aggregate($startDate, $endDate);
} catch (\Exception $e) {
    return response()->json(['message' => 'Erro ao carregar dashboard'], 500);
}
```

### Cache Pattern
**Source:** Research.md lines 362-371 + `backend/.env` (CACHE_STORE=redis confirmed)
**Apply to:** DashboardService.aggregate()
```php
return Cache::remember('dashboard', 300, function () use ($startDate, $endDate): array {
    // aggregation logic
});
```

### Permission Registration
**Source:** `backend/database/seeders/RolePermissionSeeder.php` (lines 13-14, 151-161)
**Apply to:** The `dashboard.view` permission already exists in the seeder (line 14). No changes needed.
```
['name' => 'Visualizar Dashboard', 'slug' => 'dashboard.view', 'group' => 'dashboard'],
```
The permission is already assigned to all roles (admin, supervisor, laboratorista, tecnico, consulta, auditor).

### Frontend Component Styling (PrimeVue + scoped CSS)
**Source:** `frontend/src/modules/equipment/components/EquipmentInfoTab.vue` (lines 1-87)
**Apply to:** All dashboard components
```vue
<style scoped>
.kpi-card {
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
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
  margin-top: 0.125rem;
}
</style>
```

### API Service Pattern (Axios)
**Source:** `frontend/src/modules/equipment/services/EquipmentService.ts` (lines 1-8)
**Apply to:** `dashboardService.ts`
```typescript
import { api } from '@/services/api'
```

### Pinia Store Pattern
**Source:** `frontend/src/modules/equipment/store/EquipmentStore.ts` (lines 1-3)
**Apply to:** `dashboardStore.ts`
```typescript
import { defineStore } from 'pinia'
import { ref } from 'vue'
```

### Route Registration
**Source:** `frontend/src/router/routes.ts` (lines 10-15)
**Apply to:** The route already exists. No changes needed.
```typescript
{
  path: '/',
  name: 'dashboard',
  component: () => import('@/modules/dashboard/pages/DashboardPage.vue'),
  meta: { requiresAuth: true, title: 'Dashboard', module: 'dashboard' },
},
```

### Testing — SetUp Pattern (Seed + Auth)
**Source:** `backend/tests/Feature/EquipmentApiTest.php` (lines 19-27)
**Apply to:** All dashboard tests
```php
protected function setUp(): void
{
    parent::setUp();
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->roles()->attach(Role::where('slug', 'admin')->value('id'));
}
```

## No Analog Found

Files with no close match in the codebase (planner should use RESEARCH.md patterns instead):

| File | Role | Data Flow | Reason |
|------|------|-----------|--------|
| `EquipmentsByCategoryChart.vue` | component | presentational | First ECharts component in project — no existing chart components exist |
| `CalibrationsTimelineChart.vue` | component | presentational | First ECharts component in project — no existing chart components exist |
| `StockMovementsChart.vue` | component | presentational | First ECharts component in project — no existing chart components exist |

All three chart components should follow the `vue-echarts` registration + computed option pattern documented in Research.md §249-278. The component scaffolding pattern (Card wrapper, PrimeVue imports, scoped CSS) follows `EquipmentInfoTab.vue`.

## Metadata

**Analog search scope:** 
- Backend: `backend/app/Http/Controllers/Api/V1/`, `backend/app/Services/`, `backend/tests/Feature/`, `backend/tests/Unit/Services/`, `backend/routes/`
- Frontend: `frontend/src/modules/equipment/`, `frontend/src/modules/dashboard/`, `frontend/src/services/`, `frontend/src/router/`
- Config: `backend/database/seeders/`, `backend/app/Enums/`, `frontend/src/main.ts`

**Files scanned:** 25 source files read
**Pattern extraction date:** 2026-07-27
