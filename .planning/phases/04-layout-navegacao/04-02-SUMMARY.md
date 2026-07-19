---
phase: 04-layout-navegacao
plan: 02
subsystem: ui
tags: [primevue, panelmenu, sidebar, topbar, layout, app-shell, typescript]

requires:
  - phase: 04-layout-navegacao
    plan: 01
    provides: Navigation types, useTheme composable, layout.css, routeModuleMap
provides:
  - AppSidebar.vue — PanelMenu navigation with permission filtering
  - AppTopbar.vue — User menu, theme toggle, notifications placeholder
  - AppLayout.vue — Shell wrapper composing sidebar + topbar + content
  - Conditional layout rendering in App.vue (auth routes get shell, guest routes raw)
  - Route meta updates (module field for sidebar active-state detection)
affects:
  - 04-03-polish (consumes sidebar/topbar for mobile Drawer and accessibility polish)

tech-stack:
  added: []
  patterns:
    - PrimeVue PanelMenu controlled mode with expandedKeys
    - Conditional layout rendering via route.meta.requiresAuth

key-files:
  created:
    - frontend/src/components/layout/AppSidebar.vue
    - frontend/src/components/layout/AppTopbar.vue
    - frontend/src/components/layout/AppLayout.vue
  modified:
    - frontend/src/App.vue
    - frontend/src/router/routes.ts

key-decisions:
  - "AppSidebar uses computed panelMenuModel that transforms navigationTree into PanelMenu-compatible format with dynamic permission filtering"
  - "Dashboard link rendered outside PanelMenu (fixed at top of sidebar) per D-16"
  - "AppTopbar uses useTheme composable at component level; composable manages its own state via module-level watch"
  - "App.vue uses v-if/else pattern (not RouterView wrapper slot) for conditional layout — simpler and avoids slot edge cases"
  - "Route meta module values match route names (dashboard, admin.users, admin.roles, admin.logs, profile) for consistent routeModuleMap lookup"

patterns-established:
  - "PanelMenu controlled model pattern: computed reactive model + expandedKeys watcher + command-based navigation"
  - "Topbar composable pattern: useTheme called inside setup, ui state managed by composable internals"
  - "Layout state management: sidebarCollapsed ref held in AppLayout, passed down via props and events"

requirements-completed:
  - LAYOUT-02
  - LAYOUT-03

coverage:
  - id: D4
    description: "AppSidebar with PanelMenu navigation, accordion categories, Dashboard fixed link"
    requirement: LAYOUT-02
    verification:
      - kind: other
        ref: "npx vite build"
        status: pass
    human_judgment: false
  - id: D5
    description: "AppTopbar with user avatar menu, theme toggle, notification placeholder"
    requirement: LAYOUT-03
    verification:
      - kind: other
        ref: "npx vite build"
        status: pass
    human_judgment: false
  - id: D6
    description: "AppLayout shell wrapper, conditional rendering in App.vue, route module meta"
    requirement: LAYOUT-02
    verification:
      - kind: other
        ref: "npx vite build"
        status: pass
    human_judgment: false

duration: 4min
completed: 2026-07-19
status: complete
---

# Phase 4: Layout e Navegação — Plan 02 Summary

**Complete App Shell UI: AppSidebar with PanelMenu navigation, AppTopbar with user menu and controls, AppLayout shell wrapper, conditional layout rendering in App.vue, and route meta updates for sidebar active-state detection**

## Performance

- **Duration:** 4 min
- **Started:** 2026-07-19T13:51:27Z
- **Completed:** 2026-07-19T13:55:41Z
- **Tasks:** 3
- **Files created:** 3
- **Files modified:** 2
- **Build:** 479 modules, 0 errors, 2.81s

## Task Commits

Each task was committed atomically:

1. **Task 1: Create AppSidebar.vue with PanelMenu navigation** - `73c1c21` (feat)
   - PanelMenu with 4 categories (Gestão, Operações, Administração, Relatórios)
   - Dashboard link rendered separately outside PanelMenu
   - Permission/role filtering via authStore (hasPermission, hasRole)
   - Auto-expand active category on route change via routeModuleMap
   - Active state highlighting for current route module
   - Accordion behavior (multiple=false)

2. **Task 2: Create AppTopbar.vue with user menu, theme toggle, notifications** - `d4fd542` (feat)
   - Hamburger button emits toggle-sidebar event with aria-label/aria-expanded
   - Theme toggle uses useTheme composable, sun icon in dark mode, moon in light mode
   - Notifications bell placeholder with OverlayBadge (value=0, severity=info)
   - User avatar with popup Menu (Meu Perfil, Sair with separator)
   - All copy in pt-BR per UI-SPEC Copywriting Contract
   - No breadcrumbs, no notification click handlers

3. **Task 3: Create AppLayout.vue, update App.vue, add route meta** - `0ba147d` (feat)
   - AppLayout.vue composes AppSidebar + AppTopbar + slot content
   - sidebarCollapsed state managed in AppLayout, passed as prop+event
   - App.vue conditionally renders AppLayout when route.meta.requiresAuth
   - Guest routes render without layout (raw router-view)
   - All 5 authenticated routes have module meta for sidebar highlighting

## Files Created/Modified

### Created
- `frontend/src/components/layout/AppSidebar.vue` — 122 lines — PanelMenu navigation with permission filtering, auto-expand active category, Dashboard fixed link
- `frontend/src/components/layout/AppTopbar.vue` — 98 lines — User avatar menu, theme toggle, notifications placeholder, hamburger toggle
- `frontend/src/components/layout/AppLayout.vue` — 37 lines — Shell wrapper composing AppSidebar + AppTopbar with collapsed state management

### Modified
- `frontend/src/App.vue` — Conditional layout rendering via `route.meta.requiresAuth`: AppLayout for auth routes, raw router-view for guest routes
- `frontend/src/router/routes.ts` — Added `module` meta to 5 authenticated routes (dashboard, admin.users, admin.roles, admin.logs, profile)

## Architecture

```
App.vue
  ├── route.meta.requiresAuth === true
  │   └── AppLayout.vue (shell)
  │       ├── AppSidebar.vue (PanelMenu categories + Dashboard link)
  │       ├── AppTopbar.vue (hamburger, theme toggle, notifications, user menu)
  │       └── <slot /> → <router-view /> (page content)
  └── route.meta.requiresAuth === false / undefined
      └── <router-view /> (no shell — login, register, etc.)
```

## Decisions Made

- **AppSidebar uses computed panelMenuModel**: The navigation tree is transformed into PanelMenu-compatible format reactively, with dynamic permission/role filtering. The model updates when authStore.user changes.
- **Dashboard link outside PanelMenu**: Per D-16, Dashboard is rendered as a fixed `<router-link>` above PanelMenu, not inside a category.
- **AppLayout holds sidebar collapse state**: `sidebarCollapsed` ref and `toggleSidebar()` function live in AppLayout, passed as props/events to child components. This keeps the state management simple and centralized.
- **Route module meta matches route names**: Following the existing `routeModuleMap` pattern, each authenticated route's `module` meta value matches its route name for consistent lookup.

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered

- **Pre-existing TypeScript errors (untouched)**: Two unrelated pre-existing errors exist (`PasswordInput.vue` type mismatch and `router/index.ts` roles type) in files not touched by this plan. Already documented in Plan 01 Summary.

## Stub Tracking

No stubs introduced — all created components contain production-ready code.

## Threat Flags

No threat flags — no new network endpoints, auth paths, file access patterns, or trust boundary changes introduced.

## User Setup Required

None — no external service configuration required.

## Next Phase Readiness

- Plan 02 App Shell complete: AppSidebar, AppTopbar, AppLayout, App.vue conditional rendering, route meta
- Plan 03 (Polish) can now implement: mobile Drawer overlay, responsive breakpoint detection, permission filtering verification, accessibility enhancements
- The `app-sidebar--desktop` class is already in place for CSS targeting when mobile Drawer is added

## Self-Check: PASSED

All deliverables verified:
- ✅ `frontend/src/components/layout/AppSidebar.vue` — exists, TypeScript compiles
- ✅ `frontend/src/components/layout/AppTopbar.vue` — exists, TypeScript compiles
- ✅ `frontend/src/components/layout/AppLayout.vue` — exists, TypeScript compiles
- ✅ `frontend/src/App.vue` — updated with conditional layout
- ✅ `frontend/src/router/routes.ts` — all 5 authenticated routes have module meta
- ✅ All 3 task commits verified (`73c1c21`, `d4fd542`, `0ba147d`)
- ✅ Build succeeded (`npx vite build` — 479 modules, 0 errors, 2.81s)

---

*Phase: 04-layout-navegacao*
*Completed: 2026-07-19*
