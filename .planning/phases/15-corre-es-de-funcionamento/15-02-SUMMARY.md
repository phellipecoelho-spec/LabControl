---
phase: 15-corre-es-de-funcionamento
plan: 15-02
subsystem: database, testing
tags: [laravel, seeders, idempotency, firstorcreate, phpunit, testing, rbac, factories]

# Dependency graph
requires:
  - phase: 15-01
    provides: RBAC enforcement (HasMiddleware), InventoryMovementFactory, controllers corrigidos
provides:
  - Seeders 100% idempotentes (db:seed re-executável 2x sem exceção nem duplicação)
  - SeederIdempotencyTest (prova de BUG-01: admin/6 roles/5 categorias estáveis)
  - VerificationUatFixTest migrado para rota canônica /equipments/{id}/verifications (sem Spatie, sem assignRole, role admin anexada)
  - MaintenanceVerificationTest migrado para rota canônica /equipments/{id}/maintenance
  - Suite completa 100% verde: 165 passed / 473 assertions (gate antes do /gsd-verify-work)
affects: [15-verification, frontend-integration]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Seeder idempotente: firstOrCreate em colunas UNIQUE (slug/cnpj/name) + guard de contagem (if Model::count() === 0) para dados de factory"
    - "Teste de idempotência: rodar DatabaseSeeder 2x com RefreshDatabase e assertar counts estáveis"
    - "Testes com RBAC ativo: usuários de teste devem anexar role via roles()->attach(Role::where('slug', ...)->value('id'))"

key-files:
  created:
    - backend/tests/Feature/SeederIdempotencyTest.php
    - .planning/phases/15-corre-es-de-funcionamento/deferred-items.md
  modified:
    - backend/database/seeders/EquipmentSeeder.php
    - backend/database/seeders/InventorySeeder.php
    - backend/database/seeders/CalibrationSeeder.php
    - backend/database/seeders/LoanSeeder.php
    - backend/database/seeders/VerificationSeeder.php
    - backend/database/seeders/MaintenanceSeeder.php
    - backend/tests/Feature/VerificationUatFixTest.php
    - backend/tests/Feature/MaintenanceVerificationTest.php

key-decisions:
  - "Testes de verificação assertam o shape real do VerificationResource (equipment aninhado, sem equipment_id top-level) — recurso não foi alterado para não quebrar o contrato consumido pelo frontend"
  - "Verification::latest() escopado por equipment_id nos testes de tolerância — o full DatabaseSeeder no setUp cria verifications com created_at empatado; sem o escopo o teste é flaky"
  - "VerificationUatFixTest mantém $this->seed() (DatabaseSeeder completo) — com os seeders agora idempotentes e rápidos, não há necessidade de trocar por RolePermissionSeeder isolado"
  - "InventoryMovementFactory do 15-01 validada sem ajustes — campos (item_id, type, quantity, balance_after, reason, notes, user_id, created_by) cobrem ReportServiceTest e ReportExportTest"
  - "Erros pré-existentes do typecheck frontend (7, em arquivos fora do escopo da wave) registrados em deferred-items.md para o plano de verificação da fase"

requirements-completed: [BUG-01, BUG-02]

# Coverage metadata — one entry per shipped deliverable
coverage:
  - id: D1
    description: "Seeders idempotentes — db:seed roda 2x sem exceção; admin único, 6 roles, 5 categorias de equipamento e 5 de estoque (BUG-01)"
    requirement: BUG-01
    verification:
      - kind: integration
        ref: "backend/tests/Feature/SeederIdempotencyTest.php#test_clean_seed_creates_admin_roles_and_permissions"
        status: pass
      - kind: integration
        ref: "backend/tests/Feature/SeederIdempotencyTest.php#test_seed_twice_does_not_throw_or_duplicate_records"
        status: pass
    human_judgment: false
  - id: D2
    description: "VerificationUatFixTest verde nas rotas canônicas — sem import Spatie, sem assignRole inexistente, role admin anexada (403 pós-RBAC), /equipments/{id}/verifications (BUG-02)"
    requirement: BUG-02
    verification:
      - kind: integration
        ref: "backend/tests/Feature/VerificationUatFixTest.php (5 tests, 28 assertions)"
        status: pass
    human_judgment: false
  - id: D3
    description: "MaintenanceVerificationTest verde na rota canônica /equipments/{id}/maintenance (BUG-02)"
    requirement: BUG-02
    verification:
      - kind: integration
        ref: "backend/tests/Feature/MaintenanceVerificationTest.php (6 tests, 23 assertions)"
        status: pass
    human_judgment: false
  - id: D4
    description: "ReportServiceTest e ReportExportTest verdes com a InventoryMovementFactory criada no 15-01 — factory validada, nenhuma recriação necessária (BUG-02)"
    requirement: BUG-02
    verification:
      - kind: integration
        ref: "backend/tests/Unit/Services/ReportServiceTest.php (8 tests, 23 assertions)"
        status: pass
      - kind: integration
        ref: "backend/tests/Feature/ReportExportTest.php (5 tests, 35 assertions)"
        status: pass
    human_judgment: false
  - id: D5
    description: "Suíte completa verde — 165 passed / 473 assertions, 0 falhas (gate antes do /gsd-verify-work); nenhum resíduo de Spatie/assignRole; nenhum teste em URL inexistente"
    requirement: BUG-02
    verification:
      - kind: integration
        ref: "php artisan test (165 passed, 473 assertions, 0 failed)"
        status: pass
    human_judgment: false
  - id: D6
    description: "Manual-Only (VALIDATION.md): migrate:fresh --seed 2x no PostgreSQL real + login admin@labcontrol.com / @dmin123"
    requirement: BUG-01
    verification: []
    human_judgment: true
    rationale: "phpunit usa sqlite :memory:; o contrato de BUG-01 no banco real (PostgreSQL via Docker) exige execução manual da seção Manual-Only da VALIDATION.md no plano de verificação da fase"

# Metrics
duration: 23min
completed: 2026-08-09
status: complete
---

# Phase 15 Plan 2: Seeders Idempotentes + Reparo da Suite de Testes Summary

**Seeders 100% idempotentes (firstOrCreate em colunas UNIQUE + guards de contagem) com prova automatizada SeederIdempotencyTest (db:seed 2x sem exceção, admin único, 6 roles, 5 categorias), testes de verificação/manutenção migrados para as rotas canônicas (sem Spatie/assignRole, role admin anexada pós-RBAC) e suite completa 100% verde: 165 passed / 473 assertions**

## Performance

- **Duration:** 23 min
- **Started:** 2026-08-09T23:17:00Z
- **Completed:** 2026-08-09T23:40:27Z
- **Tasks:** 3 (2 com commit; Task 3 verificação pura sem alterações de código)
- **Files modified:** 9 (6 seeders + 2 testes + 1 teste criado)

## Accomplishments

- **BUG-01 fechado:** os 6 seeders de dados (Equipment, Inventory, Calibration, Loan, Verification, Maintenance) agora são idempotentes — `firstOrCreate` nas colunas UNIQUE (categories.slug, suppliers.cnpj, manufacturers.name, inventory_categories.slug) e guards de contagem (`if Model::count() === 0`) nos blocos baseados em factories. `migrate --seed` re-executável sem exceção e sem duplicação (admin único, 6 roles, 5 categorias).
- **SeederIdempotencyTest criado (2 testes, 14 assertions):** prova de fim-de-estado — seed limpo cria admin@labcontrol.com + 6 roles + permissões; seed 2x não lança exceção e mantém counts estáveis (Category 5, InventoryCategory 5, admin 1, Role 6).
- **VerificationUatFixTest reparado (5 testes, 28 assertions):** import `Spatie\Permission\Models\Role` removido (RBAC custom do projeto), `assignRole()` inexistente substituído por `roles()->attach()` no RBAC custom, `createAdminUser()` anexa role admin (sem role o RBAC do 15-01 devolve 403), rota legada `/verifications/by-equipment/{id}` → canônica `/equipments/{id}/verifications`, e asserções de shape alinhadas ao contrato real do VerificationResource.
- **MaintenanceVerificationTest reparado (6 testes, 23 assertions):** rota legada `/maintenance-orders/by-equipment/{id}` → canônica `/equipments/{id}/maintenance`; role admin já era anexada.
- **InventoryMovementFactory do 15-01 validada sem ajustes:** ReportServiceTest (8 passed) e ReportExportTest (5 passed) verdes — a factory (item_id, type, quantity, balance_after, reason, notes, user_id, created_by) cobre o uso dos testes de relatório.
- **Gate de fase — suíte completa 100% verde:** 165 passed / 473 assertions / 0 falhas (era 157 passed / 6 failed no fim do 15-01; as 6 falhas restantes eram exatamente os arquivos corrigidos aqui + 2 novos testes de idempotência).

## Task Commits

Cada tarefa foi commitada atomicamente:

1. **Task 1: Tornar seeders idempotentes e criar SeederIdempotencyTest** - `b81ca85` (fix)
2. **Task 2: Migrar VerificationUatFixTest e MaintenanceVerificationTest para rotas canônicas** - `9ae0e9b` (fix)
3. **Task 3: Validar InventoryMovementFactory e suite completa** - sem commit (verificação pura: factory do 15-01 validada como estava, ReportServiceTest/ReportExportTest já verdes, nenhuma alteração de código necessária)

## Files Created/Modified

- `backend/database/seeders/EquipmentSeeder.php` - firstOrCreate em slug/cnpj/name (UNIQUE); Equipment::factory guard por count
- `backend/database/seeders/InventorySeeder.php` - InventoryCategory::firstOrCreate (slug); itens+movimentações guard por InventoryItem::count()
- `backend/database/seeders/CalibrationSeeder.php` - guard Calibration::count() === 0 (estados due/dueSoon preservados)
- `backend/database/seeders/LoanSeeder.php` - guard Loan::count() === 0 (estados com withItems preservados)
- `backend/database/seeders/VerificationSeeder.php` - guard Verification::count() === 0 (templates/frequencies preservados)
- `backend/database/seeders/MaintenanceSeeder.php` - guard MaintenanceOrder::count() === 0 (estados/prioridades preservados)
- `backend/tests/Feature/SeederIdempotencyTest.php` - criado: seed limpo + seed 2x (idempotência BUG-01)
- `backend/tests/Feature/VerificationUatFixTest.php` - sem Spatie, role admin, assignRole→roles()->attach, rota canônica, shape real, anti-flake
- `backend/tests/Feature/MaintenanceVerificationTest.php` - rota canônica /equipments/{id}/maintenance
- `.planning/phases/15-corre-es-de-funcionamento/deferred-items.md` - erros pré-existentes do typecheck frontend (fora de escopo)

## Decisions Made

- **Não alterar o VerificationResource:** os testes assertavam `equipment_id` top-level que não existe no contrato real (o resource expõe `equipment` aninhado quando carregado). Ajustar o teste ao contrato — mudar o resource quebraria o frontend.
- **Escopar `Verification::latest()` por equipment_id:** o full DatabaseSeeder no setUp cria dezenas de verifications com created_at empatado; sem o escopo o teste de tolerância era flaky (pegava uma verification seedada com result outside_range).
- **Manter `$this->seed()` (DatabaseSeeder completo) no setUp do VerificationUatFixTest:** com os seeders idempotentes e rápidos, a estrutura atual funciona — a alternativa (RolePermissionSeeder isolado) era uma otimização desnecessária.
- **InventoryMovementFactory não recriada:** a factory criada no 15-01 (Parte D da Task 2) já existe e cobre os testes; este plano apenas validou.
- **Erros de typecheck frontend não corrigidos aqui:** 7 erros pré-existentes em arquivos fora do escopo desta wave (PasswordInput, EquipmentLogsSection, LoanCreateDialog, router/index.ts) — registrados em deferred-items.md para o plano de verificação da fase.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Asserções de shape do VerificationUatFixTest fora do contrato real da API**
- **Found during:** Task 2 (após corrigir os 403, as asserções de estrutura falharam)
- **Issue:** O teste assertava `equipment_id` top-level no response de store e history, mas o VerificationResource expõe `equipment` (objeto aninhado, whenLoaded) e o history carrega apenas `operator`/`params` — as chaves `equipment_id` nunca existem no contrato. O 403 pós-RBAC mascarava o erro de shape antes.
- **Fix:** Ajustadas as `assertJsonStructure` para o shape real (`id`, `verified_at`, `notes`, `params` no store; `id`, `verified_at`, `params` no history). Recurso não alterado — o contrato da API permanece o consumido pelo frontend.
- **Files modified:** backend/tests/Feature/VerificationUatFixTest.php
- **Verification:** VerificationUatFixTest 5 passed / 28 assertions
- **Committed in:** 9ae0e9b (Task 2)

**2. [Rule 1 - Bug] Teste de tolerância flaky — `Verification::latest()` retornava verification do seed**
- **Found during:** Task 2 (test_verification_within_tolerance_passes falhou intermitentemente na suíte do filtro, passava isolado)
- **Issue:** Com o full DatabaseSeeder no setUp, o seed cria dezenas de verifications com created_at empatado com a do teste; `latest()` sem escopo podia retornar uma verification seedada cujo primeiro param tem result outside_range.
- **Fix:** Query escopada: `Verification::where('equipment_id', $equipment->id)->latest()->first()` — garante a verification criada pelo próprio teste (o equipment do teste é factory nova, sem verifications seedadas).
- **Files modified:** backend/tests/Feature/VerificationUatFixTest.php
- **Verification:** filtro completo 2x consecutivas verdes (5 passed / 28 assertions) + suite completa verde
- **Committed in:** 9ae0e9b (Task 2)

---

**Total deviations:** 2 auto-fixed (2 bugs de teste)
**Impact on plan:** Ambos os auto-fixes foram necessários para o done criteria da Task 2 (testes verdes nas rotas canônicas). Restritos a asserções/consultas do arquivo de teste — nenhuma mudança em código de produção, nenhum escopo extra.

## Issues Encountered

- **Typecheck frontend com 7 erros pré-existentes:** `npm run typecheck` (verificação opcional do plano) reportou erros em PasswordInput.vue, EquipmentLogsSection.vue, LoanCreateDialog.vue e router/index.ts — nenhum nos arquivos tocados por este plano nem pelas mudanças da sessão anterior (Equipment pages + InventoryItemFormPage passam limpos). Fora do escopo do 15-02; registrados em deferred-items.md para o plano de verificação da fase.
- **Falha intermitente do teste de tolerância:** ver Deviation 2 — resolvido com query escopada; suíte completa estável após o fix.

## User Setup Required

None - nenhuma configuração manual de serviços externos. O Manual-Only da VALIDATION.md (seed 2x no PostgreSQL real + login admin) é executado no plano de verificação da fase.

## Next Phase Readiness

- **Fase 15 pronta para verificação:** BUG-01 e BUG-02 fechados — seeders idempotentes comprovados por teste, testes de verificação/manutenção verdes nas rotas canônicas, relatórios verdes, suite completa 165 passed / 473 assertions.
- **Manual-Only pendente (VALIDAÇÃO.md):** `migrate:fresh --seed --force` 2x no PostgreSQL real + login admin@labcontrol.com / @dmin123 — executar no plano de verificação da fase.
- **Frontend:** mudanças da sessão anterior (status loaned, fix de tag, sync de status em LoanService/MaintenanceService, relation loans no Equipment model) permanecem na working tree, fora do escopo do 15-02 — integrar/validar no plano de verificação da fase (Manual-Only + typecheck).
- **Deferred:** 7 erros de typecheck frontend pré-existentes (deferred-items.md) — endereçar na verificação/ajuste de frontend.

## Self-Check: PASSED

- FOUND: backend/tests/Feature/SeederIdempotencyTest.php
- FOUND: backend/database/seeders/EquipmentSeeder.php (firstOrCreate + guard)
- FOUND: backend/database/seeders/InventorySeeder.php
- FOUND: backend/database/seeders/CalibrationSeeder.php, LoanSeeder.php, VerificationSeeder.php, MaintenanceSeeder.php (guards)
- FOUND: b81ca85 (Task 1), 9ae0e9b (Task 2)
- FOUND: SeederIdempotencyTest verde na suite completa (2 passed)
- FOUND: VerificationUatFixTest verde (5 passed), MaintenanceVerificationTest verde (6 passed)
- FOUND: ReportServiceTest verde (8 passed), ReportExportTest verde (5 passed)
- FOUND: Suite completa 165 passed / 473 assertions / 0 failed

---
*Phase: 15-corre-es-de-funcionamento*
*Completed: 2026-08-09*
