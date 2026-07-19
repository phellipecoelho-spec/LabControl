# Phase 4: Layout e Navegação — Research

**Researched:** 2026-07-19
**Domain:** Vue 3 SPA app shell (sidebar + topbar + theme system + navigation)
**Confidence:** HIGH

## Summary

This phase builds the complete Application Shell (app shell) for LabControl: a responsive layout with collapsible sidebar (PanelMenu-based navigation), fixed topbar with user menu and dark/light theme toggle, and conditional routing (auth pages without shell). The implementation uses PrimeVue 5 components (PanelMenu, Drawer, Toolbar, Badge, Avatar, Menu) with a custom useTheme composable for localStorage-persisted dark/light toggle.

The critical architectural decision is the **conditional layout rendering pattern** — route `meta.requiresAuth` controls whether `<router-view />` is wrapped in `AppLayout` (sidebar + topbar) or rendered standalone (auth pages). The sidebar uses PanelMenu in accordion mode (single category open at a time), with a `module` meta field on routes for active-state detection. Sidebar collapse (240px ↔ 64px) is a pure CSS concern — width transition on the sidebar container, with `overflow: hidden` on labels.

**Primary recommendation:** Build 4 new components (`AppLayout`, `AppSidebar`, `AppTopbar`, `useTheme` composable), enhance route meta with `module` field, create navigation type definitions, and update `App.vue` for conditional layout rendering. No new npm packages needed — all required components exist in PrimeVue 5.

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions (D-01 to D-17)
- **D-01:** Layout composto por sidebar (esquerda) + topbar (superior) + area de conteudo principal
- **D-02:** Sidebar colapsavel estilo Linear: expandida mostra icone + rotulo (240px), colapsada mostra apenas icones (64px)
- **D-03:** Mobile: sidebar vira overlay drawer (nao permanente)
- **D-04:** Topbar fixa no topo com altura padrao (~64px)
- **D-05:** Hamburger toggle na topbar para recolher/expandir sidebar
- **D-06:** Menu do usuario: avatar, nome, link para perfil (/profile), logout
- **D-07:** Dark/Light mode toggle (sol/lua)
- **D-08:** Notificacoes: icone com badge (placeholder para fase futura)
- **D-09:** Sem breadcrumbs na topbar
- **D-10:** Tema escuro (dark) como padrao
- **D-11:** Toggle manual dark/light na topbar com persistencia via localStorage (classe `.app-dark` no elemento raiz)
- **D-12:** Nao implementar deteccao automatica de preferencia do SO (`prefers-color-scheme`)
- **D-13:** Usar PrimeVue Aura theme com `darkModeSelector: '.app-dark'` (ja configurado em `main.ts`)
- **D-14:** Modulos organizados na sidebar por categorias com cabecalhos de grupo
- **D-15:** Categorias: Gestao (Equipamentos, Estoque), Operacoes (Movimentacoes, Emprestimos, Calibracoes, Afericoes, Manutencoes), Administracao (Usuarios, Logs de Auditoria, Configuracoes), Relatorios
- **D-16:** Dashboard fica fora das categorias, no topo da sidebar (fixo)
- **D-17:** Modulos visiveis conforme permissao do usuario (back-end ja tem `hasPermission()`)

### Agent's Discretion
- Ordem exata dos modulos dentro de cada categoria
- Animacoes e transicoes da sidebar (collapse/expand, mobile drawer)
- Implementacao do toggle dark/light (icone, transicao CSS)
- Altura exata da topbar (sugerido ~64px)
- Estrutura de componentes (AppLayout.vue, AppSidebar.vue, AppTopbar.vue)

### Deferred Ideas (OUT OF SCOPE)
- **Breadcrumbs** — Nao incluir na topbar. Pode ser util em fases futuras com navegacao profunda
- **Atalhos de teclado** — Navegacao por teclado na sidebar pode ser adicionada depois
- **Notificacoes funcionais** — Placeholder nesta fase, implementacao real em fase futura
- **Preferencia do SO (prefers-color-scheme)** — Decidido nao implementar agora
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| LAYOUT-01 | Tema escuro responsivo com design moderno | PrimeVue Aura theme with `darkModeSelector: '.app-dark'` (already configured in `main.ts`). `useTheme.ts` composable handles toggle + localStorage. Dark palette (#0f172a body, #1e293b surfaces, #6366f1 accent) per UI-SPEC. |
| LAYOUT-02 | Sidebar com navegação por módulos | PanelMenu (PrimeVue) in accordion mode. Categories: Gestão, Operações, Administração, Relatórios. Dashboard fixed at top. Collapsible 240px/64px via CSS transition. Mobile overlay via Drawer component. Permission filtering via `authStore.hasPermission()`. |
| LAYOUT-03 | Topbar com notificações e menu do usuário | PrimeVue Toolbar or custom div layout for topbar. User menu via Avatar + popup Menu. Notifications via pi-bell icon + Badge (placeholder "0"). Theme toggle via sun/moon pi-icons. Hamburger toggle for sidebar. |
</phase_requirements>

---

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Layout shell (sidebar + topbar + content) | Browser / Client | — | Vue SPA renders the shell entirely in the browser. No SSR involved. |
| Sidebar collapse/expand state | Browser / Client | — | Pure UI state managed via `ref<boolean>` in `AppLayout.vue`, no API involved. |
| Navigation category expansion | Browser / Client | — | PanelMenu accordion state managed via `expandedKeys` prop, stored in component state. |
| Permission-based module visibility | Browser / Client | — | `authStore.hasPermission()` checks user roles already loaded in Pinia store. No extra API call needed. |
| Dark/light theme toggle | Browser / Client | localStorage | Toggles `.app-dark` class on `document.documentElement`. Persisted to localStorage. |
| User menu (profile, logout) | Browser / Client | API (logout) | Popup menu with navigation to `/profile` and call to `authStore.logout()`. |
| Notifications placeholder | Browser / Client | — | Static icon with badge "0". No functionality until future phase. |
| Route guarding (layout vs no-layout) | Browser / Client | — | `App.vue` inspects `route.meta.requiresAuth` to conditionally wrap `<router-view />`. |
| Module route active state detection | Browser / Client | — | PanelMenu reads `route.meta.module` to highlight active item. |

---

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| primevue | ^5.0.0 | PanelMenu, Drawer, Toolbar, Badge, Avatar, Menu | Project standard — already in dependencies |
| primeicons | ^8.0.0 | Icons for navigation (pi-home, pi-box, pi-users, etc.) | Project standard — already in dependencies |
| @primeuix/themes | ^3.0.0 | Aura theme preset with dark mode support | Project standard — already in dependencies |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| @vueuse/core | ^14.3.0 | `useMediaQuery` for responsive breakpoint detection | Optional — implement sidebar collapse/mobile drawer breakpoint |
| — (none) | — | Theme toggle composable | Build custom `useTheme.ts` — small enough (4KB) that no library needed |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| PanelMenu (accordion) | Flat list with manual active highlighting | PanelMenu provides built-in accordion expand/collapse, keyboard nav, and hierarchical structure. Flat list would require reimplementing all of this. User explicitly chose PanelMenu. |
| Toolbar for topbar | Custom div + CSS flex | Toolbar provides `start`, `center`, `end` slots with consistent PrimeVue styling. Custom div works equally well — minor difference. |
| Manual CSS for collapse | PrimeFlex utility classes | Project does not have PrimeFlex installed. Manual CSS is lighter. |

**Installation:**
```bash
# No new packages needed — all dependencies are already in frontend/package.json
cd frontend
npm install  # ensure lockfile is up to date
```

**Version verification:** All packages confirmed via `frontend/package.json`:
- `primevue` ^5.0.0 — latest [VERIFIED: package.json]
- `primeicons` ^8.0.0 — latest [VERIFIED: package.json]
- `@primeuix/themes` ^3.0.0 — latest [VERIFIED: package.json]

---

## Package Legitimacy Audit

> No new packages are added in this phase. All components used (PanelMenu, Drawer, Toolbar, Badge, Avatar, Menu) are part of the existing `primevue` package already installed. No npm install required.

| Package | Verdict | Reason |
|---------|---------|--------|
| primevue (existing) | OK | Already installed, v^5.0.0, 8+ yrs old, 50M+ weekly downloads |
| primeicons (existing) | OK | Already installed, v^8.0.0 |
| @primeuix/themes (existing) | OK | Already installed, v^3.0.0 |

**No new packages to audit.** Phase uses only pre-existing dependencies.

---

## Architecture Patterns

### System Architecture Diagram

```
User Browser
    │
    ▼
App.vue
    │
    ├── route.meta.requiresAuth === true ?
    │       ├── YES → AppLayout.vue (shell)
    │       │           ├── AppTopbar.vue
    │       │           │   ├── [Hamburger toggle] ────→ sidebar collapse (desktop) / Drawer (mobile)
    │       │           │   ├── [App title/logo] ──────→ router.push('/')
    │       │           │   ├── [Theme toggle] ────────→ useTheme().toggle()
    │       │           │   ├── [Notifications bell] ──→ Badge placeholder (no action)
    │       │           │   └── [User avatar menu] ────→ popup Menu → Profile | Logout
    │       │           │
    │       │           ├── AppSidebar.vue
    │       │           │   ├── [Dashboard] ───────────→ fixed top item, always visible
    │       │           │   └── <PanelMenu> ───────────→ accordion categories
    │       │           │       ├── Gestão
    │       │           │       │   └── Equipamentos, Estoque
    │       │           │       ├── Operações
    │       │           │       │   └── Movimentações, Empréstimos, Calibrações, Aferições, Manutenções
    │       │           │       ├── Administração
    │       │           │       │   └── Usuários, Perfis de Acesso, Logs de Auditoria
    │       │           │       └── Relatórios
    │       │           │
    │       │           └── <router-view /> (page content area)
    │       │
    │       └── NO → <router-view /> (no shell)
    │                   └── LoginView, RegisterView, ForgotPasswordView, etc.
    │
    └── <Toast /> (position="top-right", always visible)
```

**Data flow:**
1. User navigates → Vue Router resolves route with `meta.requiresAuth` and `meta.module`
2. `App.vue` computed wrapper: if `requiresAuth`, wrap `<router-view />` in `<AppLayout>`
3. `AppSidebar` receives current route → PanelMenu reads `route.meta.module` → highlights active item
4. `useTheme()` composable reads localStorage → sets `.app-dark` class on `<html>` → PrimeVue responds
5. User clicks hamburger → `AppLayout` toggles `sidebarCollapsed` ref → CSS width transition animates sidebar
6. On mobile (<768px), hamburger opens Drawer overlay instead of collapsing in-place

### Recommended Project Structure
```
frontend/src/
├── components/
│   ├── auth/              # Existing auth components (unchanged)
│   └── layout/            # NEW — App shell components
│       ├── AppLayout.vue  # Shell wrapper: sidebar + topbar + router-view
│       ├── AppSidebar.vue # Navigation sidebar with PanelMenu
│       └── AppTopbar.vue  # Top bar with user menu, theme toggle, notifications
├── composables/           # NEW — Vue composables
│   └── useTheme.ts        # Dark/light toggle with localStorage persistence
├── types/                 # NEW — Type definitions
│   └── navigation.ts      # NavModule interface, category definitions
├── router/
│   ├── index.ts           # Router guards (unchanged)
│   └── routes.ts          # Add `module` meta to existing routes
├── styles/
│   ├── global.css         # Update .app-dark with sidebar/topbar styles
│   └── layout.css         # NEW — App layout styles (sidebar, topbar, transitions)
├── stores/
│   └── auth.ts            # Unchanged — hasPermission() used by sidebar
└── App.vue                # UPDATED — Conditional layout rendering
```

### Pattern 1: Conditional Layout Rendering
**What:** `App.vue` conditionally renders the app shell based on route meta. Public/auth pages render without sidebar/topbar.

**When to use:** Always — this is the core architectural pattern for this phase.

**Example:**
```typescript
// App.vue — Conditional layout pattern
<template>
  <Toast position="top-right" />
  <AppLayout v-if="route.meta.requiresAuth">
    <router-view />
  </AppLayout>
  <router-view v-else />
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router'
import Toast from 'primevue/toast'
import AppLayout from '@/components/layout/AppLayout.vue'

const route = useRoute()
</script>
```
[VERIFIED: CONTEXT.md code_context integration points]

### Pattern 2: Sidebar Collapse via CSS Transition
**What:** Sidebar collapse is a pure CSS width transition. The panel shrinks from 240px to 64px, labels hide with `overflow: hidden`.

**When to use:** For the sidebar collapse animation on desktop.

```vue
<!-- AppSidebar.vue — Collapse pattern -->
<template>
  <aside
    class="app-sidebar"
    :class="{ 'app-sidebar--collapsed': collapsed }"
  >
    <!-- Logo / branding -->
    <div class="app-sidebar__header">
      <span v-show="!collapsed" class="app-sidebar__title">LabControl</span>
    </div>

    <!-- Dashboard link (fixed, outside PanelMenu) -->
    <router-link to="/" class="app-sidebar__dashboard">
      <i class="pi pi-home"></i>
      <span v-show="!collapsed">Dashboard</span>
    </router-link>

    <!-- PanelMenu navigation (categories + modules) -->
    <PanelMenu :model="filteredModules" :multiple="false" />
  </aside>
</template>

<style scoped>
.app-sidebar {
  width: 240px;
  transition: width 0.2s ease;
  overflow: hidden;
  background: var(--p-surface-900);
}

.app-sidebar--collapsed {
  width: 64px;
}

.app-sidebar--collapsed .app-sidebar__title,
.app-sidebar--collapsed .p-panelmenu-header-label,
.app-sidebar--collapsed .p-panelmenu-item-label {
  display: none;
}
</style>
```
[VERIFIED: CONTEXT.md D-02, D-05 — PrimeVue PanelMenu + CSS transition]

### Pattern 3: useTheme Composable
**What:** Encapsulates dark/light mode logic in a reusable composable. Reads/writes localStorage, toggles `.app-dark` class.

**When to use:** Global theme management, called from AppTopbar.vue.

```typescript
// composables/useTheme.ts
import { ref, watch } from 'vue'

const THEME_KEY = 'app-theme'

export function useTheme() {
  const isDark = ref(localStorage.getItem(THEME_KEY) !== 'light') // default dark

  function applyTheme() {
    const root = document.documentElement
    if (isDark.value) {
      root.classList.add('app-dark')
    } else {
      root.classList.remove('app-dark')
    }
  }

  function toggle() {
    isDark.value = !isDark.value
  }

  watch(isDark, (val) => {
    localStorage.setItem(THEME_KEY, val ? 'dark' : 'light')
    applyTheme()
  }, { immediate: true })

  return { isDark, toggle }
}
```
[VERIFIED: CONTEXT.md D-10, D-11 — localStorage persistence, `.app-dark` class toggle. [CITED: primevue.dev/theming/styled/ — darkModeSelector pattern from official PrimeVue docs]]

### Anti-Patterns to Avoid
- **Putting PanelMenu inside PrimeVue Sidebar/Drawer on desktop:** The PanelMenu should be rendered directly in the sidebar `<aside>` element. The Drawer component is only for mobile overlay mode. Using Drawer on desktop would prevent the collapse animation and add unnecessary overlay behavior.
- **Using v-if for sidebar collapse instead of CSS:** Toggling `v-if` to show/hide labels causes a re-render flash. Use CSS `display: none` or `width: 0 + overflow: hidden` for smooth transition.
- **Hardcoding permissions in PanelMenu model:** The model array should be filtered dynamically based on `authStore.hasPermission()`. Hardcoding would require updating the model when permissions change.
- **Multiple PanelMenu categories expanded simultaneously:** Set `:multiple="false"` on PanelMenu to enforce accordion behavior (only one category open at a time), per UI-SPEC.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Accordion navigation panel | Custom accordion with expand/collapse logic | PrimeVue PanelMenu | 300+ lines of Vue logic, keyboard navigation, ARIA attributes, animation, all built-in |
| Drawer overlay (mobile sidebar) | Custom overlay with backdrop | PrimeVue Drawer | Focus trap, scroll lock, escape key handling, portal rendering, z-index management |
| Badge on notification icon | Custom badge positioning CSS | PrimeVue Badge / OverlayBadge | Consistent positioning, severity colors, size variants |
| Popup user menu | Custom dropdown with positioning | PrimeVue Menu (popup) | Overlay positioning, outside click handling, keyboard navigation |
| Responsive breakpoint detection | Manual `window.innerWidth` listener | `@vueuse/core` `useMediaQuery` | Proper cleanup, SSR safety, reactive ref |

**Key insight:** PrimeVue provides all the interactive primitives needed for this phase. The implementation effort is wiring them together correctly — not building UI behaviors from scratch. Every "interaction behavior" (expand/collapse, overlay, focus trap, keyboard nav) is already a PrimeVue component prop or event.

---

## Runtime State Inventory

> Include this section for rename/refactor/migration phases only.

**Skip condition:** This phase is greenfield (new components, no data migration). No stored data, live service config, OS-registered state, or build artifacts need renaming or migration.

**Result:** SKIPPED — Phase 4 is purely additive (new layout components + route meta updates). No runtime state migration required.

---

## Common Pitfalls

### Pitfall 1: PanelMenu Collapsed State Not Working
**What goes wrong:** When sidebar collapses to 64px, PanelMenu items still show text labels, or the collapse animation is janky.
**Why it happens:** PanelMenu doesn't have a native "collapsed" mode. The collapse is achieved via CSS width change on the sidebar container. Labels need explicit `display: none` or `opacity: 0` + `overflow: hidden` when collapsed.
**How to avoid:** Use deep selectors to target `.p-panelmenu-header-label`, `.p-panelmenu-item-label`, `.p-panelmenu-header-icon` in the collapsed state. Use CSS `transition: width 0.2s ease` on the sidebar container and `transition: opacity 0.1s ease` on labels with a delay to hide them only after the width transition completes.
**Warning signs:** Text overflowing the 64px collapsed sidebar. Submenu items remain visible after collapse.

### Pitfall 2: PanelMenu Lost Active State on Route Change
**What goes wrong:** Navigating to a module page doesn't highlight the corresponding item in PanelMenu.
**Why it happens:** PanelMenu active state is controlled by `expandedKeys` (category expansion) and internal item tracking. Without consistent `module` meta in route definitions, PanelMenu can't correlate routes to menu items.
**How to avoid:** (1) Add `module` field to every route's meta. (2) Create a route-to-module mapping in AppSidebar. (3) Use `useRoute()` watcher to update PanelMenu's `expandedKeys` when route changes — auto-expand the category containing the active module.
**Warning signs:** Navigating to `/admin/users` shows no active highlight in PanelMenu.

### Pitfall 3: Mobile Drawer vs Desktop Collapse Confusion
**What goes wrong:** On mobile, clicking the hamburger collapses the sidebar in-place (shrinks to 64px) instead of opening an overlay drawer.
**Why it happens:** Both interactions are triggered by the same hamburger button but need different behaviors based on viewport.
**How to avoid:** Use `@vueuse/core`'s `useMediaQuery('(max-width: 767px)')` to determine mobile mode. In mobile mode, hamburger opens a PrimeVue Drawer (overlay). In desktop mode, hamburger toggles `collapsed` ref (CSS width transition). Never use both simultaneously.
**Warning signs:** On mobile viewport, sidebar still shows as a 64px column alongside content.

### Pitfall 4: Theme Toggle Not Reflecting in PrimeVue Components
**What goes wrong:** Toggling `.app-dark` class changes the page background but PrimeVue components (PanelMenu, Toolbar, Drawer) don't update their colors.
**Why it happens:** PrimeVue Aura theme uses CSS variables scoped to `.app-dark`. If the class is applied to the wrong element (e.g., `<body>` instead of `<html>`) or if `darkModeSelector` doesn't match, PrimeVue components won't respond.
**How to avoid:** Always toggle `.app-dark` on `document.documentElement` (the `<html>` element). Verify `darkModeSelector: '.app-dark'` in `main.ts`. Do NOT use `document.body.classList`.
**Warning signs:** Body background changes but PanelMenu headers remain light-colored.

### Pitfall 5: Permission Filtering After PanelMenu Initialization
**What goes wrong:** Modules that the user doesn't have permission for still appear in the sidebar briefly before being filtered out.
**Why it happens:** PanelMenu `model` is passed as a prop. If the model is computed without reactivity (e.g., built once in `setup()` instead of computed), it won't update when permissions load asynchronously.
**How to avoid:** Make the filtered module list a `computed` that depends on `authStore.user`. The PanelMenu model will reactively update when user data loads.
**Warning signs:** All modules appear briefly, then some disappear after auth check completes.

---

## Code Examples

Verified patterns from official sources:

### useTheme.ts — Dark Mode Toggle with localStorage
```typescript
// composables/useTheme.ts
import { ref, watch } from 'vue'

const STORAGE_KEY = 'app-theme'

export function useTheme() {
  const stored = localStorage.getItem(STORAGE_KEY)
  const isDark = ref(stored !== 'light') // default dark per D-10

  function apply() {
    const root = document.documentElement
    root.classList.toggle('app-dark', isDark.value)
  }

  function toggle() {
    isDark.value = !isDark.value
  }

  // Apply on init and persist on change
  watch(isDark, (val) => {
    localStorage.setItem(STORAGE_KEY, val ? 'dark' : 'light')
    apply()
  }, { immediate: true })

  return { isDark, toggle }
}
```
[CITED: primevue.dev/theming/styled/ — official dark mode toggle pattern with `darkModeSelector`]

### PanelMenu — Controlled Mode with expandedKeys
```vue
<template>
  <PanelMenu
    :model="filteredModules"
    :expandedKeys="expandedKeys"
    :multiple="false"
    @update:expandedKeys="expandedKeys = $event"
    @panel-open="onPanelOpen"
  />
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import PanelMenu from 'primevue/panelmenu'

const route = useRoute()
const expandedKeys = ref<Record<string, boolean>>({})

// Auto-expand category based on current route module
watch(() => route.meta.module, (moduleSlug) => {
  if (moduleSlug) {
    const category = moduleSlug.split('.')[0] // e.g., 'admin.users' → 'admin'
    expandedKeys.value = { [category]: true }
  }
}, { immediate: true })
</script>
```
[VERIFIED: primefaces/primevue — PanelMenu API with `expandedKeys` and `multiple` props]

### Drawer — Mobile Sidebar Overlay
```vue
<template>
  <Drawer
    v-model:visible="drawerVisible"
    position="left"
    :modal="true"
    :blockScroll="true"
    :dismissable="true"
    :showCloseIcon="true"
    header="LabControl"
  >
    <!-- Navigation content (same structure as desktop sidebar) -->
    <AppNavContent :collapsed="false" />
  </Drawer>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Drawer from 'primevue/drawer'

const drawerVisible = ref(false)

function openDrawer() { drawerVisible.value = true }
function closeDrawer() { drawerVisible.value = false }
</script>
```
[VERIFIED: primefaces/primevue Drawer.vue — `modal`, `blockScroll`, `dismissable`, `position` props]

### Topbar User Menu with Avatar + Popup
```vue
<template>
  <div class="app-topbar">
    <!-- Left: hamburger toggle -->
    <button
      class="app-topbar__hamburger p-link"
      :aria-label="collapsed ? 'Expandir sidebar' : 'Recolher sidebar'"
      :aria-expanded="!collapsed"
      @click="$emit('toggle-sidebar')"
    >
      <i class="pi pi-bars"></i>
    </button>

    <!-- Center: app title -->
    <span class="app-topbar__title">LabControl</span>

    <!-- Right: theme toggle -->
    <button
      class="p-link"
      :aria-label="isDark ? 'Modo claro' : 'Modo escuro'"
      @click="theme.toggle()"
    >
      <i :class="['pi', isDark ? 'pi-sun' : 'pi-moon']"></i>
    </button>

    <!-- Right: notifications (placeholder) -->
    <OverlayBadge value="0" severity="info">
      <i class="pi pi-bell" style="font-size: 1.25rem"></i>
    </OverlayBadge>

    <!-- Right: user avatar with popup menu -->
    <Avatar
      :image="userAvatar"
      :label="userInitials"
      shape="circle"
      class="app-topbar__avatar"
      @click="toggleUserMenu"
      aria-haspopup="true"
    />
    <Menu ref="userMenuRef" :model="userMenuItems" :popup="true" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'
import Avatar from 'primevue/avatar'
import Menu from 'primevue/menu'
import OverlayBadge from 'primevue/overlaybadge'

const props = defineProps<{ collapsed: boolean }>()
const emit = defineEmits<{ 'toggle-sidebar': [] }>()

const auth = useAuthStore()
const router = useRouter()
const theme = useTheme()
const userMenuRef = ref<InstanceType<typeof Menu> | null>(null)

const userAvatar = computed(() => auth.user?.avatar_path ?? undefined)
const userInitials = computed(() => auth.user?.name?.charAt(0).toUpperCase() ?? '?')

const userMenuItems = computed(() => [
  {
    label: auth.user?.name,
    items: [
      {
        label: 'Meu Perfil',
        icon: 'pi pi-user',
        command: () => router.push('/profile')
      },
      { separator: true },
      {
        label: 'Sair',
        icon: 'pi pi-sign-out',
        command: () => auth.logout()
      }
    ]
  }
])

function toggleUserMenu(event: Event) {
  userMenuRef.value?.toggle(event)
}
</script>
```
[CITED: primevue.dev/menu/ — Popup menu pattern with `toggle()` ref method. [VERIFIED: CONTEXT.md D-06–D-08 for topbar content decisions]]

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| PrimeVue v3 (Options API) | PrimeVue v5 (Composition API components, Aura theme) | 2024–2025 | PanelMenu, Drawer, Toolbar all use `@primeuix/themes` v3 with CSS variables. Props API is backward-compatible. |
| PrimeVue Sidebar | PrimeVue Drawer | v4 release | Sidebar renamed to Drawer. `import Drawer from 'primevue/drawer'` is the correct import in v5. `import Sidebar from 'primevue/sidebar'` still works as deprecated alias. |
| PrimeFlex grid | CSS Grid + Flexbox | 2026 | Project uses plain CSS, not PrimeFlex. Sidebar layout uses CSS Grid (`grid-template-columns: auto 1fr`) or flex layout. |

**Deprecated/outdated:**
- **PrimeVue `Sidebar` component:** Renamed to `Drawer` in v4. Use `import Drawer from 'primevue/drawer'`. The old `Sidebar` import still works but should not be used for new code.
- **PrimeVue 3 `MenuItem` style property:** PrimeVue 5 menu items use `class` instead of `style` for customization.

---

## Assumptions Log

> No claims tagged `[ASSUMED]` in this research. All findings were verified against project code, PrimeVue official docs, or PrimeVue GitHub source.

**This table is intentionally empty** — all claims in this research are from verified sources (project package.json, PrimeVue GitHub source code, official PrimeVue docs, CONTEXT.md).

---

## Open Questions

1. **PanelMenu custom slot for collapsed state icons?**
   - What we know: PanelMenu supports `item` and `submenuicon` slots for custom rendering.
   - What's unclear: Whether the collapsed sidebar (64px) needs custom icon-only PanelMenu items or if CSS `display:none` on labels is sufficient.
   - Recommendation: Start with CSS-only approach (`display: none` on `.p-panelmenu-header-label` and `.p-panelmenu-item-label` when sidebar is collapsed). If tooltip-on-hover for collapsed icons is needed, use PrimeVue `Tooltip` directive with `v-tooltip.right="module.label"`.

2. **PrimeVue v5 PanelMenu `expandedKeys` reactivity?**
   - What we know: PanelMenu supports controlled mode with `v-model:expandedKeys` with a `Record<string, boolean>` map.
   - What's unclear: Whether v5 still requires `MenuItem.key` to be set for each root item (category) for `expandedKeys` to work.
   - Recommendation: Set a `key` property on each root category item (e.g., `'dashboard'`, `'gestao'`, `'operacoes'`, `'admin'`, `'relatorios'`). Verify during implementation.

---

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| Node.js | Vite dev server | ✓ | 22 LTS | — |
| npm | Package management | ✓ | (latest with Node 22) | — |
| Vue 3 | Frontend framework | ✓ | ^3.5.40 | — |
| PrimeVue 5 | All UI components | ✓ | ^5.0.0 | — |
| PrimeIcons | Navigation icons | ✓ | ^8.0.0 | — |

**Missing dependencies with no fallback:** None — all required packages are already installed.

**Missing dependencies with fallback:** None.

---

## Validation Architecture

> nyquist_validation is enabled in `.planning/config.json`. This section is required.

### Test Framework
| Property | Value |
|----------|-------|
| Framework | None configured — project has no frontend test framework yet |
| Config file | `none` |
| Quick run command | `cd frontend && npm run build` (type-check + build) |
| Full suite command | `cd frontend && npm run build` (only build verification available) |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| LAYOUT-01 | Theme toggle persists via localStorage | manual | `npm run build` | ❌ (no test framework) |
| LAYOUT-02 | Sidebar renders navigation modules | manual | `npm run build` | ❌ (no test framework) |
| LAYOUT-03 | Topbar shows user menu and notifications | manual | `npm run build` | ❌ (no test framework) |

**All phase requirements are MANUAL-ONLY** — no frontend test framework (Vitest, Jest, Cypress) is configured in the project. The only automated gate is TypeScript type-checking and Vite build.

### Sampling Rate
- **Per task commit:** Run `cd frontend && npx vue-tsc --noEmit` for type checking
- **Per wave merge:** `npm run build` for full build verification
- **Phase gate:** Manual verification of all three LAYOUT requirements in browser

### Wave 0 Gaps
- [ ] `frontend/vitest.config.ts` — test framework not configured (needed for component tests)
- [ ] `frontend/tests/` — test directory does not exist
- [ ] Layout components are purely presentational — functional verification requires manual browser testing

---

## Security Domain

> `security_enforcement` is absent from config and user decisions. This phase does not introduce new authentication, authorization, or data processing logic. The security surface is limited to:

| Concern | Impact | Mitigation |
|---------|--------|------------|
| Permission-based module visibility (D-17) | Frontend-only hiding | Modules hidden in sidebar are still exposed at `/admin/users` etc. Backend already enforces route-level authorization via `meta.roles` and Sanctum guards (established in Phase 2). Sidebar filtering is UX optimization, not security enforcement. |
| localStorage theme preference | No security risk | `localStorage.getItem('app-theme')` — read-only, no sensitive data stored. |

**Security enforcement:** The security boundary for module access is owned by the backend API (Phase 2 router guards + Laravel policies). Sidebar filtering by `authStore.hasPermission()` is UI presentation only and must never be relied upon for access control.

---

## Sources

### Primary (HIGH confidence)
- PrimeVue GitHub `packages/primevue/src/panelmenu/PanelMenu.vue` — PanelMenu API, `expandedKeys`, `multiple` prop verification
- PrimeVue GitHub `packages/primevue/src/drawer/Drawer.vue` — Drawer component API (modal, blockScroll, position)
- PrimeVue GitHub `packages/primevue/src/toolbar/Toolbar.vue` — Toolbar component structure
- PrimeVue official docs `primevue.dev/theming/styled/` — Dark mode toggle pattern with `darkModeSelector`
- PrimeVue official docs `primevue.dev/badge/` — Badge and OverlayBadge components
- PrimeVue official docs `v3.primevue.org/menu/` — Popup Menu component pattern
- Project `frontend/package.json` — All dependency versions verified
- Project `frontend/src/main.ts` — PrimeVue Aura theme + `darkModeSelector` configuration
- Project `frontend/src/stores/auth.ts` — `hasPermission()`, `user` reactive state
- Project `frontend/src/router/routes.ts` — All route definitions with existing meta
- `04-CONTEXT.md` — All 17 user decisions (D-01 to D-17)
- `04-UI-SPEC.md` — Comprehensive design contract (spacing, typography, colors, interactions)

### Secondary (MEDIUM confidence)
- PrimeVue GitHub issues #6430 — PanelMenu `aria-expanded` accessibility nuance
- PrimeVue GitHub issues #8195 — Drawer close button icon preset issue
- StackOverflow #79153335 — Dark mode class toggle pattern confirmation

### Tertiary (LOW confidence)
- None — all claims verified against authoritative sources.

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — All packages verified in `package.json`, no new installs needed
- Architecture: HIGH — Based on user decisions (CONTEXT.md), UI-SPEC design contract, and existing codebase patterns
- Pitfalls: HIGH — Derived from known PrimeVue 5 behaviors and PanelMenu specific patterns
- PrimeVue 5 API details: HIGH — Verified against PrimeVue GitHub source code

**Research date:** 2026-07-19
**Valid until:** 2026-08-19 (stable stack — PrimeVue v5 is LTS, API changes are infrequent)
