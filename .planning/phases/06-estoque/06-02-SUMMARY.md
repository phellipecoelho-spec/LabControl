---
phase: 06-estoque
plan: 02
subsystem: estoque
tags: [api, controllers, form-requests, api-resources, routes, rest]
dependency_graph:
  requires: [06-01]
  provides: [inventory-api-layer]
  affects: [frontend-module, api-consumers]
tech-stack:
  added: []
  patterns:
    - Static middleware() pattern per EquipmentController
    - FormRequest validation with Portuguese messages
    - API Resource with whenLoaded for relationships
    - Controller-based permission middleware (estoque.*, movimentacoes.*)
    - Immutable movement ledger with service delegation
    - Computed current_balance and is_critical via model appended attributes
    - DB::transaction for initial stock creation
    - InsufficientStockException handling with 422 response
key-files:
  created:
    - backend/app/Http/Controllers/Api/V1/InventoryItemController.php
    - backend/app/Http/Controllers/Api/V1/InventoryCategoryController.php
    - backend/app/Http/Controllers/Api/V1/InventoryMovementController.php
    - backend/app/Http/Requests/StoreInventoryItemRequest.php
    - backend/app/Http/Requests/UpdateInventoryItemRequest.php
    - backend/app/Http/Requests/StoreInventoryCategoryRequest.php
    - backend/app/Http/Requests/UpdateInventoryCategoryRequest.php
    - backend/app/Http/Requests/StoreInventoryMovementRequest.php
    - backend/app/Http/Resources/InventoryCategoryResource.php
    - backend/app/Http/Resources/InventoryItemResource.php
    - backend/app/Http/Resources/InventoryMovementResource.php
  modified:
    - backend/routes/api.php
decisions:
  - Moved byItem method to InventoryMovementController (plan had conflicting sections)
status: complete
metrics:
  duration: 0h45m
  completed_date: 2026-07-20
---

# Phase 6 Plan 2: Inventory REST API Layer Summary

**Objective:** Create the complete REST API layer for the Inventory module — controllers, form requests, API resources, and routes.

**Result:** 3 Controllers, 5 Form Requests, 3 API Resources, and 13 routes created and verified.

## Key Decisions

1. **byItem location:** The plan's route definition routed `inventory-items/{item}/movements` to `InventoryMovementController::byItem`, while the controller section described `byItem` on `InventoryItemController`. We placed it on `InventoryMovementController` (matching the route definition) since it returns `InventoryMovementResource::collection`.

2. **byItem permissions:** Since `byItem` sits on `InventoryMovementController` but operates on an inventory item context, we applied the `movimentacoes.view` permission (consistency with the controller's other methods).

## Tasks Completed

### Task 1: Form Requests (5 files)
- **StoreInventoryItemRequest** — validates name, category_id, supplier_id, unit (D-16 fixed list), min_stock required; accepts optional initial_quantity for initial stock creation
- **UpdateInventoryItemRequest** — `sometimes` rules for partial updates; unique:code exclusion via route param; no initial_quantity
- **StoreInventoryCategoryRequest** — name required, slug unique
- **UpdateInventoryCategoryRequest** — `sometimes` rules with slug exclusion
- **StoreInventoryMovementRequest** — type validated against 5 fixed values (purchase, consumption, adjustment, disposal, return); reason required_if adjustment/disposal
- All with `authorize() → true` and Portuguese error messages

### Task 2: API Resources (3 files)
- **InventoryCategoryResource** — id, name, slug, timestamps
- **InventoryItemResource** — computed current_balance and is_critical flags; whenLoaded for category and supplier
- **InventoryMovementResource** — quantity_display with sign (+/-) based on type; whenLoaded for item and user

### Task 3: Controllers + Routes (3 controllers + api.php)
- **InventoryItemController** — Full CRUD with permission middleware (estoque.*); search by name/code, filter by category/supplier/unit/critical stock; initial purchase movement creation in transaction when initial_quantity > 0
- **InventoryCategoryController** — index, store, update, destroy with soft delete
- **InventoryMovementController** — Immutable store/index/show; delegates to InventoryMovementService; catches InsufficientStockException → 422; returns is_critical in meta on store; byItem for item movement history
- **Routes** — 13 routes registered under `/api/v1/` with `auth:sanctum`

## Routes (13 total)

| Method | URI | Action |
|--------|-----|--------|
| GET|HEAD | `/api/v1/inventory-items` | index |
| POST | `/api/v1/inventory-items` | store |
| GET|HEAD | `/api/v1/inventory-items/{inventory_item}` | show |
| PUT|PATCH | `/api/v1/inventory-items/{inventory_item}` | update |
| DELETE | `/api/v1/inventory-items/{inventory_item}` | destroy |
| GET|HEAD | `/api/v1/inventory-items/{item}/movements` | byItem |
| GET|HEAD | `/api/v1/inventory-categories` | index |
| POST | `/api/v1/inventory-categories` | store |
| PUT|PATCH | `/api/v1/inventory-categories/{inventory_category}` | update |
| DELETE | `/api/v1/inventory-categories/{inventory_category}` | destroy |
| GET|HEAD | `/api/v1/inventory-movements` | index |
| POST | `/api/v1/inventory-movements` | store |
| GET|HEAD | `/api/v1/inventory-movements/{inventory_movement}` | show |

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug Fix] Duplicate import in InventoryMovementController**
- **Found during:** Task 3 (file review)
- **Issue:** `use App\Models\InventoryItem;` was duplicated (lines 9 and 10)
- **Fix:** Removed duplicate import
- **Commit:** Part of `c3cd437`

### Plan Ambiguity — Resolved
- The plan describes `byItem` method on `InventoryItemController` (Task 3, section A) but routes it to `InventoryMovementController` (Task 3, section D). Resolved by placing the method on `InventoryMovementController` with proper route model binding.

## Verification

- ✅ `php artisan route:list --path=v1/inventory` shows **13 routes**
- ✅ All `php -l` lint checks pass (via Docker)
- ✅ `migrate:fresh --seed` runs successfully with full inventory seeding (5 categories, 11 items)
- ✅ Permission middleware applied: `estoque.*` for items/categories, `movimentacoes.*` for movements
- ✅ All FormRequests have `authorize() → true` (permission handled by controller middleware)

## Threat Mitigations

| Threat ID | Status | Verification |
|-----------|--------|-------------|
| T-06-03 | ✅ Mitigated | Static middleware() enforces permission:estoque.* per action |
| T-06-04 | ✅ Mitigated | StoreInventoryMovementRequest validates type whitelist, quantity >= 1, reason required_if |
| T-06-05 | ✅ Mitigated | permission:movimentacoes.{view,create} applied to movement controller |
| T-06-06 | ✅ Mitigated | All index() methods use paginate(15) — no unbounded queries |
| T-06-07 | ✅ Accepted | UUID generation via gen_random_uuid() |
| T-06-SC | ✅ N/A | No new package installs |

## Self-Check: PASSED

- `backend/app/Http/Controllers/Api/V1/InventoryItemController.php` ✅
- `backend/app/Http/Controllers/Api/V1/InventoryCategoryController.php` ✅
- `backend/app/Http/Controllers/Api/V1/InventoryMovementController.php` ✅
- `backend/app/Http/Requests/StoreInventoryItemRequest.php` ✅
- `backend/app/Http/Requests/UpdateInventoryItemRequest.php` ✅
- `backend/app/Http/Requests/StoreInventoryCategoryRequest.php` ✅
- `backend/app/Http/Requests/UpdateInventoryCategoryRequest.php` ✅
- `backend/app/Http/Requests/StoreInventoryMovementRequest.php` ✅
- `backend/app/Http/Resources/InventoryCategoryResource.php` ✅
- `backend/app/Http/Resources/InventoryItemResource.php` ✅
- `backend/app/Http/Resources/InventoryMovementResource.php` ✅
- `backend/routes/api.php` modified ✅
- 13 routes registered ✅
