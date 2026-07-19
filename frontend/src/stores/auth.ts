import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/services/api'
import router from '@/router'

export interface User {
  id: string
  name: string
  email: string
  email_verified_at: string | null
  is_active: boolean
  roles: Array<{
    id: string
    name: string
    slug: string
    pivot: { role_id: string }
    permissions: Array<{ slug: string }>
  }>
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => user.value !== null)

  const isVerified = computed(() => user.value?.email_verified_at !== null)

  function hasRole(role: string): boolean {
    return user.value?.roles?.some(r => r.slug === role) ?? false
  }

  function hasPermission(perm: string): boolean {
    return user.value?.roles?.some(r =>
      r.permissions?.some(p => p.slug === perm)
    ) ?? false
  }

  function setUser(newUser: User | null) {
    user.value = newUser
  }

  function clearError() {
    error.value = null
  }

  async function login(credentials: { email: string; password: string; remember?: boolean }) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/auth/login', credentials)
      setUser(response.data.user)
      return response.data
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Erro ao fazer login'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function register(data: { name: string; email: string; password: string; password_confirmation: string }) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/auth/register', data)
      return response.data
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Erro ao registrar'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function logout(allDevices = false) {
    loading.value = true
    try {
      await api.post('/auth/logout', allDevices ? { current_password: '' } : {})
    } catch {
      //
    } finally {
      setUser(null)
      loading.value = false
      router.push('/login')
    }
  }

  async function fetchUser() {
    try {
      const response = await api.get('/auth/user')
      setUser(response.data)
    } catch {
      setUser(null)
    }
  }

  async function checkAuth(): Promise<boolean> {
    if (user.value) return true
    try {
      await fetchUser()
      return isAuthenticated.value
    } catch {
      setUser(null)
      return false
    }
  }

  async function verifyEmail(id: string, hash: string) {
    const response = await api.post(`/auth/verify-email/${id}/${hash}`)
    await fetchUser()
    return response.data
  }

  async function resendVerification() {
    const response = await api.post('/auth/email/verification-notification')
    return response.data
  }

  async function forgotPassword(email: string) {
    const response = await api.post('/auth/forgot-password', { email })
    return response.data
  }

  async function resetPassword(token: string, email: string, password: string, passwordConfirmation: string) {
    const response = await api.post('/auth/reset-password', { token, email, password, password_confirmation: passwordConfirmation })
    return response.data
  }

  return {
    user,
    loading,
    error,
    isAuthenticated,
    isVerified,
    hasRole,
    hasPermission,
    setUser,
    clearError,
    login,
    register,
    logout,
    fetchUser,
    checkAuth,
    verifyEmail,
    resendVerification,
    forgotPassword,
    resetPassword,
  }
})
