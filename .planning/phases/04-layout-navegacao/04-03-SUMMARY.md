---
phase: 04-layout-navegacao
plan: 03
subsystem: ui
tags: [primevue, panelmenu, permissions, mobile, drawer, accessibility, typescript]

requires:
  - phase: 04-layout-navegacao
    plan: 02
    provides: AppSidebar, AppTopbar, AppLayout, conditional layout, route meta
provides:
  - Permission-filtered sidebar computed (filteredPanelMenuModel)
  - Mobile Drawer overlay with responsive breakpoint detection
  - Accessibility enhancements (ARIA, keyboard nav, skip-to-content)
  - Tooltip directive registration for icon-only sidebar hints
affects:
  - future phases (consumes production-ready app shell with all UX layers)

tech-stack:
  added: []
  patterns:
    - Permission filtering computed reactive to authStore.user
    - useMediaQuery from @vueuse/core for responsive breakpoint detection
    - PrimeVue Drawer for mobile overlay sidebar
    - PrimeVue Tooltip directive for collapsed sidebar hints
    - Keyboard-accessible avatar button wrapper pattern

key-files:
  modified:
    - frontend/src/components/layout/AppSidebar.vue
    - frontend/src/components/layout/AppLayout.vue
    - frontend/src/components/layout/AppTopbar.vue
    - frontend/src/styles/layout.css
    - frontend/src/main.ts

key-decisions:
  - "filteredPanelMenuModel computed replaces panelMenuModel with item.key !== undefined safety check"
  - "Mobile Drawer uses Drawer from primevue/drawer (not deprecated Sidebar)"
  - "isMobile = useMediaQuery('(max-width: 767px)') drives responsive behavior"
  - "Avatar wrapped in <button> for keyboard accessibility with @keydown.enter"
  - "Tooltip registered globally via app.directive('tooltip', Tooltip)"

requirements-completed:
  - LAYOUT-02
  - LAYOUT-03

duration: 5min
completed: 2026-07-19
status: complete
---

# Phase 4: Layout e Navegação — Plan 03 Summary

**Complete app shell polish: permission-based module filtering, mobile responsive Drawer overlay, and accessibility enhancements (ARIA labels, keyboard navigation, skip-to-content, tooltips)**

## Performance

- **Duration:** 5 min
- **Started:** 2026-07-19T13:59:35Z
- **Completed:** 2026-07-19T14:04:05Z
- **Tasks:** 3
- **Files modified:** 5
- **Build:** 487 modules, 0 errors, 2.95s

## Accomplishments

### Task 1: Permission-based module filtering (AppSidebar.vue)
- Renamed `panelMenuModel` computed to `filteredPanelMenuModel`
- Added `item.key !== undefined` type guard to category filter
- Permission/role filtering logic (already implemented in Plan 02) preserved and verified
- Dashboard link remains always visible outside PanelMenu
- Empty categories auto-hidden when all items filtered out
- Reactive to `authStore.user` changes — filtering updates when user data loads

### Task 2: Mobile Drawer (AppLayout.vue + layout.css)
- PrimeVue Drawer with `position="left"`, 300px width, modal backdrop, blockScroll, dismissable, showCloseIcon
- `useMediaQuery('(max-width: 767px)')` drives mobile detection — syncs with existing CSS breakpoint
- Desktop hamburger toggles `sidebarCollapsed` ref (CSS width transition 240px ↔ 64px)
- Mobile hamburger opens Drawer overlay with full PanelMenu
- `watch(isMobile)` closes Drawer when resizing from mobile to desktop
- Drawer CSS overrides in layout.css match dark theme (surface/bg/border/accent)
- `#main-content:focus { outline: none }` to hide focus ring on content area

### Task 3: Accessibility polish (all components + main.ts)
- **AppSidebar:** `role="navigation"` with `aria-label="Navegação principal"` on nav element
- **AppSidebar:** `v-tooltip.right` on Dashboard link shows "Dashboard" when collapsed
- **AppSidebar:** PanelMenu active state CSS (`.p-panelmenu-item-active` with accent color)
- **AppTopbar:** Notification bell icon has `aria-label="Notificações"`
- **AppTopbar:** Avatar wrapped in `<button>` with `@keydown.enter="toggleUserMenu"` for keyboard accessibility
- **AppTopbar:** Focus-visible outline on avatar wrapper using accent color
- **AppLayout:** Skip-to-content link as first focusable element with `@click` to close drawer
- **AppLayout:** Main content area has `tabindex="-1"` for programmatic focus
- **main.ts:** Tooltip directive registered globally (`app.directive('tooltip', Tooltip)`)

### Verification Results
- Nothing absent: no breadcrumbs, no keyboard shortcuts, no functional notifications, no prefers-color-scheme
- No deprecated `primevue/sidebar` imports anywhere
- Build passes with 0 errors, 487 modules

## Task Commits

Each task was committed atomically:

1. **Task 1: Permission-based module filtering** - `728e8b7` (feat)
   - Renamed panelMenuModel to filteredPanelMenuModel with key safety check
2. **Task 2: Mobile Drawer with responsive breakpoint** - `678b94e` (feat)
   - AppLayout.vue + Drawer component + layout.css overrides
3. **Task 3: Accessibility polish and Tooltip directive** - `cd978be` (feat)
   - ARIA labels, keyboard avatar, skip-to-content, Tooltip registration

## Files Modified

- `frontend/src/components/layout/AppSidebar.vue` — 128 lines (+6/-4)
  - Renamed computed to filteredPanelMenuModel, added key type guard
  - Added `role="navigation"` and `aria-label` on nav
  - Added `v-tooltip.right` on Dashboard link
  - Added PanelMenu active state CSS (`:deep(.p-panelmenu-item-active)`)
- `frontend/src/components/layout/AppLayout.vue` — 75 lines (complete rewrite)
  - Mobile Drawer, skip-to-content link, isMobile detection, tabindex on main
  - watch(isMobile) to close drawer on resize
- `frontend/src/components/layout/AppTopbar.vue` — 122 lines (+24/-9)
  - Notification bell aria-label="Notificações"
  - Keyboard-accessible avatar button wrapper with focus-visible outline
- `frontend/src/styles/layout.css` — 301 lines (+38)
  - Drawer CSS overrides (p-drawer, header, title, content, close button, mask)
  - #main-content focus outline reset
- `frontend/src/main.ts` — 30 lines (+2)
  - Imported Tooltip from primevue/tooltip
  - Registered as global directive

## Architecture

```
App.vue
  └── route.meta.requiresAuth === true
      └── AppLayout.vue
          ├── <a href="#main-content"> (skip-to-content)
          ├── AppSidebar.vue (desktop, v-if !isMobile)
          │   ├── Dashboard link (fixed, always visible)
          │   └── PanelMenu (filteredPanelMenuModel computed)
          ├── Drawer (mobile, v-model:visible)
          │   └── AppSidebar (collapsed=false)
          ├── AppTopbar.vue (hamburger, theme, notifications, avatar menu)
          └── <main id="main-content"> (<slot />)
```

**State flow:**
- `sidebarCollapsed` ref — desktop CSS width transition (240px ↔ 64px)
- `mobileDrawerVisible` ref — mobile Drawer overlay
- `isMobile` — `useMediaQuery('(max-width: 767px)')` drives toggle behavior
- `filteredPanelMenuModel` — computed reactive to `authStore.user`
- `expandedKeys` — PanelMenu accordion state, auto-expands active category

## Decisions Made

- **filteredPanelMenuModel naming**: Chose the exact name from the plan for consistency between specification and implementation
- **Drawer (not Sidebar)**: Uses `primevue/drawer` (PrimeVue v5 name); no deprecated `primevue/sidebar` imports
- **Avatar button wrapper**: Using `<button>` with `@click` and `@keydown.enter` for full keyboard accessibility, rather than adding `tabindex` and `keydown` to a `<div>`
- **Tooltip on Dashboard only**: PanelMenu items cannot easily receive per-item tooltips; the collapsed icon styling (CSS `display:none` on labels) means they're not focusable, so tooltips are nice-to-have, not a blocker

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered

- **Pre-existing TypeScript errors (untouched)**: Two unrelated pre-existing errors remain in `PasswordInput.vue` and `router/index.ts` — files untouched by this plan. Already documented in Plans 01/02.
- **vue-tsc --noEmit still fails**: TypeScript check reports the pre-existing errors, but `vite build` completes successfully (TypeScript is a Vite plugin, non-blocking). The verification step's `vue-tsc` command will always show these pre-existing errors until those files are fixed.

## Stub Tracking

- **Notification bell**: Intentional placeholder — badge shows "0", no click handler, deferred to future phase per UI-SPEC.

## Threat Flags

No threat flags — no new network endpoints, auth paths, file access patterns, or trust boundary changes introduced. Sidebar permission filtering is explicitly UI-only (accepted as low severity, backend route guards enforce authorization).

## User Setup Required

None — no external service configuration required.

## Phase Completion

This plan completes Phase 4 (Layout e Navegação). The app shell is production-ready:

| Layer | Status |
|-------|--------|
| ✅ Navigation types | Plan 01 |
| ✅ useTheme composable | Plan 01 |
| ✅ layout.css (Grid, theme vars, responsive) | Plan 01 |
| ✅ AppSidebar (PanelMenu, Dashboard link, permission filtering) | Plan 02 + Plan 03 |
| ✅ AppTopbar (user menu, theme toggle, notifications placeholder) | Plan 02 |
| ✅ AppLayout (shell wrapper, conditional rendering) | Plan 02 + Plan 03 |
| ✅ Permission filtering (reactive computed) | Plan 03 |
| ✅ Mobile Drawer (PrimeVue Drawer, <768px detection) | Plan 03 |
| ✅ Accessibility (ARIA, keyboard nav, skip-to-content, tooltips) | Plan 03 |

All 3 requirements (LAYOUT-01, LAYOUT-02, LAYOUT-03) covered and building successfully.

## Self-Check: PASSED

All deliverables verified:
- ✅ Permission filtering: `filteredPanelMenuModel` computed with `item.key !== undefined` guard
- ✅ Mobile Drawer: PrimeVue Drawer with modal, blockScroll, 300px width, left position
- ✅ Responsive: `useMediaQuery('(max-width: 767px)')` drives toggle behavior
- ✅ Accessibility: skip-to-content, ARIA labels, keyboard avatar, tooltip directive
- ✅ No deferred features: no breadcrumbs, no functional notifications, no keyboard shortcuts, no prefers-color-scheme
- ✅ No deprecated imports: no `primevue/sidebar` usage
- ✅ All 3 task commits verified (`728e8b7`, `678b94e`, `cd978be`)
- ✅ Build succeeded (`npx vite build` — 487 modules, 0 errors, 2.95s)

---

*Phase: 04-layout-navegacao*
*Completed: 2026-07-19*
