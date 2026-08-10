---
status: testing
phase: 15-corre-es-de-funcionamento
source: 15-01-SUMMARY.md, 15-02-SUMMARY.md
started: 2026-08-09T21:15:22Z
updated: 2026-08-09T21:15:22Z
---

## Current Test

number: 1
name: Cold Start Smoke Test
expected: |
  O ambiente sobe do zero sem erros: containers saudáveis, banco PostgreSQL com seeders rodando sem exceção, e o login retorna dados vivos.
awaiting: user response

## Tests

### 1. Cold Start Smoke Test
expected: O ambiente sobe do zero sem erros: containers saudáveis, banco PostgreSQL com seeders rodando sem exceção, e o login retorna dados vivos.
result: pending

### 2. Manual-Only: Seed 2x no PostgreSQL real + login admin (BUG-01)
expected: `db:seed` executa 2x sem exceção nem duplicação (admin único, 6 roles, 5 categorias de equipamento e 5 de estoque); login admin@labcontrol.com / @dmin123 funciona e o usuário vê os módulos conforme o perfil admin.
result: pending

### 3. RBAC bypass eliminado - 14 controllers Api/V1 convertidos para HasMiddleware + new Middleware('permission:x')
expected: RBAC bypass eliminado - 14 controllers Api/V1 convertidos para HasMiddleware + new Middleware('permission:x')
result: pass
source: automated
coverage_id: D1

### 4. RoleController escalada de privilegio fechada - mutacoes exigem permission:roles.manage
expected: RoleController escalada de privilegio fechada - mutacoes exigem permission:roles.manage
result: pass
source: automated
coverage_id: D2

### 5. ReportController sem 500 - middleware legado convertido e dependencias dompdf/excel instaladas (ReportControllerTest verde)
expected: ReportController sem 500 - middleware legado convertido e dependencias dompdf/excel instaladas (ReportControllerTest verde)
result: pass
source: automated
coverage_id: D3

### 6. RateLimitTest verde - login limita apos 5 falhas (429 com mensagem PT), sucesso limpa o contador
expected: RateLimitTest verde - login limita apos 5 falhas (429 com mensagem PT), sucesso limpa o contador
result: pass
source: automated
coverage_id: D4

### 7. Rota GET /api/v1/verifications/pending aponta para VerificationController::pending (verificacao - nenhuma mudanca necessaria)
expected: Rota GET /api/v1/verifications/pending aponta para VerificationController::pending (verificacao - nenhuma mudanca necessaria)
result: pass
source: automated
coverage_id: D5

### 8. Seeders idempotentes — db:seed roda 2x sem exceção; admin único, 6 roles, 5 categorias de equipamento e 5 de estoque (BUG-01)
expected: Seeders idempotentes — db:seed roda 2x sem exceção; admin único, 6 roles, 5 categorias de equipamento e 5 de estoque (BUG-01)
result: pass
source: automated
coverage_id: D1

### 9. VerificationUatFixTest verde nas rotas canônicas — sem import Spatie, sem assignRole inexistente, role admin anexada (403 pós-RBAC), /equipments/{id}/verifications (BUG-02)
expected: VerificationUatFixTest verde nas rotas canônicas — sem import Spatie, sem assignRole inexistente, role admin anexada (403 pós-RBAC), /equipments/{id}/verifications (BUG-02)
result: pass
source: automated
coverage_id: D2

### 10. MaintenanceVerificationTest verde na rota canônica /equipments/{id}/maintenance (BUG-02)
expected: MaintenanceVerificationTest verde na rota canônica /equipments/{id}/maintenance (BUG-02)
result: pass
source: automated
coverage_id: D3

### 11. ReportServiceTest e ReportExportTest verdes com a InventoryMovementFactory criada no 15-01 — factory validada, nenhuma recriação necessária (BUG-02)
expected: ReportServiceTest e ReportExportTest verdes com a InventoryMovementFactory criada no 15-01 — factory validada, nenhuma recriação necessária (BUG-02)
result: pass
source: automated
coverage_id: D4

### 12. Suíte completa verde — 165 passed / 473 assertions, 0 falhas (gate antes do /gsd-verify-work); nenhum resíduo de Spatie/assignRole; nenhum teste em URL inexistente
expected: Suíte completa verde — 165 passed / 473 assertions, 0 falhas (gate antes do /gsd-verify-work); nenhum resíduo de Spatie/assignRole; nenhum teste em URL inexistente
result: pass
source: automated
coverage_id: D5

## Summary

total: 12
passed: 0
issues: 0
pending: 2
skipped: 0
blocked: 0

## Gaps

[none yet]
