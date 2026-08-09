<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\LogoutRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

/**
 * @OA\Tag(name="Auth", description="Endpoints de autenticação")
 */
class AuthController
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {}

    /**
     * Rate limit key prefix for login attempts.
     */
    private const LOGIN_RATE_LIMIT_KEY = 'login:';

    /**
     * Maximum login attempts per minute per IP.
     */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Rate limit decay time in seconds (1 minute).
     */
    private const LOGIN_DECAY_SECONDS = 60;

    /**
     * Realiza login do usuário.
     *
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Login do usuário",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@labcontrol.com"),
     *             @OA\Property(property="password", type="string", format="password", example="@dmin123"),
     *             @OA\Property(property="remember", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="message", type="string", example="Autenticado com sucesso.")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Credenciais inválidas"),
     *     @OA\Response(response=403, description="Email não verificado"),
     *     @OA\Response(response=429, description="Muitas tentativas. Aguarde 1 minuto.")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $key = self::LOGIN_RATE_LIMIT_KEY . $request->ip();

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            // Only failed attempts count toward the limit, and the check runs
            // after the attempt so a successful login can never be blocked
            // (it clears the counter instead — see below).
            if (RateLimiter::tooManyAttempts($key, self::MAX_LOGIN_ATTEMPTS)) {
                $this->activityLogService->logAuth('login_rate_limited', $request->email);
                return response()->json([
                    'message' => 'Muitas tentativas. Aguarde 1 minuto.',
                ], 429);
            }

            // Increment rate limiter on failed attempt
            RateLimiter::hit($key, self::LOGIN_DECAY_SECONDS);
            $this->activityLogService->logAuth('login_failed', $request->email);
            return response()->json(['message' => 'Credenciais inválidas.'], 422);
        }

        $user = Auth::user();

        if ($user->email_verified_at === null) {
            Auth::logout();
            $this->activityLogService->logAuth('login_unverified', $request->email);
            return response()->json(['message' => 'Email não verificado.'], 403);
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($key);

        $request->session()->regenerate();

        $this->activityLogService->logAuth('login', $request->email);

        return response()->json([
            'user' => $user->load('roles.permissions'),
            'message' => 'Autenticado com sucesso.',
        ], 200);
    }

    /**
     * Registra novo usuário.
     *
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Registrar novo usuário",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="João Silva"),
     *             @OA\Property(property="email", type="string", format="email", example="joao@labcontrol.com"),
     *             @OA\Property(property="password", type="string", format="password", example="senha123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="senha123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Conta criada, email de verificação enviado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        $consultaRole = Role::where('slug', 'consulta')->first();
        if ($consultaRole) {
            $user->roles()->attach($consultaRole->id);
        }

        $user->sendEmailVerificationNotification();

        $this->activityLogService->logAuth('register', $data['email'], ['user_id' => $user->id]);

        return response()->json([
            'user' => $user->fresh()->load('roles.permissions'),
            'message' => 'Conta criada. Email de verificação enviado.',
        ], 201);
    }

    /**
     * Verifica email do usuário.
     *
     * @OA\Get(
     *     path="/api/v1/auth/verify-email/{id}/{hash}",
     *     summary="Verificar email",
     *     tags={"Auth"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="hash", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Email verificado com sucesso"),
     *     @OA\Response(response=403, description="Link de verificação inválido ou email já verificado")
     * )
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $user = User::findOrFail($request->route('id'));

        if (!hash_equals(sha1($user->getEmailForVerification()), $request->route('hash'))) {
            return response()->json(['message' => 'Link de verificação inválido.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email já verificado.'], 200);
        }

        $user->markEmailAsVerified();

        $this->activityLogService->logAuth('email_verified', $user->email);

        return response()->json(['message' => 'Email verificado com sucesso.'], 200);
    }

    /**
     * Reenvia email de verificação.
     *
     * @OA\Post(
     *     path="/api/v1/auth/email/verification-notification",
     *     summary="Reenviar email de verificação",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Link de verificação reenviado"),
     *     @OA\Response(response=401, description="Não autenticado")
     * )
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Link de verificação reenviado.'], 200);
    }

    /**
     * Solicita reset de senha.
     *
     * @OA\Post(
     *     path="/api/v1/auth/forgot-password",
     *     summary="Solicitar reset de senha",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@labcontrol.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Se o email existir, enviaremos instruções"),
     *     @OA\Response(response=422, description="Email inválido")
     * )
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::broker()->sendResetLink($request->only('email'));

        $this->activityLogService->logAuth('password_reset_requested', $request->email);

        return response()->json([
            'message' => 'Se o email existir, enviaremos instruções de redefinição.',
        ], 200);
    }

    /**
     * Redefine senha do usuário.
     *
     * @OA\Post(
     *     path="/api/v1/auth/reset-password",
     *     summary="Redefinir senha",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token","email","password","password_confirmation"},
     *             @OA\Property(property="token", type="string", example="reset-token-here"),
     *             @OA\Property(property="email", type="string", format="email", example="admin@labcontrol.com"),
     *             @OA\Property(property="password", type="string", format="password", example="novaSenha123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="novaSenha123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Senha redefinida com sucesso"),
     *     @OA\Response(response=422, description="Token inválido ou expirado")
     * )
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $status = Password::broker()->reset(
            $validatedData,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $user->remember_token = null;
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $this->activityLogService->logAuth('password_reset', $validatedData['email']);
            return response()->json(['message' => 'Senha redefinida com sucesso.'], 200);
        }

        return response()->json([
            'message' => 'Token inválido ou expirado.',
            'errors' => ['token' => ['Token inválido ou expirado.']],
        ], 422);
    }

    /**
     * Realiza logout do usuário.
     *
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Logout do usuário",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="current_password", type="string", format="password", example="senha123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Deslogado com sucesso"),
     *     @OA\Response(response=401, description="Não autenticado"),
     *     @OA\Response(response=422, description="Senha atual incorreta")
     * )
     */
    public function logout(LogoutRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($request->filled('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Senha atual incorreta.',
                    'errors' => ['current_password' => ['Senha atual incorreta.']],
                ], 422);
            }
            $user->tokens()->delete();
            $user->remember_token = null;
            $user->save();
        }

        if ($token = $user->currentAccessToken()) {
            if (!($token instanceof \Laravel\Sanctum\TransientToken)) {
                $token->delete();
            }
        }

        $this->activityLogService->logAuth('logout', $user->email);

        auth('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Deslogado com sucesso.'], 200);
    }

    /**
     * Retorna usuário autenticado.
     *
     * @OA\Get(
     *     path="/api/v1/auth/user",
     *     summary="Obter usuário autenticado",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dados do usuário",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Não autenticado")
     * )
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load('roles.permissions')
        );
    }
}
