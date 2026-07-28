import { ref, onMounted, onUnmounted } from 'vue'

export type OnlineEvent = 'online' | 'offline' | 'visibility-change'

export function useOnline() {
  const isOnline = ref(navigator.onLine)
  const listeners: Record<OnlineEvent, Array<() => void>> = {
    online: [],
    offline: [],
    'visibility-change': [],
  }

  function on(event: OnlineEvent, callback: () => void) {
    listeners[event].push(callback)
  }

  function handleOnline() {
    isOnline.value = true
    listeners.online.forEach((cb) => cb())
  }

  function handleOffline() {
    isOnline.value = false
    listeners.offline.forEach((cb) => cb())
  }

  // Fallback for Safari/Firefox (no Background Sync API support)
  function handleVisibilityChange() {
    if (document.visibilityState === 'visible' && navigator.onLine) {
      listeners['visibility-change'].forEach((cb) => cb())
    }
  }

  let periodicTimer: ReturnType<typeof setInterval> | null = null

  onMounted(() => {
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)
    document.addEventListener('visibilitychange', handleVisibilityChange)

    // Periodic connectivity check every 5 minutes as a fallback
    periodicTimer = setInterval(() => {
      if (navigator.onLine) {
        listeners['visibility-change'].forEach((cb) => cb())
      }
    }, 5 * 60 * 1000)
  })

  onUnmounted(() => {
    window.removeEventListener('online', handleOnline)
    window.removeEventListener('offline', handleOffline)
    document.removeEventListener('visibilitychange', handleVisibilityChange)
    if (periodicTimer) clearInterval(periodicTimer)
  })

  return { isOnline, on }
}
