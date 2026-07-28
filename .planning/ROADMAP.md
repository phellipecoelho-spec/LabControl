# Roadmap: LabControl

## Milestones

- ✅ **v0.1 Foundation** — Phases 1-4 (complete)
- ✅ **v0.2 Core Business** — Phases 5-8 (complete)
- ✅ **v0.3 Advanced Features** — Phases 9-12 (complete)
- 📋 **v1.0 Production** — Phases 13-14 (planned)

## Phases

### ✅ v0.1 Foundation (Complete)

- [x] **Phase 1**: Infraestrutura (3 plans)
- [x] **Phase 2**: Autenticação (4 plans)
- [x] **Phase 3**: Usuários e Permissões (4 plans)
- [x] **Phase 4**: Layout e Navegação (3 plans)
  Plans:

  - [x] 04-01-PLAN.md — Foundation: Theme system + Type definitions + Layout CSS
  - [x] 04-02-PLAN.md — App Shell: Layout components + Conditional rendering + Route meta
  - [x] 04-03-PLAN.md — Permissions + Mobile drawer + Accessibility polish

### ✅ v0.2 Core Business (Complete)

- [x] **Phase 5**: Equipamentos (6 plans)
  Plans:

  - [x] 05-01a-PLAN.md — Database migration (5 tables: categories, manufacturers, suppliers, equipments, equipment_photos)
  - [x] 05-01b-PLAN.md — Models, Factories, Seeders
  - [x] 05-02a-PLAN.md — Backend CRUD Controllers, Form Requests, API Routes
  - [x] 05-02b-PLAN.md — API Resources, Feature Tests (8 tests, 29 assertions)
  - [x] 05-03-PLAN.md — Frontend CRUD (List, Form, Detail pages, Store, Service, Types)
  - [x] 05-04-PLAN.md — Photo upload service, controller, frontend uploader, logs timeline
- [x] **Phase 6**: Estoque (3/3 plans)
  Plans:

  - [x] 06-01-PLAN.md — Database migration (3 tables: inventory_categories, inventory_items, inventory_movements), Models, InventoryMovementService, Factories + Seeder
  - [x] 06-02-PLAN.md — Backend CRUD (InventoryItemController, InventoryCategoryController, InventoryMovementController), 5 Form Requests, 3 API Resources, API routes
  - [x] 06-03-PLAN.md — Frontend CRUD (List, Form, Detail pages), MovementsPage with filters, MovementDialog, TypeScript types, Pinia stores, routes, critical stock toast alert
- [x] **Phase 7**: Empréstimos (4 plans)
  Plans:

  - [x] 07-01-PLAN.md — Database & Models: compound migration, 2 models, LoanStatus enum, LoanService, LoanException, Factory, Seeder
  - [x] 07-02-PLAN.md — Backend CRUD & API: LoanController, 3 Form Requests, 2 API Resources, routes, CheckOverdueLoans command
  - [x] 07-03-PLAN.md — Frontend Module: types, service, store, LoanListPage, LoanDetailPage, LoanCreateDialog, LoanReturnDialog, 3 tab components, routes
  - [x] 07-04-PLAN.md — Overdue Notifications: CheckOverdueLoans command, LoanOverdue notification class, scheduling, tests
- [x] **Phase 8**: Calibrações (4 plans)
  Plans:

  - [x] 08-01-PLAN.md — Database & Models: migration (calibrations + calibration_certificates), CalibrationStatus enum, Calibration/CalibrationCertificate models, CalibrationException, CalibrationService, CalibrationCertificateService, Factory, Seeder, permissions
  - [x] 08-02-PLAN.md — Backend API: CalibrationController (7 actions), CalibrationCertificateController (4 actions), 3 Form Requests, 2 API Resources, routes, CheckCalibrationDue command, CalibrationDue notification, schedule
  - [x] 08-03-PLAN.md — Frontend Data Layer: TypeScript types, CalibrationService (10 methods), CalibrationStore, routes registration
  - [x] 08-04-PLAN.md — Frontend UI Components: CalibrationListPage, CalibrationDetailPage, 3 tabs (Info/Certificate/Timeline), 2 dialogs (Create/Conclude)

### ✅ v0.3 Advanced Features (Complete)

- [x] **Phase 9**: Aferições (2 plans)
  Plans:

  - [x] 09-01-PLAN.md — Database & Backend Foundation (migrations, models, enum, exception, service, factory, seeder, permissions)
  - [x] 09-02-PLAN.md — API & Frontend Module (controller, requests, resources, notification, routes, types, service, store, pages, components, history tab)
- [x] **Phase 10**: Manutenções (2 plans)
  Plans:

  - [x] 10-01-PLAN.md — Database & Backend Foundation (migration, enums, models, service, factory, seeder, permissions, relationships)
  - [x] 10-02-PLAN.md — API & Frontend Module (controller, requests, resources, notification, routes, list page, dialogs, history tab, detail page, EquipmentDetailPage tab)
- [x] **Phase 11**: Dashboard (2/2 plans)
  Plans:

  - [x] 11-01-PLAN.md — Backend: DashboardService + DashboardController + rota API + Redis cache + testes
  - [x] 11-02-PLAN.md — Frontend: KPIs, 3 gráficos ECharts, EmptyState, DashboardPage, toolbar
- [x] **Phase 12**: Relatórios (2 plans)
  Plans:

  - [x] 12-01-PLAN.md — Backend: ReportService, PDF/Excel generation, ReportController, routes
  - [x] 12-02-PLAN.md — Frontend: ReportPage, filters, SplitButton per report type, download composable

### 📋 v1.0 Production (Planned)

- [x] **Phase 13**: PWA e Offline (2/2 plans)
  Plans:

  - [x] 13-01-PLAN.md — PWA Foundation: Service Worker, Dexie DB, vite-plugin-pwa config, offline API interceptor, connectivity monitor, nginx headers
  - [x] 13-02-PLAN.md — Sync Engine & PWA UI: sync service, conflict detection/resolution, Pinia sync store, SyncIndicator/ConflictDialog/UpdatePrompt UI, Dashboard timestamp
- [ ] **Phase 14**: Auditoria e Ajustes Finais (2 plans)

## Progress

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|---------------|--------|-----------|
| 1. Infraestrutura | v0.1 | 3/3 | Complete | 2026-07-19 |
| 2. Autenticação | v0.1 | 4/4 | Complete | 2026-07-19 |
| 3. Usuários e Permissões | v0.1 | 4/4 | Complete | 2026-07-19 |
| 4. Layout e Navegação | v0.1 | 3/3 | ✅ Complete | 2026-07-19 |
| 5. Equipamentos | v0.2 | 6/6 | ✅ Complete | 2026-07-20 |
| 6. Estoque | v0.2 | 3/3 | ✅ Complete | 2026-07-20 |
| 7. Empréstimos | v0.2 | 4/4 | Complete    | 2026-07-25 |
| 8. Calibrações | v0.2 | 4/4 | ✅ Complete | 2026-07-25 |  |
| 9. Aferições | v0.3 | 2/2 | ✅ Complete | 2026-07-25 |
| 10. Manutenções | v0.3 | 2/2 | ✅ Complete | 2026-07-25 |
| 11. Dashboard | v0.3 | 2/2 | ✅ Complete | 2026-07-27 |
| 12. Relatórios | v0.3 | 2/2 | ✅ Complete | 2026-07-27 |
| 13. PWA e Offline | v1.0 | 2/2 | ✅ Complete | 2026-07-28 |
| 14. Auditoria e Ajustes Finais | v1.0 | 0/2 | Not started | - |
