<template>
  <header class="app-topbar">
    <!-- Hamburger toggle (leftmost) -->
    <button
      class="app-topbar__hamburger"
      :aria-label="collapsed ? 'Expandir sidebar' : 'Recolher sidebar'"
      :aria-expanded="!collapsed"
      @click="$emit('toggle-sidebar')"
    >
      <i class="pi pi-bars"></i>
    </button>

    <!-- Spacer -->
    <div class="app-topbar__spacer"></div>

    <!-- Theme toggle -->
    <button
      class="app-topbar__action"
      :aria-label="theme.isDark.value ? 'Modo claro' : 'Modo escuro'"
      :title="theme.isDark.value ? 'Modo claro' : 'Modo escuro'"
      @click="theme.toggle()"
    >
      <i :class="['pi', theme.isDark.value ? 'pi-sun' : 'pi-moon']"></i>
    </button>

    <!-- Notifications placeholder -->
    <OverlayBadge value="0" severity="info">
      <i
        class="pi pi-bell app-topbar__action"
        style="font-size: 1.25rem"
        aria-label="Notificações"
      ></i>
    </OverlayBadge>

    <!-- User avatar menu (keyboard-accessible button wrapper) -->
    <button
      class="app-topbar__avatar-wrapper p-link"
      @click="toggleUserMenu"
      @keydown.enter="toggleUserMenu"
      aria-haspopup="true"
      :aria-label="auth.user?.name || 'Menu do usuário'"
    >
      <Avatar
        :image="userAvatar"
        :label="userInitials"
        shape="circle"
      />
    </button>
    <Menu ref="userMenuRef" :model="userMenuItems" :popup="true" />
  </header>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import Avatar from 'primevue/avatar'
import Menu from 'primevue/menu'
import OverlayBadge from 'primevue/overlaybadge'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'

defineProps<{
  collapsed: boolean
}>()

defineEmits<{
  'toggle-sidebar': []
}>()

const auth = useAuthStore()
const router = useRouter()
const theme = useTheme()
const userMenuRef = ref<InstanceType<typeof Menu> | null>(null)

const userAvatar = computed(() => auth.user?.avatar_path ?? undefined)
const userInitials = computed(() => auth.user?.name?.charAt(0).toUpperCase() ?? '?')

const userMenuItems = computed(() => [
  {
    label: auth.user?.name || 'Usuário',
    items: [
      {
        label: 'Meu Perfil',
        icon: 'pi pi-user',
        command: () => router.push('/profile'),
      },
      { separator: true },
      {
        label: 'Sair',
        icon: 'pi pi-sign-out',
        command: () => auth.logout(),
      },
    ],
  },
])

function toggleUserMenu(event: Event) {
  userMenuRef.value?.toggle(event)
}
</script>

<style scoped>
.app-topbar__avatar {
  cursor: pointer;
  pointer-events: none;
}

.app-topbar__avatar-wrapper {
  display: flex;
  align-items: center;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  border-radius: 50%;
}

.app-topbar__avatar-wrapper:focus-visible {
  outline: 2px solid var(--app-accent);
  outline-offset: 2px;
}
</style>
