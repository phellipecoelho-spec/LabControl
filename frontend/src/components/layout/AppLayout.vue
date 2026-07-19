<template>
  <div class="app-shell" :class="{ 'app-shell--collapsed': sidebarCollapsed }">
    <!-- Desktop sidebar -->
    <AppSidebar
      :collapsed="sidebarCollapsed"
      class="app-sidebar--desktop"
    />

    <!-- Mobile drawer (placeholder for Plan 3) -->
    <!-- Reserved: <Drawer> will be added in Plan 3 -->

    <!-- Topbar -->
    <AppTopbar
      :collapsed="sidebarCollapsed"
      @toggle-sidebar="toggleSidebar"
    />

    <!-- Main content -->
    <main id="main-content" class="app-content">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppSidebar from './AppSidebar.vue'
import AppTopbar from './AppTopbar.vue'

const sidebarCollapsed = ref(false)

function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value
}
</script>
