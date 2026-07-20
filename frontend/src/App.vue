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
import { useTheme } from '@/composables/useTheme'

const route = useRoute()

// Re-aplica o tema após Vue montar para garantir que PrimeVue
// detecte a classe .app-dark corretamente
useTheme()
</script>
