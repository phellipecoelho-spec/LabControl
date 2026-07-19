# Discussion Log: Phase 2 — Autenticação

**Date:** 2026-07-19
**Phase:** 02-autenticacao
**Participants:** User (visionary), Agent (builder)

---

## Decisions Captured

### 1. Login & Session Strategy (AUTH-01 + AUTH-04)
**Question:** Como deve funcionar o login e persistência de sessão?
**Selected:** Session cookies (Sanctum padrão SPA)
- HttpOnly cookies com CSRF protection
- SESSION_DRIVER=redis já configurado
- Sessão padrão expira ao fechar browser
- **Checkbox "Lembrar-me" no login** → estende para 30 dias via `remember_token`
- Sanctum gerencia o token de sessão automaticamente

**Rationale:** Web-first, mais seguro (HttpOnly cookies), CSRF nativo, compatível com PWA. Token-based seria menos seguro para web (localStorage).

---

### 2. Email Verification (AUTH-02)
**Question:** Como implementar a verificação de email no registro?
**Selected:** Laravel built-in (signed URL)
- Usuário registra → recebe email com link assinado
- Clica no link → `email_verified_at` preenchido
- Middleware `verified` protege rotas que exigem email verificado
- Rotas nativas: `verification.notice`, `verification.verify`, `verification.send`

**Rationale:** Simples, usa rotas nativas do Laravel, menos código para manter.

---

### 3. Password Reset (AUTH-03)
**Question:** Como implementar a recuperação de senha?
**Selected:** Laravel built-in (signed reset link)
- Usuário solicita reset → email com link assinado (expira 60 min)
- Formulário de nova senha valida token
- Rotas nativas: `password.request`, `password.email`, `password.reset`, `password.update`

**Rationale:** Menos código customizado, rotas nativas bem testadas.

---

### 4. Frontend Auth Architecture
**Question:** Como organizar o estado de autenticação no frontend Vue 3?
**Selected:** Pinia store + router guards
- Store `useAuthStore` em `stores/auth.ts`:
  - State: `user`, `isAuthenticated`, `loading`
  - Actions: `login(credentials)`, `register(data)`, `logout()`, `fetchUser()`, `checkAuth()`
  - Getters: `hasRole(role)`, `hasPermission(perm)`
- Router guards em `router/index.ts`:
  - `meta.requiresAuth` → redireciona para login
  - `meta.requiresVerified` → redireciona para verificação de email
  - `meta.roles` → verifica roles permitidas
- Composable `useAuth()` para acesso fácil nos componentes

**Rationale:** Padrão Laravel/Vue, centraliza lógica de auth, fácil de testar e manter.

---

### 5. Session Lifetime (AUTH-04)
**Question:** Qual o tempo de vida da sessão?
**Selected:** Remember me (30 dias) + expiração normal
- Sem "remember me": sessão expira ao fechar browser (SESSION_LIFETIME=120 min)
- Com "remember me": cookie persistente 30 dias via `remember_token` na tabela users
- Sanctum gerencia automaticamente

**Rationale:** Equilíbrio entre segurança (sessão curta por padrão) e UX (opção de sessão longa).

---

### 6. Rate Limiting
**Question:** Como limitar tentativas de login/registro?
**Selected:** Laravel throttle padrão (5 req/min)
- Middleware `throttle:5,1` nas rotas de login, registro, password reset
- Suficiente para proteger contra brute force básico
- Configurável via `RateLimiter::for()` se necessário no futuro

**Rationale:** Simples, nativo, suficiente para MVP.

---

## Deferred Ideas (Not in Scope)

| Idea | Reason |
|------|--------|
| Social Login (OAuth Google/GitHub) | Nova capability — v2+ |
| MFA (2FA via TOTP) | Não nos requisitos v1 |
| API Tokens para integrações | Não nos requisitos v1 |
| SSO/SAML | v2+ |

---

## Next Steps

1. `/gsd-plan-phase 2` — criar planos detalhados para os 4 planos da fase
2. `/gsd-execute-phase 2` — implementar backend + frontend
3. `/gsd-verify-work 2` — validar AUTH-01 a AUTH-04