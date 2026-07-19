<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_valid_verification_link_marks_email_as_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $url = URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $response = $this->post($url);

        $response->assertStatus(200);
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_invalid_hash_returns_403(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $url = URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => 'hash_invalido',
        ]);

        $response = $this->post($url);
        $response->assertStatus(403);
    }

    public function test_already_verified_user_returns_200(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $url = URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $response = $this->post($url);
        $response->assertStatus(200);
    }

    public function test_resend_verification_sends_new_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        $response = $this->postJson('/api/v1/auth/email/verification-notification');

        $response->assertStatus(200);
        Notification::assertSentTo($user, \App\Notifications\VerifyEmail::class);
    }
}
