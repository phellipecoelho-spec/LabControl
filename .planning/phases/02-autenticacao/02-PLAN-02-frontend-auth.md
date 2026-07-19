---
phase: 02-autenticacao
plan: 02
type: execute
wave: 2
depends_on:
  - 01
files_modified:
  - frontend/src/stores/auth.ts
  - frontend/src/composables/useAuth.ts
  - frontend/src/router/index.ts
  - frontend/src/views/auth/LoginView.vue
  - frontend/src/views/auth/RegisterView.vue
  - frontend/src/views/auth/ForgotPasswordView.vue
  - frontend/src/views/auth/ResetPasswordView.vue
  - frontend/src/views/auth/VerifyEmailView.vue
  - frontend/src/components/auth/AuthForm.vue
  - frontend/src/components/auth/PasswordInput.vue
autonomous: false
requirements:
  - AUTH-01
  - AUTH-02
  - AUTH-03
  - AUTH-04
user_setup: []

must_haves:
  truths:
    - "Pinia store useAuthStore com state: user, isAuthenticated, loading"
    - "Actions: login(credentials), register(data), logout(), fetchUser(), checkAuth()"
    - "Getters: hasRole(role), hasPermission(perm), isVerified"
    - "LoginView.vue: formulário email/senha + checkbox 'Lembrar-me', validação client-side"
    - "RegisterView.vue: formulário nome/email/senha/confirmação, validação, redirect para /verify-email"
    - "ForgotPasswordView.vue: formulário email, envia reset, feedback genérico"
    - "ResetPasswordView.vue: formulário token/senha/confirmação (token vem da URL query)"
    - "VerifyEmailView.vue: exibe status 'Verificando...', redireciona para login ou dashboard"
    - "Router guards: meta.requiresAuth, meta.requiresVerified, meta.roles"
    - "Axios interceptor: adiciona X-XSRF-TOKEN do cookie, trata 401/403"
    - "PrimeVue components: InputText, Password, Checkbox, Button, Toast (mensagens)"
  artifacts:
    - frontend/src/stores/auth.ts
    - frontend/src/composables/useAuth.ts
    - frontend/src/router/index.ts (guards atualizados)
    - frontend/src/views/auth/*.vue (5 views)
    - frontend/src/components/auth/AuthForm.vue (componente base)
    - frontend/src/components/auth/PasswordInput.vue (com toggle visibility)
  key_links:
    - "useAuthStore → axios interceptor: anexa X-XSRF-TOKEN automaticamente via cookie"
    - "LoginView → remember: passa 'remember: true' para backend gerar remember_token"
    - "Router guards → meta.requiresAuth: redireciona para /login com redirect query"
    - "Toast messages → PrimeVue ToastService: feedback visual unificado"
---

<objective>
Implementar frontend de autenticação completo (Vue 3 + Pinia + PrimeVue) consumindo a API do Plano 01.

**Purpose:** Criar todas as telas e lógica de estado para login, registro, verificação de email, recuperação de senha e proteção de rotas no frontend Vue 3.

**Output:**
- Pinia store `useAuthStore` com estado reativo e actions assíncronas
- Composable `useAuth` para uso em componentes
- 5 views de autenticação (Login, Register, Forgot, Reset, Verify)
- Router guards para proteção de rotas (auth, verified, roles)
- Componentes reutilizáveis de formulário
- Integração com PrimeVue (Toast, InputText, Password, Button, Checkbox)
</objective>

<execution_context>
@.planning/workflows/execute-plan.md
@.planning/templates/summary.md
</execution_context>

<context>
@.planning/PROJECT.md
@.planning/REQUIREMENTS.md
@.planning/STATE.md
@.planning/phases/02-autenticacao/02-CONTEXT.md
@frontend/src/stores/auth.ts
@frontend/src/router/index.ts
@frontend/src/views/auth/
@frontend/src/components/auth/
@frontend/src/services/api.ts
</context>

<tasks>

<task type="auto">
  <name>Task 1: Criar Pinia store useAuthStore</name>
  <files>
    frontend/src/stores/auth.ts
  </files>
  <action>
    Criar frontend/src/stores/auth.ts com defineStore('auth'):

    **State:**
    - user: User | null = null
    - isAuthenticated: boolean = false
    - loading: boolean = false
    - error: string | null = null

    **Getters:**
    - hasRole(role: string): boolean — verifica se user tem role (via roles array)
    - hasPermission(perm: string): boolean — verifica permission via roles.permissions
    - isVerified: boolean — user?.email_verified_at !== null

    **Actions:**
    - async login(credentials: { email, password, remember? }): Promise<void>
      - Chama api.post('/auth/login', credentials)
      - Em sucesso: setUser(response.user), isAuthenticated = true
      - Em erro: throw error com mensagem amigável

    - async register(data: { name, email, password, password_confirmation }): Promise<void>
      - Chama api.post('/auth/register', data)
      - Não loga automaticamente (precisa verificar email)
      - Retorna sucesso para redirecionar para /verify-email

    - async logout(allDevices = false): Promise<void>
      - Chama api.post('/auth/logout', { current_password: allDevices ? ... : undefined })
      - Limpa state: user = null, isAuthenticated = false
      - Redirect para /login

    - async fetchUser(): Promise<void>
      - Chama api.get('/auth/user')
      - setUser(response)

    - async checkAuth(): Promise<boolean>
      - Se já has user, retorna true
      - Tenta fetchUser(), catch → logout silencioso, retorna false

    - setUser(user: User | null): void
      - Atualiza user, isAuthenticated = !!user

    - clearError(): void

    **Persistência:** Usar pinia-plugin-persistedstate para manter user no localStorage (apenas dados não sensíveis).
  </action>
  <verify>
    <automated>test -f frontend/src/stores/auth.ts && echo "OK"</automated>
  </verify>
  <done>Pinia store auth.ts criada com state, getters, actions completas e persistência.</done>
</task>

<task type="auto">
  <name>Task 2: Criar composable useAuth e configurar axios interceptor</name>
  <files>
    frontend/src/composables/useAuth.ts
    frontend/src/services/api.ts
  </files>
  <action>
    **1. frontend/src/composables/useAuth.ts:**
    ```ts
    export const useAuth = () => {
      const store = useAuthStore()
      return {
        user: computed(() => store.user),
        isAuthenticated: computed(() => store.isAuthenticated),
        loading: computed(() => store.loading),
        error: computed(() => store.error),
        login: store.login,
        register: store.register,
        logout: store.logout,
        fetchUser: store.fetchUser,
        checkAuth: store.checkAuth,
        hasRole: store.hasRole,
        hasPermission: store.hasPermission,
        isVerified: computed(() => store.isVerified)
      }
    }
    ```

    **2. frontend/src/services/api.ts (axios instance):**
    - baseURL: import.meta.env.VITE_API_URL || '/api/v1'
    - withCredentials: true (essencial para cookies Sanctum)
    - Interceptor request:
      - Adiciona X-XSRF-TOKEN do cookie `XSRF-TOKEN` (Laravel envia automaticamente)
      - `config.headers['X-XSRF-TOKEN'] = getCookie('XSRF-TOKEN')`
    - Interceptor response:
      - 401: store.logout(), redirect /login?expired=1
      - 403: router.push({ name: 'unauthorized' }) ou toast
      - 422: retorna validation errors para formulários
    - Função getCookie(name) para ler cookies (document.cookie.parse)
  </action>
  <verify>
    <automated>test -f frontend/src/composables/useAuth.ts && test -f frontend/src/services/api.ts && echo "OK"</automated>
  </verify>
  <done>Composable useAuth e axios interceptor configurados com withCredentials e XSRF token.</done>
</task>

<task type="auto">
  <name>Task 3: Criar componentes base de formulário (AuthForm, PasswordInput)</name>
  <files>
    frontend/src/components/auth/AuthForm.vue
    frontend/src/components/auth/PasswordInput.vue
  </files>
  <action>
    **1. AuthForm.vue (componente base):**
    - Props: title, description, submitLabel, loading, errors (object)
    - Slots: default (campos), footer (links extras)
    - Emite: submit (data), cancel
    - Usa PrimeVue: Card, Button, Toast
    - Validação: mostra errors[field] abaixo de cada input

    **2. PasswordInput.vue:**
    - Props: modelValue, label, error, placeholder, required
    - State: showPassword (boolean)
    - Template: InputText (type=password/text) + Button icon (eye/eye-slash) @click.toggle="showPassword"
    - Emite update:modelValue
    - Acessibilidade: aria-label, autocomplete="current-password" ou "new-password"
  </action>
  <verify>
    <automated>test -f frontend/src/components/auth/AuthForm.vue && test -f frontend/src/components/auth/PasswordInput.vue && echo "OK"</automated>
  </verify>
  <done>Componentes base AuthForm e PasswordInput criados com PrimeVue.</done>
</task>

<task type="auto">
  <name>Task 4: Criar 5 views de autenticação</name>
  <files>
    frontend/src/views/auth/LoginView.vue
    frontend/src/views/auth/RegisterView.vue
    frontend/src/views/auth/ForgotPasswordView.vue
    frontend/src/views/auth/ResetPasswordView.vue
    frontend/src/views/auth/VerifyEmailView.vue
  </files>
  <action>
    **1. LoginView.vue:**
    - AuthForm com: email (InputText), password (PasswordInput), remember (Checkbox)
    - Submit → store.login({ email, password, remember })
    - Sucesso → router.push(redirect || '/dashboard')
    - Links: "Esqueci a senha" → /forgot-password, "Registrar" → /register
    - Toast: erro 422/401, sucesso

    **2. RegisterView.vue:**
    - AuthForm com: name, email, password (PasswordInput), password_confirmation (PasswordInput)
    - Submit → store.register(data)
    - Sucesso → router.push('/verify-email?registered=1')
    - Link: "Já tem conta? Login" → /login

    **3. ForgotPasswordView.vue:**
    - AuthForm simples: apenas email
    - Submit → api.post('/auth/forgot-password', { email })
    - Sempre toast "Se o email existir, enviaremos instruções" (não vazar existência)
    - Link: "Voltar ao login" → /login

    **4. ResetPasswordView.vue:**
    - Lê token e email da URL query (route.query.token, route.query.email)
    - AuthForm: password (PasswordInput), password_confirmation (PasswordInput)
    - Hidden inputs: token, email
    - Submit → api.post('/auth/reset-password', { token, email, password, password_confirmation })
    - Sucesso → toast "Senha redefinida com sucesso!", auto-login via store.login(email, password) → redirect dashboard

    **5. VerifyEmailView.vue:**
    - Lê id e hash da rota params (route.params.id, route.params.hash)
    - OnMounted: store.verifyEmail(id, hash) → api.get('/auth/verify-email/{id}/{hash}')
    - Estados: loading → success → redirect /dashboard | error → toast + link "Reenviar"
    - Botão "Reenviar email" → store.resendVerification()

    **6. VerifyEmailPendingView.vue (rota /verify-email):**
    - Tela informativa: "Verifique sua caixa de entrada..."
    - Botão "Reenviar email" → store.resendVerification() (throttle 5/min)
    - Link "Não recebi" → abre modal com verificação de spam

    **7. Rotas em router/index.ts:**
    ```ts
    { path: '/login', name: 'login', component: LoginView, meta: { guest: true, title: 'Login' } },
    { path: '/register', name: 'register', component: RegisterView, meta: { guest: true, title: 'Cadastro' } },
    { path: '/forgot-password', name: 'forgot-password', component: ForgotPasswordView, meta: { guest: true, title: 'Recuperar Senha' } },
    { path: '/reset-password', name: 'reset-password', component: ResetPasswordView, meta: { guest: true, title: 'Redefinir Senha' } },
    { path: '/verify-email/:id/:hash', name: 'verify-email', component: VerifyEmailView, meta: { guest: true } },
    { path: '/verify-email', name: 'verify-email.pending', component: VerifyEmailPendingView, meta: { guest: true } },
    ```
  </action>
  <verify>
    <automated>ls frontend/src/views/auth/*.vue | wc -l</automated>
  </verify>
  <done>5 views de autenticação criadas com validação, toasts e navegação.</done>
</task>

<task type="auto">
  <name>Task 5: Atualizar router guards e rotas de autenticação</name>
  <files>
    frontend/src/router/index.ts
    frontend/src/router/routes.ts
  </files>
  <action>
    **1. frontend/src/router/routes.ts — adicionar rotas de auth:**
    ```ts
    { path: '/login', name: 'login', component: () => import('@/views/auth/LoginView.vue'), meta: { guest: true, title: 'Login' } },
    { path: '/register', name: 'register', component: () => import('@/views/auth/RegisterView.vue'), meta: { guest: true, title: 'Cadastro' } },
    { path: '/forgot-password', name: 'forgot-password', component: () => import('@/views/auth/ForgotPasswordView.vue'), meta: { guest: true, title: 'Recuperar Senha' } },
    { path: '/reset-password', name: 'reset-password', component: () => import('@/views/auth/ResetPasswordView.vue'), meta: { guest: true, title: 'Redefinir Senha' } },
    { path: '/verify-email/:id/:hash', name: 'verify-email', component: () => import('@/views/auth/VerifyEmailView.vue'), meta: { guest: true } },
    { path: '/verify-email', name: 'verify-email.pending', component: () => import('@/views/auth/VerifyEmailPendingView.vue'), meta: { guest: true } },
    ```

    **2. frontend/src/router/index.ts — guards globais:**
    ```ts
    router.beforeEach(async (to, from, next) => {
      const auth = useAuthStore()

      // Aguarda checkAuth se primeira navegação
      if (!auth.loading && !auth.isAuthenticated) {
        await auth.checkAuth()
      }

      // Rotas só para guests (não autenticados)
      if (to.meta.guest && auth.isAuthenticated) {
        return next({ name: 'dashboard' })
      }

      // Rotas protegidas
      if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return next({ name: 'login', query: { redirect: to.fullPath } })
      }

      // Verificação de email obrigatória
      if (to.meta.requiresVerified && auth.isAuthenticated && !auth.isVerified) {
        return next({ name: 'verify-email.pending', query: { redirect: to.fullPath } })
      }

      // Roles permitidas
      if (to.meta.roles && !to.meta.roles.some((r: string) => auth.hasRole(r))) {
        return next({ name: 'unauthorized' })
      }

      next()
    })
    ```

    **3. Adicionar meta nas rotas existentes (dashboard, equipamentos, etc.):**
    - `meta: { requiresAuth: true, requiresVerified: true }`
    - `meta: { roles: ['admin', 'supervisor'] }` onde aplicável
  </action>
  <verify>
    <automated>test -f frontend/src/router/index.ts && grep -q "beforeEach" frontend/src/router/index.ts && echo "OK"</automated>
  </verify>
  <done>Router guards implementados: guest, requiresAuth, requiresVerified, roles.</done>
</task>

<task type="auto">
  <name>Task 6: Configurar Toast global e estilos de autenticação</name>
  <files>
    frontend/src/main.ts
    frontend/src/styles/auth.css
  </files>
  <action>
    **1. frontend/src/main.ts — ToastService:**
    ```ts
    import ToastService from 'primevue/toastservice'
    app.use(ToastService)
    // Em componentes: const toast = useToast(); toast.add({ severity: 'error', summary: 'Erro', detail: msg })
    ```

    **2. frontend/src/styles/auth.css — estilos específicos:**
    - Container centralizado max-w-md mx-auto
    - Card PrimeVue com sombra suave (tema escuro)
    - Inputs com largura total
    - Botão primário full-width no mobile
    - Links com cor do tema
    - Animação fade-in no mount

    **3. Importar em main.ts:** `import '@/styles/auth.css'`
  </action>
  <verify>
    <automated>grep -q "ToastService" frontend/src/main.ts && echo "OK"</automated>
  </verify>
  <done>ToastService configurado, estilos de auth aplicados globalmente.</done>
</task>

</tasks>

<threat_model>
## Trust Boundaries

| Boundary | Description |
|----------|-------------|
| Browser → Frontend | Usuário interage com formulários Vue, envia dados via axios |
| Frontend → Backend API | Requisições HTTP com cookies HttpOnly (Sanctum) |

## STRIDE Threat Register

| Threat ID | Category | Component | Severity | Disposition | Mitigation Plan |
|-----------|----------|-----------|----------|-------------|-----------------|
| T-02-07 | Spoofing | Login form | medium | mitigate | Validação client + server, rate limit, CSRF token via cookie |
| T-02-08 | Tampering | Reset password form | medium | mitigate | Token validado no backend, expiração 60min |
| T-02-09 | Information Disclosure | Error messages | low | mitigate | Mensagens genéricas, não expor stack traces |
| T-02-10 | Elevation of Privilege | Router guards | low | mitigate | Guards no router + validação no backend (defesa em profundidade) |
| T-02-11 | Denial of Service | Form submissions | low | mitigate | Debounce no submit, loading state, desabilita botão |
</threat_model>

<verification>
1. `npm run dev` — frontend sobe sem erros
2. Acessar `/login` — formulário renderiza, validação funciona
3. Login com credenciais válidas → cookie session setado, redirect dashboard
4. Login inválido → toast erro, formulário limpa senha
5. Registro → email verificação enviado, redirect /verify-email
5. Acessar rota protegida sem login → redirect /login?redirect=...
6. Logout → limpa store, redirect /login
7. Remember me → cookie persistente 30 dias
8. Rate limit → 6 requests rápidas → 429
</verification>

<success_criteria>
- [ ] Pinia store useAuthStore com login, register, logout, fetchUser, checkAuth
- [ ] Composable useAuth expõe estado e actions reativas
- [ ] Axios interceptor com withCredentials, XSRF token, 401/403 handling
- [ ] 5 views: Login, Register, ForgotPassword, ResetPassword, VerifyEmail
- [ ] Componentes AuthForm, PasswordInput reutilizáveis
- [ ] Router guards: guest, requiresAuth, requiresVerified, roles
- [ ] PrimeVue Toast configurado para feedback visual
- [ ] Integração completa com backend API (Plano 01)
</success_criteria>

<output>
Criar `.planning/phases/02-autenticacao/02-PLAN-02-SUMMARY.md` quando concluído
</output>