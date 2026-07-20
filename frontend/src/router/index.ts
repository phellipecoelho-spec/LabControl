import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { routes } from './routes'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach(async (to, _from) => {
  const auth = useAuthStore()

  if (!auth.loading && !auth.isAuthenticated) {
    await auth.checkAuth()
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresVerified && auth.isAuthenticated && !auth.isVerified) {
    return { name: 'verify-email.pending', query: { redirect: to.fullPath } }
  }

  if (to.meta.roles && !to.meta.roles.some((r: string) => auth.hasRole(r))) {
    return { name: 'unauthorized' }
  }
})

export default router
