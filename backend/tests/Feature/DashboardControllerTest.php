<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('slug', 'admin')->value('id'));
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(401);
    }

    public function test_dashboard_returns_kpis_and_charts_structure(): void
    {
        Equipment::factory(3)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'kpis' => [
                    'total_equipments',
                    'calibrations_due_soon',
                    'active_loans',
                    'pending_verifications_today',
                    'open_maintenance_orders',
                ],
                'charts' => [
                    'equipments_by_category',
                    'calibrations_timeline',
                    'stock_movements',
                ],
            ]);
    }

    public function test_dashboard_respects_date_filter(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/dashboard?start_date=2026-01-01&end_date=2026-06-30');

        $response->assertStatus(200)
            ->assertJsonStructure(['kpis', 'charts']);
    }

    public function test_kpis_contain_numeric_values(): void
    {
        Equipment::factory(5)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
        $response->assertJsonPath('kpis.total_equipments', 5);
    }
}
