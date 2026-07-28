<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\MaintenanceOrder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditCoverageMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());
        Sanctum::actingAs($admin, ['*']);
    }

    public function test_maintenance_creation_logs_activity(): void
    {
        $equipment = Equipment::factory()->create();

        $this->postJson('/api/v1/maintenance-orders', [
            'equipment_id' => $equipment->id,
            'type' => 'preventive',
            'priority' => 'medium',
            'description' => 'Manutenção preventiva de auditoria',
            'scheduled_date' => now()->addDays(15)->format('Y-m-d'),
            'interval_value' => 6,
            'interval_unit' => 'months',
        ])->assertStatus(201);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'module' => 'MaintenanceOrder',
        ]);
    }

    public function test_maintenance_update_logs_activity(): void
    {
        $order = MaintenanceOrder::factory()->open()->create();

        $this->putJson("/api/v1/maintenance-orders/{$order->id}", [
            'priority' => 'high',
            'description' => 'Ordem atualizada pela auditoria',
        ])->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'module' => 'MaintenanceOrder',
        ]);
    }

    public function test_maintenance_deletion_logs_activity(): void
    {
        $order = MaintenanceOrder::factory()->open()->create();

        $this->deleteJson("/api/v1/maintenance-orders/{$order->id}")->assertStatus(204);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'deleted',
            'module' => 'MaintenanceOrder',
        ]);
    }

    public function test_maintenance_complete_logs_activity(): void
    {
        $equipment = Equipment::factory()->create();
        $order = MaintenanceOrder::factory()->inProgress()->create([
            'equipment_id' => $equipment->id,
        ]);

        $this->postJson("/api/v1/maintenance-orders/{$order->id}/complete", [
            'completed_at' => now()->format('Y-m-d'),
            'resolution' => 'Manutenção concluída com sucesso.',
            'time_spent' => 4.5,
            'cost' => 350.00,
        ])->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'module' => 'MaintenanceOrder',
        ]);
    }
}
