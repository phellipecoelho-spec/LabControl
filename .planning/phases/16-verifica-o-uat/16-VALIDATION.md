---
phase: 16
slug: verifica-o-uat
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-08-09
---

# Phase 16 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit ^12.5.12 (Laravel 13.20.0) |
| **Config file** | `backend/phpunit.xml` (DB sqlite :memory:) |
| **Quick run command** | `docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=<teste>` |
| **Full suite command** | `docker compose -f docker/docker-compose.yml exec -T php php artisan test` |
| **Estimated runtime** | ~277 seconds |

---

## Sampling Rate

- **After every task commit:** Run `docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=<teste-da-onda>`
- **After every plan wave:** Run `docker compose -f docker/docker-compose.yml exec -T php php artisan test` (full suite)
- **Before `/gsd-verify-work`:** Full suite must be green (165 passed / 473 assertions)
- **Max feedback latency:** 300 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 16-01-T1 | 01 | 1 | UAT-01 | T-16-01 / — | Environment seeded, suite green as gate | integration | `php artisan test` (full suite) | ✅ | ⬜ pending |
| 16-01-T2 | 01 | 2 | UAT-01 | T-16-01 / — | Aferições: create, tolerance, history | integration | `php artisan test --filter=VerificationUatFixTest` | ✅ | ⬜ pending |
| 16-02-T1 | 02 | 3 | UAT-02 | T-16-02 / — | Manutenções: create, complete, cancel, history | integration | `php artisan test --filter=MaintenanceVerificationTest` | ✅ | ⬜ pending |
| 16-01-Tn | 01 | 2 | UAT-01 #5/#6 | T-16-01 / — | RBAC: sem permissão → 403 real | integration | `php artisan test --filter=RbacRegressionTest` | ✅ | ⬜ pending |
| 16-01-Tn | 01 | 1 | UAT-01 #1 (BUG-01) | T-16-01 / — | Seed idempotente (admin único, 6 roles, 5+5 categorias) | integration | `php artisan test --filter=SeederIdempotencyTest` | ✅ | ⬜ pending |
| 16-UAT (manual) | 01/02 | 2/3 | UAT-01/UAT-02 | — | Execução visual dos 11 cenários na UI | manual-only | — (documentado no 16-UAT.md) | ✅ formato | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] Nenhum — toda a infraestrutura de teste já existe e está verde (VerificationUatFixTest 5/28, MaintenanceVerificationTest 6/23, RbacRegressionTest 14/23, SeederIdempotencyTest 2/14). Não há arquivos de teste a criar nesta fase.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| 6 cenários UAT-01 (Aferições): DataTable pendentes/loading/empty, formulário dinâmico de parâmetros, aba tab 3 com timeline, toast de tolerância, gating aba por `afericoes.view`, gating botão "Aferir" por `afericoes.create` | UAT-01 | Rendering condicional, toasts, layout PrimeVue e interação visual exigem olho humano | Executar na UI (dev server :5173) com admin + usuários de permissão (tecnico/consulta); registrar resultado e evidência em 16-UAT.md |
| 5 itens UAT-02 (Manutenções): DataTable + 5 filtros, criação com campos condicionais preventiva, conclusão com peças dinâmicas, aba tab 6, sidebar gating por `manutencoes.view` | UAT-02 | Idem — verificação visual interativa | Executar na UI (dev server :5173) com admin + usuário custom sem roles (caso negativo item 5); registrar em 16-UAT.md |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 300s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
