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

// ---------------------------------------------------------------------------
// Helper — extract entity type and ID from an API URL path
// ---------------------------------------------------------------------------

function parseApiUrl(url: string): { entityType: string; entityId: string | null } | null {
  // Match /api/v1/{entity-type}/{id?}
  // e.g. /api/v1/equipment/abc-123 → { entityType: 'equipment', entityId: 'abc-123' }
  // e.g. /api/v1/equipment → { entityType: 'equipment', entityId: null }
  const match = url.match(/\/api\/v1\/([a-z-]+?)(?:\/([a-f0-9-]+))?(?:\/|$)/)
  if (!match) return null
  return { entityType: match[1], entityId: match[2] || null }
}

// ---------------------------------------------------------------------------
// Sanitise method string
// ---------------------------------------------------------------------------

type HttpMethod = 'POST' | 'PUT' | 'PATCH' | 'DELETE'

function isMutatingMethod(method: string): method is HttpMethod {
  return ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method.toUpperCase())
}

// ---------------------------------------------------------------------------
// Offline-aware interceptor — must be registered first so it runs first
// ---------------------------------------------------------------------------

api.interceptors.request.use(
  async (config) => {
    // Only intercept mutating requests when offline
    const method = (config.method ?? 'GET').toUpperCase()
    if (!isMutatingMethod(method)) return config
    if (navigator.onLine) return config
    if (config.headers && (config.headers as Record<string, unknown>)['X-Sync-Engine']) return config

    // We are offline and this is a mutating request — queue it
    const parsed = parseApiUrl(config.url ?? '')
    if (!parsed) return config // can't determine entity — let it fail naturally

    let entityId = parsed.entityId
    const action = method === 'POST' ? 'create' as const
      : method === 'DELETE' ? 'delete' as const
      : 'update' as const

    // Generate a temporary UUID for new records
    if (!entityId) {
      entityId = crypto.randomUUID()
    }

    let payload: Record<string, unknown> = {}
    if (config.data) {
      try {
        payload = typeof config.data === 'string' ? JSON.parse(config.data) : config.data
      } catch {
        payload = { _raw: config.data as string }
      }
    }

    // Lazy import to avoid circular deps with Pinia auth store
    const { enqueueOperation } = await import('@/db/syncQueue')
    await enqueueOperation({
      entityType: parsed.entityType,
      entityId,
      action,
      endpoint: config.url ?? '',
      method: method as HttpMethod,
      payload,
    })

    // Short-circuit the HTTP request by overriding the adapter
    // This works reliably in Axios v1.x to return mock data
    config.adapter = () => {
      return Promise.resolve({
        data: {
          data: {
            id: entityId,
            ...payload,
            _offline: true,
            _pending: true,
          },
        },
        status: 201,
        statusText: 'Accepted (offline)',
        headers: {},
        config,
      })
    }

    return config
  },
)

// ---------------------------------------------------------------------------
// Sanctum SPA CSRF token interceptor
// ---------------------------------------------------------------------------

api.interceptors.request.use((config) => {
  const token = getCookie('XSRF-TOKEN')
  if (token) {
    config.headers['X-XSRF-TOKEN'] = token
  }
  return config
})

// ---------------------------------------------------------------------------
// Response interceptors
// ---------------------------------------------------------------------------

api.interceptors.response.use(
  (response) => {
    // On 409 Conflict, stash conflict info for later resolution
    if (response.status === 409 && response.data?.conflict) {
      import('@/db/index').then(({ db }) => {
        db.conflicts.add({
          entityType: response.data.conflict.entity_type ?? '',
          entityId: response.data.conflict.entity_id ?? '',
          localVersion: response.data.conflict.local ?? {},
          serverVersion: response.data.conflict.server ?? {},
          baseVersion: response.data.conflict.base ?? {},
          detectedAt: new Date().toISOString(),
          status: 'pending',
        })
      }).catch(() => {
        // Silently fail — conflict storage is best-effort
      })
    }
    return response
  },
  (error) => {
    if (error.response?.status === 401) {
      const auth = useAuthStore()
      auth.setUser(null)
    }
    if (error.response?.status === 403 && error.response.data?.message?.includes('verificado')) {
      const auth = useAuthStore()
      auth.setUser(null)
    }

    // On 409 Conflict in error path too
    if (error.response?.status === 409 && error.response.data?.conflict) {
      import('@/db/index').then(({ db }) => {
        db.conflicts.add({
          entityType: error.response.data.conflict.entity_type ?? '',
          entityId: error.response.data.conflict.entity_id ?? '',
          localVersion: error.response.data.conflict.local ?? {},
          serverVersion: error.response.data.conflict.server ?? {},
          baseVersion: error.response.data.conflict.base ?? {},
          detectedAt: new Date().toISOString(),
          status: 'pending',
        })
      }).catch(() => {
        // Silently fail — conflict storage is best-effort
      })
    }

    return Promise.reject(error)
  },
)
