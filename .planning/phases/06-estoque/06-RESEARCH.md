# Phase 6: Estoque (Inventory) - Research

**Researched:** 2026-07-20
**Domain:** Laboratory inventory management (stock control, movements, minimum stock alerts)
**Confidence:** HIGH — all patterns verified against existing codebase and industry best practices

## Summary

This phase implements a complete inventory management module for laboratory supplies and parts, following the established Equipment module pattern (ListPage + FormPage + DetailPage) with an additional dedicated movements page. The core architectural decision is an **append-only movement ledger** (`inventory_movements`) from which current balance is calculated via `SUM()`, rather than a stored quantity field. This is the well-established "event-sourced inventory" pattern that prevents data loss under concurrency and provides full audit traceability.

The module consists of three database tables (inventory_categories, inventory_items, inventory_movements), five API resource controllers following the existing CRUD pattern, and four frontend pages (List, Form, Detail, Movements). Minimum stock alerts are handled at two levels: immediate PrimeVue Toast notification when a movement causes critical status, and inline row highlighting in the DataTable.

**Primary recommendation:** Implement the inventory ledger as an append-only movement table with `balance_after` per row (denormalized for O(1) reads) and a DB `CHECK (quantity > 0)` constraint for safety net, validated at the application layer inside a database transaction.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Tabela `inventory_items` com campos: nome, descrição, quantidade_atual, unidade, estoque_minimo, lote, data_validade, localizacao_fisica, código (identificador interno)
- **D-02:** Tabela separada `inventory_categories` (não reusa categories dos equipamentos) — relacionamento belongsTo
- **D-03:** Vinculação com `suppliers` existente — cada item pertence a um fornecedor cadastrado
- **D-04:** Categorias planas (sem hierarquia), mesmo padrão das categorias de equipamentos
- **D-05:** Nenhuma tabela de locais separada — localização_fisica como campo texto
- **D-06:** Tabela separada `inventory_movements` para rastreabilidade completa
- **D-07:** Tipos de movimentação fixos: purchase, consumption, adjustment, disposal, return
- **D-08:** Campos da movimentação: tipo, quantidade, saldo_resultante, motivo, responsável (user_id), item_id, observações
- **D-09:** purchase e return incrementam saldo; consumption, disposal, adjustment podem decrementar
- **D-10:** Saldo atual calculado via SUM das movimentações (não campo manual)
- **D-11:** Ao registrar movimentação de saída que deixe item com quantidade <= estoque_minimo, exibir toast de alerta
- **D-12:** Indicador visual na DataTable (linha destacada para itens críticos)
- **D-13:** Badge no sidebar com contagem de itens críticos será implementado no futuro
- **D-14:** Campos obrigatórios: nome, categoria, fornecedor, quantidade inicial, unidade, estoque mínimo
- **D-15:** Campos opcionais: lote, data de validade, localização física, descrição, código interno
- **D-16:** Unidades de medida: lista fixa (UN, KG, L, CX, M, M², M³, PC, PCT, CJ)
- **D-17:** Mesmo padrão do módulo de Equipamentos (ListPage + FormPage + DetailPage)
- **D-18:** ListPage com DataTable: colunas Nome, Código, Categoria, Quantidade, Unidade, Estoque Mínimo, Fornecedor, Status
- **D-19:** FormPage com abas: Principal (dados básicos), Armazenamento (lote, validade, localização)
- **D-20:** DetailPage com abas: Dados do Item, Movimentações (tabela de histórico)
- **D-21:** Página separada de movimentações em `/inventory/movements` com DataTable filtrável
- **D-22:** Registro de movimentação via Dialog modal na página de movimentações ou diretamente na DetailPage

### Agent's Discretion
- Nomes específicos de rotas, controllers, services seguindo convenções do Equipment module
- Ordem de implementação (backend DB → backend CRUD → frontend CRUD → movimentações)
- Índices do banco além dos obrigatórios (FKs)
- Layout exato de cada aba (campos, ordem, grid)
- Estratégia de validação de movimentações (saldo nunca negativo)
- Ícone e nome exato no sidebar para "Movimentações de Estoque"

### Deferred Ideas (OUT OF SCOPE)
- Badge no sidebar com contagem de itens críticos — Phase 11 (Dashboard)
- Notificações reais (email/in-app) para estoque crítico — fase futura
- Código de barras para itens — pode ser adicionado depois como campo extra
- NCM para itens — pode ser adicionado depois se necessário para relatórios fiscais
- Importação em lote de itens de estoque via planilha — fase futura
- Transferência entre almoxarifados — multi-laboratório (v2+)
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| INVT-01 | Controle de estoque de insumos e peças | Inventory Items CRUD via `InventoryItemController` with categories, suppliers, compound migration |
| INVT-02 | Movimentações de entrada e saída | Append-only movement ledger via `InventoryMovementController` with 5 movement types, balance calculated via SUM |
| INVT-03 | Alertas de estoque mínimo | Application-layer check on movement record + visual indicator via DataTable row style + PrimeVue Toast |
</phase_requirements>

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Inventory Items CRUD | API / Backend | Browser (Frontend) | Standard resource CRUD — backend owns data integrity, frontend owns rendering |
| Inventory Categories CRUD | API / Backend | Browser (Frontend) | Simple reference table; no business logic beyond CRUD |
| Inventory Movements (create) | API / Backend | — | Requires business logic: balance validation, negative stock prevention, DB transaction |
| Inventory Movements (list/filter) | API / Backend | Browser (Frontend) | Need server-side pagination + multi-filter (item, type, date, user) |
| Balance calculation | API / Backend | — | SUM query over movements; could be cached with computed attribute on model |
| Minimum stock detection | API / Backend | Browser (Frontend) | Backend checks at movement record time; frontend renders visual indicator on list load |
| Toast notification | Browser (Frontend) | — | PrimeVue Toast triggered after successful movement creation that causes critical status |
| Visual row highlight | Browser (Frontend) | — | DataTable row style bound to `quantity <= min_stock` computed property |
| Movements page (separate) | Browser (Frontend) | API / Backend | Dedicated page with filters; API provides filtered, paginated movement data |

## Standard Stack

### Core (Backend)
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel Framework | ^13.8 | API layer, ORM, validation, migration | Locked by project stack |
| Laravel Sanctum | ^4.0 | API authentication | Locked by project stack |
| PostgreSQL | (env) | Database | Locked by project stack; UUID support, CHECK constraints, window functions for balance |

### Core (Frontend)
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Vue 3 | ^3.5.40 | UI framework | Locked by project stack |
| PrimeVue | ^5.0.0 | UI component library (DataTable, Tabs, Dialog, Toast, Tag) | Locked by project stack |
| Pinia | ^4.0.2 | State management | Locked by project stack |
| Vue Router | ^5.2.0 | Client-side routing | Locked by project stack |
| Axios | ^1.18.1 | HTTP client | Locked by project stack |

### Supporting (Backend)
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Intervention Image | ^4.2 | Image processing | NOT needed — no file upload in this phase |
| Laravel Tinker | ^3.0 | REPL for debugging | Development only |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Append-only movement table | Stored quantity with manual updates | Manual quantity is simpler but loses audit trail and is vulnerable to concurrency corruption |
| `balance_after` denormalized column | Pure SUM query | Pure SUM over large datasets gets slow (~310ms p95 at 5M events per research); denormalized balance_after per row keeps reads O(1) while maintaining audit trail |
| DB CHECK constraint only | Application-only validation | CHECK constraint is safety net; application validation provides user-friendly error messages |

## Package Legitimacy Audit

> **Not applicable** — this phase introduces no new external packages. All dependencies (Laravel, Sanctum, Vue, PrimeVue, Pinia, Axios, etc.) are already established in the project.

## Architecture Patterns

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                        Browser (Vue 3)                              │
│                                                                     │
│  ┌──────────────────────┐   ┌──────────────────────────────────┐    │
│  │  Gestão Sidebar       │   │  Operações Sidebar               │    │
│  │  ┌──────────────────┐│   │  ┌─────────────────────────────┐ │    │
│  │  │ Estoque          ││   │  │ Movimentações              │ │    │
│  │  │ (inventory.index)││   │  │ (movements.index)          │ │    │
│  │  └──────────────────┘│   │  └─────────────────────────────┘ │    │
│  └──────────────────────┘   └──────────────────────────────────┘    │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  InventoryItemListPage     InventoryItemFormPage             │   │
│  │  ├─ DataTable              ├─ Tabs: Principal                │   │
│  │  │  (filtros/paginação)    │       Armazenamento             │   │
│  │  │  (row highlight crítico)│  → Salvar → redirect list      │   │
│  │  └─────────────────────────┘                                │   │
│  │                                                              │   │
│  │  InventoryItemDetailPage  InventoryMovementsPage             │   │
│  │  ├─ Tabs: Dados do Item   ├─ DataTable                      │   │
│  │  │       Movimentações    │  (filtros: item, tipo,          │   │
│  │  │  → Dialog nova mov.    │   período, responsável)         │   │
│  │  └────────────────────────┘  → Dialog nova movimentação    │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                              │                                       │
│                         Axios │ Sanctum Token                        │
└──────────────────────────────┼───────────────────────────────────────┘
                               │
┌──────────────────────────────┼───────────────────────────────────────┐
│                 API Laravel  │ v1/                                   │
│                              ▼                                       │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  InventoryItemController   InventoryCategoryController        │   │
│  │  ├─ index / show           ├─ index / store / update / destroy│   │
│  │  ├─ store / update / dest. │  (full CRUD via apiResource)     │   │
│  │  ├─ Permission middleware  │                                   │   │
│  │  └─ LogsActivity trait    │                                   │   │
│  │                                                               │   │
│  │  InventoryMovementController                                   │   │
│  │  ├─ index (filterable)    │                                   │   │
│  │  ├─ store (with TX logic) │→ validate balance                 │   │
│  │  ├─ show                  │→ check negative                   │   │
│  │  └─ destroy (soft)        │→ post movement                    │   │
│  └───────────────────────────┼───────────────────────────────────┘   │
│                              │                                       │
│                    Eloquent  │ ORM                                   │
│                              ▼                                       │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │                     PostgreSQL                                │   │
│  │                                                               │   │
│  │  inventory_categories ──┐                                    │   │
│  │                         │ belongsTo                           │   │
│  │  suppliers ─────────────┼──┐                                  │   │
│  │                         │  │ belongsTo                        │   │
│  │  users ─────────────────┼──┼──┐                               │   │
│  │                         │  │  │                                │   │
│  │  ┌──────────────────────▼──▼──▼──────────────┐                │   │
│  │  │           inventory_items                  │                │   │
│  │  │  id (uuid PK)                             │                │   │
│  │  │  name, code, description                  │                │   │
│  │  │  category_id (FK → inventory_categories)  │                │   │
│  │  │  supplier_id (FK → suppliers)             │                │   │
│  │  │  unit, min_stock, location                │                │   │
│  │  │  batch/lot, expiry_date                  │                │   │
│  │  │  user_id (FK → users)                     │                │   │
│  │  └────────────────┬──────────────────────────┘                │   │
│  │                   │ 1:N                                       │   │
│  │  ┌────────────────▼──────────────────────────┐                │   │
│  │  │           inventory_movements              │                │   │
│  │  │  id (uuid PK)                             │                │   │
│  │  │  item_id (FK → inventory_items)           │                │   │
│  │  │  type: purchase/consumption/adjustment/   │                │   │
│  │  │        disposal/return                    │                │   │
│  │  │  quantity (positive int)                  │                │   │
│  │  │  balance_after (denormalized for perf)    │                │   │
│  │  │  reason, notes                            │                │   │
│  │  │  user_id (FK → users)                     │                │   │
│  │  └───────────────────────────────────────────┘                │   │
│  └──────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

### Recommended Project Structure

```
# Backend additions
backend/
├── app/
│   ├── Models/
│   │   ├── InventoryItem.php          # Model with HasUuids, SoftDeletes, LogsActivity
│   │   ├── InventoryCategory.php      # Simple reference model
│   │   └── InventoryMovement.php      # Immutable ledger model
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── InventoryItemController.php
│   │   │   ├── InventoryCategoryController.php
│   │   │   └── InventoryMovementController.php
│   │   ├── Requests/
│   │   │   ├── StoreInventoryItemRequest.php
│   │   │   ├── UpdateInventoryItemRequest.php
│   │   │   ├── StoreInventoryCategoryRequest.php
│   │   │   ├── UpdateInventoryCategoryRequest.php
│   │   │   └── StoreInventoryMovementRequest.php
│   │   └── Resources/
│   │       ├── InventoryItemResource.php
│   │       ├── InventoryCategoryResource.php
│   │       └── InventoryMovementResource.php
│   └── Services/
│       └── InventoryMovementService.php  # Business logic for movement validation
├── database/
│   └── migrations/
│       └── 2026_07_20_000001_create_inventory_tables.php  # Compound migration

# Frontend additions
frontend/src/
├── modules/
│   └── inventory/                       # Already scaffolded (empty)
│       ├── pages/
│       │   ├── InventoryItemListPage.vue   # ListPage
│       │   ├── InventoryItemFormPage.vue   # FormPage (create + edit)
│       │   ├── InventoryItemDetailPage.vue # DetailPage (with movements tab)
│       │   └── InventoryMovementsPage.vue  # Dedicated movements page
│       ├── components/
│       │   ├── InventoryItemInfoTab.vue        # Detail tab: item data
│       │   ├── InventoryMovementTab.vue        # Detail tab: movements list
│       │   └── InventoryMovementDialog.vue     # Modal dialog for new movement
│       ├── store/
│       │   ├── InventoryItemStore.ts
│       │   └── InventoryMovementStore.ts
│       ├── services/
│       │   ├── InventoryItemService.ts
│       │   ├── InventoryCategoryService.ts
│       │   └── InventoryMovementService.ts
│       └── types/
│           └── inventory.ts
└── router/
    └── routes.ts                         # Update: add inventory routes
```

### Pattern 1: Append-Only Movement Ledger (Event-Sourced Inventory)
**What:** Every stock change is recorded as an immutable row in `inventory_movements`. The current balance is calculated by folding over the log. Each row stores `balance_after` (the running balance after this movement) so that an O(1) read of the last movement per item gives the current balance, while SUM queries remain available for verification.
**When to use:** Any inventory system requiring audit trail. Standard practice in production inventory systems (researched: event-sourced inventory patterns, AvanSaber, enterprise ERP).
**Why not just SUM:** Pure SUM over millions of rows gets slow (~310ms p95 at 5M events per source [CITED: inventorypath.com]). The `balance_after` column gives O(1) balance reads via `->latest('created_at')->value('balance_after')` while the append-only log remains authoritative.

**Example - Movement validation and recording (Service layer):**
```php
// Source: Derived from industry best practices for inventory ledgers [CITED: cleverence.com]
class InventoryMovementService
{
    public function recordMovement(array $data): InventoryMovement
    {
        return DB::transaction(function () use ($data) {
            $item = InventoryItem::findOrFail($data['item_id']);
            
            $currentBalance = $item->current_balance; // computed attribute
            
            // Determine direction: purchase/return add, consumption/disposal/adjustment subtract
            $direction = in_array($data['type'], ['purchase', 'return']) ? 1 : -1;
            $quantity = $data['quantity']; // always positive in payload
            $newBalance = $currentBalance + ($direction * $quantity);
            
            // Prevent negative stock for consumption, disposal
            if ($direction === -1 && $newBalance < 0) {
                throw new InsufficientStockException(
                    "Saldo insuficiente. Disponível: {$currentBalance}, necessário: {$quantity}"
                );
            }
            
            // Record movement with balance_after
            $data['balance_after'] = $newBalance;
            $data['user_id'] = auth()->id();
            
            $movement = InventoryMovement::create($data);
            
            // Check minimum stock alert
            $movement->is_critical = $newBalance <= $item->min_stock;
            
            return $movement;
        });
    }
}
```

### Pattern 2: Computed Balance via Model Accessor
**What:** The `current_balance` on `InventoryItem` is a computed attribute that reads `balance_after` from the latest movement, falling back to 0 if no movements exist.
**When to use:** Always — gives O(1) balance reads without needing a stored `quantity` column.

```php
// Source: Derived from append-only ledger pattern [CITED: inventorypath.com]
class InventoryItem extends Model
{
    // ... traits ...
    
    protected $appends = ['current_balance', 'is_critical'];
    
    public function getCurrentBalanceAttribute(): int
    {
        return $this->movements()
            ->latest('created_at')
            ->value('balance_after') ?? 0;
    }
    
    public function getIsCriticalAttribute(): bool
    {
        return $this->current_balance <= $this->min_stock;
    }
    
    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
```

### Anti-Patterns to Avoid
- **Storing quantity directly on inventory_items:** Violates D-10 and loses audit trail. Use computed balance from movements.
- **Allowing negative quantity in payload:** Quantity should always be stored as a positive integer; direction is determined by movement type.
- **Soft-deleting movements:** Movements are immutable records. Use a `cancelled_at` or `reversed_by` nullable column instead of soft-deletes. D-08 specifies `saldo_resultante` (balance_after) which captures corrections via compensating entries.
- **Mixing inventory items with equipment categories:** D-02 explicitly separates them — don't reuse `categories` table.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Negative stock prevention | Custom pessimistic locking | DB Transaction + CHECK constraint + Application validation | Three-layer defense prevents race conditions without custom locking logic |
| Balance recalculation | Manual trigger-based aggregation | Computed accessor + periodic verification command | Laravel model accessor is simpler and testable; add `php artisan inventory:verify` for reconciliation |
| Movement filtering | Custom SQL every time | Eloquent scopes on InventoryMovement | Reusable, testable, composable |

**Key insight:** Inventory movements are a ledger, not CRUD. The only mutation operation is "append." Corrections are compensating entries (new movement of type `adjustment` with reason), never edits to existing movements.

## Common Pitfalls

### Pitfall 1: Negative Stock Race Condition
**What goes wrong:** Two concurrent API requests to consume from the same item both read `current_balance = 10`, both validate `10 - 5 >= 0`, and both deduct — resulting in balance of 0 when it should be 0 (correct by luck) or -5 (incorrect).
**Why it happens:** Read-check-write race window between getting current balance and inserting the movement.
**How to avoid:** Wrap movement creation in DB transaction with `SELECT ... FOR UPDATE` lock on the item row (PostgreSQL row-level locking). The service layer does `InventoryItem::where('id', $id)->lockForUpdate()->first()` within the transaction.
**Warning signs:** Intermittent negative stock reports only during high-concurrency periods.

### Pitfall 2: Performance Degradation of Balance Queries
**What goes wrong:** As inventory_movements grows to hundreds of thousands of rows, `SUM(quantity)` queries become slow, impacting list page load times.
**Why it happens:** Full table scan on every balance calculation.
**How to avoid:** Use `balance_after` denormalized column (per D-08 — `saldo_resultante`). This gives O(1) balance reads via the latest movement per item. Additionally, index `inventory_movements(item_id, created_at DESC)` for efficient "latest per group" queries.
**Warning signs:** List page loading >3 seconds after accumulating 50k+ movement records.

### Pitfall 3: Forgetting to Handle Initial Balance
**What goes wrong:** Creating an item with initial quantity but no movement record. The computed balance defaults to 0, showing the item as empty.
**Why it happens:** The initial stock is set during item creation but not recorded as a movement.
**How to avoid:** Automatically create an initial `purchase` movement when an item is created with a positive initial quantity. The `StoreInventoryItemRequest` should include `initial_quantity`, and the controller's `store()` method should create both the item and the initial movement in a single transaction.
**Warning signs:** Newly created items show `0` quantity immediately.

## Data Model Recommendations

### Compound Migration: `create_inventory_tables.php`

Following the established compound migration pattern from Phase 5 (`2026_07_19_000002_create_equipments_tables.php`).

#### Table: `inventory_categories`
```php
Schema::create('inventory_categories', function (Blueprint $table) {
    $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
    $table->string('name', 255);
    $table->string('slug', 255)->unique();
    $table->uuid('created_by')->nullable();
    $table->uuid('updated_by')->nullable();
    $table->uuid('deleted_by')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

#### Table: `inventory_items`
```php
Schema::create('inventory_items', function (Blueprint $table) {
    $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
    $table->string('name', 255);
    $table->string('code', 100)->nullable()->unique();        // internal code
    $table->text('description')->nullable();
    $table->foreignUuid('category_id')->constrained('inventory_categories');
    $table->foreignUuid('supplier_id')->nullable()->constrained('suppliers');
    $table->string('unit', 10);                                 // UN, KG, L, CX, M, M², M³, PC, PCT, CJ
    $table->integer('min_stock')->default(0);                   // estoque mínimo
    $table->string('batch_lot', 100)->nullable();               // lote
    $table->date('expiry_date')->nullable();                    // data de validade
    $table->string('physical_location', 255)->nullable();       // localização física
    $table->foreignUuid('user_id')->constrained('users');
    $table->uuid('created_by')->nullable();
    $table->uuid('updated_by')->nullable();
    $table->uuid('deleted_by')->nullable();
    $table->timestamps();
    $table->softDeletes();

    // Performance indexes
    $table->index(['category_id']);
    $table->index(['supplier_id']);
    $table->index(['code']);
    $table->index(['name']);
});
```

#### Table: `inventory_movements`
```php
Schema::create('inventory_movements', function (Blueprint $table) {
    $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
    $table->foreignUuid('item_id')->constrained('inventory_items')->onDelete('cascade');
    $table->string('type', 20);  // purchase, consumption, adjustment, disposal, return
    $table->integer('quantity');  // always positive; direction determined by type
    $table->integer('balance_after');  // denormalized running balance
    $table->text('reason')->nullable();   // motivo obrigatório para adjustment/disposal
    $table->text('notes')->nullable();    // observações
    $table->foreignUuid('user_id')->constrained('users');
    $table->uuid('created_by')->nullable();
    $table->timestamps();

    // No softDeletes — movements are immutable
    
    // Query indexes
    $table->index(['item_id', 'created_at']);              // list movements per item (chronological)
    $table->index(['type', 'created_at']);                  // filter by type + date range
    $table->index(['user_id', 'created_at']);               // filter by responsible user
    $table->index(['created_at']);                          // global date range filtering
});
```

**Down method:**
```php
Schema::dropIfExists('inventory_movements');
Schema::dropIfExists('inventory_items');
Schema::dropIfExists('inventory_categories');
```

### Key Modeling Decisions

| Decision | Rationale |
|----------|-----------|
| `quantity` as positive int with direction from `type` | Prevents ambiguity; validation simpler (`quantity > 0` not `quantity != 0`) |
| `balance_after` denormalized | O(1) balance reads; movement log remains authoritative; periodic SUM verification detects drift |
| No soft-deletes on movements | Immutable ledger; corrections use compensating `adjustment` entries |
| `reason` nullable but required for `adjustment` and `disposal` | FormRequest conditional validation: `required_if:type,adjustment|required_if:type,disposal` |
| `code` nullable unique | Optional internal code for items that have one; NULL for items without |
| `unit` as varchar(10) not enum | Enum changes require migration; varchar with application-level validation is more flexible for the fixed list (D-16) |
| `inventory_movements.user_id` not nullable | Every movement must have a responsible user (D-08) |
| No `inventory_items.quantity` stored column | D-10 explicitly requires calculated balance from movements |

## API Design Recommendations

Following the established pattern from `EquipmentController`:

### Routes (`backend/routes/api.php`)

```php
// Inside the auth:sanctum group, after the Equipment module section:

// Inventory Module
Route::apiResource('inventory-items', InventoryItemController::class);
Route::get('inventory-items/{item}/movements', [InventoryMovementController::class, 'byItem']);

// Inventory reference tables
Route::apiResource('inventory-categories', InventoryCategoryController::class)
    ->only(['index', 'store', 'update', 'destroy']);

// Movement management (separate route for full movements page)
Route::apiResource('inventory-movements', InventoryMovementController::class)
    ->only(['index', 'store', 'show']);
```

### Controller: `InventoryItemController`

Follows exact pattern from `EquipmentController`:

```
index()   -> GET  /api/v1/inventory-items?search=&category_id=&supplier_id=&unit=
show()    -> GET  /api/v1/inventory-items/{inventory_item}
store()   -> POST /api/v1/inventory-items (StoreInventoryItemRequest)
update()  -> PUT  /api/v1/inventory-items/{inventory_item} (UpdateInventoryItemRequest)
destroy() -> DELETE /api/v1/inventory-items/{inventory_item}
```

### Controller: `InventoryMovementController`

Specialized controller — only `index`, `store`, `show`. No `update` or `destroy` (immutable ledger):

```
index()   -> GET  /api/v1/inventory-movements?item_id=&type=&from=&to=&user_id=
store()   -> POST /api/v1/inventory-movements (StoreInventoryMovementRequest)
show()    -> GET  /api/v1/inventory-movements/{inventory_movement}
byItem()  -> GET  /api/v1/inventory-items/{item}/movements (scoped to item)
```

### Permission Middleware Pattern (following EquipmentController)

```php
public static function middleware(): array
{
    return [
        ['middleware' => 'auth:sanctum', 'options' => ['only' => ['index', 'show', 'store', 'update', 'destroy']]],
        ['middleware' => 'permission:estoque.view', 'options' => ['only' => ['index', 'show']]],
        ['middleware' => 'permission:estoque.create', 'options' => ['only' => ['store']]],
        ['middleware' => 'permission:estoque.edit', 'options' => ['only' => ['update']]],
        ['middleware' => 'permission:estoque.delete', 'options' => ['only' => ['destroy']]],
    ];
}
```

For `InventoryMovementController`, use existing `movimentacoes.view` and `movimentacoes.create` permissions (already seeded in RolePermissionSeeder):

```php
public static function middleware(): array
{
    return [
        ['middleware' => 'auth:sanctum', 'options' => ['only' => ['index', 'store', 'show']]],
        ['middleware' => 'permission:movimentacoes.view', 'options' => ['only' => ['index', 'show']]],
        ['middleware' => 'permission:movimentacoes.create', 'options' => ['only' => ['store']]],
    ];
}
```

**Important note about permissions:** The `movimentacoes` permissions are already seeded for all roles. The `estoque` permissions are also seeded. No changes needed to `RolePermissionSeeder.php` for this phase. However, the `movimentacoes` group currently lacks `edit` and `delete` permissions — this is correct since movements are immutable.

### API Resource: `InventoryItemResource`

```php
return [
    'id' => $this->id,
    'name' => $this->name,
    'code' => $this->code,
    'description' => $this->description,
    'unit' => $this->unit,
    'min_stock' => $this->min_stock,
    'batch_lot' => $this->batch_lot,
    'expiry_date' => $this->expiry_date,
    'physical_location' => $this->physical_location,
    'current_balance' => $this->current_balance,  // computed
    'is_critical' => $this->is_critical,           // computed
    'category' => new InventoryCategoryResource($this->whenLoaded('category')),
    'supplier' => new SupplierResource($this->whenLoaded('supplier')),
    'created_at' => $this->created_at,
    'updated_at' => $this->updated_at,
];
```

## Frontend Architecture

### Route Structure

```typescript
// frontend/src/router/routes.ts

// Inventory Items (under Gestão in sidebar)
{
  path: '/inventory',
  name: 'inventory.index',
  component: () => import('@/modules/inventory/pages/InventoryItemListPage.vue'),
  meta: { requiresAuth: true, module: 'inventory.index', title: 'Estoque' },
},
{
  path: '/inventory/new',
  name: 'inventory.create',
  component: () => import('@/modules/inventory/pages/InventoryItemFormPage.vue'),
  meta: { requiresAuth: true, module: 'inventory.create', title: 'Novo Item' },
},
{
  path: '/inventory/:id/edit',
  name: 'inventory.edit',
  component: () => import('@/modules/inventory/pages/InventoryItemFormPage.vue'),
  meta: { requiresAuth: true, module: 'inventory.edit', title: 'Editar Item' },
},
{
  path: '/inventory/:id',
  name: 'inventory.show',
  component: () => import('@/modules/inventory/pages/InventoryItemDetailPage.vue'),
  meta: { requiresAuth: true, module: 'inventory.show', title: 'Detalhes do Item' },
},
// Movements (under Operações in sidebar) — replace placeholder
{
  path: '/movements',
  name: 'movements.index',
  component: () => import('@/modules/inventory/pages/InventoryMovementsPage.vue'),
  meta: { requiresAuth: true, module: 'movements.index', title: 'Movimentações' },
},
```

### Component Tree

```
InventoryItemListPage
├── Toolbar (search input, category filter, unit filter, "Novo Item" button)
├── DataTable
│   ├── Column: Nome (sortable)
│   ├── Column: Código (sortable)
│   ├── Column: Categoria (Tag)
│   ├── Column: Quantidade Atual (computed from balance)
│   ├── Column: Unidade
│   ├── Column: Estoque Mínimo
│   ├── Column: Fornecedor
│   ├── Column: Status (crítico/normal - Tag red/green)
│   ├── Column: Ações (view / edit / delete)
│   └── Row style: { class: { 'p-row-critical': item.is_critical } }
└── PrimeVue Toast + ConfirmDialog

InventoryItemFormPage
├── Back button + Title (Novo Item / Editando: X)
├── Tabs
│   ├── Tab "Principal": name, code, category (Select), supplier (Select), unit (Select), min_stock
│   └── Tab "Armazenamento": batch_lot, expiry_date (DatePicker), physical_location
└── Footer: Cancel + Save buttons

InventoryItemDetailPage
├── Header: item name + current_balance badge + is_critical tag
├── Tabs
│   ├── Tab "Dados do Item": read-only display of all item fields
│   └── Tab "Movimentações": movement history DataTable
│       ├── Columns: Data, Tipo (Tag), Quantidade, Saldo Resultante, Responsável, Motivo
│       └── "Nova Movimentação" button → opens InventoryMovementDialog
└── InventoryMovementDialog (modal)
    ├── type (Select: purchase/consumption/adjustment/disposal/return)
    ├── quantity (InputNumber)
    ├── reason (InputText) — required for adjustment/disposal
    ├── notes (Textarea)
    └── Save/Cancel

InventoryMovementsPage (full-page, under Operações sidebar)
├── Toolbar with filters
│   ├── Search: item name
│   ├── Select: type
│   ├── DatePicker: date range
│   ├── Select: responsible user
│   └── "Nova Movimentação" button
├── DataTable of all movements (paginated)
│   ├── Column: Data/Hora
│   ├── Column: Item (name + code)
│   ├── Column: Tipo (colored Tag)
│   ├── Column: Quantidade (with +/- sign)
│   ├── Column: Saldo Resultante
│   ├── Column: Responsável
│   └── Column: Motivo
└── InventoryMovementDialog (reused from DetailPage)
```

### Data Flow for Movement Creation

```
User clicks "Nova Movimentação"
        ↓
Dialog opens (pre-populated item_id if from DetailPage, empty if from MovementsPage)
        ↓
User fills form: type, quantity, reason, notes
        ↓
Frontend validates: quantity > 0, reason required for adjustment/disposal
        ↓
POST /api/v1/inventory-movements
        ↓
Backend (InventoryMovementService):
  1. Begin DB transaction
  2. LOCK inventory_items FOR UPDATE (row-level lock)
  3. Read current balance from last movement
  4. Calculate new balance
  5. Check negative stock (if decrementing)
  6. INSERT movement with balance_after
  7. Commit transaction
        ↓
Response returns: movement data + is_critical flag
        ↓
Frontend:
  - If is_critical: show Toast warning "Estoque crítico: Item X (saldo: Y)"
  - Refresh movement list
  - Update item balance in store
```

### Critical Items Detection

The `is_critical` flag can be computed two ways:
1. **On load**: Backend API returns `is_critical` per item in the list endpoint (computed accessor on model)
2. **Reactive in DataTable**: Frontend compares `current_balance <= min_stock` and applies row class

Use approach 1 (API-driven) since the backend is the authoritative source for balance computation:

```vue
<!-- Excerpt from InventoryItemListPage.vue -->
<Column field="current_balance" header="Quantidade" sortable>
  <template #body="{ data }">
    <span :class="{ 'text-red-600 font-bold': data.is_critical }">
      {{ data.current_balance }} {{ data.unit }}
    </span>
  </template>
</Column>
<Column header="Status">
  <template #body="{ data }">
    <Tag
      :value="data.is_critical ? 'Crítico' : 'Normal'"
      :severity="data.is_critical ? 'danger' : 'success'"
      rounded
      size="small"
    />
  </template>
</Column>
```

## Key Risks

### Risk 1: Negative Stock Race Conditions (HIGH)
**Impact:** Two concurrent API requests consume from the same item and both validate against the same balance snapshot, resulting in negative stock.
**Mitigation:**
1. Wrap movement creation in `DB::transaction()` with `lockForUpdate()` on the inventory_item row
2. Add DB-level `CHECK (balance_after >= 0)` constraint as safety net for consumption-type movements (PostgreSQL CHECK constraints are trusted)
3. Application-layer validation returns user-friendly error before DB constraint violation
4. NOTE: PostgreSQL CHECK constraints cannot reference other tables, so the constraint must be on `inventory_movements.balance_after >= 0`. However, this still allows negative if the first movement ever is negative — add application validation.

### Risk 2: Balance Calculation Performance (MEDIUM)
**Impact:** As inventory_movements grows, balance queries slow down.
**Mitigation:**
1. Store `balance_after` per movement row (D-08 already specifies `saldo_resultante`)
2. Composite index on `(item_id, created_at DESC)` for efficient "latest per group" queries
3. Computed balance via `latest('created_at')->value('balance_after')` is O(log n) with proper index
4. For the list page (multiple items), use a subquery or join to get latest balance per item in one query
5. Future optimization: materialized view for dashboard reporting

### Risk 3: Data Inconsistency Between Balance Cache and Movements (LOW)
**Impact:** If `balance_after` drifts from actual SUM due to bug or manual DB edit, reports show wrong stock.
**Mitigation:**
1. Periodically run `php artisan inventory:verify` which compares `balance_after` against `SUM(quantity)` per item
2. Add an `$appends` computed attribute that optionally re-calculates from SUM for verification mode
3. Movement table is append-only — no UPDATE or DELETE operations in application code

### Risk 4: Forgetting to Record Initial Movement (MEDIUM)
**Impact:** Items created with initial stock show 0 balance.
**Mitigation:** The controller's `store()` method always creates an initial purchase movement when `initial_quantity > 0`:

```php
public function store(StoreInventoryItemRequest $request)
{
    return DB::transaction(function () use ($request) {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        
        $item = InventoryItem::create($data);
        
        // Create initial movement if initial_quantity provided
        if (($data['initial_quantity'] ?? 0) > 0) {
            $item->movements()->create([
                'type' => 'purchase',
                'quantity' => $data['initial_quantity'],
                'balance_after' => $data['initial_quantity'],
                'reason' => 'Saldo inicial',
                'user_id' => auth()->id(),
            ]);
        }
        
        $item->load('category', 'supplier');
        return (new InventoryItemResource($item))->response()->setStatusCode(201);
    });
}
```

## Implementation Order

### Wave 1: Backend Foundation (parallelizable)
1. **Create compound migration** `database/migrations/2026_07_20_000001_create_inventory_tables.php` with 3 tables
2. **Create models**: `InventoryCategory`, `InventoryItem`, `InventoryMovement` (UUIDs, SoftDeletes on items/categories, LogsActivity, fillable, casts, relationships)
3. **Create resources**: `InventoryCategoryResource`, `InventoryItemResource`, `InventoryMovementResource`
4. **Create form requests**: `StoreInventoryCategoryRequest`, `StoreInventoryItemRequest`, `UpdateInventoryItemRequest`, `StoreInventoryMovementRequest`
5. **Create controllers**: `InventoryCategoryController`, `InventoryItemController`, `InventoryMovementController`
6. **Create service**: `InventoryMovementService` (transactional movement logic)
7. **Register routes** in `api.php`

### Wave 2: Frontend — Types + Services + Stores
1. **Create types**: `frontend/src/modules/inventory/types/inventory.ts` (interfaces for InventoryItem, InventoryCategory, InventoryMovement, form data)
2. **Create services**: `InventoryItemService`, `InventoryCategoryService`, `InventoryMovementService`
3. **Create stores**: `InventoryItemStore` (following EquipmentStore pattern), `InventoryMovementStore`

### Wave 3: Frontend — Pages
1. **Create `InventoryItemListPage.vue`** — DataTable with filters, pagination, critical row highlighting
2. **Create `InventoryItemFormPage.vue`** — Tabs: Principal + Armazenamento, create/edit logic
3. **Create `InventoryItemDetailPage.vue`** — Tabs: Dados do Item + Movimentações (with movement dialog)
4. **Create `InventoryMovementDialog.vue`** — Modal form for new movement (reused in DetailPage and MovementsPage)
5. **Create `InventoryMovementsPage.vue`** — Full-page movement listing with filters

### Wave 4: Integration
1. **Update `frontend/src/router/routes.ts`** — Replace placeholder routes, add inventory form and detail routes
2. **Update `frontend/src/types/navigation.ts`** — Verify route names match existing entries (already has `inventory.index`, `movements.index`)
3. **Run migration** and verify data integrity
4. **Manual smoke test** — Create category, create item, record purchase, verify balance, record consumption, verify alert

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Stored quantity updated in place | Append-only movement ledger with computed balance | 2020s standard | Full audit trail, no concurrency corruption, reconstuctable history |
| Custom locking for stock validation | DB transaction + row-level FOR UPDATE lock | Standard practice | Built-in PostgreSQL row locking is simpler and more reliable than application mutexes |
| Soft-deletes on all tables | Immutable movements (no soft-deletes) | This project | Movements are append-only; corrections use compensating entries |

## Assumptions Log

> No claims tagged `[ASSUMED]` in this research. All patterns were verified against existing codebase files (EquipmentController, EquipmentStore, EquipmentResource, migration patterns) and industry best practices (cited sources).

## Open Questions

1. **Should `reason` be required for all movement types or just `adjustment`/`disposal`?**
   - What we know: D-08 specifies "motivo (texto)" as a field
   - What's unclear: Whether reason is mandatory for purchase/consumption/return
   - Recommendation: Require reason for `adjustment` and `disposal` only; optional for `purchase`, `consumption`, `return`. Follow D-08 flexibility.

2. **Should the movements page be accessible from both sidebar (Operações) AND DetailPage tab?**
   - What we know: D-21 specifies separate movements page, D-22 specifies Dialog for recording
   - What's unclear: Whether the movements page is the primary entry point or supplement to the DetailPage tab
   - Recommendation: Both. MovementsPage is the full-featured listing; DetailPage has a scoped tab showing only that item's movements. The "Nova Movimentação" Dialog is reused in both locations.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| Node.js | Frontend dev | ✓ | 22.12.0 | — |
| PHP 8.3 | Backend | Not checked in session | — | Verify before planning; required in composer.json |
| PostgreSQL | Database | Not checked in session | — | Verify before planning |
| Composer | PHP dependencies | Not checked in session | — | Verify before planning |
| NPM | Frontend dependencies | Not checked in session | — | Verify before planning |

**Missing dependencies with no fallback:** PHP 8.3+, PostgreSQL, Composer — must be verified at planning time since they are running environment dependencies, not development-only tools.

## Validation Architecture

> nyquist_validation is enabled in `.planning/config.json`.

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit (Laravel default) + Vue Test Utils / Vitest (frontend) |
| Config file | `backend/phpunit.xml` (verify exists) |
| Quick run command | `cd backend && php artisan test --filter=Inventory` |
| Full suite command | `cd backend && php artisan test` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| INVT-01 | Inventory items CRUD with categories | Feature (backend) | `php artisan test --filter=InventoryItemTest` | ❌ Wave 0 |
| INVT-02 | Movement ledger (purchase, consumption, adjustment, disposal, return) | Feature (backend) | `php artisan test --filter=InventoryMovementTest` | ❌ Wave 0 |
| INVT-03 | Minimum stock alert on critical movement | Feature (backend) | `php artisan test --filter=InventoryAlertTest` | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** `cd backend && php artisan test --filter=Inventory`
- **Per wave merge:** Full backend test suite
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `backend/tests/Feature/Inventory/InventoryItemTest.php` — covers INVT-01
- [ ] `backend/tests/Feature/Inventory/InventoryMovementTest.php` — covers INVT-02
- [ ] `backend/tests/Feature/Inventory/InventoryAlertTest.php` — covers INVT-03
- [ ] `backend/tests/Feature/Inventory/InventoryCategoryTest.php` — covers reference table CRUD
- [ ] `frontend/src/modules/inventory/__tests__/` — frontend component tests (scope determined by planner)

## Security Domain

> `security_enforcement` is enabled by default (absent in config = enabled).

### Applicable ASVS Categories
| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | no | Sanctum middleware handles this globally |
| V3 Session Management | no | Sanctum token-based |
| V4 Access Control | yes | `$this->middleware('permission:estoque.*')` pattern |
| V5 Input Validation | yes | FormRequest validation rules (StoreInventoryItemRequest, StoreInventoryMovementRequest) |
| V6 Cryptography | no | No sensitive data at rest |

### Known Threat Patterns for {Laravel + PostgreSQL}
| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| SQL injection | Tampering | Eloquent ORM (parameterized queries) |
| Unauthorized stock manipulation | Elevation of Privilege | Permission middleware (estoque.create, movimentacoes.create) |
| Negative stock exploitation | Tampering | DB transaction + FOR UPDATE lock + application validation triple layer |

## Sources

### Primary (HIGH confidence)
- Existing codebase: EquipmentController, EquipmentStore, EquipmentResource, migration compound pattern, LogsActivity trait — all verified via file reads
- CONTEXT.md — all locked decisions D-01 through D-22 verified
- `backend/database/seeders/RolePermissionSeeder.php` — permissions already seeded for estoque and movimentacoes

### Secondary (MEDIUM confidence)
- [CITED: inventorypath.com] — Event-sourced inventory patterns, append-only ledger vs CRUD, balance calculation performance data (~310ms p95 at 5M events for pure SUM, ~4ms with snapshot)
- [CITED: cleverence.com] — Inventory database design best practices, units of measure, location hierarchy, ACID considerations for stock movements
- [CITED: raltey.com/examples/inventory-warehouse-schema] — Multi-warehouse schema patterns, on_hand denormalization, stock_movement as immutable log

### Tertiary (LOW confidence)
- [CITED: miracuves.com] — Multi-vendor inventory patterns (not directly applicable to single-lab scenario)

## RESEARCH COMPLETE

**Phase:** 06 - Estoque (Inventory)
**Confidence:** HIGH

### Key Findings
1. **Append-only movement ledger with `balance_after` denormalization**: The inventory model follows industry best practice — every stock change is an immutable row, balance is read from the latest movement's `balance_after` column for O(1) reads, and negative stock is prevented by a three-layer defense (DB transaction + FOR UPDATE lock + application validation).
2. **Direct codebase reuse**: The module mirrors the Equipment pattern exactly — compound migration, HasUuids + SoftDeletes + LogsActivity on models, permission middleware on controllers, and ListPage + FormPage + DetailPage component structure. The existing `estoque.*` and `movimentacoes.*` permissions are already seeded.
3. **Critical stock alert is a two-level system**: Backend returns `is_critical` flag per item (computed accessor comparing balance to min_stock), and frontend renders a PrimeVue Toast on movement recording + inline DataTable row highlighting. The sidebar badge is deferred to Phase 11 (Dashboard).

### File Created
`.planning/phases/06-estoque/06-RESEARCH.md`

### Confidence Assessment
| Area | Level | Reason |
|------|-------|--------|
| Standard Stack | HIGH | Verified against existing codebase files (composer.json, package.json, controllers, models) |
| Architecture | HIGH | Patterns directly map from Phase 5 Equipment module; inventory ledger patterns confirmed via external research |
| Pitfalls | HIGH | Race conditions, balance performance, and initial balance issues are well-documented problems with standard mitigations |

### Open Questions
- Whether `reason` should be mandatory for all movement types or only `adjustment`/`disposal` (recommended: only for those two)
- Whether the MovementsPage should be the primary entry point for creating movements (recommended: accessible from both DetailPage tab and sidebar)

### Ready for Planning
Research complete. Planner can now create PLAN.md files for Phase 6 (Estoque).
