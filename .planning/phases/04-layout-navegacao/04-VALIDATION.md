---
phase: 04
slug: layout-navegacao
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-07-19
---

# Phase 4 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | none — Wave 0 installs |
| **Config file** | none — Wave 0 creates |
| **Quick run command** | `cd frontend && npx vue-tsc --noEmit` |
| **Full suite command** | `cd frontend && npx vue-tsc --noEmit && npm run build` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `cd frontend && npx vue-tsc --noEmit`
- **After every plan wave:** Run `cd frontend && npm run build`
- **Before `/gsd-verify-work`:** Full build must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 04-01-01 | 01 | 1 | LAYOUT-02 | N/A | layout renders without errors | type-check | `cd frontend && npx vue-tsc --noEmit` | ❌ W0 | ⬜ pending |
| 04-01-02 | 01 | 1 | LAYOUT-02 | N/A | sidebar toggle works | manual | visual check | ❌ W0 | ⬜ pending |
| 04-01-03 | 01 | 1 | LAYOUT-03 | N/A | topbar renders user menu | type-check | `cd frontend && npx vue-tsc --noEmit` | ❌ W0 | ⬜ pending |
| 04-02-01 | 02 | 1 | LAYOUT-01 | N/A | dark/light toggle works | manual | visual check | ❌ W0 | ⬜ pending |
| 04-02-02 | 02 | 1 | LAYOUT-01 | N/A | theme persists in localStorage | manual | localStorage check | ❌ W0 | ⬜ pending |
| 04-03-01 | 03 | 2 | LAYOUT-02 | N/A | navigation renders permissions-filtered | type-check | `cd frontend && npx vue-tsc --noEmit` | ❌ W0 | ⬜ pending |

---

## Wave 0 Requirements

- [ ] `frontend/package.json` — add `"scripts"` section with `"typecheck": "vue-tsc --noEmit"` and `"build": "vite build"`
- [ ] `frontend/vitest.config.ts` — test framework config (if adding tests in future phases)
- [ ] Vue Router type declarations for route meta (already partially defined)

*Existing infrastructure covers basic type-checking and build. No test framework is installed for this phase.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Sidebar collapse/expand animation | LAYOUT-02 | Visual interaction — no DOM assertion library | Click hamburger, verify width transition 240px ↔ 64px |
| Mobile drawer overlay | LAYOUT-02 | Responsive behavior | Resize to <768px, verify drawer opens as overlay |
| Dark/light toggle visual correctness | LAYOUT-01 | Visual theme — CSS custom properties | Toggle dark/light, verify colors change correctly |
| PanelMenu accordion expand/collapse | LAYOUT-02 | Visual interaction | Click category header, verify sub-items expand |
| Navigation permission filtering | LAYOUT-02 | Depends on logged-in user with role | Login as different roles, verify menu items match permissions |
| Theme persistence across reload | LAYOUT-01 | Depends on localStorage | Toggle theme, refresh page, verify theme persists |
| Auth pages render without layout | — | Conditional layout | Visit /login while logged out, verify no sidebar/topbar |

---

## Validation Sign-Off

- [ ] All tasks have verify steps or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending 2026-07-19
