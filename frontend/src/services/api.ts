import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

function getCookie(name: string): string | null {
  const match = document.cookie.match(new RegExp(`(^| )${name}=([^;]+)`))
  return match ? decodeURIComponent(match[2]) : null
}

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = getCookie('XSRF-TOKEN')
  if (token) {
    config.headers['X-XSRF-TOKEN'] = token
  }
  return config
})

api.interceptors.response.use(
  response => response,
  (error) => {
    if (error.response?.status === 401) {
      const auth = useAuthStore()
      auth.setUser(null)
    }
    if (error.response?.status === 403 && error.response.data?.message?.includes('verificado')) {
      const auth = useAuthStore()
      auth.setUser(null)
    }
    return Promise.reject(error)
  },
)
