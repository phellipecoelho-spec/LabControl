# Project Retrospective

*A living document updated after each milestone. Lessons feed forward into future planning.*

## Milestone: v1.0 — Production

**Shipped:** 2026-07-28
**Phases:** 14 | **Plans:** 42 | **Commits:** 205
**Timeline:** 10 days (2026-07-18 to 2026-07-28)
**Files:** 510 changed, 84,104 insertions, 1,423 deletions

### What Was Built

- **v0.1 Foundation:** Docker stack (nginx, php-fpm, postgres, redis), Laravel Sanctum SPA auth with 6 roles and 31 permissions, AppLayout with dark mode, responsive sidebar, and accessibility
- **v0.2 Core Business:** Equipment module with photos and history, Inventory with three-layer negative stock defense, Loans with overdue notifications, Calibrations with certificates and due-date alerts
- **v0.3 Advanced Features:** Verification (aferições) with tolerance checking, Maintenance orders with preventive auto-creation, Dashboard with 3 ECharts charts and Redis cache, Reports (PDF/Excel/CSV) with 4 report types
- **v1.0 Production:** PWA with IndexedDB offline cache and sync conflict detection, 6 audit coverage test files, UI polish (EmptyState, LoadingSkeleton), deploy automation (Docker health checks, backup/setup scripts), full documentation (README, architecture, deploy guide, Swagger API)

### What Worked

- **Modular architecture by phases:** Each phase built on previous ones with clear boundaries. Equipment module completed in 6 plans, Inventory in 3, each adding backend + frontend + tests.
- **TDD for bug fixes (Phase 14-03):** All 4 bug fix areas (rate limit, forgot password, aferições UAT, manutenções verification) had tests written first (RED) then implementation (GREEN). 19 new tests, all passing.
- **Reusable UI components:** EmptyState and LoadingSkeleton components established a consistent visual pattern across 13+ pages in a single plan.
- **Cross-cutting audit coverage:** Phase 14-01 verified all 7 core models have LogsActivity trait and created 6 audit test files (21 tests, 43 assertions) proving the coverage.

### What Was Inefficient

- **Verification debt accumulation:** Phases 1-13 were built without systematic verification. Only phases 07 and 12 had passing verification at implementation time. Retroactive verification (Phase 14 + this closeout) added VERIFICATION.md for all 14 phases.
- **gsd-verifier agent reliability:** The gsd-verifier agent repeatedly failed to create VERIFICATION.md during execute-phase workflow, requiring manual VERIFICATION.md creation for Phase 14 and the retroactive phases.
- **GSD tool milestone alignment:** The tool's internal milestone tracking (v0.1) didn't match the actual ROADMAP.md structure (4 milestones: v0.1-v1.0), causing init.manager to return empty phase data.

### Patterns Established

- **Page template pattern:** LoadingSkeleton v-if=loading → EmptyState v-else-if=empty → Content v-else
- **Module structure:** modules/{feature}/pages/ + components/ + services/ + stores/ + types/
- **Backend layers:** Controller → Form Request → Service → Model (consistent across all 6+ business modules)
- **VERIFICATION.md frontmatter:** Proper YAML with phase/name/status/verified_by for gsd-tools parsing

### Key Lessons

1. **Verify as you build.** Retroactive verification of 12 phases at milestone close is inefficient. Each phase should produce VERIFICATION.md immediately after execution.
2. **gsd-verifier agent has reliability issues.** When the agent doesn't produce expected output, manual intervention (writing VERIFICATION.md directly) is faster than repeated retries.
3. **Milestone structure should be defined early** and reflected consistently in ROADMAP.md, config.json, and STATE.md to avoid tool alignment issues.
4. **Cross-cutting plans (audit, docs, deploy) at the end are effective** for closing out a milestone. Phase 14-01 through 14-05 caught pre-existing bugs and established deploy readiness.

### Cost Observations

- Model mix: Large % sonnet for execution, smaller % for planning
- Sessions: Multiple sessions across 10 days
- Notable: Phase 14 execution was the most efficient (~2.5 hours for 5 plans with 23 key artifacts created) due to established patterns and reusable components

---

## Cross-Milestone Trends

### Process Evolution

| Milestone | Phases | Plans | Key Change |
|-----------|--------|-------|------------|
| v0.1 | 4 | 14 | Foundation: Docker, auth, roles, layout |
| v0.2 | 4 | 17 | Core business: equipments, inventory, loans, calibrations |
| v0.3 | 4 | 8 | Advanced: verifications, maintenance, dashboard, reports |
| v1.0 | 2 | 7 | Production: PWA, audit, deploy prep, docs |

### Cumulative Quality

| Milestone | Backend Tests | Verification | Artifacts |
|-----------|--------------|--------------|-----------|
| v0.1 | 28 | Mixed | 6 roles, 31 permissions, auth frontend |
| v0.2 | 60+ | Mixed | 4 business modules full-stack |
| v0.3 | 90+ | Passed (07,12) | Dashboard + reports |
| v1.0 | 120+ | All phases verified | PWA, docs, deploy scripts |

### Top Lessons (Verified Across Milestones)

1. **Modular phase structure scales well** — each module (Equipment, Inventory, Loans, Calibrations, etc.) follows the same backend→frontend→tests pattern
2. **Foundation phases (auth, layout, infra) are critical enablers** — all business modules depend on them
3. **Audit/cross-cutting concerns should be addressed systematically** — LogsActivity trait applied at Phase 3 saved massive rework later
