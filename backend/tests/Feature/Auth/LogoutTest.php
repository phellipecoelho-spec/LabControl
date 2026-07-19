<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Deslogado com sucesso.']);
    }

    public function test_logout_without_authentication_returns_401(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');
        $response->assertStatus(401);
    }
}
