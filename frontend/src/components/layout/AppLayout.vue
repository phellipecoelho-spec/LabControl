<template>
  <div class="app-shell" :class="{ 'app-shell--collapsed': !isMobile && sidebarCollapsed }">
    <!-- Skip-to-content link (first focusable element) -->
    <a href="#main-content" class="skip-to-content" @click="mobileDrawerVisible = false">
      Pular para conteúdo
    </a>

    <!-- Desktop sidebar (hidden on mobile) -->
    <AppSidebar
      v-if="!isMobile"
      :collapsed="sidebarCollapsed"
      class="app-sidebar--desktop"
    />

    <!-- Mobile drawer -->
    <Drawer
      v-model:visible="mobileDrawerVisible"
      position="left"
      :modal="true"
      :blockScroll="true"
      :dismissable="true"
      :showCloseIcon="true"
      header="LabControl"
      class="app-sidebar--mobile"
      :pt="{
        root: { style: { width: '300px' } },
        header: { style: { padding: '16px' } },
        content: { style: { padding: '0' } },
      }"
    >
      <AppSidebar
        :collapsed="false"
        @toggle-sidebar="mobileDrawerVisible = false"
      />
    </Drawer>

    <!-- Topbar -->
    <AppTopbar
      :collapsed="isMobile ? false : sidebarCollapsed"
      @toggle-sidebar="toggleSidebar"
    />

    <!-- Main content -->
    <main id="main-content" tabindex="-1" class="app-content">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useMediaQuery } from '@vueuse/core'
import Drawer from 'primevue/drawer'
import AppSidebar from './AppSidebar.vue'
import AppTopbar from './AppTopbar.vue'

const sidebarCollapsed = ref(false)
const mobileDrawerVisible = ref(false)
const isMobile = useMediaQuery('(max-width: 767px)')

function toggleSidebar() {
  if (isMobile.value) {
    mobileDrawerVisible.value = !mobileDrawerVisible.value
  } else {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }
}

// Close drawer when resizing from mobile to desktop
watch(isMobile, (mobile) => {
  if (!mobile) {
    mobileDrawerVisible.value = false
  }
})
</script>
