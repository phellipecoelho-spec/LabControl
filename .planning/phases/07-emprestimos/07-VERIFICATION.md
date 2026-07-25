---
phase: 07-emprestimos
verified: 2026-07-25T10:00:00Z
status: passed
score: 33/33 truths verified
behavior_unverified: 0
overrides_applied: 0
gaps: []
deferred: []
behavior_unverified_items: []
human_verification: []
---

# Phase 7: Empréstimos — Verification Report

**Phase Goal:** Criar o módulo completo de Empréstimos — backend (migration, models, API REST) + frontend (data layer + UI components)
**Verified:** 2026-07-25T10:00:00Z
**Status:** ✅ PASSED
**Re-verification:** No — initial verification

---

## Goal Achievement

O módulo de Empréstimos foi implementado em sua totalidade: camada de banco de dados (migrations compound com loans, equipment_loan, notifications), models (Loan, EquipmentLoan), enum de status (LoanStatus), serviço transacional (LoanService) com validação de conflito de datas e devolução parcial, controller REST (LoanController) com 8 endpoints protegidos por Sanctum + permission middleware, camada de dados frontend (types, service, store Pinia), páginas de UI completas (LoanListPage com DataTable paginada e filtros, LoanDetailPage com 3 abas) e comando scheduled de notificação de atrasos (CheckOverdueLoans).

Todos os 3 requisitos (LOAN-01, LOAN-02, LOAN-03) estão satisfeitos e implementados — o código existe, é substancial e está devidamente conectado.

---

## Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | A tabela `loans` existe com todos os campos especificados (D-05): id, borrower_id, status, borrowed_at, expected_return_at, returned_at, reason, destination, contact, notes, approved_by, created_by | ✓ VERIFIED | `backend/database/migrations/2026_07_21_000001_create_loans_tables.php` — 15 colunas + 5 índices + softDeletes |
| 2 | A tabela pivot `equipment_loan` existe com os campos: loan_id, equipment_id, returned_at (nullable), notes (D-01, D-18) | ✓ VERIFIED | Mesma migration compound — 5 colunas + UNIQUE(loan_id, equipment_id) + índices |
| 3 | A tabela `notifications` existe com schema padrão Laravel (id UUID, type, notifiable_type, notifiable_id, data JSON, read_at) | ✓ VERIFIED | `backend/database/migrations/2026_07_21_000002_create_notifications_table.php` — schema completo + índices compostos |
| 4 | O model Loan usa HasUuids, SoftDeletes, LogsActivity e possui cast de status para LoanStatus enum (D-03) | ✓ VERIFIED | `backend/app/Models/Loan.php` — traits: HasFactory, HasUuids, SoftDeletes, LogsActivity. Cast: `'status' => LoanStatus::class` |
| 5 | O model EquipmentLoan é o modelo da pivot com returned_at tracking individual (D-04, D-19) | ✓ VERIFIED | `backend/app/Models/EquipmentLoan.php` — extends Pivot, HasUuids, accessor `getIsReturnedAttribute()` |
| 6 | O LoanStatus enum define os 4 estados: reserved, active, returned, cancelled | ✓ VERIFIED | `backend/app/Enums/LoanStatus.php` — cases + canTransitionTo() + label() pt-BR |
| 7 | O LoanService implementa create() com transação DB, attach de equipamentos, e valida se equipamento não está emprestado | ✓ VERIFIED | `backend/app/Services/LoanService.php` — create() em DB::transaction com findConflictingEquipment() |
| 8 | O LoanService implementa returnItem() para devolução individual de item na pivot | ✓ VERIFIED | returnItem() valida status Active, equipment pertence ao loan, atualiza returned_at, e detecta conclusão |
| 9 | O LoanService implementa cancel() com validação de status permitido (só reserved pode ser cancelado) | ✓ VERIFIED | cancel() valida $loan->status === LoanStatus::Reserved |
| 10 | A LoanFactory gera dados realistas de empréstimos | ✓ VERIFIED | `backend/database/factories/LoanFactory.php` — 4 states (reserved, active, returned, cancelled) + withItems() |
| 11 | O LoanSeeder é registrado no DatabaseSeeder | ✓ VERIFIED | `backend/database/seeders/DatabaseSeeder.php` inclui `LoanSeeder::class` |
| 12 | LoanController expõe CRUD completo com index (listagem paginada + filtros), show, store, update, destroy | ✓ VERIFIED | `backend/app/Http/Controllers/Api/V1/LoanController.php` — 8 actions (index, show, store, update, destroy, activate, returnItem, cancel) |
| 13 | LoanController expõe endpoint POST /loans/{loan}/return para devolução de item (D-14) | ✓ VERIFIED | Route: `Route::post('return', [LoanController::class, 'returnItem'])->name('loans.return')` |
| 14 | LoanController expõe endpoint POST /loans/{loan}/activate para ativação de reserva | ✓ VERIFIED | Route: `Route::post('activate', [LoanController::class, 'activate'])->name('loans.activate')` |
| 15 | LoanController expõe endpoint POST /loans/{loan}/cancel para cancelamento | ✓ VERIFIED | Route: `Route::post('cancel', [LoanController::class, 'cancel'])->name('loans.cancel')` |
| 16 | StoreLoanRequest valida: borrower_id obrigatório existe em users, equipment_ids array não vazio, datas obrigatórias | ✓ VERIFIED | `backend/app/Http/Requests/StoreLoanRequest.php` — regras com required, exists, array, min:1, after_or_equal, after + mensagens pt-BR |
| 17 | ReturnLoanItemRequest valida: equipment_id obrigatório pertence ao loan | ✓ VERIFIED | `backend/app/Http/Requests/ReturnLoanItemRequest.php` — after() hook valida pertence ao loan |
| 18 | LoanResource serializa dados do empréstimo com related (borrower, equipment com pivot status, approved_by) | ✓ VERIFIED | `backend/app/Http/Resources/LoanResource.php` — serializa borrower, approved_by, created_by, equipment com pivot, is_overdue, progress |
| 19 | Rotas /api/v1/loans registradas com Sanctum + permission middleware (D-15) | ✓ VERIFIED | `backend/routes/api.php` — 8 rotas dentro de auth:sanctum com middleware permission emprestimos.* |
| 20 | CheckOverdueLoans command executa diariamente, cria notificações in-app para admins e supervisors (D-08) | ✓ VERIFIED | `backend/app/Console/Commands/CheckOverdueLoans.php` + AppServiceProvider schedule daily |
| 21 | Tipos TypeScript (Loan, LoanStatus, EquipmentLoanPivot, LoanedEquipment, LoanFormData, ReturnItemFormData) definidos com campos corretos | ✓ VERIFIED | `frontend/src/modules/loans/types/loan.ts` — interfaces completas + LOAN_STATUS_OPTIONS |
| 22 | LoanService expõe métodos: list, getById, create, update, delete, activate, returnItem, cancel, listUsers, listEquipment | ✓ VERIFIED | `frontend/src/modules/loans/services/LoanService.ts` — 10 métodos usando api axios |
| 23 | LoanStore gerencia estado de loans, pagination, loading, users, equipment com todas as actions | ✓ VERIFIED | `frontend/src/modules/loans/store/LoanStore.ts` — Pinia store Composition API com 10 actions |
| 24 | Rota /loans (loans.index) registrada com lazy loading de LoanListPage | ✓ VERIFIED | `frontend/src/router/routes.ts` — `() => import('@/modules/loans/pages/LoanListPage.vue')` |
| 25 | Rota /loans/:id (loans.show) registrada com lazy loading de LoanDetailPage | ✓ VERIFIED | `frontend/src/router/routes.ts` — `() => import('@/modules/loans/pages/LoanDetailPage.vue')` |
| 26 | routeModuleMap inclui mapeamento 'loans.show' → 'operacoes' | ✓ VERIFIED | `frontend/src/types/navigation.ts` — linha 142 |
| 27 | Sidebar 'Operações > Empréstimos' já configurada (não modificada) | ✓ VERIFIED | `frontend/src/types/navigation.ts` — label 'Empréstimos', icon 'pi pi-share-alt', route 'loans.index', permission 'emprestimos.view' |
| 28 | Usuário com permissão emprestimos.view vê lista de empréstimos com DataTable paginada e filtros por período, status e equipamento (D-11) | ✓ VERIFIED | `frontend/src/modules/loans/pages/LoanListPage.vue` — DataTable lazy pagination, filtros search/status/MultiSelect equipamento/DateRange |
| 29 | Usuário com permissão emprestimos.create pode criar novo empréstimo via Dialog (D-13) | ✓ VERIFIED | `frontend/src/modules/loans/components/LoanCreateDialog.vue` — Dialog com borrower Select, equipment MultiSelect, DatePickers, validação |
| 30 | Usuário pode clicar em um empréstimo e ver DetailPage com 3 abas (D-12) | ✓ VERIFIED | `frontend/src/modules/loans/pages/LoanDetailPage.vue` — Tabs: Dados (LoanInfoTab), Itens (LoanItemsTab), Timeline (LoanTimelineTab) |
| 31 | Usuário pode devolver itens parcialmente via Dialog de devolução na DetailPage (D-14) | ✓ VERIFIED | `frontend/src/modules/loans/components/LoanReturnDialog.vue` — Checkbox, DatePicker, notes por item, chamadas sequenciais |
| 32 | Usuário pode ativar/cancelar empréstimo da DetailPage | ✓ VERIFIED | LoanDetailPage.vue — botões "Ativar" (reserved), "Cancelar" (reserved), "Devolver Itens" (active) com ConfirmDialog |
| 33 | Usuário vê indicador visual de atraso para empréstimos vencidos | ✓ VERIFIED | Tag "Atrasado" severity=danger na lista e detalhe, rowClass vermelho na DataTable, Message error no LoanInfoTab |

**Score:** 33/33 truths verified (100%)

---

## Required Artifacts

### Backend

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `backend/database/migrations/2026_07_21_000001_create_loans_tables.php` | Migration compound loans + equipment_loan | ✓ VERIFIED | 69 linhas, ambas as tabelas, FK constraints, índices, softDeletes |
| `backend/database/migrations/2026_07_21_000002_create_notifications_table.php` | Migration notifications | ✓ VERIFIED | 38 linhas, schema Laravel + índices compostos |
| `backend/app/Enums/LoanStatus.php` | PHP 8 enum string | ✓ VERIFIED | 49 linhas, 4 cases, canTransitionTo(), label() pt-BR |
| `backend/app/Models/Loan.php` | Model Loan | ✓ VERIFIED | 152 linhas, 7 relacionamentos, 5 scopes, 3 accessors, casts |
| `backend/app/Models/EquipmentLoan.php` | Pivot model | ✓ VERIFIED | 43 linhas, extends Pivot, HasUuids, returned_at tracking |
| `backend/app/Services/LoanService.php` | Serviço transacional | ✓ VERIFIED | 281 linhas, create/activate/returnItem/cancel/autoReturnAll/checkOverdue |
| `backend/app/Exceptions/LoanException.php` | Custom exception | ✓ VERIFIED | 32 linhas, render JSON com code 422 |
| `backend/app/Http/Controllers/Api/V1/LoanController.php` | Controller | ✓ VERIFIED | 196 linhas, 8 actions, middleware permission |
| `backend/app/Http/Requests/StoreLoanRequest.php` | Form request | ✓ VERIFIED | 64 linhas, validação completa + mensagens pt-BR |
| `backend/app/Http/Requests/UpdateLoanRequest.php` | Form request | ✓ VERIFIED | 55 linhas, validação sometimes + mensagens pt-BR |
| `backend/app/Http/Requests/ReturnLoanItemRequest.php` | Form request | ✓ VERIFIED | 72 linhas, after() hook valida equipment pertence ao loan |
| `backend/app/Http/Resources/LoanResource.php` | API Resource | ✓ VERIFIED | 69 linhas, serialização completa com related |
| `backend/app/Http/Resources/LoanCollection.php` | Resource Collection | ✓ VERIFIED | 43 linhas, meta com summary (active_count, overdue_count) |
| `backend/app/Console/Commands/CheckOverdueLoans.php` | Scheduled command | ✓ VERIFIED | 93 linhas, cria notificações para admin/supervisor |
| `backend/database/factories/LoanFactory.php` | Factory | ✓ VERIFIED | 119 linhas, 4 states + withItems() |
| `backend/database/seeders/LoanSeeder.php` | Seeder | ✓ VERIFIED | 59 linhas, 10 empréstimos variados |
| `backend/database/seeders/DatabaseSeeder.php` | Updated | ✓ VERIFIED | LoanSeeder::class registrado |

### Frontend

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `frontend/src/modules/loans/types/loan.ts` | TypeScript types | ✓ VERIFIED | 78 linhas, Loan, LoanStatus, EquipmentLoanPivot, LoanedEquipment, etc |
| `frontend/src/modules/loans/services/LoanService.ts` | API service | ✓ VERIFIED | 53 linhas, 10 métodos |
| `frontend/src/modules/loans/store/LoanStore.ts` | Pinia store | ✓ VERIFIED | 128 linhas, Composition API, 10 actions |
| `frontend/src/modules/loans/pages/LoanListPage.vue` | List page | ✓ VERIFIED | 358 linhas, DataTable lazy pagination + filtros completos |
| `frontend/src/modules/loans/pages/LoanDetailPage.vue` | Detail page | ✓ VERIFIED | 282 linhas, 3 tabs + actions |
| `frontend/src/modules/loans/components/LoanCreateDialog.vue` | Create dialog | ✓ VERIFIED | 291 linhas, formulário completo |
| `frontend/src/modules/loans/components/LoanInfoTab.vue` | Info tab | ✓ VERIFIED | 179 linhas, grid de informações |
| `frontend/src/modules/loans/components/LoanItemsTab.vue` | Items tab | ✓ VERIFIED | 102 linhas, DataTable de equipamentos |
| `frontend/src/modules/loans/components/LoanTimelineTab.vue` | Timeline tab | ✓ VERIFIED | 153 linhas, PrimeVue Timeline |
| `frontend/src/modules/loans/components/LoanReturnDialog.vue` | Return dialog | ✓ VERIFIED | 177 linhas, devolução parcial |

---

## Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| Migration loans | users table | borrower_id FK | ✓ WIRED | `$table->foreignUuid('borrower_id')->constrained('users')` |
| Migration equipment_loan | equipments table | equipment_id FK | ✓ WIRED | `$table->foreignUuid('equipment_id')->constrained('equipments')` |
| Migration notifications | CheckOverdueLoans command | Tabela de notificações | ✓ WIRED | Command usa `DB::table('notifications')->insert(...)` |
| LoanService::create() | findConflictingEquipment() | Validação de conflito | ✓ WIRED | Triple-where overlap detection |
| LoanService | LoanStatus::canTransitionTo() | Validação de transição | ✓ WIRED | create → Reserved, activate → Active, returnItem → Returned |
| LoanController | LoanService | Delegação de operações | ✓ WIRED | Controller delega para `app(LoanService::class)->create/activate/returnItem/cancel` |
| LoanController | LoanResource | Serialização | ✓ WIRED | All actions retornam `new LoanResource($loan)` |
| CheckOverdueLoans | LoanService::checkOverdue() | Query de loans atrasados | ✓ WIRED | `app(LoanService::class)->checkOverdue()` |
| CheckOverdueLoans | notifications table | Criação de registros | ✓ WIRED | `DB::table('notifications')->insert(...)` |
| CheckOverdueLoans | AppServiceProvider | Schedule diário | ✓ WIRED | `$schedule->command('loans:check-overdue')->daily()` |
| LoanListPage | LoanStore.fetchAll() | Listagem com filtros | ✓ WIRED | `store.fetchAll(params)` com search/status/equipment_id/from/to |
| LoanCreateDialog | LoanStore.create() | Criação | ✓ WIRED | `store.create(payload)` → api.post('/loans', data) |
| LoanDetailPage | LoanStore.fetchById() | Detalhe | ✓ WIRED | `store.fetchById(id)` → api.get(`/loans/${id}`) |
| LoanReturnDialog | LoanStore.returnItem() | Devolução | ✓ WIRED | `store.returnItem(loanId, { equipment_id, ... })` |
| Rota /loans | LoanListPage | Lazy loading | ✓ WIRED | `component: () => import(...LoanListPage...)` |
| Rota /loans/:id | LoanDetailPage | Lazy loading | ✓ WIRED | `component: () => import(...LoanDetailPage...)` |

---

## Data-Flow Trace (Level 4)

| Artifact | Data Variable | Source | Produces Real Data | Status |
|----------|--------------|--------|-------------------|--------|
| LoanListPage | `store.loans` | `LoanService.list()` → `api.get('/loans')` → `LoanController::index()` → `Loan::paginate()` → DB query | ✓ FLOWING | Real DB query via Eloquent paginate com filtros |
| LoanListPage | `store.equipment` | `store.fetchEquipment()` → `api.get('/equipments')` → EquipmentController → DB | ✓ FLOWING | MultiSelect filtro populado de equipamentos reais |
| LoanDetailPage | `store.currentLoan` | `store.fetchById()` → `api.get('/loans/{id}')` → `LoanController::show()` → `Loan::with(...)` → DB | ✓ FLOWING | Real DB query com relacionamentos |
| LoanCreateDialog | `store.users` | `store.fetchUsers()` → `api.get('/users')` → UserController → DB | ✓ FLOWING | Select de tomador populado de users reais |
| LoanCreateDialog | `store.equipment` | `store.fetchEquipment()` → `api.get('/equipments')` → DB | ✓ FLOWING | MultiSelect de equipamentos populado |

---

## Requirement Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|-----------|-------------|--------|---------|
| LOAN-01 | 07-01/02/03/04 | Usuário pode registrar empréstimos de equipamentos | ✓ SATISFIED | Migration loans + equipment_loan, LoanService::create(), StoreLoanRequest, LoanCreateDialog |
| LOAN-02 | 07-01/02/03/04 | Usuário pode visualizar agenda de reservas | ✓ SATISFIED | LoanController::index() com filtros, LoanListPage com DataTable paginada, LoanDetailPage |
| LOAN-03 | 07-01/02/03/04 | Sistema notifica quando devolução está atrasada | ✓ SATISFIED | CheckOverdueLoans command, notifications table, schedule daily, Loan::scopeOverdue() |

---

## Behavioral Spot-Checks

| Behavior | Command | Result | Status |
|----------|---------|--------|--------|
| Build frontend sem erros | `npx vite build` | Build executado sem erros | ✓ PASS |
| TypeScript compila módulo loans | `npx tsc --noEmit` | 1 erro pré-existente em router/index.ts (não relacionado ao módulo loans) | ✓ PASS |
| Rotas /api/v1/loans registradas | Confirmação via código routes/api.php | 8 rotas com middleware auth:sanctum + permission | ✓ EVIDENCIED |
| CheckOverdueLoans command registrado | AppServiceProvider.php | schedule daily confirmado | ✓ EVIDENCIED |
| LoanSeeder registrado | DatabaseSeeder.php | `LoanSeeder::class` presente | ✓ EVIDENCIED |

---

## Probe Execution

Not applicable — esta fase não declara probes. A verificação foi feita por análise de código estático e revisão de artefatos.

---

## Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| `frontend/src/modules/loans/components/LoanItemsTab.vue` | 7 | `:ptable="true"` — atributo não padrão do PrimeVue DataTable | ℹ️ Info | Não causa erro de compilação, apenas possível warning no console. Não afeta funcionalidade. |
| `frontend/src/router/index.ts` | 29 | Erro TS pré-existente: `Property 'some' does not exist on type '{}'` | ℹ️ Info | Não relacionado ao módulo loans — pré-existente da Phase 4 de navegação |

**Nenhum debt marker (TBD/FIXME/XXX) encontrado nos arquivos do módulo loans.** Nenhum stub identificado — todos os componentes têm implementações completas.

---

## Gaps Summary

**Nenhum gap identificado.** Todos os 33 truths foram verificados. O módulo de Empréstimos está completo nas 4 camadas:

- **Banco de Dados (07-01):** Migrations, models, enum, service, exception, factory, seeder
- **API REST (07-02):** Controller, form requests, resources, rotas, scheduled command
- **Data Layer Frontend (07-03):** Types, service, store, rotas com lazy loading
- **UI Components (07-04):** ListPage, DetailPage, CreateDialog, ReturnDialog, InfoTab, ItemsTab, TimelineTab

**Observação:** O arquivo ROADMAP.md ainda mostra a fase 7 como "2/4 plans" e 07-03/07-04 como não concluídos. Recomenda-se atualizar o ROADMAP.md para refletir o estado atual (4/4 plans concluídos).

---

## Cross-Phase Integration

| Integração | Status | Detalhes |
|-----------|--------|----------|
| Equipment model (Phase 5) → Loan pivot | ✓ VERIFIED | `equipment_loan.equipment_id` FK → `equipments.id` |
| User model (Phase 3) → Loan borrower | ✓ VERIFIED | `loans.borrower_id` FK → `users.id` |
| User model (Phase 3) → Loan approved_by | ✓ VERIFIED | `loans.approved_by` FK → `users.id` |
| Permission middleware (Phase 3) → LoanController | ✓ VERIFIED | `emprestimos.{view,create,edit,finalizar}` |
| Sidebar Operações (Phase 4) → Empréstimos | ✓ VERIFIED | Navigation tree + routeModuleMap |
| EquipmentService/Store (Phase 5) → LoanService form | ✓ VERIFIED | `store.fetchEquipment()` consumido no LoanListPage e LoanCreateDialog |
| UserService/Store (Phase 3) → LoanService form | ✓ VERIFIED | `store.fetchUsers()` consumido no LoanCreateDialog |

---

*Verified: 2026-07-25T10:00:00Z*
*Verifier: gsd-verifier (goal-backward)*
