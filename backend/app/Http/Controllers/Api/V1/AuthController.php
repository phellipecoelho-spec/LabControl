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

class AuthController
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {}
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            $this->activityLogService->logAuth('login_failed', $request->email);
            return response()->json(['message' => 'Credenciais inválidas.'], 422);
        }

        $user = Auth::user();

        if ($user->email_verified_at === null) {
            Auth::logout();
            $this->activityLogService->logAuth('login_unverified', $request->email);
            return response()->json(['message' => 'Email não verificado.'], 403);
        }

        $request->session()->regenerate();

        $this->activityLogService->logAuth('login', $request->email);

        return response()->json([
            'user' => $user->load('roles.permissions'),
            'message' => 'Autenticado com sucesso.',
        ], 200);
    }

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

    public function resendVerification(Request $request): JsonResponse
    {
        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Link de verificação reenviado.'], 200);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::broker()->sendResetLink($request->only('email'));

        $this->activityLogService->logAuth('password_reset_requested', $request->email);

        return response()->json([
            'message' => 'Se o email existir, enviaremos instruções de redefinição.',
        ], 200);
    }

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

    public function user(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load('roles.permissions')
        );
    }
}
