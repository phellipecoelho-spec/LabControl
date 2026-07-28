import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import Tooltip from 'primevue/tooltip'
import Aura from '@primeuix/themes/aura'
import router from './router'
import App from './App.vue'

import 'primeicons/primeicons.css'
import './styles/global.css'
import './styles/auth.css'
import './styles/layout.css'

// Aplica tema ANTES de inicializar PrimeVue
const stored = localStorage.getItem('app-theme')
const isDark = stored !== 'light'
document.documentElement.classList.toggle('app-dark', isDark)

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      darkModeSelector: '.app-dark',
    },
  },
})
app.use(ToastService)
app.use(ConfirmationService)
app.directive('tooltip', Tooltip)

app.mount('#app')

// Register service worker after mount (no component setup context needed)
if (import.meta.env.PROD) {
  import('virtual:pwa-register').then(({ registerSW }) => {
    registerSW({
      onOfflineReady() {
        console.log('[PWA] App ready for offline use')
      },
    })
  }).catch(() => {
    // virtual:pwa-register not available in dev mode — ignore
  })
}
