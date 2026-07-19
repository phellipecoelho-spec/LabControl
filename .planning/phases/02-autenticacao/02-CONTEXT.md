---
phase: 02-autenticacao
padded_phase: 02
phase_name: Autenticação
phase_slug: autenticacao
date: 2026-07-19
status: discussed
---

<domain>
Fase 2: Autenticação

Esta fase implementa o sistema completo de autenticação do LabControl usando Laravel Sanctum (SPA-first) com Vue 3 + Pinia no frontend.

**Requisitos cobertos:**
- AUTH-01: Login com email/senha via Sanctum
- AUTH-02: Registro com verificação de email
- AUTH-03: Recuperação de senha via email
- AUTH-04: Sessão persiste entre atualizações (remember me + refresh)
</domain>

<spec_lock>
SPEC.md não encontrado — decisões de implementação capturadas abaixo.
</spec_lock>

<canonical_refs>
- .planning/REQUIREMENTS.md — Requisitos AUTH-01 a AUTH-04
- .planning/PROJECT.md — Stack: Laravel + Sanctum, Vue 3 + Pinia, Docker
- .planning/STATE.md — Fase 1 concluída (infraestrutura, Redis, Sanctum configurado)
- .planning/phases/01-infraestrutura/01-RESEARCH.md — Decisões técnicas da fase anterior
</canonical_refs>

<code_context>
**Backend (já existente):**
- Laravel 13.x com Sanctum ^4.0 instalado
- config/cors.php — origem http://localhost:5173, supports_credentials=true
- config/sanctum.php — publicado
- SESSION_DRIVER=redis, SESSION_LIFETIME=120
- routes/api.php — prefixo /api/v1/, health check funcional
- bootstrap/app.php — api routes registradas com withRouting
- Models User (UUID, SoftDeletes), Role, Permission já criados
- Seeders: RolePermissionSeeder, AdminUserSeeder (admin@labcontrol.com / @dmin123)

**Frontend (já existente):**
- Vue 3 + Vite + TypeScript + Pinia + Vue Router
- PrimeVue como UI library
- Estrutura modular: stores/, composables/, router/, modules/
- Router guards já configurados para proteção de rotas
</code_context>

<decisions>
### 1. Login & Session Strategy (AUTH-01 + AUTH-04)
**Decisão:** Session cookies (Sanctum padrão SPA)
- HttpOnly cookies com CSRF protection
- SESSION_DRIVER=redis já configurado
- Sessão padrão expira ao fechar browser
- **Checkbox "Lembrar-me" no login** → estende para 30 dias via `remember_token`
- Sanctum gerencia o token de sessão automaticamente

**Por que não token-based:** Web-first, mais seguro (HttpOnly cookies), CSRF nativo, compatível com PWA.

### 2. Email Verification (AUTH-02)
**Decisão:** Laravel built-in (signed URL)
- Usuário registra → recebe email com link assinado
- Clica no link → `email_verified_at` preenchido
- Middleware `verified` protege rotas que exigem email verificado
- Rotas nativas: `verification.notice`, `verification.verify`, `verification.send`

### 3. Password Reset (AUTH-03)
**Decisão:** Laravel built-in (signed reset link)
- Usuário solicita reset → email com link assinado (expira 60 min)
- Formulário de nova senha valida token
- Rotas nativas: `password.request`, `password.email`, `password.reset`, `password.update`

### 4. Frontend Auth Architecture
**Decisão:** Pinia store + router guards
- Store `useAuthStore` em `stores/auth.ts`:
  - State: `user`, `isAuthenticated`, `loading`
  - Actions: `login(credentials)`, `register(data)`, `logout()`, `fetchUser()`, `checkAuth()`
  - Getters: `hasRole(role)`, `hasPermission(perm)`
- Router guards em `router/index.ts`:
  - `meta.requiresAuth` → redireciona para login
  - `meta.requiresVerified` → redireciona para verificação de email
  - `meta.roles` → verifica roles permitidas
- Composable `useAuth()` para acesso fácil nos componentes

### 5. Session Lifetime (AUTH-04)
**Decisão:** Remember me (30 dias) + expiração normal
- Sem "remember me": sessão expira ao fechar browser (SESSION_LIFETIME=120 min)
- Com "remember me": cookie persistente 30 dias via `remember_token` na tabela users
- Sanctum gerencia automaticamente

### 6. Rate Limiting
**Decisão:** Laravel throttle padrão (5 req/min)
- Middleware `throttle:5,1` nas rotas de login, registro, password reset
- Suficiente para proteger contra brute force básico
- Configurável via `RateLimiter::for()` se necessário no futuro
</decisions>

<deferred_ideas>
- **Social Login (OAuth Google/GitHub)** — mencionado como futuro, seria nova capability (não nos requisitos v1)
- **MFA (2FA via TOTP)** — não nos requisitos v1
- **API Tokens para integrações** — não nos requisitos v1
- **Single Sign-On (SSO)** — v2+
</deferred_ideas>

<scope_boundaries>
**In scope (Fase 2):**
- Login/logout com email/senha
- Registro com verificação de email
- Recuperação de senha
- Remember me + sessão persistente
- Rate limiting básico
- Integração frontend (store + guards)

**Out of scope (fases futuras):**
- OAuth/Social login
- MFA
- Gestão de API tokens
- SSO/SAML
- Auditoria de login (Fase 3 - LOGS-01/02)
</scope_boundaries>