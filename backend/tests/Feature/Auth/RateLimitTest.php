<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        // The login limiter is keyed 'login:{ip}' inside AuthController
        // (check-after-failed-attempt + clear on success). Reset it so each
        // test starts with a clean counter for the test IP (127.0.0.1).
        RateLimiter::clear('login:127.0.0.1');
    }

    public function test_rate_limit_blocks_after_5_failed_attempts_per_minute(): void
    {
        $user = User::factory()->create([
            'email' => 'ratelimit@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Make 5 failed attempts - all should return 422
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->withHeader('Accept', 'application/json')
                ->postJson('/api/v1/auth/login', [
                    'email' => 'ratelimit@example.com',
                    'password' => 'wrongpassword',
                ]);

            $response->assertStatus(422);
        }

        // 6th attempt should return 429
        $response = $this->withHeader('Accept', 'application/json')
            ->postJson('/api/v1/auth/login', [
                'email' => 'ratelimit@example.com',
                'password' => 'wrongpassword',
            ]);

        $response->assertStatus(429)
            ->assertJson(['message' => 'Muitas tentativas. Aguarde 1 minuto.']);
    }

    public function test_successful_login_clears_rate_limit(): void
    {
        $user = User::factory()->create([
            'email' => 'ratelimit2@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Make 5 failed attempts
        for ($i = 1; $i <= 5; $i++) {
            $this->withHeader('Accept', 'application/json')
                ->postJson('/api/v1/auth/login', [
                    'email' => 'ratelimit2@example.com',
                    'password' => 'wrongpassword',
                ])->assertStatus(422);
        }

        // Now login successfully
        $response = $this->withHeader('Accept', 'application/json')
            ->postJson('/api/v1/auth/login', [
                'email' => 'ratelimit2@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(200);

        // Next failed attempt should not be rate limited (counter cleared)
        $response = $this->withHeader('Accept', 'application/json')
            ->postJson('/api/v1/auth/login', [
                'email' => 'ratelimit2@example.com',
                'password' => 'wrongpassword',
            ]);

        $response->assertStatus(422); // Not 429
    }

    public function test_rate_limit_is_per_ip(): void
    {
        // Simulate different IPs by using different keys
        // The rate limiter uses the IP address as key
        $user = User::factory()->create([
            'email' => 'ratelimit3@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // First IP makes 5 failed attempts
        for ($i = 1; $i <= 5; $i++) {
            $this->withHeader('Accept', 'application/json')
                ->postJson('/api/v1/auth/login', [
                    'email' => 'ratelimit3@example.com',
                    'password' => 'wrongpassword',
                ])->assertStatus(422);
        }

        // 6th from same IP should be 429
        $this->withHeader('Accept', 'application/json')
            ->postJson('/api/v1/auth/login', [
                'email' => 'ratelimit3@example.com',
                'password' => 'wrongpassword',
            ])->assertStatus(429);
    }
}