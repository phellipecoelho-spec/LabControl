<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_creation_logs_activity(): void
    {
        $admin = User::factory()->create();
        $role = Role::where('slug', 'admin')->first();
        $admin->roles()->attach($role);

        Sanctum::actingAs($admin, ['*']);

        $this->postJson('/api/v1/users', [
            'name' => 'Novo Usuário',
            'email' => 'novo@example.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
        ])->assertStatus(201);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'module' => 'User',
        ]);
    }

    public function test_user_update_logs_activity(): void
    {
        $admin = User::factory()->create();
        $role = Role::where('slug', 'admin')->first();
        $admin->roles()->attach($role);

        Sanctum::actingAs($admin, ['*']);

        $user = User::factory()->create();

        $this->putJson("/api/v1/users/{$user->id}", [
            'name' => 'Nome Atualizado',
        ])->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'module' => 'User',
        ]);
    }

    public function test_user_deletion_logs_activity(): void
    {
        $admin = User::factory()->create();
        $role = Role::where('slug', 'admin')->first();
        $admin->roles()->attach($role);

        Sanctum::actingAs($admin, ['*']);

        $user = User::factory()->create();

        $this->deleteJson("/api/v1/users/{$user->id}")->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'deleted',
            'module' => 'User',
        ]);
    }

    public function test_login_logs_activity(): void
    {
        $user = User::factory()->create([
            'email' => 'login-test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'login-test@example.com',
            'password' => 'password123',
        ])->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'login',
            'module' => 'auth',
        ]);
    }

    public function test_failed_login_logs_activity(): void
    {
        User::factory()->create([
            'email' => 'failed-login@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'failed-login@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'login_failed',
            'module' => 'auth',
        ]);
    }

    public function test_logout_logs_activity(): void
    {
        $user = User::factory()->create([
            'email' => 'logout-test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'logout-test@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/v1/auth/logout', [])->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'logout',
            'module' => 'auth',
        ]);
    }

    public function test_activity_log_index_requires_auditoria_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::where('slug', 'consulta')->first();
        $user->roles()->attach($role);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/logs')->assertStatus(403);
    }

    public function test_activity_log_index_filters_by_module(): void
    {
        $admin = User::factory()->create();
        $role = Role::where('slug', 'admin')->first();
        $admin->roles()->attach($role);

        Sanctum::actingAs($admin, ['*']);

        ActivityLog::create([
            'action' => 'created',
            'module' => 'User',
            'table_name' => 'users',
            'new_values' => json_encode(['name' => 'Test']),
        ]);

        ActivityLog::create([
            'action' => 'login',
            'module' => 'auth',
            'new_values' => json_encode(['email' => 'test@example.com']),
        ]);

        $response = $this->getJson('/api/v1/logs?module=auth');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('auth', $data[0]['module']);
    }

    public function test_activity_log_index_filters_by_date_range(): void
    {
        $admin = User::factory()->create();
        $role = Role::where('slug', 'admin')->first();
        $admin->roles()->attach($role);

        Sanctum::actingAs($admin, ['*']);

        ActivityLog::create([
            'action' => 'created',
            'module' => 'User',
            'table_name' => 'users',
            'new_values' => json_encode(['name' => 'Test 1']),
        ]);

        $recentLog = ActivityLog::create([
            'action' => 'login',
            'module' => 'auth',
            'new_values' => json_encode(['email' => 'test@example.com']),
            'created_at' => now()->subDay(),
        ]);

        $recentLog->timestamps = false;
        $recentLog->save();

        $dateFrom = now()->subDays(2)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $response = $this->getJson("/api/v1/logs?date_from={$dateFrom}&date_to={$dateTo}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_activity_log_modules_endpoint(): void
    {
        $admin = User::factory()->create();
        $role = Role::where('slug', 'admin')->first();
        $admin->roles()->attach($role);

        Sanctum::actingAs($admin, ['*']);

        ActivityLog::create([
            'action' => 'created',
            'module' => 'User',
            'table_name' => 'users',
            'new_values' => json_encode(['name' => 'Test']),
        ]);

        ActivityLog::create([
            'action' => 'login',
            'module' => 'auth',
            'new_values' => json_encode(['email' => 'test@example.com']),
        ]);

        $response = $this->getJson('/api/v1/logs/modules/list');

        $response->assertStatus(200);
        $modules = $response->json();
        $this->assertContains('User', $modules);
        $this->assertContains('auth', $modules);
    }
}
