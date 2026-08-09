---
phase: 15-corre-es-de-funcionamento
verified: 2026-08-09T23:50:00Z
status: human_needed
score: 10/10 truths verified
behavior_unverified: 0
overrides_applied: 0
gaps: []
human_verification:
  - test: "Seed 2x no PostgreSQL real: `docker compose -f docker/docker-compose.yml exec -T php php artisan migrate:fresh --seed --force` duas vezes seguidas, depois logar com admin@labcontrol.com / @dmin123 na UI"
    expected: "1ª e 2ª execução do seeder sem exceção; admin único (sem duplicação de roles/categorias); login admin@labcontrol.com com @dmin123 funciona e o usuário vê os módulos conforme o perfil admin"
    why_human: "phpunit usa sqlite :memory: (phpunit.xml). O contrato de BUG-01 'admin loga em ambiente limpo' no banco real (PostgreSQL via Docker) não pode ser provado pela suíte automatizada; migrate:fresh é uma operação destrutiva no banco real — exige decisão manual do operador para executar."
---

# Phase 15: Correções de Funcionamento — Verification Report

**Phase Goal:** Ambiente limpo funcional — seeders criam admin/roles/permissões e bugs da varredura de verificação são corrigidos
**Verified:** 2026-08-09T23:50:00Z
**Status:** human_needed
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths

Must-haves agregados dos PLANs 15-01/15-02 (frontmatter) + Success Criteria do ROADMAP (contrato). Todas as truths dependentes de comportamento foram exercitadas por execução real da suíte (165 testes rodados pelo verifier) — nenhuma permaneceu PRESENT_BEHAVIOR_UNVERIFIED.

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | Usuário autenticado SEM permissão recebe 403 em endpoints de módulo (não 200) | ✓ VERIFIED | RbacRegressionTest executado na suite completa: 14 testes / 23 assertions PASS (403 em dashboard, equipamentos, categorias/fabricantes/fornecedores, inventário, movimentações, empréstimos, calibrações, aferições + pending, manutenções, relatórios, usuários, logs, mutação de roles; 200 em GET /roles; 200 admin bypass) |
| 2 | GET /api/v1/reports não retorna mais 500 | ✓ VERIFIED | ReportControllerTest 15 testes / 39 assertions PASS (index → 200; pdf/xlsx/csv válidos; 403 para sem-permissão). Causa raiz (formato array legado de middleware → BindingResolutionException) eliminada — middleware usa `new Middleware()` |
| 3 | GET /api/v1/verifications/pending aponta para VerificationController (verificação de rota) | ✓ VERIFIED | routes/api.php:130 `Route::get('pending', [VerificationController::class, 'pending'])->name('verifications.pending')`; RbacRegressionTest#test_verifications_require_permission cobre `/api/v1/verifications/pending` → 403 (rota ativa e protegida) |
| 4 | RateLimitTest verde (RateLimiter::clear() com chave correta) | ✓ VERIFIED | RateLimitTest executado isolado pelo verifier: 3 testes / 20 assertions PASS (bloqueio após 5 falhas → 429 PT; sucesso limpa contador; por IP). AuthController: check-após-falha + `RateLimiter::clear('login:{ip}')` |
| 5 | RoleController exige permission:roles.manage para mutações (sem escalada) | ✓ VERIFIED | RoleController::middleware() → `new Middleware('permission:roles.manage', only: ['store','update','destroy','syncPermissions'])`; RolePermissionSeeder contém `roles.manage`; RbacRegressionTest#test_role_mutations_require_roles_manage_permission PASS (POST /roles → 403) |
| 6 | db:seed roda 2x sem exceção (seeders idempotentes) | ✓ VERIFIED | SeederIdempotencyTest executado na suite: 2 testes / 14 assertions PASS — `test_seed_twice_does_not_throw_or_duplicate_records` roda DatabaseSeeder 2x: counts estáveis (Category 5, InventoryCategory 5, admin 1, Role 6) |
| 7 | Admin/roles/permissões existem após setup limpo (BUG-01) | ✓ VERIFIED | SeederIdempotencyTest#test_clean_seed_creates_admin_roles_and_permissions PASS: admin@labcontrol.com único, 6 roles, permissões > 0. DatabaseSeeder chama RolePermissionSeeder + AdminUserSeeder (já idempotentes) + 6 seeders de dados |
| 8 | VerificationUatFixTest e MaintenanceVerificationTest verdes nas rotas canônicas | ✓ VERIFIED | VerificationUatFixTest 5 testes / 28 assertions PASS (rota `/api/v1/equipments/{id}/verifications`, sem Spatie, sem assignRole, role admin anexada); MaintenanceVerificationTest 6 testes / 23 assertions PASS (rota `/api/v1/equipments/{id}/maintenance`) — ambos na suite completa |
| 9 | ReportServiceTest verde com InventoryMovementFactory | ✓ VERIFIED | ReportServiceTest executado isolado pelo verifier: 8 testes / 23 assertions PASS. InventoryMovementFactory (15-01) validada sem ajustes — campos cobrem item/type/quantity/balance_after/user |
| 10 | Suíte completa verde ao fim do wave (gate antes de /gsd-verify-work) | ✓ VERIFIED | Suite completa executada pelo verifier: **165 passed / 473 assertions / 0 falhas** (277s) — inclui RbacRegressionTest, ReportControllerTest, ReportExportTest, SeederIdempotencyTest, VerificationUatFixTest, MaintenanceVerificationTest, RateLimitTest, ReportServiceTest |

**Score:** 10/10 truths verified (0 present, behavior-unverified)

### Deferred Items

Não há gaps_found; itens abaixo são registros informativos, não gaps da fase:

| # | Item | Addressed In | Evidence |
|---|------|-------------|----------|
| 1 | 7 erros pré-existentes de typecheck frontend (PasswordInput.vue, EquipmentLogsSection.vue, LoanCreateDialog.vue, router/index.ts) | Plano de verificação da fase / frontend integration | deferred-items.md — nenhum arquivo foi tocado pelos planos 15-01/15-02 |
| 2 | Mudanças não commitadas da sessão anterior (Equipment model, LoanService/MaintenanceService sync, frontend status `loaned`/tag fix) | Plano de verificação da fase (VALIDATION.md Manual-Only/frontend) | Working tree — não são regressões desta fase |

### Required Artifacts

| Artifact | Expected | Status | Details |
| -------- | ----------- | ------ | ------- |
| `backend/tests/Feature/RbacRegressionTest.php` | Rede de regressão RBAC (wave 0) | ✓ VERIFIED | 167 linhas, 14 testes / 23 assertions; usuário sem role → 403 em todos os módulos; GET /roles aberto; admin bypass |
| 14 controllers Api/V1 com `implements HasMiddleware` + `new Middleware()` | RBAC enforcement real | ✓ VERIFIED | Category, Manufacturer, Supplier, Equipment, EquipmentPhoto, InventoryCategory, InventoryItem, InventoryMovement, Loan, Calibration, CalibrationCertificate, Verification, MaintenanceOrder, Dashboard — auditados 1 a 1: `implements HasMiddleware` + `new Middleware()` e ZERO `'options' =>` legado |
| `backend/app/Http/Controllers/Api/V1/ReportController.php` | Sem formato array legado (sem 500) | ✓ VERIFIED | `middleware()` retorna 3× `new Middleware()` (auth:sanctum, relatorios.view/index, relatorios.export/download); `index()` → 200 |
| `backend/app/Http/Controllers/Api/V1/RoleController.php` | Gate `roles.manage` nas mutações | ✓ VERIFIED | `new Middleware('permission:roles.manage', only: ['store','update','destroy','syncPermissions'])`; index/show abertas com auth |
| `backend/database/seeders/RolePermissionSeeder.php` | Permissão `roles.manage` | ✓ VERIFIED | Permissão presente; seeder idempotente (updateOrCreate) |
| `backend/database/factories/InventoryMovementFactory.php` | Factory p/ ReportControllerTest | ✓ VERIFIED | 37 linhas; model InventoryMovement; item_id/type/quantity/balance_after/reason/notes/user_id/created_by — validada sem ajustes |
| `backend/tests/Feature/SeederIdempotencyTest.php` | Prova de idempotência (wave 0) | ✓ VERIFIED | 49 linhas, 2 testes / 14 assertions — seed limpo + seed 2x com counts estáveis |
| Seeders corrigidos (Equipment, Inventory, Calibration, Loan, Verification, Maintenance) | Idempotentes | ✓ VERIFIED | EquipmentSeeder: firstOrCreate em slug/cnpj/name + guard `Equipment::count() === 0`; InventorySeeder: firstOrCreate slug + guard `InventoryItem::count() > 0`; Calibration/Loan/Verification/Maintenance: guard `count() > 0` com early return |

### Key Link Verification

| From | To | Via | Status | Details |
| ---- | --- | --- | ------ | ------- |
| 14 controllers `middleware()` | `routes/api.php` | alias `permission` → `CheckPermission` | ✓ WIRED | bootstrap/app.php `alias(['permission' => CheckPermission::class])`; controllers declaram `new Middleware('permission:x', ...)`; RbacRegressionTest prova o 403 real |
| RolePermissionSeeder | RoleController::middleware() | `permission:roles.manage` | ✓ WIRED | Seeder cria a permissão; controller exige nas mutações; teste de mutação → 403 |
| SeederIdempotencyTest | DatabaseSeeder | EquipmentSeeder/InventorySeeder (categorias únicas) | ✓ WIRED | Teste chama `$this->seed(DatabaseSeeder::class)` 2x; DatabaseSeeder chama os 6 seeders de dados; counts estáveis assertados |
| VerificationUatFixTest / MaintenanceVerificationTest | rotas canônicas | `/equipments/{id}/verifications` e `/equipments/{id}/maintenance` | ✓ WIRED | Testes usam as URLs canônicas (routes/api.php:134,157); rotas legado inexistentes; suite verde |

### Data-Flow Trace (Level 4)

| Artifact | Data Variable | Source | Produces Real Data | Status |
| -------- | ------------- | ------ | ------------------ | ------ |
| RbacRegressionTest 403s | user sem roles + permission middleware | CheckPermission::handle → roles()->hasPermission | Sim — resposta real do middleware (403) | ✓ FLOWING |
| ReportController index | lista de tipos de relatório | constante estática VALID_TYPES + metadados | Sim — JSON com 4 tipos (formats corretos por tipo) | ✓ FLOWING |
| SeederIdempotencyTest | counts após seed 2x | DatabaseSeeder → firstOrCreate/guards | Sim — contagens estáveis verificadas em banco | ✓ FLOWING |

### Behavioral Spot-Checks

| Behavior | Command | Result | Status |
| -------- | ------- | ------ | ------ |
| Suite completa (gate da fase) | `docker compose -f docker/docker-compose.yml exec -T php php artisan test` | **165 passed / 473 assertions / 0 failed** (277s) | ✓ PASS |
| RateLimitTest (contrato 5 falhas → 429, sucesso limpa) | `--filter=RateLimitTest` | 3 passed / 20 assertions (40.76s) | ✓ PASS |
| ReportServiceTest (relatórios com factory) | `--filter=ReportServiceTest` | 8 passed / 23 assertions (31.90s) | ✓ PASS |
| RbacRegressionTest, SeederIdempotencyTest, VerificationUatFixTest, MaintenanceVerificationTest, ReportControllerTest, ReportExportTest | (dentro da suite completa) | Todos PASS — 14+2+5+6+15+5 testes verdes | ✓ PASS |

### Probe Execution

N/A — a fase não declara probes executáveis (`scripts/*/tests/probe-*.sh`); a validação é via suíte PHPUnit (acima).

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
| ----------- | ---------- | ----------- | ------ | -------- |
| BUG-01 | 15-02 | Usuário admin consegue logar em ambiente limpo (seeders criam admin/roles/permissões após setup) | ✓ SATISFIED (automatizado) + ⚠️ 1 item Manual-Only pendente (PostgreSQL real) | SeederIdempotencyTest 2 tests PASS: admin@labcontrol.com único, 6 roles, permissões; seed 2x sem exceção. Login real no PostgreSQL exige execução manual (human_verification #1) |
| BUG-02 | 15-01, 15-02 | Bugs encontrados na varredura de verificação são corrigidos e validados | ✓ SATISFIED | RBAC enforcement (14 controllers + RbacRegressionTest), ReportController sem 500, escalada RoleController fechada, RateLimitTest verde, rotas canônicas, factory criada, suite 165 passed / 0 failed |

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
| ---- | ---- | ------- | -------- | ------ |
| — | — | Nenhum TBD/FIXME/XXX/TODO/HACK nos 21 arquivos da fase | — | Nenhum |
| `backend/tests/Feature/ReportControllerTest.php` (linha 198-205) | 198 | Teste `test_dashboard_export_pdf_returns_500` asserte 500 para `reports/dashboard?format=pdf` | ℹ️ Info (pré-existente, Fase 12 commit `bdea28a`, NÃO criado/regressão desta fase) | Comportamento intencional documentado: `ReportService::dashboardExport` lança `InvalidArgumentException` para PDF (dashboard suporta apenas xlsx/csv — `index()` expõe `formats: ['xlsx','csv']`). O bug 500 da fase (middleware legado → 500 em TODAS as rotas de relatório) está eliminado; o caso dashboard+PDF é restrição de formato pré-existente e explícita. Não bloqueia BUG-02 (truth #2 refere-se ao endpoint index e formatos suportados) |

### Test Quality Audit

| Test File | Linked Req | Active | Skipped | Circular | Assertion Level | Verdict |
|-----------|-----------|--------|---------|----------|----------------|---------|
| RbacRegressionTest | BUG-02 | 14 | 0 | Não | Behavioral (403/200 reais por endpoint) | ✓ Adequado |
| SeederIdempotencyTest | BUG-01 | 2 | 0 | Não | Value (counts exatos: 5/5/1/6) | ✓ Adequado |
| VerificationUatFixTest | BUG-02 | 5 | 0 | Não | Behavioral (fluxo de criação, tolerância, notificação, histórico) | ✓ Adequado |
| MaintenanceVerificationTest | BUG-02 | 6 | 0 | Não | Behavioral (criar, completar, cancelar, editar, histórico) | ✓ Adequado |
| ReportServiceTest | BUG-02 | 8 | 0 | Não | Value (arquivos válidos, totais, filtros) | ✓ Adequado |
| ReportControllerTest | BUG-02 | 15 | 0 | Não | Value + Behavioral (200/403/pdf/xlsx/csv válidos) | ✓ Adequado |
| ReportExportTest | BUG-02 | 5 | 0 | Não | Value (colunas, totais, BOM) | ✓ Adequado |
| RateLimitTest | BUG-02 | 3 | 0 | Não | Behavioral (429/422/200 por contrato) | ✓ Adequado |

**Disabled tests on requirements:** 0 → sem bloqueio
**Circular patterns detected:** 0 → sem bloqueio
**Insufficient assertions:** 0 → sem warnings

### Human Verification Required

#### 1. Seed 2x no PostgreSQL real + login admin (BUG-01 — Manual-Only da VALIDATION.md)

**Test:** Executar `docker compose -f docker/docker-compose.yml exec -T php php artisan migrate:fresh --seed --force` duas vezes seguidas no banco real (PostgreSQL via Docker); depois logar na UI com admin@labcontrol.com / @dmin123.

**Expected:** 1ª e 2ª execução do `--seed` sem exceção (seeders idempotentes); admin único, 6 roles, 5 categorias de equipamento e 5 de estoque (sem duplicação); login admin funciona e o usuário vê os módulos conforme o perfil admin (roles/permissões aplicadas).

**Why human:** phpunit usa sqlite :memory: (phpunit.xml) — o contrato de BUG-01 "admin loga em ambiente limpo" no banco real não pode ser provado pela suíte automatizada. `migrate:fresh` é uma operação **destrutiva** no banco real (dropa todas as tabelas) — exige decisão explícita do operador antes de executar; o verifier não executa mutações destrutivas.

### Gaps Summary

Nenhum gap encontrado. Todas as 10 truths foram verificadas com evidência comportamental real (suíte completa executada: 165 passed / 473 assertions / 0 falhas; RateLimitTest e ReportServiceTest re-executados isolados). BUG-01 e BUG-02 satisfeitos no ambiente de teste automatizado; resta apenas a confirmação Manual-Only no PostgreSQL real (login admin em ambiente limpo), que exige ação humana.

Observações informativas (não bloqueiam):
- O teste `test_dashboard_export_pdf_returns_500` (Fase 12, pré-existente) documenta comportamento intencional — dashboard não oferece formato PDF; não é regressão desta fase e não contradiz a truth #2 (o bug de 500 do middleware legado foi eliminado).
- 7 erros de typecheck frontend pré-existentes e mudanças não commitadas da sessão anterior estão registrados em `deferred-items.md` — fora do escopo dos planos 15-01/15-02, endereçados no plano de verificação de frontend da fase.

---

_Verified: 2026-08-09T23:50:00Z_
_Verifier: the agent (gsd-verifier)_
