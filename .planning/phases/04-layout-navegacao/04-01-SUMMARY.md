---
phase: 04-layout-navegacao
plan: 01
subsystem: ui
tags: [primevue, panelmenu, typescript, theme, css-variables, layout]

requires:
  - phase: 03-usuarios-permissoes
    provides: Auth store (hasPermission, hasRole), route guards, user model
provides:
  - Navigation type system (NavModule, NavCategory, NavItem interfaces)
  - Complete navigation tree constant with 4 categories + Dashboard
  - Route-to-module mapping for PanelMenu active state detection
  - useTheme composable with localStorage persistence
  - Layout CSS stylesheet with dark/light theme variables
  - App shell CSS Grid layout (sidebar + topbar + content)
affects:
  - 04-02-app-shell (consumes navigation types, theme composable, layout CSS)

tech-stack:
  added: []
  patterns:
    - TypeScript type definitions for navigation structure (types/navigation.ts)
    - Vue composable pattern for theme management (composables/useTheme.ts)
    - CSS custom properties for theming with .app-dark dark mode selector
    - CSS Grid layout for app shell (grid-template-areas)

key-files:
  created:
    - frontend/src/types/navigation.ts
    - frontend/src/composables/useTheme.ts
    - frontend/src/styles/layout.css
  modified:
    - frontend/src/main.ts

key-decisions:
  - "Navigation types use NavCategory (accordion group) + NavModule (leaf) pattern for PanelMenu model"
  - "Admin modules (Usuários, Perfis de Acesso, Logs de Auditoria) use roles[] instead of permission; permission set to null"
  - "useTheme uses readonly(isDark) to prevent external mutation of theme state"
  - "layout.css uses CSS Grid with grid-template-areas for shell layout"
  - "Collapsed sidebar hides PanelMenu labels via CSS display:none with deep selectors"

patterns-established:
  - "Type composable pattern: types/ directory exports interfaces and constants"
  - "Theme composable pattern: localStorage get/set + document.documentElement class toggle + watch with immediate"
  - "CSS variable architecture: :root for light mode defaults, .app-dark for dark overrides"
  - "Grid shell layout: sidebar + topbar + content using grid-template-areas"

requirements-completed:
  - LAYOUT-01

coverage:
  - id: D1
    description: "Navigation type definitions (NavModule, NavCategory, NavItem) and navigation tree constant"
    requirement: LAYOUT-01
    verification:
      - kind: other
        ref: "npx vite build"
        status: pass
    human_judgment: false
  - id: D2
    description: "useTheme composable with localStorage persistence and .app-dark class toggle"
    requirement: LAYOUT-01
    verification:
      - kind: other
        ref: "npx vite build"
        status: pass
    human_judgment: false
  - id: D3
    description: "Layout CSS stylesheet with custom properties, shell grid, sidebar/topbar/content styles, responsive breakpoints, and skip-to-content a11y"
    requirement: LAYOUT-01
    verification:
      - kind: other
        ref: "npx vite build"
        status: pass
    human_judgment: false

duration: 17min
completed: 2026-07-19
status: complete
---

# Phase 4: Layout e Navegação — Plan 01 Summary

**Foundation layer for App Shell: navigation type definitions, useTheme composable with localStorage persistence, and layout CSS stylesheet with dark/light theme variables**

## Performance

- **Duration:** 17 min
- **Started:** 2026-07-19T16:30:00Z
- **Completed:** 2026-07-19T16:47:00Z
- **Tasks:** 3
- **Files modified:** 4

## Accomplishments

- Navigation type system (`NavModule`, `NavCategory`, `NavItem`) defined and exported
- Complete navigation tree with Dashboard (fixed) + 4 categories (Gestão, Operações, Administração, Relatórios) totaling 11 module entries
- Route-to-module mapping (`routeModuleMap`) for PanelMenu active-state detection
- `useTheme` composable with `isDark` (readonly) and `toggle()` — localStorage persistence, `watch` with `immediate: true`
- Layout CSS with full app shell grid layout (sidebar, topbar, content areas)
- Dark mode CSS custom properties (`#0f172a` body, `#1e293b` surfaces, `#6366f1` accent) and light mode overrides
- Sidebar collapse CSS with width transition, label hiding via deep selectors on PanelMenu
- Responsive breakpoint at 768px for mobile drawer overlay
- Skip-to-content a11y link styles
- `main.ts` updated to import `layout.css` in correct order

## Task Commits

Each task was committed atomically:

1. **Task 1: Create navigation type definitions and module tree** - `922c4a6` (feat)
2. **Task 2: Create useTheme composable with localStorage persistence** - `f423a7d` (feat)
3. **Task 3: Create layout CSS with theme variables and component structure styles** - `c2112be` (feat)

**Plan metadata:** `pending` (docs: complete plan)

## Files Created/Modified

- `frontend/src/types/navigation.ts` — NavModule, NavCategory, NavItem types + navigationTree constant + routeModuleMap mapping
- `frontend/src/composables/useTheme.ts` — Dark/light toggle composable with localStorage persistence
- `frontend/src/styles/layout.css` — Layout styles with CSS custom properties, shell grid, sidebar/topbar/content styles, responsive breakpoints, a11y skip link
- `frontend/src/main.ts` — Added `import './styles/layout.css'` after `auth.css`

## Decisions Made

- **Admin modules use `roles[]` instead of `permission`**: Usuários, Perfis de Acesso, and Logs de Auditoria set `permission: null` with `roles` array. Sidebar will check `roles` when `permission` is null (delegated to Plan 2 filtering logic).
- **`routeModuleMap` includes create/edit/show variants**: Route-name-to-category mapping includes CRUD variants (e.g., `equipment.index`, `equipment.create`, `equipment.edit`) so sidebar highlighting works across all sub-pages.
- **`readonly(isDark)` in return**: Prevents components from mutating theme state directly — only `toggle()` can change the value.
- **`:root` for light defaults, `.app-dark` for dark overrides**: This ensures clean separation — `:root` values apply when `.app-dark` is absent, and `.app-dark` selectors override them when present.
- **CSS Grid with `grid-template-areas`**: The shell uses named grid areas (`sidebar`, `topbar`, `content`) for semantic, maintainable layout.

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered

- **No `build` script in package.json**: The plan referenced `npm run build` but no scripts are defined in `frontend/package.json`. Used `npx vite build` directly, which completed successfully (460 modules, 0 errors).
- **Pre-existing TypeScript errors**: Two unrelated pre-existing errors exist (`PasswordInput.vue` type mismatch and `router/index.ts` roles type) in files untouched by this plan.

## Stub Tracking

No stubs introduced — all created files contain production-ready code with no placeholders.

## Threat Flags

No threat flags — no new network endpoints, auth paths, file access patterns, or trust boundary changes introduced.

## User Setup Required

None — no external service configuration required.

## Next Phase Readiness

- Plan 1 foundation complete: navigation types, theme composable, and layout CSS are ready for consumption by Plan 2 (App Shell components)
- `types/navigation.ts` exports are ready to be imported by `AppSidebar.vue`
- `useTheme()` composable is ready to be called from `AppTopbar.vue`
- `layout.css` classes (`.app-shell`, `.app-sidebar`, `.app-topbar`, `.app-content`) are ready for use in `AppLayout.vue`, `AppSidebar.vue`, `AppTopbar.vue`

## Self-Check: PASSED

All deliverables verified:
- ✅ `frontend/src/types/navigation.ts` — exists, compiles with 0 errors
- ✅ `frontend/src/composables/useTheme.ts` — exists, compiles with 0 errors
- ✅ `frontend/src/styles/layout.css` — exists, build passes with 460 modules
- ✅ `frontend/src/main.ts` — imports layout.css after auth.css
- ✅ All 3 task commits verified (`922c4a6`, `f423a7d`, `c2112be`)
- ✅ Final metadata commit verified (`29b39a6`)
- ✅ Build succeeded (`npx vite build` — 0 errors)

---

*Phase: 04-layout-navegacao*
*Completed: 2026-07-19*
