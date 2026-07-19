<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_register_creates_user_sends_verification_returns_201(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Novo Usuário',
            'email' => 'novo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user' => ['id', 'name', 'email']]);

        $user = User::where('email', 'novo@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertEquals('Novo Usuário', $user->name);

        $consultaRole = Role::where('slug', 'consulta')->first();
        $this->assertTrue($user->roles->contains($consultaRole));

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_register_with_duplicate_email_returns_422(): void
    {
        User::factory()->create(['email' => 'existente@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Outro',
            'email' => 'existente@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_with_mismatched_passwords_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Teste',
            'email' => 'teste@example.com',
            'password' => 'password123',
            'password_confirmation' => 'diferente',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
