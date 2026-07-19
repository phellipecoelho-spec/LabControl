---
phase: 02-autenticacao
plan: 01
type: execute
wave: 1
depends_on: []
files_modified:
  - backend/routes/api.php
  - backend/app/Http/Controllers/Api/V1/AuthController.php
  - backend/app/Http/Requests/LoginRequest.php
  - backend/app/Http/Requests/RegisterRequest.php
  - backend/app/Http/Requests/VerifyEmailRequest.php
  - backend/app/Http/Requests/ForgotPasswordRequest.php
  - backend/app/Http/Requests/ResetPasswordRequest.php
  - backend/app/Http/Requests/LogoutRequest.php
  - backend/config/sanctum.php
  - backend/config/auth.php
autonomous: false
requirements:
  - AUTH-01
  - AUTH-02
  - AUTH-03
  - AUTH-04
user_setup: []

must_haves:
  truths:
    - "POST /api/v1/auth/login retorna token de sessão (cookie HttpOnly) e usuário"
    - "POST /api/v1/auth/register cria usuário, envia email de verificação, retorna 201"
    - "GET /api/v1/auth/verify-email/{id}/{hash} marca email_verified_at, redireciona para frontend"
    - "POST /api/v1/auth/forgot-password envia email com link de reset (expira 60 min)"
    - "POST /api/v1/auth/reset-password valida token, atualiza senha, invalida sessões"
    - "POST /api/v1/auth/logout invalida sessão atual (e remember token se solicitado)"
    - "GET /api/v1/auth/user retorna usuário autenticado (401 se não autenticado)"
    - "Rate limiting: 5 req/min em login, register, forgot-password, reset-password"
    - "Sanctum configurado para SPA (cookie HttpOnly, CSRF, CORS)"
  artifacts:
    - backend/app/Http/Controllers/Api/V1/AuthController.php
    - backend/app/Http/Requests/LoginRequest.php (email, password, remember)
    - backend/app/Http/Requests/RegisterRequest.php (name, email, password, password_confirmation)
    - backend/app/Http/Requests/VerifyEmailRequest.php
    - backend/app/Http/Requests/ForgotPasswordRequest.php (email)
    - backend/app/Http/Requests/ResetPasswordRequest.php (email, password, password_confirmation, token)
    - backend/app/Http/Requests/LogoutRequest.php
    - backend/config/sanctum.php (expiration, token_prefix)
    - backend/config/auth.php (guards, providers, passwords)
  key_links:
    - "AuthController → rotas /api/v1/auth/*: centraliza toda lógica de autenticação"
    - "LoginRequest → remember: gera remember_token (30 dias) + cookie persistente"
    - "RegisterRequest → MustVerifyEmail: usuário criado com email_verified_at=null"
    - "Sanctum → cookie HttpOnly + SameSite=Lax: proteção CSRF + XSS"
    - "RateLimiter::for('auth', ...) → throttle:5,1 nas rotas sensíveis"
---

<objective>
Implementar API de autenticação completa (Backend) usando Laravel Sanctum com session cookies para SPA.

**Purpose:** Entregar todos os endpoints de autenticação necessários para o frontend consumir: login, registro, verificação de email, recuperação de senha, logout e usuário atual. Baseia-se no Sanctum já instalado na Fase 1.

**Output:**
- AuthController com 7 actions: login, register, verifyEmail, resendVerification, forgotPassword, resetPassword, logout, user
- Form Requests para validação de cada endpoint
- Configuração Sanctum e Auth otimizada para SPA
- Rate limiting nas rotas sensíveis
- Rotas registradas em routes/api.php com prefixo /api/v1/auth
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
@backend/routes/api.php
@backend/app/Http/Controllers/Api/V1/AuthController.php
@backend/config/sanctum.php
@backend/config/auth.php
@backend/app/Models/User.php
</context>

<tasks>

<task type="auto">
  <name>Task 1: Criar Form Requests de validação</name>
  <files>
    backend/app/Http/Requests/LoginRequest.php
    backend/app/Http/Requests/RegisterRequest.php
    backend/app/Http/Requests/VerifyEmailRequest.php
    backend/app/Http/Requests/ForgotPasswordRequest.php
    backend/app/Http/Requests/ResetPasswordRequest.php
    backend/app/Http/Requests/LogoutRequest.php
  </files>
  <action>
    Criar os 6 Form Requests em backend/app/Http/Requests/:

    **LoginRequest.php:**
    - email (required, email, exists:users,email)
    - password (required, string, min:8)
    - remember (boolean, optional) — se true, gera remember_token de 30 dias

    **RegisterRequest.php:**
    - name (required, string, max:255)
    - email (required, email, unique:users,email)
    - password (required, string, min:8, confirmed)
    - password_confirmation (required)

    **VerifyEmailRequest.php:**
    - id (required, exists:users,id)
    - hash (required, string) — valida assinatura do Laravel

    **ForgotPasswordRequest.php:**
    - email (required, email, exists:users,email)

    **ResetPasswordRequest.php:**
    - token (required, string)
    - email (required, email, exists:users,email)
    - password (required, string, min:8, confirmed)
    - password_confirmation (required)

    **LogoutRequest.php:**
    - current_password (string, optional) — para confirmar logout de todos os dispositivos se fornecido

    Todos devem usar `Illuminate\Foundation\Http\FormRequest` e autorizar (`authorize() = true`).
  </action>
  <verify>
    <automated>docker compose exec -T php php artisan route:list --path=api/v1/auth 2>&1 | head -20</automated>
  </verify>
  <done>6 Form Requests criados com validações corretas e autorização habilitada.</done>
</task>

<task type="auto">
  <name>Task 2: Criar AuthController com 7 actions</name>
  <files>
    backend/app/Http/Controllers/Api/V1/AuthController.php
  </files>
  <action>
    Criar backend/app/Http/Controllers/Api/V1/AuthController.php com:

    **login(LoginRequest $request):**
    - Tenta autenticar via `Auth::attempt($credentials, $request->remember)`
    - Se falhar: return 422 com mensagem "Credenciais inválidas"
    - Se usuário não verificado: return 403 com "Email não verificado"
    - Regenera sessão: `$request->session()->regenerate()`
    - Retorna 200 com user (sem password) + mensagem

    **register(RegisterRequest $request):**
    - Cria User com `$request->validated()` (password via Hash::make)
    - Atribui role "Consulta" (padrão) via `$user->roles()->attach($consultaRoleId)`
    - Dispara `$user->sendEmailVerificationNotification()`
    - Retorna 201 com user + "Email de verificação enviado"

    **verifyEmail(VerifyEmailRequest $request):**
    - Encontra user por id
    - Valida hash: `hash_equals(sha1($user->getEmailForVerification()), $request->hash)`
    - Se já verificado: return 200 "Email já verificado"
    - Marca `$user->markEmailAsVerified()`
    - Redireciona para `FRONTEND_URL/email-verified` (ou retorna JSON)

    **resendVerification(Request $request):**
    - `$request->user()->sendEmailVerificationNotification()`
    - Retorna "Link de verificação reenviado"

    **forgotPassword(ForgotPasswordRequest $request):**
    - `Password::broker()->sendResetLink($request->only('email'))`
    - Sempre retorna sucesso genérico (não vazar se email existe)

    **resetPassword(ResetPasswordRequest $request):**
    - `$status = Password::broker()->reset($request->validated(), fn($user, $pwd) => $user->forceFill(['password' => Hash::make($pwd)])->save())`
    - Se sucesso: invalida remember_token `$user->remember_token = null; $user->save()`
    - Retorna status apropriado

    **logout(LogoutRequest $request):**
    - Se `current_password` fornecido e confere: `$request->user()->tokens()->delete()` (todas sessões)
    - Senão: `$request->user()->currentAccessToken()->delete()` (sessão atual)
    - `$request->session()->invalidate(); $request->session()->regenerateToken()`
    - Retorna 200 "Deslogado com sucesso"

    **user(Request $request):**
    - Retorna `$request->user()->load('roles.permissions')` (sem password)

    Usar `Sanctum::actingAs()` não necessário — middleware `auth:sanctum` já injeta user.
  </action>
  <verify>
    <automated>docker compose exec -T php php artisan route:list --path=api/v1/auth 2>&1</automated>
  </verify>
  <done>AuthController criado com 7 actions funcionando, retornando JSON consistente.</done>
</task>

<task type="auto">
  <name>Task 3: Registrar rotas em routes/api.php e configurar Sanctum/Auth</name>
  <files>
    backend/routes/api.php
    backend/config/sanctum.php
    backend/config/auth.php
  </files>
  <action>
    **1. Atualizar backend/routes/api.php:**
    ```php
    Route::prefix('v1')->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
        Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
        Route::post('/auth/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify')->middleware('throttle:5,1');
        Route::post('/auth/email/verification-notification', [AuthController::class, 'resendVerification'])->middleware('auth:sanctum', 'throttle:5,1');
        Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
        Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('/auth/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
    });
    ```

    **2. Atualizar backend/config/sanctum.php:**
    - `'expiration' => null` (não expira tokens de API, só session cookies)
    - `'token_prefix' => 'labctrl_'`
    - `'middleware' => ['web']` (para session cookies)

    **3. Atualizar backend/config/auth.php:**
    - Guards: `'web' => ['driver' => 'session', 'provider' => 'users']`, `'sanctum' => ['driver' => 'sanctum', 'provider' => 'users']`
    - Providers: `'users' => ['driver' => 'eloquent', 'model' => App\Models\User::class]`
    - Passwords: `'users' => ['provider' => 'users', 'table' => 'password_reset_tokens', 'expire' => 60, 'throttle' => 5]`
    - Defaults: `'guard' => 'web', 'passwords' => 'users'`

    **4. Adicionar RateLimiter no backend/bootstrap/app.php (ou AppServiceProvider):**
    ```php
    RateLimiter::for('auth', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });
    ```
    E usar `->middleware('throttle:auth')` nas rotas.
  </action>
  <verify>
    <automated>docker compose exec -T php php artisan route:list --path=api/v1/auth 2>&1</automated>
  </verify>
  <done>Rotas registradas, Sanctum configurado, Auth guards definidos, rate limiting ativo.</done>
</task>

<task type="auto">
  <name>Task 4: Configurar email verification e password reset notifications</name>
  <files>
    backend/app/Notifications/VerifyEmail.php
    backend/app/Notifications/ResetPassword.php
  </files>
  <action>
    **1. Publicar e customizar VerifyEmail:**
    - `docker compose exec -T php php artisan vendor:publish --provider="Illuminate\Auth\Notifications\VerifyEmail" --tag=laravel-notifications` (cria em app/Notifications)
    - Customizar `VerifyEmail.php`:
      - `via()` retorna `['mail']`
      - `toMail()` usa `Markdown::mail('emails.verify-email')` com botão "Verificar Email"
      - URL: `url(config('app.frontend_url') . "/verify-email/{$this->verificationUrl()}")`

    **2. Publicar e customizar ResetPassword:**
    - `docker compose exec -T php php artisan vendor:publish --provider="Illuminate\Auth\Notifications\ResetPassword" --tag=laravel-notifications`
    - Customizar `ResetPassword.php`:
      - URL: `url(config('app.frontend_url') . "/reset-password?token={$this->token}&email={$this->email}")`
      - Expiração: 60 minutos (padrão)

    **3. Criar templates de email (Markdown):**
    - `resources/views/emails/verify-email.blade.php`
    - `resources/views/emails/reset-password.blade.php`
    - Usar tema escuro compatível com PrimeVue (cores neutras, botão primário)

    **4. Configurar FRONTEND_URL no .env.example e .env** (já feito na Fase 1).
  </action>
  <verify>
    <automated>docker compose exec -T php php artisan tinker --execute="echo class_exists('App\Notifications\VerifyEmail') ? 'OK' : 'MISSING';" 2>&1</automated>
  </verify>
  <done>Notificações de verificação de email e reset de senha publicadas e customizadas com templates Markdown.</done>
</task>

</tasks>

<threat_model>
## Trust Boundaries

| Boundary | Description |
|----------|-------------|
| Frontend → Backend API | Requisições HTTP do Vue (porta 5173) para Laravel (porta 80) via Sanctum cookies |
| Email → User | Links de verificação e reset enviados via SMTP (configurado em .env) |

## STRIDE Threat Register

| Threat ID | Category | Component | Severity | Disposition | Mitigation Plan |
|-----------|----------|-----------|----------|-------------|-----------------|
| T-02-01 | Spoofing | Login endpoint | medium | mitigate | Rate limit 5/min, credenciais validadas, senha não logada |
| T-02-02 | Tampering | Email verification link | low | mitigate | Link assinado com hash SHA1, expira automaticamente |
| T-02-03 | Tampering | Password reset token | medium | mitigate | Token expira em 60 min, invalida após uso, invalida remember_token |
| T-02-04 | Information Disclosure | User enumeration | low | mitigate | Respostas genéricas em forgot/reset (sempre "se email existe, enviamos") |
| T-02-05 | Elevation of Privilege | Remember token | medium | mitigate | Token aleatório 60 chars, invalida em logout total, rotação periódica |
| T-02-06 | Denial of Service | Auth endpoints | low | mitigate | Rate limit 5 req/min por IP, Sanctum CSRF protection |
</threat_model>

<verification>
1. `docker compose exec php php artisan route:list --path=api/v1/auth` — 8 rotas listadas
2. `curl -X POST http://localhost/api/v1/auth/login -H "Content-Type: application/json" -d '{"email":"admin@labcontrol.com","password":"@dmin123"}` — 200 com cookie session
3. `curl -X POST http://localhost/api/v1/auth/register -H "Content-Type: application/json" -d '{"name":"Teste","email":"teste@teste.com","password":"12345678","password_confirmation":"12345678"}` — 201
4. `curl http://localhost/api/v1/auth/user -H "Cookie: <session_cookie>"` — 200 com user
5. Rate limit: 6 requests rápidas em login → 429 Too Many Requests
</verification>

<success_criteria>
- [ ] POST /api/v1/auth/login funciona com credenciais válidas (cookie HttpOnly retornado)
- [ ] POST /api/v1/auth/register cria usuário, envia email verificação, retorna 201
- [ ] GET /api/v1/auth/verify-email/{id}/{hash} marca email_verified_at
- [ ] POST /api/v1/auth/forgot-password envia email com link reset
- [ ] POST /api/v1/auth/reset-password valida token e atualiza senha
- [ ] POST /api/v1/auth/logout invalida sessão (e remember se solicitado)
- [ ] GET /api/v1/auth/user retorna usuário autenticado (401 se não autenticado)
- [ ] Rate limiting 5 req/min ativo em login/register/forgot/reset
- [ ] Sanctum cookies HttpOnly, SameSite=Lax, Secure em produção
</success_criteria>

<output>
Criar `.planning/phases/02-autenticacao/02-PLAN-01-SUMMARY.md` quando concluído
</output>