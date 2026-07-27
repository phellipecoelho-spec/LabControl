---
phase: 02-autenticacao
plan: 03
type: execute
wave: 2
depends_on:
  - 01
  - 02
files_modified:
  - backend/app/Models/User.php
  - backend/app/Providers/RouteServiceProvider.php
  - backend/resources/views/emails/verify-email.blade.php
  - backend/resources/views/emails/reset-password.blade.php
  - frontend/src/views/auth/VerifyEmailView.vue
  - frontend/src/views/auth/ResetPasswordView.vue
  - frontend/src/router/guards.ts
  - frontend/src/stores/auth.ts
  - frontend/src/composables/useAuth.ts
autonomous: false
requirements:
  - AUTH-02
  - AUTH-03
user_setup: []

must_haves:
  truths:
    - "Email de verificação: link assinado expira em 60 min, marca email_verified_at"
    - "Reenviar verificação: botão na tela /verify-email, throttle 5/min"
    - "Password reset: link expira 60 min, invalida remember_token após uso"
    - "Frontend: /verify-email/{id}/{hash} valida e redireciona para dashboard"
    - "Frontend: /reset-password?token=...&email=... formulário nova senha"
    - "Guard requiresVerified bloqueia rotas se email não verificado"
    - "Toast notifications para sucesso/erro em cada fluxo"
  artifacts:
    - backend/resources/views/emails/verify-email.blade.php (Markdown escuro)
    - backend/resources/views/emails/reset-password.blade.php (Markdown escuro)
    - frontend/src/views/auth/VerifyEmailView.vue
    - frontend/src/views/auth/ResetPasswordView.vue
    - frontend/src/router/guards.ts (requiresVerified)
    - frontend/src/stores/auth.ts (actions verifyEmail, resendVerification, forgotPassword, resetPassword)
  key_links:
    - "VerifyEmailView → store.verifyEmail(id, hash) → API GET /api/v1/auth/verify-email/{id}/{hash}"
    - "ResetPasswordView → store.resetPassword(token, email, password) → API POST /api/v1/auth/reset-password"
    - "requiresVerified guard → redireciona para /verify-email se email_verified_at null"
    - "Emails Markdown → tema escuro, botão PrimeVue estilo, link com FRONTEND_URL"
---

<objective>
Integrar fluxos completos de verificação de email e recuperação de senha (Backend + Frontend).

**Purpose:** Conectar os endpoints do Plano 01 com as views do Plano 02, implementando a UX completa: usuário registra → recebe email → clica link → email verificado → pode acessar sistema. E: esqueceu senha → recebe link → define nova senha → login automático.

**Output:**
- Email templates Markdown (verify-email, reset-password) com tema escuro
- Frontend views: VerifyEmailView, ResetPasswordView
- Router guard requiresVerified
- Pinia actions: verifyEmail, resendVerification, forgotPassword, resetPassword
- Integração ponta-a-ponta testada
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
@backend/app/Models/User.php
@backend/app/Providers/RouteServiceProvider.php
@backend/resources/views/emails/verify-email.blade.php
@backend/resources/views/emails/reset-password.blade.php
@frontend/src/views/auth/VerifyEmailView.vue
@frontend/src/views/auth/ResetPasswordView.vue
@frontend/src/router/guards.ts
@frontend/src/stores/auth.ts
@frontend/src/composables/useAuth.ts
</context>

<tasks>

<task type="auto">
  <name>Task 1: Implementar User::mustVerifyEmail e configuração de verificação</name>
  <files>
    backend/app/Models/User.php
    backend/app/Providers/RouteServiceProvider.php
  </files>
  <action>
    **1. backend/app/Models/User.php:**
    - Adicionar `implements MustVerifyEmail` na classe
    - Import: `use Illuminate\Contracts\Auth\MustVerifyEmail;`
    - O trait `HasApiTokens` já está presente (Sanctum)

    **2. backend/app/Providers/RouteServiceProvider.php:**
    - No método `boot()`, definir:
      ```php
      public static string $home = '/dashboard'; // ou '/'
      public static string $verificationUrl = '/verify-email/{id}/{hash}';
      ```
    - Isso faz o Laravel gerar links corretos para `FRONTEND_URL/verify-email/{id}/{hash}`

    **3. Verificar configuração de email em .env:**
    - MAIL_MAILER=log (desenvolvimento) ou smtp
    - MAIL_FROM_ADDRESS e MAIL_FROM_NAME
    - FRONTEND_URL=http://localhost:5173

    **4. Testar geração de URL:**
    ```bash
    docker compose exec php php artisan tinker --execute="
    \$u = App\Models\User::first();
    echo \$u->verificationUrl();
    "
    ```
    Deve retornar `http://localhost:5173/verify-email/{id}/{hash}`
  </action>
  <verify>
    <automated>docker compose exec -T php php artisan tinker --execute="\$u = App\Models\User::first(); echo \$u->verificationUrl();" 2>&1</automated>
  </verify>
  <done>User implementa MustVerifyEmail, RouteServiceProvider gera URLs corretas para frontend.</done>
</task>

<task type="auto">
  <name>Task 2: Criar templates de email Markdown (tema escuro)</name>
  <files>
    backend/resources/views/emails/verify-email.blade.php
    backend/resources/views/emails/reset-password.blade.php
  </files>
  <action>
    **1. verify-email.blade.php:**
    ```blade
    @component('mail::layout')
    @slot('subject')
    Verifique seu email - {{ config('app.name') }}
    @endslot

    @component('mail::message')
    # Bem-vindo ao {{ config('app.name') }}!

    Obrigado por se cadastrar. Clique no botão abaixo para verificar seu endereço de email:

    @component('mail::button', ['url' => $verificationUrl, 'color' => '#10b981'])
    Verificar Email
    @endcomponent

    Se o botão não funcionar, copie e cole este link no navegador:
    {{ $verificationUrl }}

    **Este link expira em 60 minutos.**

    Se você não criou esta conta, ignore este email.

    @component('mail::subcopy')
    © {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
    @endcomponent
    @endcomponent
    @endcomponent
    ```

    **2. reset-password.blade.php:**
    ```blade
    @component('mail::layout')
    @slot('subject')
    Redefinição de senha - {{ config('app.name') }}
    @endslot

    @component('mail::message')
    # Redefinição de Senha

    Você solicitou a redefinição de sua senha. Clique no botão abaixo para criar uma nova:

    @component('mail::button', ['url' => $resetUrl, 'color' => '#f59e0b'])
    Redefinir Senha
    @endcomponent

    Se o botão não funcionar, copie e cole:
    {{ $resetUrl }}

    **Este link expira em 60 minutos e só pode ser usado uma vez.**

    Se você não solicitou isso, ignore este email. Sua senha não será alterada.

    @component('mail::subcopy')
    © {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
    @endcomponent
    @endcomponent
    @endcomponent
    ```

    **3. Publicar e customizar layouts se necessário:**
    - `docker compose exec php php artisan vendor:publish --tag=laravel-mail` (opcional)
  </action>
  <verify>
    <automated>ls -la backend/resources/views/emails/ 2>&1</automated>
  </verify>
  <done>Templates verify-email.blade.php e reset-password.blade.php criados com tema escuro e botões estilizados.</done>
</task>

<task type="auto">
  <name>Task 3: Criar VerifyEmailView no Frontend</name>
  <files>
    frontend/src/views/auth/VerifyEmailView.vue
    frontend/src/views/auth/VerifyEmailPendingView.vue
  </files>
  <action>
    **1. VerifyEmailView.vue (rota /verify-email/:id/:hash):**
    - Rota com params `id` e `hash`
    - OnMounted: chama `authStore.verifyEmail(id, hash)`
    - Loading state com spinner PrimeVue
    - Sucesso: toast "Email verificado com sucesso!", redirect to `/dashboard`
    - Erro: toast com mensagem (link expirado, inválido, já verificado)
    - Link "Reenviar email" → chama `authStore.resendVerification()`

    **2. VerifyEmailPendingView.vue (rota /verify-email):**
    - Tela informativa: "Verifique sua caixa de entrada..."
    - Botão "Reenviar email" → `authStore.resendVerification()` (throttle 5/min)
    - Link "Não recebi" → abre modal com verificação de spam

    **3. Componentes reutilizáveis:**
    - `AuthCard` wrapper com título, descrição, slot para formulário
    - `AuthButton` com loading state
    - `AuthAlert` para mensagens de erro/sucesso

    **4. Rotas em router/index.ts:**
    ```ts
    { path: '/verify-email/:id/:hash', name: 'verify-email', component: VerifyEmailView, meta: { guest: true } },
    { path: '/verify-email', name: 'verify-email.pending', component: VerifyEmailPendingView, meta: { guest: true } },
    ```
  </action>
  <verify>
    <automated>ls -la frontend/src/views/auth/ 2>&1</automated>
  </verify>
  <done>VerifyEmailView e VerifyEmailPendingView criadas com fluxo completo de verificação.</done>
</task>

<task type="auto">
  <name>Task 4: Criar ResetPasswordView no Frontend</name>
  <files>
    frontend/src/views/auth/ForgotPasswordView.vue
    frontend/src/views/auth/ResetPasswordView.vue
  </files>
  <action>
    **1. ForgotPasswordView.vue (rota /forgot-password):**
    - Formulário: email (required, email)
    - Submit: `authStore.forgotPassword(email)`
    - Sucesso: toast "Se o email existir, enviaremos instruções", redirect to `/forgot-password/sent`
    - Erro: toast genérico (não vazar existência)

    **2. ForgotPasswordSentView.vue (rota /forgot-password/sent):**
    - Tela: "Verifique seu email para redefinir a senha"
    - Link "Não recebeu? Reenviar" → `authStore.forgotPassword(email)` (throttle)

    **3. ResetPasswordView.vue (rota /reset-password?token=...&email=...):**
    - Recebe query params `token` e `email`
    - Formulário: password (PasswordInput), password_confirmation (PasswordInput)
    - Hidden inputs: token, email
    - Validação: min 8 chars, confirmação igual
    - Submit: `authStore.resetPassword(token, email, password, password_confirmation)`
    - Sucesso: toast "Senha redefinida com sucesso!", auto-login via `authStore.login(email, password)` → redirect dashboard
    - Erro: toast "Token inválido ou expirado", link para `/forgot-password`

    **4. Rotas:**
    ```ts
    { path: '/forgot-password', name: 'forgot-password', component: ForgotPasswordView, meta: { guest: true } },
    { path: '/forgot-password/sent', name: 'forgot-password.sent', component: ForgotPasswordSentView, meta: { guest: true } },
    { path: '/reset-password', name: 'reset-password', component: ResetPasswordView, meta: { guest: true } },
    ```
  </action>
  <verify>
    <automated>ls -la frontend/src/views/auth/ 2>&1</automated>
  </verify>
  <done>ForgotPasswordView, ForgotPasswordSentView, ResetPasswordView criadas com fluxo completo.</done>
</task>

<task type="auto">
  <name>Task 5: Implementar guards requiresVerified e integração Pinia</name>
  <files>
    frontend/src/router/guards.ts
    frontend/src/stores/auth.ts
    frontend/src/composables/useAuth.ts
  </files>
  <action>
    **1. router/guards.ts — adicionar requiresVerified:**
    ```ts
    export const requiresVerified = (to: RouteLocationNormalized) => {
      const authStore = useAuthStore()
      if (authStore.isAuthenticated && !authStore.user?.email_verified_at) {
        return { name: 'verify-email.pending', query: { redirect: to.fullPath } }
      }
      return true
    }
    ```
    - Aplicar em rotas que exigem email verificado (meta.requiresVerified: true)

    **2. stores/auth.ts — adicionar actions:**
    ```ts
    async verifyEmail(id: string, hash: string) {
      await api.get('/auth/verify-email/${id}/${hash}')
      await this.fetchUser()
    },
    async resendVerification() {
      await api.post('/auth/email/verification-notification')
    },
    async forgotPassword(email: string) {
      await api.post('/auth/forgot-password', { email })
    },
    async resetPassword(token: string, email: string, password: string, password_confirmation: string) {
      await api.post('/auth/reset-password', { token, email, password, password_confirmation })
      // Auto-login após reset
      await this.login(email, password)
    },
    ```

    **3. composables/useAuth.ts — expor actions:**
    - Já expõe store, garantir que `verifyEmail`, `resendVerification`, `forgotPassword`, `resetPassword` estão disponíveis

    **4. Atualizar axios interceptor para 403 (email não verificado):**
    ```ts
    if (error.response?.status === 403 && error.response.data.message?.includes('verificado')) {
      router.push({ name: 'verify-email.pending', query: { redirect: router.currentRoute.value.fullPath } })
    }
    ```

    **5. PrimeVue Toast global:**
    - Em `main.ts`: `app.use(ToastService)`
    - Em `App.vue`: `<Toast position="top-right" />`
    - Helper `useToast()` no composable
  </action>
  <verify>
    <automated>grep -r "requiresVerified" frontend/src/router/ 2>&1</automated>
  </verify>
  <done>Guards requiresVerified aplicado nas rotas protegidas, Pinia actions para verificação/reset, axios interceptor trata 403 email não verificado, PrimeVue Toast configurado globalmente.</done>
</task>

</tasks>

<threat_model>
## Trust Boundaries

| Boundary | Description |
|----------|-------------|
| Email → User | Links de verificação/reset enviados via SMTP |
| Frontend → Backend | Requisições com cookies HttpOnly + CSRF |

## STRIDE Threat Register

| Threat ID | Category | Component | Severity | Disposition | Mitigation Plan |
|-----------|----------|-----------|----------|-------------|-----------------|
| T-02-12 | Spoofing | Email verification link | low | mitigate | Link assinado, expira 60min, uso único |
| T-02-13 | Tampering | Reset password token | medium | mitigate | Token expira 60min, invalida remember_token |
| T-02-14 | Information Disclosure | Error messages | low | mitigate | Mensagens genéricas, não expor existência de email |
| T-02-15 | Elevation of Privilege | requiresVerified bypass | low | mitigate | Guard no router + validação no backend (middleware verified) |
</threat_model>

<verification>
1. Registro → email verificação enviado → link funciona → email_verified_at preenchido
2. Login sem verificação → redirect /verify-email
3. Reenviar verificação → throttle 5/min funciona
4. Esqueci senha → email reset enviado → link funciona
5. Reset senha → token expira 60min → invalida remember_token
6. Guard requiresVerified bloqueia rotas protegidas
7. Toast notifications aparecem em todos os fluxos
</verification>

<success_criteria>
- [ ] User implements MustVerifyEmail, URLs geradas corretas
- [ ] Templates verify-email.blade.php e reset-password.blade.php (tema escuro)
- [ ] VerifyEmailView, VerifyEmailPendingView funcionais
- [ ] ForgotPasswordView, ForgotPasswordSentView, ResetPasswordView funcionais
- [ ] Guard requiresVerified aplicado nas rotas protegidas
- [ ] Pinia actions: verifyEmail, resendVerification, forgotPassword, resetPassword
- [ ] Axios interceptor trata 403 email não verificado
- [ ] PrimeVue Toast configurado globalmente
- [ ] Fluxo ponta-a-ponta: registro → verificação → login → dashboard
</success_criteria>

<output>
Criar `.planning/phases/02-autenticacao/03-PLAN-03-SUMMARY.md` quando concluído
</output>