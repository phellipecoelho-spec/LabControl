<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ResetPassword;

class ForgotPasswordSentViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_forgot_password_with_existing_email_returns_200_and_sends_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->withHeader('Accept', 'application/json')
            ->postJson('/api/v1/auth/forgot-password', [
                'email' => 'existing@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Se o email existir, enviaremos instruções de redefinição.']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_with_invalid_email_format_returns_422(): void
    {
        $response = $this->withHeader('Accept', 'application/json')
            ->postJson('/api/v1/auth/forgot-password', [
                'email' => 'not-a-valid-email',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_with_nonexistent_email_returns_200_to_prevent_enumeration(): void
    {
        Notification::fake();

        $response = $this->withHeader('Accept', 'application/json')
            ->postJson('/api/v1/auth/forgot-password', [
                'email' => 'nonexistent@example.com',
            ]);

        // Should return 200 to prevent email enumeration
        $response->assertStatus(200)
            ->assertJson(['message' => 'Se o email existir, enviaremos instruções de redefinição.']);

        // No notification should be sent
        Notification::assertNothingSent();
    }
}