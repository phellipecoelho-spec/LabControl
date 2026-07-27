<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Role;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('slug', 'admin')->value('id'));

        $this->actingAs($this->user);
        $this->service = app(DashboardService::class);
    }

    public function test_kpis_return_correct_values(): void
    {
        Equipment::factory(5)->create();

        $result = $this->service->aggregate(now()->subYear(), now());

        $this->assertEquals(5, $result['kpis']['total_equipments']);
        $this->assertIsInt($result['kpis']['calibrations_due_soon']);
        $this->assertIsInt($result['kpis']['active_loans']);
        $this->assertIsInt($result['kpis']['pending_verifications_today']);
        $this->assertIsInt($result['kpis']['open_maintenance_orders']);
    }

    public function test_equipments_by_category_returns_expected_structure(): void
    {
        $category = Category::factory()->create(['name' => 'Balanças']);
        Equipment::factory(2)->create(['category_id' => $category->id]);

        $result = $this->service->aggregate(now()->subYear(), now());

        $this->assertCount(1, $result['charts']['equipments_by_category']);
        $this->assertEquals('Balanças', $result['charts']['equipments_by_category'][0]['name']);
        $this->assertEquals(2, $result['charts']['equipments_by_category'][0]['value']);
    }

    public function test_cache_is_used_on_subsequent_calls(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with(\Mockery::type('string'), 300, \Mockery::type('Closure'))
            ->andReturn(['kpis' => [], 'charts' => []]);

        $this->service->aggregate(now()->subYear(), now());
    }
}
