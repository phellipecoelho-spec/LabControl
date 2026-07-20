import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
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
app.directive('tooltip', Tooltip)

app.mount('#app')
