# Phase 09: Aferições — Research

**Researched:** 2026-07-25
**Domain:** Verification/Inspection module (operational checks per equipment)
**Confidence:** HIGH

## Summary

Phase 09 implements the "Aferições" (Verification/Inspection) module — daily/weekly/shift operational checks performed by operators on equipment, without certificates or costs. This is distinct from Calibrações (Phase 08), which are scheduled events generating certificates via external labs.

The implementation follows the established compound-CRUD pattern (Calibrações + Certificates → Aferições + Param values) and the notification pattern (CalibrationDue → ToleranceExceeded). The key difference is dynamic form fields: verification parameters are loaded from `verification_templates` (FK to category), and tolerance auto-calculation runs server-side during save.

The frontend scaffold (`modules/verifications/`) already exists with empty directories. The sidebar navigation entry (`pi pi-check-circle`, `/verifications`, `afericoes.view` permission) is already registered in `navigation.ts`. The route currently points to `PlaceholderPage.vue`.

**Primary recommendation:** Follow the exact Calibrações module architecture (Service → Controller → Request → Resource → Store → Types → Service → Pages) with the addition of a dynamic-template-params pattern for the verification form.

## User Constraints (from CONTEXT.md)

*No specific Phase 09 CONTEXT.md exists. The following are extracted from the project's overarching architecture decisions in the root CONTEXT.md and from upstream discussions that established the module boundary:*

### Locked Decisions
- **Stack:** Laravel 13 backend (API REST, Sanctum auth), Vue 3 + PrimeVue 5 + Pinia + Vue Router frontend, PostgreSQL database
- **Module structure:** Each module has `types/`, `services/`, `store/`, `components/`, `pages/`, `routes/` subdirectories
- **Architecture:** Monorepo with `backend/` and `frontend/` top-level directories
- **Permission pattern:** Backend middleware `permission:{module}.{action}` + frontend `authStore.hasPermission()` checks
- **DB naming:** UUID PKs, `foreignUuid`, `timestamps()` + `softDeletes()`, composite indexes for frequent queries
- **Code quality:** Production code only — no examples, no scaffolding stubs

### the agent's Discretion
- Exact field naming for `verification_frequency` columns on equipment
- Whether `verification_params` values are stored as `numeric` or `text` (D-04 says "TEXT" for flexibility)
- UI layout of the verification form (dynamic param fields)
- Whether to implement pending verification detection via computed query or scheduled task
- Which PrimeVue components to use for dynamic field rendering

### Deferred Ideas (OUT OF SCOPE)
- Offline/PWA sync for verifications
- Mobile app for verifications
- Statistical process control charts

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Template definition (CRUD) | Backend API | Database | Templates are lookup data — defined once, referenced by category |
| Verification event registration | Backend API | — | Transactional save with tolerance auto-calculation (must be server-side) |
| Tolerance calculation | Backend Service | — | D-05: result calculated on save, not client-side |
| Pending verification detection | Backend API | Database query | Computed query: equipment with overdue/last verification + frequency |
| Dynamic param form | Frontend (Vue) | Backend API | Template params loaded by category → form fields rendered dynamically |
| Tolerance exceeded alert | Backend Service | Notification | Synchronous in-app notification (D-12) |
| Verification history tab | Frontend (Vue) | Backend API | Tab in EquipmentDetailPage, loads by equipment_id |
| Permission gating | Backend middleware + Frontend store | — | afericoes.{view,create,edit} |

## Standard Stack

### Core (following established patterns)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel Framework | ^13.8 | Backend API, migrations, ORM | Project standard — `backend/composer.json` |
| Laravel Sanctum | ^4.0 | API token authentication | Project standard |
| Vue 3 | ^3.5 | Frontend framework | Project standard |
| PrimeVue | ^5.0 | UI component library | Project standard — `frontend/package.json` |
| Pinia | ^4.0 | Frontend state management | Project standard |
| Vue Router | ^5.2 | Frontend routing | Project standard |
| Axios | ^1.18 | HTTP client | Project standard |

### Backend Files to Create

| File | Purpose | Pattern Reference |
|------|---------|-------------------|
| `app/Enums/VerificationResult.php` | passed/failed/warning enum | `backend/app/Enums/CalibrationStatus.php` |
| `app/Models/VerificationTemplate.php` | Template model (per category) | `backend/app/Models/CalibrationCertificate.php` |
| `app/Models/Verification.php` | Verification event model | `backend/app/Models/Calibration.php` |
| `app/Models/VerificationParam.php` | Param result model | `backend/app/Models/CalibrationCertificate.php` |
| `app/Exceptions/VerificationException.php` | Custom exception | `backend/app/Exceptions/CalibrationException.php` |
| `app/Services/VerificationService.php` | Business logic | `backend/app/Services/CalibrationService.php` |
| `app/Http/Controllers/Api/V1/VerificationController.php` | API controller | `backend/app/Http/Controllers/Api/V1/CalibrationController.php` |
| `app/Http/Requests/StoreVerificationRequest.php` | Create/update validation | `backend/app/Http/Requests/StoreCalibrationRequest.php` |
| `app/Http/Resources/VerificationResource.php` | JSON resource | `backend/app/Http/Resources/CalibrationResource.php` |
| `app/Http/Resources/VerificationCollection.php` | Paginated collection | `backend/app/Http/Resources/CalibrationCollection.php` |
| `app/Notifications/ToleranceExceeded.php` | Alert notification | `backend/app/Notifications/CalibrationDue.php` |
| Migration 1: `create_verification_templates_table.php` | — | `backend/database/migrations/2026_07_25_000001_create_calibrations_tables.php` |
| Migration 2: `create_verifications_table.php` | — | same |
| Migration 3: `add_verification_frequency_to_equipments.php` | — | `backend/database/migrations/0001_01_01_000000...` |

### Frontend Files to Create / Modify

| File | Purpose | Pattern Reference |
|------|---------|-------------------|
| `frontend/src/modules/verifications/types/verification.ts` | TypeScript types | `frontend/src/modules/calibrations/types/calibration.ts` |
| `frontend/src/modules/verifications/services/VerificationService.ts` | API service | `frontend/src/modules/calibrations/services/CalibrationService.ts` |
| `frontend/src/modules/verifications/store/VerificationStore.ts` | Pinia store | `frontend/src/modules/calibrations/store/CalibrationStore.ts` |
| `frontend/src/modules/verifications/pages/VerificationPendingPage.vue` | Pending list | `frontend/src/modules/calibrations/pages/CalibrationListPage.vue` |
| `frontend/src/modules/verifications/components/VerificationFormDialog.vue` | Dynamic form | `frontend/src/modules/calibrations/components/CalibrationCreateDialog.vue` |
| `frontend/src/modules/verifications/components/VerificationHistoryTab.vue` | History tab | `frontend/src/modules/calibrations/components/CalibrationInfoTab.vue` |
| `frontend/src/modules/equipment/components/VerificationHistoryTab.vue` | Tab embed | `frontend/src/components/...` |

### Modified Existing Files

| File | Change | Reason |
|------|--------|--------|
| `backend/app/Models/Equipment.php` | Add `verification_frequency`, `verification_frequency_unit`, `verifications()` hasMany | D-06/07 |
| `backend/app/Models/Category.php` | Add `verificationTemplates()` hasMany | D-01 |
| `backend/routes/api.php` | Add verification routes | Standard routing |
| `frontend/src/router/routes.ts` | Replace PlaceholderPage with VerificationPendingPage | D-18 |
| `frontend/src/modules/equipment/pages/EquipmentDetailPage.vue` | Add Aferições tab (conditional) | D-14/15/16/19 |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Separate `VerificationParam` model | JSON column on `verifications` table | Separate model enables indexing, querying, and cleaner API — JSON would be simpler but less flexible |
| Dedicated `VerificationTemplate` model | No templates — hardcode params per form | Templates enable per-category customization without code changes |

## Package Legitimacy Audit

> This phase uses only existing project dependencies. No new external packages are required.

| Package | Registry | Age | Downloads | Source Repo | Verdict | Disposition |
|---------|----------|-----|-----------|-------------|---------|-------------|
| laravel/framework ^13.8 | Packagist | ~18 mo | massive | laravel/framework | OK | Already installed |
| primevue ^5.0 | npm | ~12 mo | ~500k/wk | primefaces/primevue | OK | Already installed |
| pinia ^4.0 | npm | ~6 mo | ~3M/wk | vuejs/pinia | OK | Already installed |

**Packages removed due to SLOP verdict:** none
**Packages flagged as suspicious:** none

## Architecture Patterns

### System Architecture Diagram

```
┌───────────────┐     ┌──────────────────────────────────────────────┐
│               │     │              Backend API (Laravel)           │
│   Frontend    │     │                                              │
│   (Vue 3)     │     │  ┌──────────────┐  ┌──────────────────────┐  │
│               │     │  │Permissions    │  │VerificationController │  │
│  PendingList  │─────┼─>│Middleware     │──>│index, show, store    │  │
│  Page         │     │  │(auth:sanctum, │  │                      │  │
│               │     │  │ permission)   │  └──────────┬───────────┘  │
│  DetailPage   │─────┼─>│              │             │              │
│  (Equipment)  │     │  └──────────────┘              ▼              │
│               │     │                       ┌──────────────┐       │
│  FormDialog   │     │                       │Verification  │───────┼──> ToleranceExceeded
│  (Dynamic     │     │                       │Service       │       │    Notification
│   Params)     │     │  ┌────────────────┐   │              │       │
│               │     │  │StoreVerification│  │ 1. Validate   │       │
│  HistoryTab   │     │  │Request          │  │ 2. Create     │       │
│               │     │  │(FormRequest)    │  │ 3. Auto-calc  │       │
│               │     │  └────────────────┘  │    tolerance   │       │
└───────────────┘     │                      │ 4. Save params │       │
                      │                      └──────┬─────────┘       │
                      │                             ▼                 │
                      │                      ┌──────────────┐        │
                      │                      │  PostgreSQL   │        │
                      │                      │ ┌───────────┐│        │
                      │                      │ │templates  ││        │
                      │                      │ │verifications││       │
                      │                      │ │params     ││        │
                      │                      │ │equipments  ││        │
                      │                      │ └───────────┘│        │
                      │                      └──────────────┘        │
                      └──────────────────────────────────────────────┘
```

**Data flow (primary use case — Register Verification):**
1. User navigates to Pending Verifications list or Equipment Detail → Aferições tab
2. Frontend loads pending equipment or history
3. User clicks "Aferir" → VerificationFormDialog opens
4. Dialog fetches verification_templates by equipment's category_id
5. Dynamic form fields rendered (one per template param, showing name, unit, tolerance range)
6. User fills values, submits
7. Backend validates (StoreVerificationRequest), VerificationsService.create() in transaction:
   a. Creates `verifications` record
   b. Creates `verification_params` records with auto-calculated result (passed/failed/warning)
   c. If any param result is "failed", fires ToleranceExceeded notification synchronously
8. Frontend shows success toast, refreshes list

### Recommended Project Structure

```
# Backend additions
backend/
├── app/
│   ├── Enums/
│   │   └── VerificationResult.php        # NEW: passed, failed, warning
│   ├── Exceptions/
│   │   └── VerificationException.php     # NEW: custom exception
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   └── VerificationController.php # NEW: CRUD + pending
│   │   ├── Requests/
│   │   │   └── StoreVerificationRequest.php # NEW: validation
│   │   └── Resources/
│   │       ├── VerificationResource.php    # NEW: JSON transform
│   │       └── VerificationCollection.php  # NEW: paginated collection
│   ├── Models/
│   │   ├── Equipment.php                 # MODIFY: add verification_frequency, verifications()
│   │   ├── Category.php                  # MODIFY: add verificationTemplates()
│   │   ├── VerificationTemplate.php       # NEW: template model
│   │   ├── Verification.php               # NEW: verification event model
│   │   └── VerificationParam.php          # NEW: param result model
│   ├── Notifications/
│   │   └── ToleranceExceeded.php          # NEW: alert notification
│   └── Services/
│       └── VerificationService.php        # NEW: business logic
├── database/
│   └── migrations/
│       ├── XXXX_create_verification_templates_table.php
│       ├── XXXX_create_verifications_table.php
│       └── XXXX_add_verification_frequency_to_equipments.php
└── routes/
    └── api.php                           # MODIFY: add verification routes

# Frontend additions
frontend/src/modules/verifications/
├── types/
│   └── verification.ts                  # NEW: interfaces, constants
├── services/
│   └── VerificationService.ts            # NEW: API calls
├── store/
│   └── VerificationStore.ts              # NEW: Pinia store
├── pages/
│   ├── VerificationPendingPage.vue       # NEW: pending list
│   └── VerificationHistoryPage.vue       # NEW: history (standalone)
├── components/
│   ├── VerificationFormDialog.vue        # NEW: dynamic param form
│   └── VerificationHistoryTab.vue        # NEW: embeddable tab

frontend/src/modules/equipment/
└── components/
    └── VerificationHistoryTab.vue        # NEW: tab for EquipmentDetailPage

# Modified existing files
frontend/src/router/routes.ts            # MODIFY: replace placeholder
frontend/src/modules/equipment/pages/
    └── EquipmentDetailPage.vue          # MODIFY: add tab
```

### Pattern 1: Dynamic Template Params Form (Verification Form)

**What:** A form that renders input fields dynamically based on `verification_templates` loaded for the selected equipment's category. Each template param becomes an input field showing the parameter name, unit, and tolerance range.

**When to use:** EquipmentDetailPage → Aferições tab → "Aferir" button, or PendingVerificationList → click item

**Example pattern (component structure):**
```vue
<!-- VerificationFormDialog.vue — conceptual structure -->
<template>
  <Dialog header="Nova Aferição" :visible="visible" modal style="width: 700px">
    <!-- Equipment selector (or pre-filled from context) -->
    <Select v-model="form.equipment_id" :options="equipmentOptions"
      @change="loadTemplates" optionLabel="label" optionValue="value" />

    <!-- Dynamic param fields -->
    <div v-for="tpl in templates" :key="tpl.id" class="field">
      <label>{{ tpl.parameter_name }}
        <small v-if="tpl.unit">({{ tpl.unit }})</small>
        <small v-if="tpl.tolerance_min !== null || tpl.tolerance_max !== null">
          — Tolerância: {{ tpl.tolerance_min }} ~ {{ tpl.tolerance_max }}
        </small>
      </label>
      <InputText v-model="params[tpl.id]" type="number" step="any"
        :placeholder="`Valor (${tpl.unit || '—'})`" />
    </div>
  </Dialog>
</template>
```

### Pattern 2: Compound CRUD with Auto-Calculation (Service Layer)

**What:** The VerificationService.create() method wraps the entire save in a DB::transaction(), creating the verification record, creating verification_params with server-side calculated results, and conditionally firing the tolerance-exceeded notification.

**When to use:** All verification saves (pending list or detail page)

**Example pattern (Service method — follows `backend/app/Services/CalibrationService.php`):**
```php
public function create(array $data): Verification
{
    return DB::transaction(function () use ($data) {
        // 1. Create the verification event
        $verification = Verification::create([
            'equipment_id' => $data['equipment_id'],
            'verified_at' => $data['verified_at'] ?? now(),
            'operator_id' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);

        // 2. Load templates to get tolerance ranges
        $templates = VerificationTemplate::where('equipment_category_id',
            $verification->equipment->category_id
        )->get()->keyBy('id');

        // 3. Create params with auto-calculated result
        $hasFailed = false;
        foreach ($data['params'] as $templateId => $value) {
            $template = $templates->get($templateId);
            $result = $this->calculateResult($value, $template);
            if ($result === VerificationResult::Failed) {
                $hasFailed = true;
            }
            VerificationParam::create([
                'verification_id' => $verification->id,
                'template_id' => $templateId,
                'value' => $value,
                'result' => $result,
                'notes' => $data['param_notes'][$templateId] ?? null,
            ]);
        }

        // 4. Alert if tolerance exceeded (D-11/12/13 — synchronous)
        if ($hasFailed) {
            $this->notifyToleranceExceeded($verification);
        }

        return $verification->load(['equipment', 'params.template']);
    });
}

private function calculateResult(mixed $value, VerificationTemplate $template): VerificationResult
{
    if ($template->tolerance_min !== null && $value < $template->tolerance_min) {
        return VerificationResult::Failed;
    }
    if ($template->tolerance_max !== null && $value > $template->tolerance_max) {
        return VerificationResult::Failed;
    }
    return VerificationResult::Passed;
}
```

### Pattern 3: Pending Verifications Detection

**What:** A query that finds equipment which should have been verified based on `verification_frequency` but hasn't been verified within the expected timeframe. This can be computed on-the-fly (API query with LEFT JOIN and date math).

**When to use:** `GET /api/v1/verifications/pending` endpoint

**Example pattern:**
```php
// In VerificationService or a scope
public function getPendingVerifications(): Collection
{
    return Equipment::query()
        ->whereNotNull('verification_frequency')
        ->where(function ($query) {
            $query->whereDoesntHave('verifications')
                ->orWhereHas('verifications', function ($q) {
                    $q->select('equipment_id')
                      ->groupBy('equipment_id')
                      ->havingRaw('MAX(verified_at) < NOW() - CASE equipment.verification_frequency
                          WHEN \'daily\' THEN INTERVAL \'1 day\'
                          WHEN \'weekly\' THEN INTERVAL \'7 days\'
                          WHEN \'shift\' THEN INTERVAL \'12 hours\'
                      END');
                });
        })
        ->with(['category', 'lastVerification'])
        ->get();
}
```

*Note: The exact SQL depends on the frequency unit storage approach. Alternative: store `verification_frequency_hours` (int) for simpler date math.*

### Anti-Patterns to Avoid

- **Client-side tolerance calculation:** Results MUST be calculated server-side (D-05) — never trust client-computed results
- **Batch notification:** D-12 says immediate (synchronous), not queued — use direct DB insert in the transaction
- **Static form fields:** Don't hardcode verification params — always load from templates dynamically
- **Mixing verificações with calibrações:** Keep separate models, controllers, and permissions — they have fundamentally different lifecycle semantics

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| API authentication | Custom auth | Laravel Sanctum (already installed) | Battle-tested, project standard |
| Permission enforcement | Manual checks | Laravel permission middleware (already configured) | Consistent across modules |
| Frontend HTTP client | Raw fetch() | Axios (already configured in `frontend/src/services/api.ts`) | Interceptors, error handling, XSRF |
| UI components | Custom form fields | PrimeVue (already installed) | Consistent theming, a11y, dark mode |
| State management | Component-local state | Pinia store (already configured) | Shared state, persistence |
| Activity logging | Manual logging | `LogsActivity` trait (already built) | Automatic create/update/delete tracking |

**Key insight:** All infrastructure decisions are already made. Phase 09 only needs to implement domain logic following established patterns — no new libraries or architectural changes.

## Common Pitfalls

### Pitfall 1: Dynamic Form Field Reactivity
**What goes wrong:** Input values for dynamic template fields don't update reactively because the `params` object is not properly initialized with reactive properties for each template.
**Why it happens:** Vue 3's `reactive`/`ref` system needs all keys pre-declared for reactivity. Adding new keys after initialization won't trigger re-renders.
**How to avoid:** Use a computed or watcher on templates to build the params object schema. Reset `params` to a fresh reactive object whenever templates change.
**Warning signs:** Form fields show but typing doesn't update the underlying data.

### Pitfall 2: Tolerance Comparison Type Coercion
**What goes wrong:** `value < tolerance_min` fails because one is a string and the other is a number.
**Why it happens:** Input fields return strings; tolerance values from DB are stored as numeric. PHP's loose comparison may handle this, but explicit casting is safer.
**How to avoid:** Cast `tolerance_min` and `tolerance_max` to float in the model or in the calculation method. Cast input values to float before comparison.
**Warning signs:** Comparison returns unexpected results for edge cases (e.g., zero values, negative numbers).

### Pitfall 3: Pending Verification Query Performance
**What goes wrong:** The pending verification query (finding equipment that should have been verified but wasn't) becomes slow with thousands of equipment records.
**Why it happens:** The correlated subquery or complex JOIN with date calculation doesn't use indexes efficiently.
**How to avoid:** Add an index on `verifications.equipment_id` and `verifications.verified_at`. Consider storing `last_verified_at` as a denormalized field on `equipments` updated via observer or trigger.
**Warning signs:** Pending list page load time > 2 seconds.

### Pitfall 4: Synchronous Notification Blocking Response
**What goes wrong:** The ToleranceExceeded notification (D-12, synchronous) slows down the API response if the notification logic is expensive or if there are many recipients.
**Why it happens:** D-12 requires immediate notification (not queued), so the response waits for notification completion.
**How to avoid:** Keep the notification lightweight — direct DB insert only (following the same pattern as `CheckCalibrationDue` inserts into `notifications` table). Do NOT trigger email/SMS in the synchronous path.
**Warning signs:** Verification save requests taking > 1 second.

### Pitfall 5: Tab Conditional Rendering with Permission
**What goes wrong:** The Aferições tab in EquipmentDetailPage renders with a loading state forever or flashes on unauthorized pages.
**Why it happens:** The `v-if` directive for permission gating interacts poorly with PrimeVue Tabs/TabPanel component lifecycle.
**How to avoid:** Use `v-if="authStore.hasPermission('afericoes.view')"` on the Tab element itself, and keep the TabPanel's content eager-loaded but hidden.
**Warning signs:** Tab count mismatch or "Tab N not found" errors in PrimeVue.

## Code Examples

### Backend: Verification Enum
```php
// backend/app/Enums/VerificationResult.php
// Source: Pattern from backend/app/Enums/CalibrationStatus.php

enum VerificationResult: string
{
    case Passed = 'passed';
    case Failed = 'failed';
    case Warning = 'warning';

    public function label(): string
    {
        return match ($this) {
            self::Passed => 'Aprovado',
            self::Failed => 'Reprovado',
            self::Warning => 'Alerta',
        };
    }
}
```

### Backend: Verification Model
```php
// backend/app/Models/Verification.php
// Source: Pattern from backend/app/Models/Calibration.php

class Verification extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    protected $table = 'verifications';

    protected $fillable = [
        'equipment_id', 'verified_at', 'operator_id', 'notes',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function params()
    {
        return $this->hasMany(VerificationParam::class);
    }
}
```

### Backend: Pending Verifications Route & Controller
```php
// In routes/api.php — following the calibrations pattern
Route::apiResource('verifications', VerificationController::class)
    ->only(['index', 'show', 'store']);
Route::prefix('verifications')->group(function () {
    Route::get('pending', [VerificationController::class, 'pending']);
});
Route::prefix('equipments/{equipment}/verifications')->group(function () {
    Route::get('/', [VerificationController::class, 'byEquipment']);
    Route::post('/', [VerificationController::class, 'store']);
});
```

### Frontend: Verification Types
```typescript
// frontend/src/modules/verifications/types/verification.ts
// Source: Pattern from frontend/src/modules/calibrations/types/calibration.ts

export type VerificationResult = 'passed' | 'failed' | 'warning'
export type VerificationFrequency = 'daily' | 'weekly' | 'shift'

export interface VerificationTemplate {
  id: string
  equipment_category_id: string
  parameter_name: string
  unit: string | null
  tolerance_min: number | null
  tolerance_max: number | null
  sort_order: number
}

export interface VerificationParam {
  id: string
  verification_id: string
  template_id: string
  template?: VerificationTemplate
  value: string
  result: VerificationResult
  notes: string | null
}

export interface Verification {
  id: string
  equipment: { id: string; name: string; patrimony_id?: string }
  verified_at: string
  operator: { id: string; name: string } | null
  params: VerificationParam[]
  notes: string | null
  created_at: string
}

export interface VerificationFormData {
  equipment_id: string
  verified_at?: string
  notes?: string
  params: Record<string, string>  // template_id => value
}
```

### Frontend: Dynamic Template Params Loading
```typescript
// Inside VerificationFormDialog.vue <script setup>
// Source: Pattern from frontend/src/modules/calibrations/components/CalibrationCreateDialog.vue

const templates = ref<VerificationTemplate[]>([])
const params = ref<Record<string, string>>({})

async function loadTemplates() {
  if (!form.value.equipment_id) return
  templates.value = await verificationService.getTemplatesByEquipment(form.value.equipment_id)
  // Initialize params object for each template
  const p: Record<string, string> = {}
  for (const tpl of templates.value) {
    p[tpl.id] = ''
  }
  params.value = p
}
```

## Integration Points

### Point 1: Equipment Model — Add verification_frequency + relationship
**File:** `backend/app/Models/Equipment.php` (lines 16-27 fillable, lines 29-57 relationships)
**What:** Add `verification_frequency` (string: daily/weekly/shift/null) and `verification_frequency_hours` (int/null for simpler date math) to fillable. Add `verifications()` hasMany relationship.
**Impact:** Equipment API resource will need updating if frequency should be exposed.

### Point 2: Category Model — Add verificationTemplates relationship
**File:** `backend/app/Models/Category.php`
**What:** Add `verificationTemplates()` hasMany relationship.
**Impact:** Category API resource may need updating.

### Point 3: EquipmentDetailPage — Add Aferições tab
**File:** `frontend/src/modules/equipment/pages/EquipmentDetailPage.vue` (lines 31-64 tabs)
**What:** Add a new Tab `value="5"` and TabPanel for Aferições history, gated by `authStore.hasPermission('afericoes.view')`.
**Impact:** Tab ordering — existing tabs go 0-4 (Principal, Localização, Técnica, Arquivos, Logs). New tab should be `value="5"` or reorder insert after Técnica.

### Point 4: Routes — Replace Placeholder
**File:** `frontend/src/router/routes.ts` (lines 153-157)
**What:** Replace `component: () => import('@/views/PlaceholderPage.vue')` with the actual verification pending page.
**Impact:** Route `verifications.index` already registered with correct meta.

### Point 5: API Routes — Add verification endpoints
**File:** `backend/routes/api.php` (lines 98-112 calibrations block)
**What:** Add verification routes following the same pattern as calibrations. Optionally nest under equipments for history.

## Estimated Impact

### Backend
- **New files:** ~15 (1 enum, 3 models, 1 exception, 1 service, 1 controller, 1 request, 2 resources, 1 notification, 3 migrations)
- **Modified files:** 3 (Equipment.php, Category.php, routes/api.php)

### Frontend
- **New files:** ~7 (1 types, 1 service, 1 store, 2 pages, 2 components)
- **Modified files:** 2 (routes.ts, EquipmentDetailPage.vue)

### Database
- **New tables:** 3 (verification_templates, verifications, verification_params)
- **Modified tables:** 1 (equipments — add 2 columns)

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP 8.3+ | Backend | ✓ | (composer.lock) | — |
| Laravel 13.8 | Backend | ✓ | (composer.lock) | — |
| PostgreSQL | Database | ✓ | (config/database.php) | — |
| Node.js | Frontend build | ✓ | (package.json) | — |
| Vue 3.5 | Frontend | ✓ | (package.json) | — |
| PrimeVue 5 | Frontend UI | ✓ | (package.json) | — |
| Redis | Cache/queue | ✗ (verify) | — | DB fallback for notifications |

**Missing dependencies with no fallback:** None identified — all required stack components are already installed in the project.
**Missing dependencies with fallback:** Redis is used for queue but we can use `sync` driver for synchronous notifications (D-12 requires sync anyway).

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes | Sanctum token (already implemented) |
| V4 Access Control | yes | Permission middleware: `afericoes.{view,create,edit}` |
| V5 Input Validation | yes | StoreVerificationRequest with rules |
| V7 Logging | yes | LogsActivity trait auto-logs all model changes |

### Known Threat Patterns for Stack

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Tampering with tolerance results | Tampering | Server-side calculation in Service layer (never trust client) |
| Unauthorized verification creation | Elevation of Privilege | Permission middleware `afericoes.create` |
| Data validation bypass | Tampering | FormRequest validation + model $fillable |
| Tolerance value manipulation | Tampering | Cast to float before comparison |

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | `verification_frequency` is stored as a string enum on the `equipments` table directly | Architecture Patterns | Low — could also be a separate table, but D-06/07 indicate per-equipment field |
| A2 | Tolerance comparison is always `< min` or `> max` = failed | Standard Stack | Low — business rule may include "warning" zone between thresholds |
| A3 | Pending verification detection uses a computed query (not materialized) | Architecture Patterns | Medium — performance concerns at scale; may need denormalized `last_verified_at` |
| A4 | No file uploads needed for verifications | Integration Points | Medium — if operators need to attach photos, storage logic needed |

## Open Questions

1. **Tolerance warning zone?**
   - What we know: D-05 says "result calculated on save" with passed/failed
   - What's unclear: Should there be a "warning" zone (e.g., within 10% of tolerance) or just binary passed/failed?
   - Recommendation: Implement ternary result enum (passed/failed/warning) but initially only auto-calculate passed/failed. Add warning when business rules are defined.

2. **Pending verification query implementation?**
   - What we know: Equipment with `verification_frequency` set need periodic checks
   - What's unclear: Should we use a raw SQL query with CASE for frequency conversion, or store `verification_frequency_hours` (integer) for simpler math?
   - Recommendation: Store `verification_frequency` (string enum) for human readability + `verification_next_at` (timestamp, nullable, updated on create) for query performance. The pending query becomes: `WHERE verification_next_at <= NOW() OR verification_next_at IS NULL`.

3. **Standalone pending page vs. integrated?**
   - What we know: D-18 says sidebar entry at `/verifications/pending`
   - What's unclear: Should the same form component be shared between the pending page workflow and the detail page "Aferir" button?
   - Recommendation: Build one `VerificationFormDialog.vue` component used in both contexts. It accepts an `equipmentId` prop (pre-filled when coming from detail page) or allows equipment selection (pending page).

## Sources

### Primary (HIGH confidence)
- [VERIFIED: codebase] `backend/app/Models/Calibration.php` — model pattern with enum casts, scopes, accessors
- [VERIFIED: codebase] `backend/app/Services/CalibrationService.php` — transactional service pattern
- [VERIFIED: codebase] `backend/app/Http/Controllers/Api/V1/CalibrationController.php` — controller with permissions, try/catch, custom actions
- [VERIFIED: codebase] `backend/app/Http/Resources/CalibrationResource.php` — JSON transform with whenLoaded
- [VERIFIED: codebase] `backend/routes/api.php` — route registration pattern
- [VERIFIED: codebase] `frontend/src/modules/calibrations/` — complete CRUD module pattern
- [VERIFIED: codebase] `frontend/src/modules/loans/` — secondary module pattern
- [VERIFIED: codebase] `frontend/src/modules/equipment/pages/EquipmentDetailPage.vue` — tab structure
- [VERIFIED: codebase] `backend/app/Console/Commands/CheckCalibrationDue.php` — due-check command
- [VERIFIED: codebase] `backend/app/Notifications/CalibrationDue.php` — marker notification class
- [VERIFIED: codebase] `frontend/src/types/navigation.ts` — sidebar configuration
- [VERIFIED: codebase] `backend/database/migrations/2026_07_25_000001_create_calibrations_tables.php` — migration pattern

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all tools already installed and verified
- Architecture: HIGH — follows proven Calibrações/Loans patterns
- Pitfalls: MEDIUM — dynamic form reactivity is a known Vue issue, tolerance coercion is standard PHP gotcha
- Integration points: HIGH — all files verified in codebase

**Research date:** 2026-07-25
**Valid until:** 2026-08-25 (30 days — stack is stable, but business rules may evolve)
