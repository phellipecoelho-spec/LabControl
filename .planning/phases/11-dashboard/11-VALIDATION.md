---
phase: 11
slug: dashboard
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-07-27
---

# Phase 11 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework (Backend)** | PHPUnit 12 |
| **Config file** | `backend/phpunit.xml` |
| **Quick run command** | `cd backend && vendor/bin/phpunit --filter Dashboard` |
| **Full suite command** | `cd backend && vendor/bin/phpunit` |
| **Framework (Frontend)** | None detected (Vitest/jest not configured) |
| **Quick run (Frontend)** | `cd frontend && npm run build` (type-check only) |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `cd backend && vendor/bin/phpunit --filter Dashboard`
- **After every plan wave:** Run `cd backend && vendor/bin/phpunit` + `cd frontend && npm run build`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 60 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 11-01-01 | 01 | 1 | DASH-01 | — | N/A | unit | `vendor/bin/phpunit --filter DashboardController` | ❌ W0 | ⬜ pending |
| 11-01-02 | 01 | 1 | DASH-01 | — | N/A | unit | `php -r "require 'vendor/autoload.php'; echo class_exists(App\Services\DashboardService::class) ? 'OK' : 'MISSING';"` | ❌ W0 | ⬜ pending |
| 11-02-01 | 02 | 1 | DASH-02 | — | N/A | build | `npm run build` | ❌ W0 | ⬜ pending |
| 11-02-02 | 02 | 1 | DASH-01 | — | N/A | manual | Visual inspection | — | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `backend/tests/Feature/DashboardControllerTest.php` — stubs for DASH-01
- [ ] `backend/tests/Unit/DashboardServiceTest.php` — stubs for DASH-01

*If none: "Existing infrastructure covers all phase requirements."*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Dashboard layout rendering | DASH-01 | Frontend sem test runner configurado | Abrir /dashboard e verificar grid responsivo, KPIs visíveis, gráficos renderizados |
| Drill-down navigation | DASH-02 | Interação com roteamento | Clicar em KPI/gráfico e confirmar navegação para listagem filtrada |
| Empty state | DASH-01 | Estado visual | Testar com banco vazio — mensagem de onboarding deve aparecer |
| Date range filter | DASH-01 | Interação com componente | Alterar período e verificar KPIs/gráficos atualizados |

*If none: "All phase behaviors have automated verification."*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 60s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
