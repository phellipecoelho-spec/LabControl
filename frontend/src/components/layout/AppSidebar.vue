<template>
  <aside
    class="app-sidebar app-sidebar--desktop"
    :class="{ 'app-sidebar--collapsed': collapsed }"
  >
    <a href="#main-content" class="skip-to-content">Pular para conteúdo</a>

    <!-- Header -->
    <div class="app-sidebar__header">
      <span class="app-sidebar__header-title">LabControl</span>
    </div>

    <!-- Navigation -->
    <nav class="app-sidebar__nav">
      <!-- Dashboard link (fixed top, outside PanelMenu) -->
      <router-link
        to="/"
        class="app-sidebar__dashboard"
        :class="{ 'app-sidebar__dashboard--active': isDashboardActive }"
        aria-label="Dashboard"
      >
        <i class="pi pi-home"></i>
        <span>Dashboard</span>
      </router-link>

      <!-- PanelMenu for category groups -->
      <PanelMenu
        :model="panelMenuModel"
        :expandedKeys="expandedKeys"
        :multiple="false"
        @update:expandedKeys="expandedKeys = $event"
      />
    </nav>
  </aside>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PanelMenu from 'primevue/panelmenu'
import { navigationTree, routeModuleMap } from '@/types/navigation'
import type { NavCategory, NavModule } from '@/types/navigation'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  collapsed: boolean
}>()

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const expandedKeys = ref<Record<string, boolean>>({})

// Computed: Dashboard is active when route path is '/'
const isDashboardActive = computed(() => route.path === '/')

// Visibility check for a module item
function isModuleVisible(module: NavModule): boolean {
  if (module.permission) {
    return authStore.hasPermission(module.permission)
  }
  if (module.roles && module.roles.length > 0) {
    return module.roles.some(role => authStore.hasRole(role))
  }
  // No permission or roles restriction — visible (future module placeholder / dashboard)
  return true
}

// Computed: Current active route name for item highlighting
const activeRouteName = computed(() => route.name as string | undefined)

// Computed: PanelMenu model from navigation tree
const panelMenuModel = computed(() => {
  return navigationTree
    .filter((item): item is NavCategory => 'items' in item)
    .filter(category => {
      const visibleItems = category.items.filter(isModuleVisible)
      return visibleItems.length > 0
    })
    .map(category => ({
      key: category.key,
      label: category.label,
      icon: category.icon,
      items: category.items
        .filter(isModuleVisible)
        .map(module => ({
          label: module.label,
          icon: module.icon,
          class:
            activeRouteName.value === module.route
              ? 'app-sidebar__item--active'
              : undefined,
          command: () => {
            router.push({ name: module.route })
          },
        })),
    }))
})

// Auto-expand the category containing the active module
watch(
  () => route.name,
  name => {
    if (name && typeof name === 'string') {
      const categoryKey = routeModuleMap[name]
      if (categoryKey) {
        expandedKeys.value = { [categoryKey]: true }
      }
    }
  },
  { immediate: true },
)
</script>

<style scoped>
.app-sidebar__item--active {
  color: var(--app-accent) !important;
  background: var(--app-accent-soft) !important;
  border-radius: 6px;
}
</style>
