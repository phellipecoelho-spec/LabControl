---
phase: 02-autenticacao
padded_phase: 02
phase_name: Autenticação
date: 2026-07-19
status: complete
---

# Research: Phase 2 — Autenticação

## Overview

This document captures technical research for implementing authentication in LabControl using **Laravel Sanctum (SPA-first)** with **Vue 3 + Pinia** frontend. The phase covers 4 requirements: AUTH-01 (Login), AUTH-02 (Registro + Email Verification), AUTH-03 (Password Reset), AUTH-04 (Session Persistence).

---

## 1. Sanctum SPA Configuration

### Approach
Use **session cookies** (Sanctum's SPA mode) rather than token-based authentication. This aligns with the decision in CONTEXT.md for HttpOnly cookies with CSRF protection.

### Key Configuration

**config/sanctum.php:**
```php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost:5173')),
    'expiration' => null,           // API tokens don't expire (we use session cookies)
    'token_prefix' => 'labctrl_',   // Prefix for API tokens if needed
    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
```

**config/session.php (critical for SPA):**
```php
'driver' => env('SESSION_DRIVER', 'redis'),
'lifetime' => env('SESSION_LIFETIME', 120),
'expire_on_close' => false,        // Remember me extends this
'encrypt' => true,
'files' => storage_path('framework/sessions'),
'connection' => 'default',
'table' => 'sessions',
'store' => 'default',
'lottery' => [2, 100],
'cookie' => env('SESSION_COOKIE', 'labctrl_session'),
'path' => '/',
'domain' => env('SESSION_DOMAIN', null),  // null for localhost
'secure' => env('SESSION_SECURE_COOKIE', false), // true in production
'http_only' => true,
'same_site' => 'lax',              // Lax allows cross-site navigation for OAuth, Strict for max security
'partitioned' => false,
```

**config/cors.php (already created in Phase 1):**
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,  // CRITICAL for cookies
];
```

### CSRF Protection
- Laravel automatically sets `XSRF-TOKEN` cookie when visiting `/sanctum/csrf-cookie`
- Frontend must read this cookie and send as `X-XSRF-TOKEN` header
- Axios interceptor handles this automatically

### Remember Token (30 days)
- Laravel's built-in `remember_token` column (60 chars)
- Set via `Auth::attempt($credentials, $remember = true)`
- Cookie: `remember_web_<hash>` with 30-day lifetime
- Invalidated on: logout (all devices), password reset, password change

**Documentation:** [Laravel Sanctum SPA Authentication](https://laravel.com/docs/sanctum#spa-authentication), [Session Configuration](https://laravel.com/docs/session)

---

## 2. Email Verification Flow

### Implementation: Laravel's `MustVerifyEmail`

**User Model:**
```php
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes;
    // ...
}
```

**RouteServiceProvider:**
```php
public static string $home = '/dashboard';
public static string $verificationUrl = '/verify-email/{id}/{hash}';
// Generates: FRONTEND_URL/verify-email/{id}/{hash}
```

**Verification URL Generation:**
```php
// In User model or Notification
public function verificationUrl(): string
{
    return URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),  // 60 min expiration
        ['id' => $this->id, 'hash' => sha1($this->getEmailForVerification())]
    );
}
```

**Frontend URL Construction:**
- Backend generates: `http://localhost:5173/verify-email/{id}/{hash}`
- Frontend route: `/verify-email/:id/:hash`
- GET request to backend: `GET /api/v1/auth/verify-email/{id}/{hash}`

### Resend Verification
- Endpoint: `POST /api/v1/auth/email/verification-notification`
- Throttle: 5 req/min (same as auth)
- Returns 200 even if already verified (idempotent)

### Middleware: `verified`
```php
// Apply to routes requiring verified email
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // protected routes
});
```

**Documentation:** [Email Verification](https://laravel.com/docs/verification), [Signed URLs](https://laravel.com/docs/urls#signed-urls)

---

## 3. Password Reset Flow

### Implementation: Laravel's Built-in Password Reset

**Token Generation:**
```php
// In ForgotPasswordController / custom action
$status = Password::broker()->sendResetLink(
    $request->only('email')
);
// Returns Password::RESET_LINK_SENT or Password::INVALID_USER
```

**Token Characteristics:**
- 60-minute expiration (configurable in `config/auth.php`)
- Stored in `password_reset_tokens` table (email, token, created_at)
- Single-use: invalidated after successful reset
- Hash: `hash_hmac('sha256', $token, $secret)`

**Reset Password:**
```php
$status = Password::broker()->reset(
    $request->only('email', 'password', 'password_confirmation', 'token'),
    function ($user, $password) {
        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),  // Invalidate remember tokens
        ])->save();
        
        event(new PasswordReset($user));
    }
);
```

**Security Notes:**
- Always return generic success message: "Se o email existir, enviaremos instruções"
- Token expires in 60 minutes (configurable)
- Remember token cleared on reset (forces re-login on all devices)
- Rate limit: 5 req/min on both forgot and reset endpoints

**Frontend Flow:**
1. User visits `/forgot-password` → submits email
2. Backend sends email with link: `FRONTEND_URL/reset-password?token={token}&email={email}`
3. Frontend `/reset-password` page reads `token` and `email` from query params
4. User submits new password → `POST /api/v1/auth/reset-password`
5. On success: auto-login → redirect to dashboard

**Documentation:** [Password Reset](https://laravel.com/docs/passwords), [Password Broker](https://laravel.com/docs/passwords#resetting-passwords)

---

## 4. Session Persistence & Remember Me

### Session Configuration
- **Driver:** Redis (already configured in Phase 1)
- **Default lifetime:** 120 minutes (2 hours)
- **Remember me:** 30 days (43200 minutes)

### Remember Token Flow
```php
// Login with remember
Auth::attempt($credentials, $request->boolean('remember'));

// Internally:
// 1. Generates 60-char random token
// 2. Stores in user.remember_token
// 3. Sets cookie: remember_web_{guard_hash} with 30-day expiry
// 4. On subsequent requests, middleware checks cookie → logs in automatically
```

### Session Regeneration
```php
// On successful login
$request->session()->regenerate();

// On logout
$request->session()->invalidate();
$request->session()->regenerateToken();
```

### Cookie Security (Production)
```env
SESSION_SECURE_COOKIE=true     # HTTPS only
SESSION_SAME_SITE=lax          # or 'strict'
SESSION_DOMAIN=.labcontrol.com # for subdomain sharing
```

**Documentation:** [Session Configuration](https://laravel.com/docs/session), [Remember Me](https://laravel.com/docs/authentication#remembering-users)

---

## 5. Frontend Integration (Vue 3 + Pinia)

### Pinia Store: `useAuthStore`

```typescript
// stores/auth.ts
export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!user.value)
  const isVerified = computed(() => user.value?.email_verified_at !== null)

  const hasRole = (role: string) => user.value?.roles?.some(r => r.slug === role) ?? false
  const hasPermission = (perm: string) => user.value?.roles?.some(r => r.permissions?.some(p => p.slug === perm)) ?? false

  async function login(credentials: { email: string; password: string; remember?: boolean }) {
    loading.value = true
    try {
      const response = await api.post('/auth/login', credentials)
      user.value = response.data.user
      error.value = null
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Erro ao fazer login'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function register(data: { name: string; email: string; password: string; password_confirmation: string }) {
    loading.value = true
    try {
      await api.post('/auth/register', data)
      error.value = null
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Erro ao registrar'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function logout(allDevices = false) {
    try {
      await api.post('/auth/logout', { current_password: allDevices ? await getCurrentPassword() : undefined })
      user.value = null
    } catch (e) {
      // Force clear local state even if API fails
      user.value = null
    }
  }

  async function fetchUser() {
    try {
      const response = await api.get('/auth/user')
      user.value = response.data
    } catch {
      user.value = null
    }
  }

  async function checkAuth(): Promise<boolean> {
    if (isAuthenticated.value) return true
    try {
      await fetchUser()
      return true
    } catch {
      user.value = null
      return false
    }
  }

  return { user, loading, error, isAuthenticated, isVerified, hasRole, hasPermission, login, register, logout, fetchUser, checkAuth }
})
```

### Axios Interceptor (services/api.ts)

```typescript
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  withCredentials: true,  // ESSENTIAL for cookies
})

// Request: Add XSRF token
api.interceptors.request.use(config => {
  const xsrfToken = getCookie('XSRF-TOKEN')
  if (xsrfToken) config.headers['X-XSRF-TOKEN'] = xsrfToken
  return config
})

// Response: Handle auth errors
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      useAuthStore().logout()
      router.push({ name: 'login', query: { expired: '1' } })
    }
    if (error.response?.status === 403 && error.response.data.message?.includes('verificado')) {
      router.push({ name: 'verify-email.pending', query: { redirect: router.currentRoute.value.fullPath } })
    }
    return Promise.reject(error)
  }
)

function getCookie(name: string): string | null {
  const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'))
  return match ? match[2] : null
}
```

### Router Guards (router/index.ts)

```typescript
router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore()

  if (!auth.loading && !auth.isAuthenticated) {
    await auth.checkAuth()
  }

  // Guest-only routes
  if (to.meta.guest && auth.isAuthenticated) {
    return next({ name: 'dashboard' })
  }

  // Auth required
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  // Email verified required
  if (to.meta.requiresVerified && auth.isAuthenticated && !auth.isVerified) {
    return next({ name: 'verify-email.pending', query: { redirect: to.fullPath } })
  }

  // Role-based access
  if (to.meta.roles && !to.meta.roles.some((r: string) => auth.hasRole(r))) {
    return next({ name: 'unauthorized' })
  }

  next()
})
```

**Route Meta Examples:**
```typescript
{ path: '/dashboard', meta: { requiresAuth: true, requiresVerified: true } }
{ path: '/admin', meta: { requiresAuth: true, requiresVerified: true, roles: ['admin'] } }
{ path: '/login', meta: { guest: true } }
```

---

## 6. Rate Limiting

### Configuration (AppServiceProvider or bootstrap/app.php)

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

// Apply to routes:
Route::middleware(['throttle:auth'])->group(function () {
    Route::post('/auth/login', ...);
    Route::post('/auth/register', ...);
    Route::post('/auth/forgot-password', ...);
    Route::post('/auth/reset-password', ...);
    Route::post('/auth/email/verification-notification', ...);
});
```

### Custom Key Resolver (Optional)
```php
RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(5)
        ->by($request->ip() . '|' . $request->route()->getName());
});
```

---

## 7. Security Hardening

### Password Hashing
```php
// config/hashing.php
'driver' => 'bcrypt',
'bcrypt' => ['rounds' => 12],  // 12 rounds (default 10, 12 = ~250ms)
```

### Secure Headers (Middleware)
```php
// Add to kernel or middleware
response->headers->set('X-Content-Type-Options', 'nosniff');
response->headers->set('X-Frame-Options', 'DENY');
response->headers->set('X-XSS-Protection', '1; mode=block');
response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
```

### CSRF Token Rotation
- Sanctum handles automatically
- Token regenerated on login
- Frontend reads fresh token from cookie on each request

### Password Reset Token Security
- 60-minute expiration
- Single-use (deleted after reset)
- Hashed in database (bcrypt)
- Rate limited (5/min)

---

## Canonical References

### Laravel Documentation
- [Sanctum SPA Authentication](https://laravel.com/docs/sanctum#spa-authentication)
- [Session Configuration](https://laravel.com/docs/session)
- [Email Verification](https://laravel.com/docs/verification)
- [Password Reset](https://laravel.com/docs/passwords)
- [Rate Limiting](https://laravel.com/docs/rate-limiting)
- [Authentication](https://laravel.com/docs/authentication)

### Vue/Pinia References
- [Pinia State Management](https://pinia.vuejs.org/)
- [Vue Router Navigation Guards](https://router.vuejs.org/guide/advanced/navigation-guards.html)
- [Axios Interceptors](https://axios-http.com/docs/interceptors)

### Security
- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [Laravel Security Best Practices](https://laravel.com/docs/security)

---

## Implementation Recommendations Summary

| Area | Recommendation |
|------|----------------|
| **Auth Method** | Session cookies (Sanctum SPA) with HttpOnly + CSRF |
| **Email Verification** | Laravel `MustVerifyEmail` + signed URLs (60 min) |
| **Password Reset** | Laravel built-in + remember_token invalidation |
| **Remember Me** | 30-day cookie via `remember_token` |
| **Frontend Auth** | Pinia store + axios interceptor + router guards |
| **Rate Limiting** | 5 req/min per IP on auth endpoints |
| **Security** | bcrypt 12 rounds, secure headers, CSRF rotation |

---

## Potential Pitfalls & Mitigations

| Pitfall | Mitigation |
|---------|------------|
| CORS blocking cookies | `supports_credentials: true` + matching origin |
| CSRF token missing | Visit `/sanctum/csrf-cookie` on app init |
| Session not persisting | Check `withCredentials: true` in axios |
| Email verification link 404 | Verify `FRONTEND_URL` matches frontend origin |
| Rate limit too aggressive | Adjust per endpoint, use custom key resolver |
| Remember me not working | Check `remember_token` column, cookie domain |

---

*Research completed: 2026-07-19*
*Phase: 02-autenticacao*
*Status: Ready for planning*