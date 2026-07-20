import { ref, readonly, watch } from 'vue'

const STORAGE_KEY = 'app-theme'

export function useTheme() {
  const stored = localStorage.getItem(STORAGE_KEY)
  const isDark = ref(stored !== 'light')

  function applyTheme() {
    const root = document.documentElement
    root.classList.toggle('app-dark', isDark.value)
  }

  // Aplica tema imediatamente na inicialização
  applyTheme()

  function toggle() {
    isDark.value = !isDark.value
  }

  watch(isDark, (val) => {
    localStorage.setItem(STORAGE_KEY, val ? 'dark' : 'light')
    applyTheme()
  }, { immediate: true })

  return {
    isDark: readonly(isDark),
    toggle,
  }
}
