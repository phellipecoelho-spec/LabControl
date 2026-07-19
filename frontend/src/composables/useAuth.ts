import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function useAuth() {
  const store = useAuthStore()

  return {
    user: computed(() => store.user),
    isAuthenticated: computed(() => store.isAuthenticated),
    isVerified: computed(() => store.isVerified),
    loading: computed(() => store.loading),
    error: computed(() => store.error),
    login: store.login,
    register: store.register,
    logout: store.logout,
    fetchUser: store.fetchUser,
    checkAuth: store.checkAuth,
    verifyEmail: store.verifyEmail,
    resendVerification: store.resendVerification,
    forgotPassword: store.forgotPassword,
    resetPassword: store.resetPassword,
    hasRole: store.hasRole,
    hasPermission: store.hasPermission,
  }
}
