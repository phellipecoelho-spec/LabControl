# Phase 10: Manutenções — Research

**Researched:** 2026-07-25
**Domain:** Maintenance orders for equipment (preventive/corrective)
**Confidence:** HIGH

## Summary

This phase implements a maintenance orders module for equipment, supporting both preventive (interval-scheduled) and corrective (on-demand) maintenance types. The module follows the established Calibrações pattern (Phase 8) for interval-based scheduling and the Loan/Calibration pattern for CRUD operations, status workflows, and permission gates.

The codebase already has:
- Sidebar item scaffolded under "Operações" → "Manutenções" with `pi pi-wrench` icon (file: `frontend/src/types/navigation.ts:74-78`)
- Route scaffolded at `/maintenance` pointing to `PlaceholderPage.vue` (`frontend/src/router/routes.ts:159-163`)
- Empty `frontend/src/modules/maintenance/` directory structure

**Primary recommendation:** Follow Calibrações module pattern exactly — migration, Model with enum-backed status, Service with transactional operations, Controller with static middleware, Form Requests, API Resources, Pinia store, TypeScript types, ListPage with DataTable + filters, DetailPage with tabs + action buttons, and a history tab integrated into EquipmentDetailPage.

<user_constraints>
## User Constraints

### Locked Decisions
- **D-01:** Single type field (preventive|corrective), same workflow for both
- **D-02:** Status workflow: open → in_progress → completed | cancelled
- **D-03:** Fields: equipment_id, type, status, priority (low/medium/high/critical), description, scheduled_date, assigned_to, opened_by, completed_at, resolution, time_spent, cost, interval_value, interval_unit (days/months/hours), next_due_at, notes
- **D-04:** Interval-based scheduling (like Calibrações)
- **D-05:** Pivot table maintenance_order_parts linking to inventory_items
- **D-06/D-07:** Opening form: equipment, type, priority, description, scheduled_date (no technician)
- **D-08:** In-app notification MaintenanceOrderCreated when order is opened
- **D-09/D-10/D-11:** Closure form with resolution, parts, hours, cost; auto-create next preventive order
- **D-12/D-13:** History as tab in EquipmentDetailPage + dedicated list page /maintenance
- **D-14/D-15:** Permissions: manutencoes.view/create/edit/concluir. Sidebar already scaffolded (pi pi-wrench)
- **D-16/D-17:** Tab and buttons gated by permissions
- **D-19:** No scheduled command for this phase

### the agent's Discretion
- Nomes específicos de rotas, controllers, services seguindo convenções dos módulos existentes
- Ordem de implementação (backend DB → backend CRUD → frontend)
- Layout exato do formulário de abertura/fechamento (campos, ordem, grid)
- Template da notificação in-app (texto, prioridade, link)
- Ícone e label exatos para os botões de ação
- Cálculo de next_due_at considerando dias úteis ou corridos (default: corridos como Calibrações)

### Deferred Ideas (OUT OF SCOPE)
- Nenhum item diferido para esta fase.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| MAINT-01 | User can open maintenance orders | Full CRUD pattern established in Calibrações (backend) and Loan/Calibration (frontend) — see sections below |
| MAINT-02 | System maintains history of preventive and corrective maintenance | History tab pattern established in Aferições (`VerificationHistoryTab.vue`) + dedicated list page in Calibrações (`CalibrationListPage.vue`) |
</phase_requirements>

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Maintenance order CRUD | API / Backend | Frontend (SPA) | Controllers → Services → Models per existing Calibrações pattern |
| Interval scheduling | Backend (Service) | — | `calculateNextDue()` in service layer, same as `CalibrationService::calculateNextDue()` |
| Parts consumption (pivot) | Backend (Pivot Model) | — | `equipment_loan` pivot pattern from Loans, adapted for `maintenance_order_parts` |
| Notification on creation | Backend (Notification) | — | `MaintenanceOrderCreated` notification, same pattern as `ToleranceExceeded` |
| History display (tab) | Browser / Client | API / Backend | `VerificationHistoryTab.vue` pattern — component in EquipmentDetailPage, data via API |
| List page with filters | Browser / Client | API / Backend | `CalibrationListPage.vue` pattern — DataTable with filters, lazy pagination |
| Permission gating | API / Backend (Middleware) | Browser / Client (v-if) | Static middleware on controller, `authStore.hasPermission()` in templates |

## Standard Stack

### Core Libraries
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel Framework | ^13.8 (composer.json:11) | REST API + Eloquent ORM | Project standard |
| Vue 3 + Vite | latest SFC | SPA frontend | Project standard |
| PrimeVue | latest | UI components (Dialog, DataTable, Select, Button, Tag, etc.) | Project standard — all existing modules use it |
| Pinia | latest | Frontend state management | Project standard |
| Vue Router | latest | Frontend routing | Project standard |

### Supporting Backend Patterns
| Library | Version | Purpose | Why |
|---------|---------|---------|-----|
| Laravel Sanctum | ^4.0 | Session-based auth | Existing auth mechanism |
| Intervention Image | ^4.2 | Image processing (if needed) | Existing dependency |

### Backend File Manifest
| File | Action | Purpose |
|------|--------|---------|
| `backend/database/migrations/YYYY_MM_DD_HHMMSS_create_maintenance_tables.php` | **Create** | Schema for `maintenance_orders` + `maintenance_order_parts` |
| `backend/app/Enums/MaintenanceStatus.php` | **Create** | Status enum: Open, InProgress, Completed, Cancelled |
| `backend/app/Enums/MaintenanceType.php` | **Create** | Type enum: Preventive, Corrective |
| `backend/app/Enums/MaintenancePriority.php` | **Create** | Priority enum: Low, Medium, High, Critical |
| `backend/app/Models/MaintenanceOrder.php` | **Create** | Model with relationships, scopes, accessors |
| `backend/app/Models/MaintenanceOrderPart.php` | **Create** | Pivot model for parts consumption |
| `backend/app/Exceptions/MaintenanceException.php` | **Create** | Domain exception (422 default) |
| `backend/app/Services/MaintenanceService.php` | **Create** | Transactional business logic |
| `backend/app/Http/Requests/StoreMaintenanceOrderRequest.php` | **Create** | Validation for opening |
| `backend/app/Http/Requests/UpdateMaintenanceOrderRequest.php` | **Create** | Validation for editing |
| `backend/app/Http/Requests/CompleteMaintenanceOrderRequest.php` | **Create** | Validation for closing |
| `backend/app/Http/Resources/MaintenanceOrderResource.php` | **Create** | JSON resource |
| `backend/app/Http/Resources/MaintenanceOrderCollection.php` | **Create** | Collection resource |
| `backend/app/Http/Controllers/Api/V1/MaintenanceOrderController.php` | **Create** | CRUD + complete + cancel |
| `backend/app/Notifications/MaintenanceOrderCreated.php` | **Create** | In-app notification |
| `backend/database/seeders/MaintenanceSeeder.php` | **Create** | Seed data |
| `backend/database/seeders/RolePermissionSeeder.php` | **Modify** | Add manutencoes.* permissions |
| `backend/app/Models/Equipment.php` | **Modify** | Add `maintenanceOrders()` and `lastMaintenance()` relationships |

### Frontend File Manifest
| File | Action | Purpose |
|------|--------|---------|
| `frontend/src/modules/maintenance/types/maintenance.ts` | **Create** | TypeScript interfaces + constants |
| `frontend/src/modules/maintenance/services/MaintenanceService.ts` | **Create** | Axios API calls |
| `frontend/src/modules/maintenance/store/MaintenanceStore.ts` | **Create** | Pinia store with CRUD |
| `frontend/src/modules/maintenance/pages/MaintenanceListPage.vue` | **Create** | List with filters + DataTable |
| `frontend/src/modules/maintenance/pages/MaintenanceDetailPage.vue` | **Create** | Detail with tabs + action buttons |
| `frontend/src/modules/maintenance/components/MaintenanceOpenDialog.vue` | **Create** | Dialog for opening orders |
| `frontend/src/modules/maintenance/components/MaintenanceCloseDialog.vue` | **Create** | Dialog for closing orders |
| `frontend/src/modules/maintenance/components/MaintenanceHistoryTab.vue` | **Create** | History tab for EquipmentDetailPage |
| `frontend/src/modules/equipment/pages/EquipmentDetailPage.vue` | **Modify** | Add Tab "Manutenções" (index 6) |
| `frontend/src/router/routes.ts` | **Modify** | Replace PlaceholderPage with real pages |
| `frontend/src/types/navigation.ts` | **Modify** | Already scaffolded — no changes needed |

## Package Legitimacy Audit

> **Not required** — this phase introduces no new external packages. All dependencies (Laravel, Vue, PrimeVue, Pinia, etc.) are already in the project.

## Architecture Patterns

### System Architecture Diagram

```
User → EquipmentDetailPage (Tab: "Manutenções")
   ├── MaintenanceHistoryTab → api.get('/equipments/{id}/maintenance') → MaintenanceOrderController@byEquipment
   ├── "Abrir Manutenção" button → MaintenanceOpenDialog → api.post('/maintenance') → MaintenanceOrderController@store → MaintenanceService::create()
   └── "Concluir" button → MaintenanceCloseDialog → api.post('/maintenance/{id}/complete') → MaintenanceOrderController@complete → MaintenanceService::complete()

User → /maintenance (Dedicated List Page)
   └── MaintenanceListPage → api.get('/maintenance') → MaintenanceOrderController@index (with filters)
       └── View → /maintenance/{id} → MaintenanceDetailPage → api.get('/maintenance/{id}') → MaintenanceOrderController@show
           ├── Tab "Dados da Manutenção" — info display
           ├── Tab "Peças Utilizadas" — parts list (pivot)
           └── Tab "Timeline" — status history via activity log

Automated (on complete with preventive type):
   └── MaintenanceService::complete() → if type === preventive, auto-creates next order
       └── Dispatches maintenance order for prevention: next_due_at = completed_at + interval
```

### Recommended Project Structure

```
backend/
├── app/
│   ├── Enums/
│   │   ├── MaintenanceStatus.php        # NEW: open, in_progress, completed, cancelled
│   │   ├── MaintenanceType.php          # NEW: preventive, corrective
│   │   └── MaintenancePriority.php      # NEW: low, medium, high, critical
│   ├── Exceptions/
│   │   └── MaintenanceException.php     # NEW
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   └── MaintenanceOrderController.php  # NEW
│   │   ├── Requests/
│   │   │   ├── StoreMaintenanceOrderRequest.php      # NEW
│   │   │   ├── UpdateMaintenanceOrderRequest.php     # NEW
│   │   │   └── CompleteMaintenanceOrderRequest.php   # NEW
│   │   └── Resources/
│   │       ├── MaintenanceOrderResource.php          # NEW
│   │       └── MaintenanceOrderCollection.php        # NEW
│   ├── Models/
│   │   ├── Equipment.php                  # MODIFY: add relationships
│   │   ├── MaintenanceOrder.php           # NEW
│   │   └── MaintenanceOrderPart.php       # NEW
│   ├── Notifications/
│   │   └── MaintenanceOrderCreated.php    # NEW
│   └── Services/
│       └── MaintenanceService.php         # NEW
├── database/
│   ├── migrations/
│   │   └── YYYY_MM_DD_HHMMSS_create_maintenance_tables.php  # NEW
│   └── seeders/
│       ├── MaintenanceSeeder.php          # NEW
│       └── RolePermissionSeeder.php       # MODIFY
└── routes/
    └── api.php                            # MODIFY: add maintenance routes

frontend/
└── src/
    ├── modules/
    │   ├── equipment/
    │   │   └── pages/
    │   │       └── EquipmentDetailPage.vue  # MODIFY: add Tab "Manutenções"
    │   └── maintenance/                     # NEW module
    │       ├── components/
    │       │   ├── MaintenanceOpenDialog.vue
    │       │   ├── MaintenanceCloseDialog.vue
    │       │   └── MaintenanceHistoryTab.vue
    │       ├── pages/
    │       │   ├── MaintenanceListPage.vue
    │       │   └── MaintenanceDetailPage.vue
    │       ├── services/
    │       │   └── MaintenanceService.ts
    │       ├── store/
    │       │   └── MaintenanceStore.ts
    │       └── types/
    │           └── maintenance.ts
    └── router/
        └── routes.ts                      # MODIFY: replace PlaceholderPage
```

### Pattern 1: Model with Typed Enum (from Calibration)

**What:** Every business model uses backed enums for status and type fields. Enums include `label()` for Portuguese display and `canTransitionTo()` for state machine validation.

**When to use:** Models with finite status/type values that need validation or display mapping.

**Backend example** (VERIFIED: `backend/app/Enums/CalibrationStatus.php:1-45`):
```php
enum MaintenanceStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberta',
            self::InProgress => 'Em Andamento',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }

    public function canTransitionTo(MaintenanceStatus $target): bool
    {
        return match ($this) {
            self::Open => in_array($target, [self::InProgress, self::Cancelled], true),
            self::InProgress => in_array($target, [self::Completed, self::Cancelled], true),
            self::Completed => false,
            self::Cancelled => false,
        };
    }
}
```

### Pattern 2: Service with Transactional Status Transition (from CalibrationService)

**What:** All state-changing operations are wrapped in `DB::transaction()`. Status transitions validate current status before proceeding. Separate methods for each transition (create, complete, cancel).

**When to use:** Any operation that modifies multiple records or changes status.

**Backend example** (VERIFIED: `backend/app/Services/CalibrationService.php:63-94`):
```php
public function complete(MaintenanceOrder $order, array $data): MaintenanceOrder
{
    return DB::transaction(function () use ($order, $data) {
        if (!$order->status->canTransitionTo(MaintenanceStatus::Completed)) {
            throw new MaintenanceException(
                'Apenas ordens em andamento podem ser concluídas.'
            );
        }

        $completedAt = isset($data['completed_at'])
            ? Carbon::parse($data['completed_at'])
            : now();

        $nextDueAt = null;
        if ($order->type === MaintenanceType::Preventive) {
            $nextDueAt = $this->calculateNextDue($completedAt, $order->interval_value, $order->interval_unit);
        }

        $order->update([
            'status' => MaintenanceStatus::Completed,
            'completed_at' => $completedAt,
            'resolution' => $data['resolution'] ?? null,
            'time_spent' => $data['time_spent'] ?? null,
            'cost' => $data['cost'] ?? null,
            'next_due_at' => $nextDueAt,
        ]);

        // Attach parts from inventory (pivot)
        if (!empty($data['parts'])) {
            foreach ($data['parts'] as $part) {
                $order->parts()->create([
                    'inventory_item_id' => $part['inventory_item_id'],
                    'quantity' => $part['quantity'],
                ]);
            }
        }

        // Auto-create next preventive order (D-11)
        if ($order->type === MaintenanceType::Preventive && $nextDueAt) {
            $this->createNextPreventive($order, $nextDueAt);
        }

        return $order->fresh(['equipment', 'parts.item']);
    });
}
```

### Pattern 3: Frontend Service Module (from VerificationService)

**What:** Each module has a service file that wraps all Axios API calls. Functions are typed with TypeScript interfaces.

**When to use:** Every module that communicates with the backend API.

**Frontend example** (VERIFIED: `frontend/src/modules/verifications/services/VerificationService.ts:1-31`):
```typescript
export const maintenanceService = {
    async list(params?: Record<string, any>): Promise<{ data: MaintenanceOrder[]; meta: any }> {
        const { data } = await api.get('/maintenance', { params })
        return data
    },

    async getById(id: string): Promise<MaintenanceOrder> {
        const { data } = await api.get(`/maintenance/${id}`)
        return data.data
    },

    async create(data: OpenMaintenanceFormData): Promise<MaintenanceOrder> {
        const response = await api.post('/maintenance', data)
        return response.data.data
    },

    async complete(id: string, data: CompleteMaintenanceFormData): Promise<MaintenanceOrder> {
        const response = await api.post(`/maintenance/${id}/complete`, data)
        return response.data.data
    },

    async cancel(id: string): Promise<MaintenanceOrder> {
        const response = await api.post(`/maintenance/${id}/cancel`)
        return response.data.data
    },

    async getHistoryByEquipment(equipmentId: string, params?: { page?: number; per_page?: number }): Promise<{ data: MaintenanceOrder[]; meta: any }> {
        const { data } = await api.get(`/equipments/${equipmentId}/maintenance`, { params })
        return data
    },
}
```

### Pattern 4: History Tab with Event-Driven Refresh (from VerificationHistoryTab)

**What:** A reusable tab component embedded in EquipmentDetailPage that displays paginated history. Refreshes when a window event `maintenance-saved` fires.

**When to use:** When displaying equipment-specific history as a tab in EquipmentDetailPage.

**Frontend example** (VERIFIED: `frontend/src/modules/verifications/components/VerificationHistoryTab.vue:1-215`):

Key elements:
- Props: `equipmentId: string`
- Emits: `'start-maintenance'`
- Lifecycle: `onMounted` fetches data, listens for `maintenance-saved` event
- DataTable with lazy pagination, expandable rows
- Empty state with icon when no records
- Permission-gated action button (`hasPermission('manutencoes.create')`)

### Pattern 5: Permission Gating (from CalibrationController)

**What:** Two-layer permission enforcement — static middleware on controller actions + `v-if` in templates.

**When to use:** Every module that needs role-based access control.

**Backend** (VERIFIED: `backend/app/Http/Controllers/Api/V1/CalibrationController.php:25-37`):
```php
public static function middleware(): array
{
    return [
        ['middleware' => 'auth:sanctum', 'options' => ['only' => ['index', 'show', 'store', 'update', 'destroy', 'complete', 'cancel']]],
        ['middleware' => 'permission:manutencoes.view', 'options' => ['only' => ['index', 'show', 'byEquipment']]],
        ['middleware' => 'permission:manutencoes.create', 'options' => ['only' => ['store']]],
        ['middleware' => 'permission:manutencoes.edit', 'options' => ['only' => ['update', 'destroy']]],
        ['middleware' => 'permission:manutencoes.concluir', 'options' => ['only' => ['complete', 'cancel']]],
    ];
}
```

**Frontend** (VERIFIED: `frontend/src/modules/calibrations/pages/CalibrationListPage.vue:12-16, 157-163`):
```vue
<Button v-if="authStore.hasPermission('manutencoes.create')" ... />
<Button v-if="authStore.hasPermission('manutencoes.edit') && data.status === 'open'" ... />
```

### Pattern 6: Pivot Model for Parts (from EquipmentLoan)

**What:** A custom pivot model with UUIDs, belonging to both `MaintenanceOrder` and `InventoryItem`. Used when `maintenance_order_parts` needs additional fields beyond FK pairs.

**When to use:** Many-to-many relationships with extra data (quantity, etc.).

**Backend example** (VERIFIED: `backend/app/Models/EquipmentLoan.php:1-43`):
```php
class MaintenanceOrderPart extends Pivot
{
    use HasUuids;

    protected $table = 'maintenance_order_parts';

    protected $fillable = [
        'maintenance_order_id', 'inventory_item_id', 'quantity',
    ];

    public function order()
    {
        return $this->belongsTo(MaintenanceOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
```

### Anti-Patterns to Avoid

- **Direct status updates in controller** — always go through Service layer with validation (see `CalibrationController::update` at line 99-103 validates status before update)
- **Forgetting `$auditExclude` on model** — every model must exclude `updated_by` and `deleted_by` from audit logging (see `Calibration.php:34`)
- **Missing `deleted_by` in soft delete** — set `$model->deleted_by = auth()->id()` before `$model->delete()` (see `CalibrationController::destroy` lines 120-122)
- **Not reloading relationships after mutations** — always call `->fresh()` or `->load()` after service operations (see `CalibrationService::complete` line 92)

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Status state machine | Custom validation | PHP Enum with `canTransitionTo()` | Already established in `CalibrationStatus.php:37-44` — prevents invalid transitions |
| Date/interval math | Manual date arithmetic | `Carbon::addMonths()`, `addDays()`, `addHours()` | Already established in `CalibrationService.php:129-137` |
| Paginated list with filters | Custom pagination | Laravel `paginate()` + Vue DataTable lazy mode | Already established in all CRUD modules |
| File structure scaffolding | Creating empty directories | Create on demand | ARCHITECTURE.md:177 identifies empty scaffolds as anti-pattern |

## Common Pitfalls

### Pitfall 1: Wrong next_due_at for corrective maintenance
**What goes wrong:** Creating a next_due_at for corrective maintenance when only preventive should have it.
**Why it happens:** Corrective maintenance is unplanned — there's no "next" due date.
**How to avoid:** Only calculate `next_due_at` when `type === 'preventive'`, as established in D-11.
**Warning signs:** Corrective orders showing a next_due_at value.

### Pitfall 2: Not reconciling inventory when consuming parts
**What goes wrong:** Parts are deducted from `maintenance_order_parts` but inventory balance is not updated.
**Why it happens:** The pivot table tracks consumption but doesn't create inventory movement records.
**How to avoid:** Create an `InventoryMovement` record for each part consumed, following the `InventoryMovementService` pattern (movement type: "out").
**Warning signs:** Parts balance doesn't change when maintenance order is completed.

### Pitfall 3: Missing parts pivot when main table is created
**What goes wrong:** Parts are added via the close dialog but the pivot table doesn't exist yet — migration must create it.
**Why it happens:** D-05 specifies the pivot table, but it's easy to forget in migration planning.
**How to avoid:** Create BOTH `maintenance_orders` and `maintenance_order_parts` in the same migration file, similar to `2026_07_25_000001_create_calibrations_tables.php` which creates `calibrations` + `calibration_certificates`.

### Pitfall 4: Notification dispatched before order is fully persisted
**What goes wrong:** The `MaintenanceOrderCreated` notification fires before the order is committed to the database.
**Why it happens:** Service wraps creation in a transaction; notification may be sent inside the transaction.
**How to avoid:** Dispatch the notification AFTER the transaction commits, using `DB::afterCommit()` or in the controller after the service returns.

## Code Examples

### Migration — Create maintenance tables (from Calibration migration pattern)

Source: `backend/database/migrations/2026_07_25_000001_create_calibrations_tables.php:1-75` [VERIFIED]

```php
Schema::create('maintenance_orders', function (Blueprint $table) {
    $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
    $table->foreignUuid('equipment_id')->constrained('equipments');
    $table->string('type', 20);                                      // preventive|corrective
    $table->string('status', 20)->default('open');                   // open|in_progress|completed|cancelled
    $table->string('priority', 20)->default('medium');               // low|medium|high|critical
    $table->text('description');
    $table->date('scheduled_date');
    $table->foreignUuid('assigned_to')->nullable()->constrained('users');
    $table->foreignUuid('opened_by')->nullable()->constrained('users');
    $table->timestamp('completed_at')->nullable();
    $table->text('resolution')->nullable();
    $table->decimal('time_spent', 10, 2)->nullable();                // hours
    $table->decimal('cost', 12, 2)->nullable();
    $table->integer('interval_value')->nullable();                   // for preventive scheduling
    $table->string('interval_unit', 10)->nullable();                 // months|days|hours
    $table->timestamp('next_due_at')->nullable();
    $table->text('notes')->nullable();
    $table->foreignUuid('created_by')->nullable()->constrained('users');
    $table->uuid('updated_by')->nullable();
    $table->uuid('deleted_by')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['equipment_id']);
    $table->index(['status']);
    $table->index(['type']);
    $table->index(['priority']);
    $table->index(['scheduled_date']);
    $table->index(['next_due_at']);
    $table->index(['status', 'next_due_at']);
    $table->index(['equipment_id', 'status']);
});

Schema::create('maintenance_order_parts', function (Blueprint $table) {
    $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
    $table->foreignUuid('maintenance_order_id')->constrained('maintenance_orders')->onDelete('cascade');
    $table->foreignUuid('inventory_item_id')->constrained('inventory_items');
    $table->integer('quantity')->default(1);
    $table->timestamps();

    $table->index(['maintenance_order_id']);
    $table->index(['inventory_item_id']);
});
```

### Equipment Model — Add maintenance relationships (from existing Verification pattern)

Source: `backend/app/Models/Equipment.php:62-68` [VERIFIED]

```php
public function maintenanceOrders()
{
    return $this->hasMany(MaintenanceOrder::class);
}

public function lastMaintenance()
{
    return $this->hasOne(MaintenanceOrder::class)->latestOfMany('completed_at');
}
```

### EquipmentDetailPage — Add tab integration (from existing Tab pattern)

Source: `frontend/src/modules/equipment/pages/EquipmentDetailPage.vue:31-71` [VERIFIED]

```vue
<Tab value="6" v-if="authStore.hasPermission('manutencoes.view')">Manutenções</Tab>
<!-- ... -->
<TabPanel value="6" v-if="authStore.hasPermission('manutencoes.view')">
  <MaintenanceHistoryTab
    v-if="equipment"
    :equipmentId="equipment.id"
    @start-maintenance="startMaintenance"
  />
</TabPanel>
```

Also add the import:
```typescript
import MaintenanceHistoryTab from '@/modules/maintenance/components/MaintenanceHistoryTab.vue'
```

### Status Flow Logic (from CalibrationStatus + D-02)

```php
enum MaintenanceStatus: string
{
    case Open = 'open';           // initial state
    case InProgress = 'in_progress';  // technician assigned, work started
    case Completed = 'completed';  // terminal — work finished
    case Cancelled = 'cancelled';  // terminal — cancelled

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberta',
            self::InProgress => 'Em Andamento',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }

    // Transitions: open → in_progress → completed; open/in_progress → cancelled
    public function canTransitionTo(MaintenanceStatus $target): bool
    {
        return match ($this) {
            self::Open => in_array($target, [self::InProgress, self::Cancelled], true),
            self::InProgress => in_array($target, [self::Completed, self::Cancelled], true),
            self::Completed => false,
            self::Cancelled => false,
        };
    }
}
```

### Interval calculation (from CalibrationService)

Source: `backend/app/Services/CalibrationService.php:129-137` [VERIFIED]

```php
private function calculateNextDue(Carbon $completedAt, int $value, string $unit): Carbon
{
    return match ($unit) {
        'months' => $completedAt->copy()->addMonths($value),
        'days' => $completedAt->copy()->addDays($value),
        'hours' => $completedAt->copy()->addHours($value),
        default => $completedAt->copy()->addMonths($value),
    };
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| VBA/Excel development | Vue 3 + Laravel 13 + PostgreSQL | Phase 0 → Phase 1 decision | Full stack web architecture |
| Direct table access via modals | REST API → Service layer → Repository | Phase 5-8 evolution | Consistent transactional patterns |

**Deprecated/outdated:** None for this phase.

## Assumptions Log

> No claims in this research are tagged `[ASSUMED]`. All patterns and conventions documented here were verified against the codebase.

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| — | (none) | — | — |

## Open Questions

1. **Inventory movement on parts consumption**
   - What we know: D-05 specifies a pivot table `maintenance_order_parts` linking to `inventory_items`
   - What's unclear: Should consuming a part from inventory create a corresponding `InventoryMovement` record (type "out") to keep inventory balances accurate? The existing `InventoryMovementService` pattern suggests yes, but D-19 explicitly says "no scheduled command for this phase" — inventory reconciliation may be separate
   - Recommendation: Implement pivot recording only (quantity deduction tracking). Inventory movement integration can be deferred if it adds scope complexity. **Flag for planner decision.**

2. **Assigned_to vs opened_by on creation**
   - What we know: D-06/D-07 specifies opening form has equipment, type, priority, description, scheduled_date — NO technician
   - What's unclear: When does `assigned_to` get set? Possibly when status transitions to `in_progress`
   - Recommendation: `opened_by` defaults to `auth()->id()`. `assigned_to` is set by a separate "assign" action or via edit. **Leave assigned_to nullable, don't require on creation.**

## Environment Availability

> Skip this section — the phase has no external dependencies beyond what the project stack already requires (Laravel, Vue, PostgreSQL, Redis). All services run in Docker. Code/config-only changes.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit ^12.5 (composer.json) |
| Config file | `backend/phpunit.xml` |
| Quick run command | `cd backend && php artisan test --filter=Maintenance` |
| Full suite command | `cd backend && php artisan test` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| MAINT-01 | Create maintenance order (open → persisted) | unit | `php artisan test --filter=MaintenanceServiceTest::test_can_create_order` | ❌ Wave 0 |
| MAINT-01 | Status transition: open → in_progress → completed | unit | `php artisan test --filter=MaintenanceServiceTest::test_status_transitions` | ❌ Wave 0 |
| MAINT-02 | Preventive auto-creates next order on complete | unit | `php artisan test --filter=MaintenanceServiceTest::test_auto_create_next_preventive` | ❌ Wave 0 |
| MAINT-02 | History returned by equipment scope | unit | `php artisan test --filter=MaintenanceOrderTest::test_history_by_equipment` | ❌ Wave 0 |
| D-05 | Parts pivot saves correctly on completion | unit | `php artisan test --filter=MaintenanceServiceTest::test_attach_parts_on_complete` | ❌ Wave 0 |
| D-08 | Notification dispatched on create | unit | `php artisan test --filter=MaintenanceOrderControllerTest::test_notification_on_create` | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --filter=Maintenance`
- **Per wave merge:** `php artisan test`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `backend/tests/Unit/Services/MaintenanceServiceTest.php` — covers all 5 tests above
- [ ] `backend/tests/Feature/Http/Controllers/Api/V1/MaintenanceOrderControllerTest.php` — HTTP-level tests (auth, permissions, validation)
- [ ] `backend/database/factories/MaintenanceOrderFactory.php` — factory for seeding

## Security Domain

> `security_enforcement` is enabled by default (no explicit `false` in config.json).

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes | Sanctum session-based auth (existing) |
| V3 Session Management | yes | Sanctum cookie-based sessions (existing) |
| V4 Access Control | yes | Static middleware `permission:manutencoes.*` on controller |
| V5 Input Validation | yes | Form Request validation (Store/Update/CompleteMaintenanceOrderRequest) |
| V6 Cryptography | no | No sensitive data in maintenance orders |

### Known Threat Patterns for Laravel/Vue Stack

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Unauthorized status transition | Tampering | `MaintenanceStatus::canTransitionTo()` in Service layer |
| Mass assignment on parts | Tampering | `$fillable` on `MaintenanceOrderPart` pivot model |
| IDOR on order access | Information Disclosure | Controller uses `permission:manutencoes.view` gate — implicit model binding shows all orders user has permission to view |
| Deletion without audit | Repudiation | `LogsActivity` trait logs soft deletes with `deleted_by` |

## Sources

### Primary (HIGH confidence) — Codebase files read and patterns verified
- `backend/app/Models/Calibration.php` — Model with enum, scopes, accessors, relationships
- `backend/app/Models/Equipment.php` — Existing relationships (verifications, photos)
- `backend/app/Models/Loan.php` — Status enum, scope pattern, computed attributes, pivot
- `backend/app/Models/EquipmentLoan.php` — Pivot model pattern
- `backend/app/Models/InventoryItem.php` — Scope pattern for filtering
- `backend/app/Models/Verification.php` — Equipment-scoped history pattern
- `backend/app/Services/CalibrationService.php` — Transactional CRUD + interval calculation
- `backend/app/Services/LoanService.php` — Complex transactional service
- `backend/app/Services/VerificationService.php` — Transactional compound record creation
- `backend/app/Http/Controllers/Api/V1/CalibrationController.php` — Controller with static middleware, status validation, try-catch pattern
- `backend/app/Http/Controllers/Api/V1/LoanController.php` — Controller with action methods (activate, cancel, returnItem)
- `backend/app/Http/Controllers/Api/V1/VerificationController.php` — Controller with notification dispatch, byEquipment endpoint
- `backend/app/Enums/CalibrationStatus.php` — Backed string enum with label() and canTransitionTo()
- `backend/app/Exceptions/CalibrationException.php` — Domain exception pattern
- `backend/app/Http/Requests/StoreCalibrationRequest.php` — FormRequest with validation + Portuguese messages
- `backend/app/Http/Requests/CompleteCalibrationRequest.php` — Closure form validation
- `backend/app/Http/Resources/CalibrationResource.php` — Resource with whenLoaded pattern
- `backend/app/Http/Resources/CalibrationCollection.php` — Collection resource
- `backend/app/Notifications/CalibrationDue.php` — Marker notification class
- `backend/app/Notifications/ToleranceExceeded.php` — Notification with dynamic dispatch
- `backend/app/Traits/LogsActivity.php` — Audit trail trait
- `backend/database/migrations/2026_07_25_000001_create_calibrations_tables.php` — Migration with UUIDs, FKs, indexes
- `backend/database/seeders/RolePermissionSeeder.php` — Permission seeding with manutencoes.* placeholder
- `backend/database/seeders/CalibrationSeeder.php` — Seeder with factory states
- `frontend/src/modules/calibrations/pages/CalibrationListPage.vue` — List page with filters, DataTable, dialogs
- `frontend/src/modules/calibrations/pages/CalibrationDetailPage.vue` — Detail page with tabs, action buttons
- `frontend/src/modules/calibrations/components/CalibrationCreateDialog.vue` — Create dialog with v-model pattern
- `frontend/src/modules/calibrations/components/CalibrationConcludeDialog.vue` — Action dialog pattern
- `frontend/src/modules/calibrations/store/CalibrationStore.ts` — Pinia store with CRUD
- `frontend/src/modules/calibrations/types/calibration.ts` — TypeScript types + constants
- `frontend/src/modules/verifications/services/VerificationService.ts` — Service module pattern
- `frontend/src/modules/verifications/components/VerificationHistoryTab.vue` — History tab pattern
- `frontend/src/modules/equipment/pages/EquipmentDetailPage.vue` — Tab integration pattern
- `frontend/src/types/navigation.ts` — Sidebar structure (Manutenções already scaffolded)
- `frontend/src/router/routes.ts` — Route pattern (Manutenções placeholder existing)
- `frontend/src/services/api.ts` — Axios instance with XSRF handling

### Secondary (MEDIUM confidence)
- Official Laravel 13 docs — Sanctum middleware pattern, Form Request validation
- PrimeVue documentation — DataTable lazy mode, Dialog modal, TabPanel usage

### Tertiary (LOW confidence)
None — all patterns verified against actual codebase files.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — verified against 18+ source files
- Architecture: HIGH — patterns consistent across Calibrações, Aferições, Empréstimos
- Pitfalls: HIGH — derived from actual code review of existing modules

**Research date:** 2026-07-25
**Valid until:** 2026-08-25 (standard 30-day window for stable patterns)
