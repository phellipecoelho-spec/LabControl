---
gsd_state_version: 1.0
milestone: v0.2
milestone_name: Core Business
current_phase: 07
status: executing
stopped_at: Phase 07 - Plan 01 completed
last_updated: "2026-07-21T17:15:00.000Z"
last_activity: 2026-07-21
progress:
  total_phases: 7
  completed_phases: 5
  total_plans: 29
  completed_plans: 24
  percent: 75
---

# State: LabControl

## Project Reference

See: .planning/PROJECT.md (updated 2026-07-19)

**Core value:** Rastreabilidade completa de equipamentos laboratoriais

## Current Status

**Current Phase:** 07
**Status:** Executing Phase 07
**Last activity:** 2026-07-21

## Plan Progress

| Plan | Status | Summary |
|------|--------|---------|
| 01 - Backend Auth API | ✅ Completed | AuthController, 6 Form Requests, Sanctum SPA, rate limiting |
| 02 - Frontend Auth | ✅ Completed | Store, 6 views, router guards, axios interceptor |
| 03 - Email & Reset Integration | ✅ Completed | Notifications, templates, verify/reset flows |
| 04 - Tests | ✅ Completed | 18 tests, 47 assertions, all passing |

## Phase 3 — Usuários e Permissões

| Plan | Status | Summary |
|------|--------|---------|
| 01 - Models, API, CRUD | ✅ Completed | User/Role/Permission models, controllers, seeder, tests |
| 02 - Frontend User/Role Admin | ✅ Completed | User and Role management pages with CRUD UI |
| 03 - Profile & Avatar | ✅ Completed | Profile page, AvatarService, password change |
| 04 - Activity Logging | ✅ Completed | ActivityLog model, LogsActivity trait, auth event hooks, Timeline viewer, 10 tests |

## Phase 4 — Layout e Navegação (3 Plans ✅)

**Plan 03 completed** 2026-07-19 — Permission filtering, mobile Drawer overlay, accessibility polish

### Plans

| Plan | Wave | Status | Description |
|------|------|--------|-------------|
| 01 — Foundation | 1 | ✅ Completed | Navigation types, useTheme composable, layout.css, main.ts import |
| 02 — App Shell | 2 | ✅ Completed | AppSidebar, AppTopbar, AppLayout, App.vue conditional layout, routes.ts meta |
| 03 — Polish | 3 | ✅ Completed | Permission filtering, mobile Drawer, accessibility, build verification |

**Decisões de design:**

- **Sidebar:** PanelMenu accordion (PrimeVue), colapsável 240px/64px, mobile drawer overlay
- **Topbar:** Menu do usuário, dark/light toggle, notificações placeholder, hamburger toggle
- **Tema:** Dark mode padrão (#0f172a), toggle manual localStorage, accent Indigo (#6366f1)
- **Tipografia:** 4 sizes (13/14/20/28px), 2 weights (400/600)
- **Navegação:** Módulos agrupados por categoria (Gestão, Operações, Administração, Relatórios), Dashboard fixo

## Phase 5 — Equipamentos (6 Plans ✅)

**Plan 05-04 completed** 2026-07-20 — Equipment photo upload service, controller, frontend uploader, logs timeline

### Plans

| Plan | Subsystem | Status | Description |
|------|-----------|--------|-------------|
| 01a — Database | Database | ✅ Completed | Migration with 5 tables (categories, manufacturers, suppliers, equipments, equipment_photos) |
| 01b — Models | Backend | ✅ Completed | Equipment, Category, Manufacturer, Supplier, EquipmentPhoto models + factories + seeders |
| 02a — Backend CRUD | Backend | ✅ Completed | EquipmentController, CategoryController, ManufacturerController, SupplierController, Form Requests, 21 API routes |
| 02b — API Resources | Backend | ✅ Completed | API Resources, 8 Feature Tests (29 assertions) |
| 03 — Frontend CRUD | Frontend | ✅ Completed | EquipmentListPage, EquipmentFormPage, EquipmentDetailPage, EquipmentStore, EquipmentService, navigation routes |
| 04 — Photos & History | Fullstack | ✅ Completed | EquipmentPhotoService, EquipmentPhotoController, EquipmentPhotoUploader, EquipmentLogsSection |

## Phase 6 — Estoque (3 Plans ✅)

**Plan 03 completed** 2026-07-20 — Frontend module: 6 types/services/stores, 4 pages, 3 components, 5 routes

### Plans

| Plan | Subsystem | Status | Description |
|------|-----------|--------|-------------|
| 01 — Database & Models | Backend | ✅ Completed | Compound migration (3 tables), 3 models, InventoryMovementService, InsufficientStockException, 2 factories, seeder (5 categories, 11 items, 11 movements) |
| 02 — REST API Layer | Backend | ✅ Completed | 3 Controllers (InventoryItem, InventoryCategory, InventoryMovement), 5 Form Requests, 3 API Resources, 13 routes under /api/v1/ with Sanctum + permission middleware |
| 03 — Frontend Module | Frontend | ✅ Completed | Types, 3 API services, 2 Pinia stores, 4 pages (List, Form, Detail, Movements), 3 components (InfoTab, MovementTab, MovementDialog), routes updated |

## Decisions

| Decision | Outcome |
|----------|---------|
| Vue 3 + PrimeVue | ✓ Good |
| Laravel + PostgreSQL | ✓ Good |
| Docker Compose | ✓ Good |
| UUIDs | ✓ Good |
| Sanctum SPA (session cookies) | ✓ Implemented |
| Rate limiting 5 req/min | ✓ Implemented |
| Email verification (signed URL) | ✓ Implemented |
| Password reset (60 min expiry) | ✓ Implemented |
| Remember me (30 days) | ✓ Implemented |
| Custom notification classes | ✓ Implemented |
| Session middleware on API routes | ✓ Implemented |
| LogsActivity trait for model event logging | ✓ Implemented (reusable bootable trait) |
| ActivityLogService for non-model events | ✓ Implemented (auth events, custom logging) |
| Navigation types: NavCategory + NavModule for PanelMenu | ✓ Implemented |
| useTheme composable with readonly(isDark) | ✓ Implemented |
| layout.css uses CSS Grid with grid-template-areas | ✓ Implemented |
| Collapsed sidebar hides PanelMenu labels via CSS display:none | ✓ Implemented |
| AppSidebar uses computed panelMenuModel for reactive permission filtering | ✓ Implemented |
| Dashboard link rendered outside PanelMenu (fixed at top) per D-16 | ✓ Implemented |
| App.vue uses v-if/else for conditional layout rendering | ✓ Implemented |
| sidebarCollapsed state managed in AppLayout, passed as props/events | ✓ Implemented |
| Route meta module field for sidebar active-state detection | ✓ Implemented |
| Mobile Drawer uses PrimeVue Drawer with useMediaQuery breakpoint | ✓ Implemented |
| filteredPanelMenuModel computed reactive to authStore.user | ✓ Implemented |
| Avatar wrapped in button for keyboard accessibility | ✓ Implemented |
| Tooltip directive registered globally for collapsed sidebar hints | ✓ Implemented |
| Single migration for all 5 equipment tables (atomic deployment) | ✓ Implemented |
| Equipment photos with cascade delete | ✓ Implemented |
| equipment_photos without softDeletes or updated_at | ✓ Implemented |
| Permission middleware via HasMiddleware trait | ✓ Implemented |
| useInfiniteScroll composable for server-side pagination | ✓ Implemented |
| Module-scoped router for equipment routes | ✓ Implemented |
| 5-tab detail page (Info, Technical, Location, Photos, Logs) | ✓ Implemented |
| Composite index equipment_photos(equipment_id, sort_order) | ✓ Implemented |
| Inventory categories separate from equipment categories (D-02) | ✓ Implemented |
| supplier_id NOT NULL on inventory_items (D-03, D-14) | ✓ Implemented |
| Balance computed from movements, not stored on items (D-10) | ✓ Implemented |
| Append-only movement ledger — no SoftDeletes on movements | ✓ Implemented |
| balance_after denormalized per movement for O(1) reads | ✓ Implemented |
| CHECK (balance_after >= 0) as safety net for negative stock | ✓ Implemented |
| Three-layer negative stock defense: tx + lock + validation | ✓ Implemented |

## Blockers

- None

## Accumulated Context

Phase 2 (Autenticação) concluída com sucesso:

- Backend: AuthController com 8 endpoints, Sanctum SPA com cookies HttpOnly, rate limiting, email verification, password reset
- Frontend: 6 views de autenticação, Pinia store, router guards (guest, requiresAuth, requiresVerified, roles)
- Testes: 18 testes backend passando (Login, Register, VerifyEmail, PasswordReset, Logout)

Phase 3 (Usuários e Permissões) — Planos 01-04 concluídos:

- Plan 01: User/Role/Permission models, controllers, seeder com roles (admin, supervisor, laboratorista, tecnico, consulta, auditor) e permissões
- Plan 02: Frontend CRUD de usuários e roles com PrimeVue DataTable, formulários, gerenciamento de permissões
- Plan 03: Profile page, AvatarService, alteração de senha
- Plan 04: ActivityLog model, LogsActivity trait, UserObserver, ActivityLogService, 8 auth event hooks no AuthController, ActivityLogController com 3 endpoints de consulta, AuditLogsPage.vue com Timeline PrimeVue, 10 testes

Phase 4 (Layout e Navegação) — 3 planos executados:

- Plan 01: Navigation types (NavModule, NavCategory), useTheme composable (localStorage), layout.css (grid, custom properties, breakpoints 768px), main.ts import
- Plan 02: AppSidebar (PanelMenu accordion, 4 categorias, Dashboard fixo), AppTopbar (user menu, dark/light toggle, notificações placeholder, hamburger), AppLayout (shell wrapper), App.vue conditional layout (auth vs authed), routes.ts meta module
- Plan 03: Permission filtering via hasPermission(), mobile Drawer overlay (<768px), a11y (skip-to-content, ARIA labels, keyboard navigation, v-tooltip), build passes 2.73s

Phase 5 (Equipamentos) — 6 planos concluídos:

- Plan 05-01a: Migration única com 5 tabelas (categories, manufacturers, suppliers, equipments, equipment_photos), UUIDs, softDeletes, deleted_by audit
- Plan 05-01b: Models Equipment, Category, Manufacturer, Supplier, EquipmentPhoto com relacionamentos, factories, seeders com dados iniciais
- Plan 05-02a: EquipmentController CRUD completo, CategoryController, ManufacturerController, SupplierController, StoreEquipmentRequest, UpdateEquipmentRequest, 21 rotas API com middleware de permissão
- Plan 05-02b: EquipmentResource, CategoryResource, ManufacturerResource, SupplierResource, 8 Feature Tests (29 assertions)
- Plan 05-03: Frontend completo — EquipmentListPage com DataTable paginada, EquipmentFormPage com formulário de abas, EquipmentDetailPage com 5 tabs, EquipmentStore (Pinia), EquipmentService (axios), roteamento aninhado por módulo
- Plan 05-04: EquipmentPhotoService (upload/storage/thumbnails), EquipmentPhotoController (6 rotas), EquipmentPhotoUploader.vue (drag & drop, preview, sort), EquipmentLogsSection.vue (timeline de alterações)

Phase 6 (Estoque) — 3 planos concluídos:

- Plan 06-01: Compound migration com 3 tabelas (inventory_categories, inventory_items, inventory_movements), UUIDs, CHECK constraint, índices compostos. Models: InventoryCategory (HasUuids, SoftDeletes, LogsActivity, auto-slug), InventoryItem (HasUuids, SoftDeletes, LogsActivity, computed current_balance, is_critical, 4 scopes), InventoryMovement (HasUuids, imutável — sem SoftDeletes). InventoryMovementService com DB::transaction + lockForUpdate + InsufficientStockException. Seeder: 5 categorias, 11 itens com movimentações iniciais.
- Plan 06-02: REST API layer — 3 Controllers with static permission middleware (InventoryItemController full CRUD + initial stock movement, InventoryCategoryController index/store/update/destroy, InventoryMovementController immutable index/store/show + byItem). 5 Form Requests with validation rules (StoreInventoryItemRequest validates unit list D-16, supplier_id required D-14; StoreInventoryMovementRequest validates type D-07, reason required_if adjustment/disposal D-08). 3 API Resources with computed attributes (current_balance, is_critical, quantity_display). 13 routes under /api/v1/inventory-*. All controllers enforce auth:sanctum and permission:estoque.* / permission:movimentacoes.*.
- Plan 06-03: Frontend module — TypeScript interfaces (InventoryItem, InventoryCategory, InventoryMovement, form data), 3 API services (item, category, movement), 2 Pinia stores (InventoryItemStore, InventoryMovementStore), 4 pages (InventoryItemListPage with filters + critical row styling, InventoryItemFormPage with 2 tabs Principal+Armazenamento, InventoryItemDetailPage with 2 tabs Dados do Item+Movimentações, InventoryMovementsPage with filters + movement dialog). 3 components (InventoryItemInfoTab, InventoryMovementTab, InventoryMovementDialog). 5 routes registered replacing placeholder pages. Vite build passes in 7.28s.

## Session

**Last session:** 2026-07-21T10:03:06.354Z
**Stopped at:** Phase 07 context gathered
**Resume file:** .planning/phases/07-emprestimos/07-CONTEXT.md
