---
phase: 06-estoque
plan: 01
subsystem: database, backend
tags: migration, postgres, models, uuid, inventory, stock, ledger, service

requires:
  - phase: 05-equipamentos
    provides: Suppliers table, compound migration pattern, LogsActivity trait, model patterns (HasUuids, SoftDeletes)
  - phase: 03-usuarios-permissoes
    provides: Users table, LogsActivity trait, ActivityLog model

provides:
  - inventory_categories table (5 reference categories seeded)
  - inventory_items table (11 sample items with initial movements)
  - inventory_movements table (append-only ledger with DB CHECK constraint)
  - InventoryCategory model (HasUuids, SoftDeletes, LogsActivity, auto-slug)
  - InventoryItem model (HasUuids, SoftDeletes, LogsActivity, computed current_balance + is_critical)
  - InventoryMovement model (HasUuids, immutable — NO SoftDeletes)
  - InventoryMovementService (transactional recordMovement with lockForUpdate + InsufficientStockException)
  - InventoryCategoryFactory, InventoryItemFactory
  - InventorySeeder (5 categories, 11 items, 11 initial movements)

affects:
  - Phase 6 future plans (frontend CRUD, movements page)

tech-stack:
  added: []
  patterns:
    - Compound migration layout (3 tables, FK-safe down)
    - Append-only movement ledger with denormalized balance_after
    - Three-layer negative stock defense (DB transaction + row lock + validation)
    - Computed model accessors for stock balance (O(1) via latest movement)

key-files:
  created:
    - backend/database/migrations/2026_07_20_000001_create_inventory_tables.php
    - backend/app/Models/InventoryCategory.php
    - backend/app/Models/InventoryItem.php
    - backend/app/Models/InventoryMovement.php
    - backend/app/Services/InventoryMovementService.php
    - backend/app/Exceptions/InsufficientStockException.php
    - backend/database/factories/InventoryCategoryFactory.php
    - backend/database/factories/InventoryItemFactory.php
    - backend/database/seeders/InventorySeeder.php
  modified:
    - backend/database/seeders/DatabaseSeeder.php

key-decisions:
  - "Inventory categories are separate from equipment categories (D-02)"
  - "supplier_id is NOT NULL — every inventory item requires a supplier (D-03, D-14)"
  - "Balance is never stored on inventory_items — computed from movements (D-10)"
  - "Movement table is append-only with NO softDeletes — corrections via compensating entries"
  - "balance_after is denormalized per movement for O(1) balance reads"
  - "CHECK (balance_after >= 0) on inventory_movements as third-layer safety net"

patterns-established:
  - "Append-only inventory ledger: every stock change is an immutable movement row"
  - "O(1) balance reads via balance_after from latest movement, not SUM aggregation"
  - "Three-layer negative stock defense: DB transaction + row-level FOR UPDATE lock + application validation"
  - "Compound migration for related tables (same pattern as Phase 5 equipments)"

requirements-completed: [INVT-01, INVT-02]

coverage:
  - id: D1
    description: "Inventory compound migration — creates inventory_categories, inventory_items, inventory_movements with FKs, indexes, CHECK constraints"
    requirement: INVT-01
    verification:
      - kind: integration
        ref: "migrate:fresh passes without errors"
        status: pass
    human_judgment: false
  - id: D2
    description: "InventoryCategory model with HasUuids, SoftDeletes, LogsActivity, auto-slug, search scope"
    requirement: INVT-01
    verification:
      - kind: integration
        ref: "php artisan tinker class_exists check"
        status: pass
    human_judgment: false
  - id: D3
    description: "InventoryItem model with computed current_balance (O(1) via latest movement), is_critical accessor, search/category/supplier/unit/critical scopes"
    requirement: INVT-01
    verification:
      - kind: integration
        ref: "verified model loaded, balance=10 for first item (10 L HCl), is_critical=false"
        status: pass
    human_judgment: false
  - id: D4
    description: "InventoryMovement model (immutable — no SoftDeletes), HasUuids, type/item/user/date scopes"
    requirement: INVT-02
    verification:
      - kind: integration
        ref: "verified Immutable trait check: SoftDeletes absent on Movement"
        status: pass
    human_judgment: false
  - id: D5
    description: "InventoryMovementService with transactional recordMovement, lockForUpdate, InsufficientStockException"
    requirement: INVT-02
    verification:
      - kind: integration
        ref: "Service class loaded via php artisan tinker"
        status: pass
    human_judgment: false
  - id: D6
    description: "Inventory seeder with 5 categories, 11 items, and initial purchase movements"
    requirement: INVT-01
    verification:
      - kind: integration
        ref: "seeded: 5 categories, 11 items, 11 movements"
        status: pass
    human_judgment: false

duration: 12min
completed: 2026-07-20
status: complete
---

# Phase 6 Plan 01: Inventory Database Schema & Model Layer

**Compound migration with 3 inventory tables (categories, items, movements), 3 Eloquent models with computed balance accessors, transactional movement service with three-layer negative stock defense, and reference seed data**

## Performance

- **Duration:** 12 min
- **Started:** 2026-07-20T22:22:27Z
- **Completed:** 2026-07-20T22:34:30Z
- **Tasks:** 2
- **Files modified:** 10

## Accomplishments

- Created compound migration `2026_07_20_000001_create_inventory_tables.php` with 3 tables, all FKs, composite indexes, and DB CHECK constraint for non-negative balance
- Created `InventoryCategory` model with HasUuids, SoftDeletes, LogsActivity, auto-slug generation, and search scope
- Created `InventoryItem` model with computed `current_balance` (O(1) via latest movement's `balance_after`), `is_critical` accessor, and 4 query scopes (search, byCategory, bySupplier, byUnit, critical)
- Created `InventoryMovement` model as immutable ledger entry (HasUuids, NO SoftDeletes) with 4 filter scopes
- Created `InventoryMovementService` with three-layer negative stock defense: `DB::transaction()` + `lockForUpdate()` row lock + `InsufficientStockException` application validation
- Created `InsufficientStockException` custom exception class with descriptive Portuguese error message
- Created `InventoryCategoryFactory` with predefined laboratory category names
- Created `InventoryItemFactory` with 28 realistic laboratory item templates
- Created `InventorySeeder` populating 5 categories, 11 items distributed across categories, each with initial purchase movement
- Registered `InventorySeeder` in `DatabaseSeeder.php`

## Task Commits

Each task was committed atomically:

1. **Task 1: Create compound migration** — `0ebcff3` (feat)
2. **Task 2: Create Models, Service, Factory, Seeder** — `bc10f25` (feat)

**Plan metadata:** *Pending final commit*

## Files Created/Modified

### Created
- `backend/database/migrations/2026_07_20_000001_create_inventory_tables.php` — Compound migration: 3 tables, FKs, indexes, CHECK constraint
- `backend/app/Models/InventoryCategory.php` — Category model with auto-slug, search scope
- `backend/app/Models/InventoryItem.php` — Item model with computed balance + critical flag, 4 scopes
- `backend/app/Models/InventoryMovement.php` — Movement model (immutable, no SoftDeletes), 4 filter scopes
- `backend/app/Services/InventoryMovementService.php` — Transactional movement service with lockForUpdate
- `backend/app/Exceptions/InsufficientStockException.php` — Custom 422 exception for negative stock
- `backend/database/factories/InventoryCategoryFactory.php` — Factory for 5 category names
- `backend/database/factories/InventoryItemFactory.php` — Factory with 28 laboratory item templates
- `backend/database/seeders/InventorySeeder.php` — Seeder: 5 categories, 11 items, 11 movements

### Modified
- `backend/database/seeders/DatabaseSeeder.php` — Registered InventorySeeder

## Decisions Made

All decisions followed the plan and D-decisions from CONTEXT.md:

- **D-02 follow-through:** Created `inventory_categories` as separate table from equipment categories
- **D-03 follow-through:** `supplier_id` is NOT NULL with FK constraint to existing `suppliers` table
- **D-05 follow-through:** `physical_location` implemented as varchar(255) text field
- **D-07 follow-through:** Movement types fixed: purchase, consumption, adjustment, disposal, return
- **D-08 follow-through:** Movement record includes type, quantity, balance_after, reason, notes, user_id
- **D-10 follow-through:** Balance computed from movements only — no stored quantity on items
- **D-16 follow-through:** Unit field is varchar(10) with application-level validation for fixed list
- Three-layer negative stock defense: DB CHECK constraint (`balance_after >= 0`) + row-level `FOR UPDATE` lock + `InsufficientStockException` in service

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered

- Initial `migrate:fresh --seed --seeder=InventorySeeder` failed because no users existed when running the seeder standalone. Fixed by using `migrate:fresh --seed` (full DatabaseSeeder chain) which runs AdminUserSeeder first.

## User Setup Required

None — no external service configuration required.

## Next Phase Readiness

- Database schema and model layer complete
- 5 inventory categories, 11 items with initial movements seeded
- Ready for backend CRUD (controllers, requests, resources) and frontend pages
- `InventoryMovementService::recordMovement()` ready for controller integration
- Next plan (06-02): backend CRUD controllers + form requests + API resources + routes

## Self-Check: PASSED

All 10 files verified present on disk. Both commits (`0ebcff3`, `bc10f25`) verified in git log. Database migration and seed verified running with correct data (5 categories, 11 items, 11 movements).

---

*Phase: 06-estoque*
*Completed: 2026-07-20*
