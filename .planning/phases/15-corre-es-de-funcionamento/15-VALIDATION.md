---
phase: 15
slug: corre-es-de-funcionamento
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-08-09
---

# Phase 15 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit ^12.5.12 (Laravel 13.20.0) |
| **Config file** | `backend/phpunit.xml` (DB sqlite :memory:) |
| **Quick run command** | `docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=<teste-da-tarefa>` |
| **Full suite command** | `docker compose -f docker/docker-compose.yml exec -T php php artisan test` |
| **Estimated runtime** | ~120 seconds |

---

## Sampling Rate

- **After every task commit:** Run `docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=<teste-da-tarefa>`
- **After every plan wave:** Run `docker compose -f docker/docker-compose.yml exec -T php php artisan test`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 120 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 15-01-01 | 01 | 1 | BUG-02 | T-15-01 | RBAC bypass eliminado — zero-permissão recebe 403 | feature | `php artisan test --filter=RbacRegressionTest` | ❌ W0 | ⬜ pending |
| 15-01-02 | 01 | 1 | BUG-02 | T-15-02 | ReportController não retorna 500 | feature | `php artisan test --filter=ReportControllerTest` | ✅ (falha hoje) | ⬜ pending |
| 15-01-03 | 01 | 1 | BUG-02 | T-15-03 | RateLimiter::clear com chave | unit | `php artisan test --filter=RateLimitTest` | ✅ (falha hoje) | ⬜ pending |
| 15-02-01 | 02 | 2 | BUG-01 | — | Seeders rodam 2x sem erro | unit/feature | `php artisan test --filter=SeederIdempotencyTest` | ❌ W0 | ⬜ pending |
| 15-02-02 | 02 | 2 | BUG-02 | — | Testes de verificação usam rotas corretas | feature | `php artisan test --filter=VerificationUatFixTest` / `--filter=MaintenanceVerificationTest` | ✅ (falham hoje) | ⬜ pending |
| 15-02-03 | 02 | 2 | BUG-02 | — | ReportServiceTest acha InventoryMovementFactory | unit | `php artisan test --filter=ReportServiceTest` | ✅ (falha hoje) | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `backend/tests/Feature/RbacRegressionTest.php` — 403 p/ zero-permissão em todos os endpoints de módulo
- [ ] `backend/tests/Feature/SeederIdempotencyTest.php` — db:seed 2x sem exception
- [ ] `backend/database/factories/InventoryMovementFactory.php` — factory faltante (ReportServiceTest)

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Seeders criam admin no PostgreSQL real (não sqlite) | BUG-01 | phpunit usa sqlite :memory:; o banco real é PostgreSQL via Docker | `docker compose -f docker/docker-compose.yml exec -T php php artisan migrate:fresh --seed --force` e logar com admin@labcontrol.com / @dmin123 |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 120s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
