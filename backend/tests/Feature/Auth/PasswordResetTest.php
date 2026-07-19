<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_forgot_password_sends_reset_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'reset@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Se o email existir, enviaremos instruções de redefinição.']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_with_nonexistent_email_returns_generic_success(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'naoexiste@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Se o email existir, enviaremos instruções de redefinição.']);
    }

    public function test_reset_password_with_valid_token_updates_password(): void
    {
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
        $this->assertTrue(Hash::check('novaSenha123', $user->password));
        $this->assertNull($user->remember_token);
    }

    public function test_reset_password_with_invalid_token_fails(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'token_invalido',
            'email' => 'reset@example.com',
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_password_with_mismatched_passwords_fails(): void
    {
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
    }
}
