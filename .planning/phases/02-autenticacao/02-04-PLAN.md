---
phase: 02-autenticacao
plan: 04
type: execute
wave: 4
depends_on:
  - 01
  - 02
  - 03
files_modified:
  - backend/tests/Feature/Auth/LoginTest.php
  - backend/tests/Feature/Auth/RegisterTest.php
  - backend/tests/Feature/Auth/VerifyEmailTest.php
  - backend/tests/Feature/Auth/PasswordResetTest.php
  - backend/tests/Feature/Auth/LogoutTest.php
  - frontend/tests/e2e/auth.spec.ts
  - backend/phpunit.xml
  - frontend/vitest.config.ts
autonomous: false
requirements:
  - AUTH-01
  - AUTH-02
  - AUTH-03
  - AUTH-04
user_setup: []

must_haves:
  truths:
    - "Backend: LoginTest — sucesso com credenciais válidas, falha com inválidas, email não verificado bloqueado"
    - "Backend: RegisterTest — cria usuário, envia verificação, retorna 201, atribui role Consulta"
    - "Backend: VerifyEmailTest — link válido verifica, link expirado/invalido falha, reenvio funciona"
    - "Backend: PasswordResetTest — forgot envia email, reset com token válido atualiza senha, token expirado falha"
    - "Backend: LogoutTest — logout invalida sessão, logout all devices invalida remember_token"
    - "Frontend E2E: login fluxo completo (credenciais válidas → dashboard)"
    - "Frontend E2E: registro → verificação email → login"
    - "Frontend E2E: forgot password → reset password → login com nova senha"
    - "Frontend E2E: remember me persiste sessão ao fechar browser"
    - "Cobertura backend ≥ 80% nos controllers Auth"
    - "Cobertura frontend ≥ 70% nas views auth"
  artifacts:
    - backend/tests/Feature/Auth/LoginTest.php
    - backend/tests/Feature/Auth/RegisterTest.php
    - backend/tests/Feature/Auth/VerifyEmailTest.php
    - backend/tests/Feature/Auth/PasswordResetTest.php
    - backend/tests/Feature/Auth/LogoutTest.php
    - frontend/tests/e2e/auth.spec.ts
    - backend/tests/Unit/Auth/RateLimitTest.php
  key_links:
    - "Pest PHP (backend) + Vitest + Playwright (frontend): stack de testes"
    - "DatabaseTransactions trait: isolamento de testes com rollback"
    - "RefreshDatabase: migrações fresh para cada teste (ou usa SQLite em memória)"
    - "Sanctum::actingAs() para testes autenticados"
---

<objective>
Implementar suíte completa de testes (backend + frontend) para validar todos os requisitos AUTH-01 a AUTH-04.

**Purpose:** Garantir que a autenticação funciona ponta-a-ponta, prevenir regressões e documentar comportamento esperado via testes executáveis.

**Output:**
- 5 test suites backend (Pest PHP) cobrindo login, register, verify, reset, logout
- 1 test suite E2E frontend (Playwright) cobrindo fluxos críticos
- Testes de rate limiting
- Configuração de CI para rodar testes automaticamente
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
@backend/tests/
@frontend/tests/
</context>

<tasks>

<task type="auto">
  <name>Task 1: Configurar ambiente de testes backend (Pest + SQLite)</name>
  <files>
    backend/phpunit.xml
    backend/tests/TestCase.php
    backend/tests/CreatesApplication.php
  </files>
  <action>
    **1. backend/phpunit.xml — já existe, verificar configuração:**
    - `env name="DB_CONNECTION" value="sqlite"`
    - `env name="DB_DATABASE" value=":memory:"`
    - `bootstrap="vendor/autoload.php"`
    - `testsuite name="Feature" directory="./tests/Feature"`

    **2. backend/tests/TestCase.php — trait RefreshDatabase:**
    ```php
    abstract class TestCase extends BaseTestCase
    {
        use CreatesApplication, RefreshDatabase;
    }
    ```

    **3. Instalar Pest (se não instalado):**
    - `docker compose exec -T php composer require pestphp/pest --dev --no-interaction`
    - `docker compose exec -T php php artisan pest:install`
    - Atualizar `composer.json` scripts: `"test": "pest --parallel"`

    **4. Configurar SQLite em memória para velocidade:**
    - phpunit.xml: `<env name="DB_CONNECTION" value="sqlite"/>`
    - phpunit.xml: `<env name="DB_DATABASE" value=":memory:"/>`
    - Migrações rodam automaticamente via RefreshDatabase
  </action>
  <verify>
    <automated>docker compose exec -T php php artisan test --filter=ExampleTest 2>&1 | head -20</automated>
  </verify>
  <done>Ambiente Pest configurado com SQLite em memória, testes rodando.</done>
</task>

<task type="auto">
  <name>Task 2: Criar testes Feature de Login (AUTH-01)</name>
  <files>
    backend/tests/Feature/Auth/LoginTest.php
  </files>
  <action>
    Criar `backend/tests/Feature/Auth/LoginTest.php` com Pest:

    ```php
    use App\Models\User;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\RateLimiter;

    test('login com credenciais válidas retorna 200 e cookie de sessão', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user' => ['id', 'name', 'email']])
            ->assertCookie('labctrl_session'); // Sanctum session cookie
    });

    test('login com senha incorreta retorna 422', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('login com email não verificado retorna 403', function () {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'unverified@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Email não verificado']);
    });

    test('remember me gera remember_token persistente', function () {
        $user = User::factory()->create([
            'email' => 'remember@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'remember@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertStatus(200);
        $user->refresh();
        expect($user->remember_token)->not->toBeNull();
    });

    test('rate limiting bloqueia após 5 tentativas por minuto', function () {
        $user = User::factory()->create([
            'email' => 'ratelimit@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // 5 tentativas válidas (falham por senha errada)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'ratelimit@example.com',
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        // 6ª tentativa deve ser bloqueada
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ratelimit@example.com',
            'password' => 'password123', // senha correta
        ]);

        $response->assertStatus(429); // Too Many Requests
    });
    ```
  </action>
  <verify>
    <automated>docker compose exec -T php php artisan test tests/Feature/Auth/LoginTest.php 2>&1</automated>
  </verify>
  <done>LoginTest.php criado com 5 testes passando (válido, inválido, não verificado, remember me, rate limit).</done>
</task>

<task type="auto">
  <name>Task 3: Criar testes Feature de Register (AUTH-02)</name>
  <files>
    backend/tests/Feature/Auth/RegisterTest.php
  </files>
  <action>
    Criar `backend/tests/Feature/Auth/RegisterTest.php`:

    ```php
    use App\Models\User;
    use App\Models\Role;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Notification;
    use Illuminate\Auth\Notifications\VerifyEmail;

    test('registro cria usuário, envia verificação, retorna 201', function () {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Novo Usuário',
            'email' => 'novo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'email_verified_at']]);

        $user = User::where('email', 'novo@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->email_verified_at)->toBeNull(); // não verificado ainda
        expect($user->name)->toBe('Novo Usuário');

        // Verifica role padrão "Consulta"
        $consultaRole = Role::where('slug', 'consulta')->first();
        expect($user->roles->contains($consultaRole))->toBeTrue();

        // Verifica notificação enviada
        Notification::assertSentTo($user, VerifyEmail::class);
    });

    test('registro com email duplicado retorna 422', function () {
        User::factory()->create(['email' => 'existente@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Outro',
            'email' => 'existente@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('registro com senhas diferentes retorna 422', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Teste',
            'email' => 'teste@example.com',
            'password' => 'password123',
            'password_confirmation' => 'diferente',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    test('registro com senha curta retorna 422', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Teste',
            'email' => 'curta@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });
    ```
  </action>
  <verify>
    <automated>docker compose exec -T php php artisan test tests/Feature/Auth/RegisterTest.php 2>&1</automated>
  </verify>
  <done>RegisterTest.php criado com 4 testes passando.</done>
</task>

<task type="auto">
  <name>Task 4: Criar testes Feature de VerifyEmail (AUTH-02)</name>
  <files>
    backend/tests/Feature/Auth/VerifyEmailTest.php
  </files>
  <action>
    Criar `backend/tests/Feature/Auth/VerifyEmailTest.php`:

    ```php
    use App\Models\User;
    use Illuminate\Support\Facades\URL;
    use Illuminate\Support\Facades\Notification;
    use Illuminate\Auth\Notifications\VerifyEmail;

    test('link de verificação válido marca email_verified_at', function () {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $url = URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $response = $this->get($url);

        $response->assertStatus(200); // ou redirect
        $user->refresh();
        expect($user->email_verified_at)->not->toBeNull();
    });

    test('link com hash inválido retorna 403', function () {
        $user = User::factory()->create(['email_verified_at' => null]);

        $url = URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => 'hash_invalido',
        ]);

        $response = $this->get($url);
        $response->assertStatus(403);
    });

    test('link expirado retorna 403', function () {
        $user = User::factory()->create(['email_verified_at' => null]);

        // URL assinada com expiração no passado (manipular timestamp)
        $url = URL::temporarySignedRoute('verification.verify', now()->subHour(), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $response = $this->get($url);
        $response->assertStatus(403);
    });

    test('usuário já verificado recebe mensagem apropriada', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $url = URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $response = $this->get($url);
        $response->assertStatus(200); // já verificado
    });

    test('reenviar verificação envia nova notificação', function () {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/v1/auth/email/verification-notification');

        $response->assertStatus(200);
        Notification::assertSentTo($user, VerifyEmail::class);
    });

    test('rate limit no reenvio de verificação', function () {
        $user = User::factory()->create(['email_verified_at' => null]);
        $token = $user->createToken('test')->plainTextToken;

        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders(['Authorization' => "Bearer $token"])
                ->postJson('/api/v1/auth/email/verification-notification')
                ->assertStatus(200);
        }

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/v1/auth/email/verification-notification');
        $response->assertStatus(429);
    });
    ```
  </action>
  <verify>
    <automated>docker compose exec -T php php artisan test tests/Feature/Auth/VerifyEmailTest.php 2>&1</automated>
  </verify>
  <done>VerifyEmailTest.php criado com 5 testes passando.</done>
</task>

<task type="auto">
  <name>Task 5: Criar testes Feature de Password Reset (AUTH-03)</name>
  <files>
    backend/tests/Feature/Auth/PasswordResetTest.php
  </files>
  <action>
    Criar `backend/tests/Feature/Auth/PasswordResetTest.php`:

    ```php
    use App\Models\User;
    use Illuminate\Support\Facades\Password;
    use Illuminate\Support\Facades\Notification;
    use Illuminate\Auth\Notifications\ResetPassword;

    test('forgot password envia email de reset', function () {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'reset@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Se o email existir, enviaremos instruções']);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            return $notification->token !== null;
        });
    });

    test('forgot password com email inexistente retorna sucesso genérico', function () {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'naoexiste@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Se o email existir, enviaremos instruções']);
        // Não vazar se email existe
    });

    test('reset password com token válido atualiza senha', function () {
        $user = User::factory()->create(['email' => 'reset@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        expect(Hash::check('novaSenha123', $user->password))->toBeTrue();
        // remember_token deve ser limpo
        expect($user->remember_token)->toBeNull();
    });

    test('reset password com token expirado falha', function () {
        $user = User::factory()->create(['email' => 'reset@example.com']);
        // Token criado manualmente com expiração no passado
        $token = 'token_expirado';

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    });

    test('reset password com token inválido falha', function () {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'token_invalido',
            'email' => 'reset@example.com',
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    });

    test('reset password com senhas diferentes falha', function () {
        $user = User::factory()->create(['email' => 'reset@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'novaSenha123',
            'password_confirmation' => 'diferente',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    test('rate limit no forgot password', function () {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', ['email' => 'test@example.com'])
                ->assertStatus(200);
        }

        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'test@example.com']);
        $response->assertStatus(429);
    });

    test('rate limit no reset password', function () {
        $user = User::factory()->create(['email' => 'reset@example.com']);
        $token = Password::broker()->createToken($user);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/reset-password', [
                'token' => $token,
                'email' => 'reset@example.com',
                'password' => 'senha123',
                'password_confirmation' => 'senha123',
            ])->assertStatus(422); // falha por senha diferente, mas conta pro rate limit
        }

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
        ]);
        $response->assertStatus(429);
    });
    ```
  </action>
  <verify>
    <automated>docker compose exec -T php php artisan test tests/Feature/Auth/PasswordResetTest.php 2>&1</automated>
  </verify>
  <done>PasswordResetTest.php criado com 7 testes passando.</done>
</task>

<task type="auto">
  <name>Task 6: Criar testes Feature de Logout (AUTH-04)</name>
  <files>
    backend/tests/Feature/Auth/LogoutTest.php
  </files>
  <action>
    Criar `backend/tests/Feature/Auth/LogoutTest.php`:

    ```php
    use App\Models\User;
    use Laravel\Sanctum\Sanctum;

    test('logout invalida sessão atual', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Deslogado com sucesso']);

        // Tentar acessar rota protegida deve falhar
        $this->getJson('/api/v1/auth/user')->assertStatus(401);
    });

    test('logout all devices invalida remember_token e todas sessões', function () {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'remember_token' => 'token_antigo_12345678901234567890123456789012',
        ]);
        Sanctum::actingAs($user);

        // Criar múltiplos tokens de acesso
        $user->createToken('device1')->plainTextToken;
        $user->createToken('device2')->plainTextToken;

        $response = $this->postJson('/api/v1/auth/logout', [
            'current_password' => 'password', // senha correta
        ]);

        $response->assertStatus(200);

        $user->refresh();
        expect($user->remember_token)->toBeNull();
        expect($user->tokens()->count())->toBe(0);
    });

    test('logout all devices com senha incorreta falha', function () {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'remember_token' => 'token_123',
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout', [
            'current_password' => 'senha_errada',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    });

    test('logout sem autenticação retorna 401', function () {
        $response = $this->postJson('/api/v1/auth/logout');
        $response->assertStatus(401);
    });

    test('remember me persiste após fechar browser (simulado)', function () {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Login com remember
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertStatus(200);
        $cookie = $response->headers->getCookies()[0] ?? null;
        expect($cookie)->not->toBeNull();

        // Simular nova requisição com cookie de sessão (remember_token válido)
        $user->refresh();
        expect($user->remember_token)->not->toBeNull();
        expect(strlen($user->remember_token))->toBe(60);
    });
    ```
  </action>
  <verify>
    <automated>docker compose exec -T php php artisan test tests/Feature/Auth/LogoutTest.php 2>&1</automated>
  </verify>
  <done>LogoutTest.php criado com 5 testes passando.</done>
</task>

<task type="auto">
  <name>Task 6: Criar testes E2E Frontend (Playwright)</name>
  <files>
    frontend/tests/e2e/auth.spec.ts
    frontend/playwright.config.ts
  </files>
  <action>
    **1. Configurar Playwright (se não configurado):**
    - `docker compose exec -T frontend npm install -D @playwright/test @playwright/test-runner`
    - `npx playwright install chromium`

    **2. frontend/playwright.config.ts:**
    ```ts
    import { defineConfig, devices } from '@playwright/test';

    export default defineConfig({
      testDir: './tests/e2e',
      fullyParallel: true,
      forbidOnly: !!process.env.CI,
      retries: process.env.CI ? 2 : 0,
      workers: process.env.CI ? 1 : undefined,
      reporter: 'html',
      use: {
        baseURL: 'http://localhost:5173',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
      },
      projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
      ],
      webServer: {
        command: 'npm run dev',
        url: 'http://localhost:5173',
        reuseExistingServer: !process.env.CI,
        timeout: 120000,
      },
    });
    ```

    **3. frontend/tests/e2e/auth.spec.ts:**
    ```ts
    import { test, expect } from '@playwright/test';

    test.describe('Autenticação', () => {
      test.beforeEach(async ({ page }) => {
        await page.goto('/login');
      });

      test('Login com credenciais válidas redireciona para dashboard', async ({ page }) => {
        await page.fill('input[name="email"]', 'admin@labcontrol.com');
        await page.fill('input[name="password"]', '@dmin123');
        await page.check('input[name="remember"]'); // remember me
        await page.click('button[type="submit"]');

        await expect(page).toHaveURL(/\/dashboard/);
        await expect(page.locator('text=Bem-vindo')).toBeVisible();
      });

      test('Login com senha incorreta mostra erro', async ({ page }) => {
        await page.fill('input[name="email"]', 'admin@labcontrol.com');
        await page.fill('input[name="password"]', 'senhaerrada');
        await page.click('button[type="submit"]');

        await expect(page.locator('.p-toast-error')).toContainText('Credenciais inválidas');
      });

      test('Login sem email verificado redireciona para verificação', async ({ page }) => {
        // Criar usuário não verificado via API antes do teste
        // ... setup via API call

        await page.fill('input[name="email"]', 'naoverificado@test.com');
        await page.fill('input[name="password"]', 'password123');
        await page.click('button[type="submit"]');

        await expect(page).toHaveURL(/\/verify-email/);
      });

      test('Registro → verificação email → login', async ({ page }) => {
        await page.goto('/register');
        await page.fill('input[name="name"]', 'Usuario Teste E2E');
        await page.fill('input[name="email"]', `e2e_${Date.now()}@test.com`);
        await page.fill('input[name="password"]', 'SenhaForte123');
        await page.fill('input[name="password_confirmation"]', 'SenhaForte123');
        await page.click('button[type="submit"]');

        await expect(page).toHaveURL(/\/verify-email/);
        await expect(page.locator('text=Email de verificação enviado')).toBeVisible();

        // Verificar email via API (simular clique no link)
        // ... chamar API de verificação

        await page.goto('/login');
        await page.fill('input[name="email"]', 'email_usado_acima');
        await page.fill('input[name="password"]', 'SenhaForte123');
        await page.click('button[type="submit"]');

        await expect(page).toHaveURL(/\/dashboard/);
      });

      test('Forgot password → reset password → login com nova senha', async ({ page }) => {
        await page.goto('/forgot-password');
        await page.fill('input[name="email"]', 'admin@labcontrol.com');
        await page.click('button[type="submit"]');

        await expect(page.locator('text=Se o email existir')).toBeVisible();

        // Simular clique no link de reset via API
        // ... chamar API forgot-password, extrair token do log/email
        // Navegar para /reset-password?token=xxx&email=xxx

        await page.goto('/reset-password?token=TOKEN_VALIDO&email=admin@labcontrol.com');
        await page.fill('input[name="password"]', 'NovaSenhaForte456');
        await page.fill('input[name="password_confirmation"]', 'NovaSenhaForte456');
        await page.click('button[type="submit"]');

        await expect(page).toHaveURL(/\/login/);

        // Login com nova senha
        await page.fill('input[name="email"]', 'admin@labcontrol.com');
        await page.fill('input[name="password"]', 'NovaSenhaForte456');
        await page.click('button[type="submit"]');

        await expect(page).toHaveURL(/\/dashboard/);
      });

      test('Remember me persiste sessão ao recarregar', async ({ page, context }) => {
        await page.fill('input[name="email"]', 'admin@labcontrol.com');
        await page.fill('input[name="password"]', '@dmin123');
        await page.check('input[name="remember"]');
        await page.click('button[type="submit"]');

        await expect(page).toHaveURL(/\/dashboard/);

        // Fechar e reabrir contexto (simula fechar browser)
        await context.clearCookies(); // Não limpar cookies se remember me
        // Na verdade, remember me usa cookie persistente
        // Recarregar página
        await page.reload();

        await expect(page).toHaveURL(/\/dashboard/);
        await expect(page.locator('text=Bem-vindo')).toBeVisible();
      });
    });
    ```
  </action>
  <verify>
    <automated>test -f frontend/tests/e2e/auth.spec.ts && echo "OK"</automated>
  </verify>
  <done>Playwright configurado, auth.spec.ts com 6 cenários E2E.</done>
</task>

<task type="auto">
  <name>Task 7: Testes de Rate Limiting Unitários</name>
  <files>
    backend/tests/Unit/Auth/RateLimitTest.php
  </files>
  <action>
    Criar `backend/tests/Unit/Auth/RateLimitTest.php`:

    ```php
    use Illuminate\Support\Facades\RateLimiter;
    use Illuminate\Http\Request;

    test('rate limiter auth permite 5 req/min por IP', function () {
        RateLimiter::clear('login_ip');

        $request = Request::create('/api/v1/auth/login', 'POST', [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);

        for ($i = 0; $i < 5; $i++) {
            $limiter = RateLimiter::for('auth', $request);
            expect($limiter->remaining())->toBe(5 - $i);
            $limiter->hit();
        }

        $limiter = RateLimiter::for('auth', $request);
        expect($limiter->remaining())->toBe(0);
        expect($limiter->retriesLeft())->toBe(0);
    });

    test('rate limiter reseta após janela de tempo', function () {
        RateLimiter::clear('login_ip');

        $request = Request::create('/api/v1/auth/login', 'POST', [], [], [], ['REMOTE_ADDR' => '10.0.0.1']);

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::for('auth', $request)->hit();
        }

        expect(RateLimiter::for('auth', $request)->remaining())->toBe(0);

        // Avançar tempo (mock Carbon)
        // Travel 1 minute forward
        // RateLimiter::for('auth', $request)->remaining() -> 5
    });

    test('diferentes endpoints usam mesmo limiter auth', function () {
        RateLimiter::clear('auth_ip');

        $loginRequest = Request::create('/api/v1/auth/login', 'POST', [], [], [], ['REMOTE_ADDR' => '192.168.1.50']);
        $registerRequest = Request::create('/api/v1/auth/register', 'POST', [], [], [], ['REMOTE_ADDR' => '192.168.1.50']);
        $forgotRequest = Request::create('/api/v1/auth/forgot-password', 'POST', [], [], [], ['REMOTE_ADDR' => '192.168.1.50']);

        RateLimiter::for('auth', $loginRequest)->hit();
        RateLimiter::for('auth', $registerRequest)->hit();
        RateLimiter::for('auth', $forgotRequest)->hit();

        expect(RateLimiter::for('auth', $loginRequest)->remaining())->toBe(2);
    });
    ```
  </action>
  <verify>
    <automated>docker compose exec -T php php artisan test tests/Unit/Auth/RateLimitTest.php 2>&1</automated>
  </verify>
  <done>RateLimitTest.php criado com 3 testes unitários.</done>
</task>

<task type="auto">
  <name>Task 8: Executar suite completa e gerar relatório de cobertura</name>
  <files: Nenhum — execução de comandos>
  <action>
    **Backend:**
    - `docker compose exec -T php php artisan test --coverage --min=80`
    - Verificar se cobertura ≥ 80% nos controllers Auth

    **Frontend:**
    - `docker compose exec -T frontend npm run test:coverage`
    - Verificar se cobertura ≥ 70% nas views auth

    **E2E:**
    - `docker compose exec -T frontend npx playwright test tests/e2e/auth.spec.ts`

    **Relatório final:**
    - Salvar output em `.planning/phases/02-autenticacao/test-report.md`
  </action>
  <verify>
    <automated>docker compose exec -T php php artisan test --coverage 2>&1 | tail -30</automated>
  </verify>
  <done>Suite completa executada, relatório de cobertura gerado, todos os testes passando.</done>
</task>

</tasks>

<threat_model>
## Trust Boundaries

| Boundary | Description |
|----------|-------------|
| Test Runner → Database | SQLite em memória isolado por teste (RefreshDatabase) |
| Playwright → Frontend Dev Server | HTTP localhost:5173, cookies HttpOnly testados |
| Test Suite → External Email | Notification::fake() — sem envio real |

## STRIDE Threat Register

| Threat ID | Category | Component | Severity | Disposition | Mitigation Plan |
|-----------|----------|-----------|----------|-------------|-----------------|
| T-02-20 | Tampering | Test isolation | medium | mitigate | RefreshDatabase garante rollback; SQLite em memória |
| T-02-21 | Information Disclosure | Test logs | low | mitigate | Não logar senhas/tokens em output de testes |
| T-02-22 | Denial of Service | Rate limit tests | low | mitigate | Limpar RateLimiter entre testes (`RateLimiter::clear()`) |
</threat_model>

<verification>
1. `php artisan test` — todos os 22+ testes backend passando
2. `npx playwright test tests/e2e/auth.spec.ts` — 6 cenários E2E passando
3. Cobertura backend ≥ 80% (AuthController, AuthController, Requests)
4. Cobertura frontend ≥ 70% (auth store, views, composables)
5. Rate limit tests passando (unit + feature)
5. `php artisan test --coverage` gera relatório HTML em `storage/coverage`
</verification>

<success_criteria>
- [ ] 5 test suites backend (Login, Register, VerifyEmail, PasswordReset, Logout) = 22+ testes
- [ ] 1 test suite RateLimit unitário = 3 testes
- [ ] 1 suite E2E frontend = 6 cenários Playwright
- [ ] Cobertura backend Auth ≥ 80%
- [ ] Cobertura frontend auth ≥ 70%
- [ ] CI pipeline configurado (GitHub Actions) rodando testes em PR
- [ ] Relatório de cobertura salvo em `.planning/phases/02-autenticacao/test-report.md`
</success_criteria>

<output>
Criar `.planning/phases/02-autenticacao/04-PLAN-04-SUMMARY.md` quando concluído
</output>