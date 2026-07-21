# Roadmap: LabControl

## Milestones

- ✅ **v0.1 Foundation** — Phases 1-4 (complete)
- 🚧 **v0.2 Core Business** — Phases 5-8 (next)
- 📋 **v0.3 Advanced Features** — Phases 9-12 (planned)
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

### 🚧 v0.2 Core Business (In Progress)

- [x] **Phase 5**: Equipamentos (6 plans)
  Plans:

  - [x] 05-01a-PLAN.md — Database migration (5 tables: categories, manufacturers, suppliers, equipments, equipment_photos)
  - [x] 05-01b-PLAN.md — Models, Factories, Seeders
  - [x] 05-02a-PLAN.md — Backend CRUD Controllers, Form Requests, API Routes
  - [x] 05-02b-PLAN.md — API Resources, Feature Tests (8 tests, 29 assertions)
  - [x] 05-03-PLAN.md — Frontend CRUD (List, Form, Detail pages, Store, Service, Types)
  - [x] 05-04-PLAN.md — Photo upload service, controller, frontend uploader, logs timeline
- [x] **Phase 6**: Estoque (3 plans)
  Plans:
  - [ ] 06-01-PLAN.md — Database migration (3 tables: inventory_categories, inventory_items, inventory_movements), Models, InventoryMovementService, Factories + Seeder
  - [ ] 06-02-PLAN.md — Backend CRUD (InventoryItemController, InventoryCategoryController, InventoryMovementController), 5 Form Requests, 3 API Resources, API routes
  - [ ] 06-03-PLAN.md — Frontend CRUD (List, Form, Detail pages), MovementsPage with filters, MovementDialog, TypeScript types, Pinia stores, routes, critical stock toast alert
- [ ] **Phase 7**: Empréstimos (3 plans)
- [ ] **Phase 8**: Calibrações (4 plans)

### 📋 v0.3 Advanced Features (Planned)

- [ ] **Phase 9**: Aferições (2 plans)
- [ ] **Phase 10**: Manutenções (2 plans)
- [ ] **Phase 11**: Dashboard (2 plans)
- [ ] **Phase 12**: Relatórios (2 plans)

### 📋 v1.0 Production (Planned)

- [ ] **Phase 13**: PWA e Offline (2 plans)
- [ ] **Phase 14**: Auditoria e Ajustes Finais (2 plans)

## Progress

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|---------------|--------|-----------|
| 1. Infraestrutura | v0.1 | 3/3 | Complete | 2026-07-19 |
| 2. Autenticação | v0.1 | 4/4 | Complete | 2026-07-19 |
| 3. Usuários e Permissões | v0.1 | 4/4 | Complete | 2026-07-19 |
| 4. Layout e Navegação | v0.1 | 3/3 | ✅ Complete | 2026-07-19 |
| 5. Equipamentos | v0.2 | 6/6 | ✅ Complete | 2026-07-20 |
| 6. Estoque | v0.2 | 0/3 | Not started | - |
| 7. Empréstimos | v0.2 | 0/3 | Not started | - |
| 8. Calibrações | v0.2 | 0/4 | Not started | - |
| 9. Aferições | v0.3 | 0/2 | Not started | - |
| 10. Manutenções | v0.3 | 0/2 | Not started | - |
| 11. Dashboard | v0.3 | 0/2 | Not started | - |
| 12. Relatórios | v0.3 | 0/2 | Not started | - |
| 13. PWA e Offline | v1.0 | 0/2 | Not started | - |
| 14. Auditoria e Ajustes Finais | v1.0 | 0/2 | Not started | - |
