<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_with_valid_credentials_returns_200_and_user(): void
    {
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
            ->assertJsonStructure(['user' => ['id', 'name', 'email']]);
    }

    public function test_login_with_wrong_password_returns_422(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_with_unverified_email_returns_403(): void
    {
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
            ->assertJson(['message' => 'Email não verificado.']);
    }

    public function test_remember_me_generates_remember_token(): void
    {
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
        $this->assertNotNull($user->remember_token);
    }
}
