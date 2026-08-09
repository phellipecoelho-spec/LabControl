<?php

namespace Tests\Feature;

use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Models\Equipment;
use App\Models\InventoryItem;
use App\Models\MaintenanceOrder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

class MaintenanceVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Equipment $equipment;
    private InventoryItem $inventoryItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('slug', 'admin')->value('id'));

        $this->equipment = Equipment::factory()->create();
        $this->inventoryItem = InventoryItem::factory()->create();
    }

    public function test_create_maintenance(): void
    {
        $payload = [
            'equipment_id' => $this->equipment->id,
            'type' => 'preventive',
            'priority' => 'medium',
            'description' => 'Manutenção preventiva mensal',
            'scheduled_date' => now()->addDays(7)->toISOString(),
            'interval_value' => 1,
            'interval_unit' => 'months',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/maintenance-orders', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'status', 'type', 'equipment']])
            ->assertJsonPath('data.status', 'open');
    }

    public function test_complete_maintenance(): void
    {
        $order = MaintenanceOrder::factory()->create([
            'equipment_id' => $this->equipment->id,
            'status' => MaintenanceStatus::InProgress,
            'type' => MaintenanceType::Corrective,
        ]);

        $payload = [
            'resolution' => 'Troca do componente danificado',
            'time_spent' => 2.5,
            'cost' => 150.00,
            'completed_at' => now()->toISOString(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/maintenance-orders/{$order->id}/complete", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_cannot_complete_already_completed(): void
    {
        $order = MaintenanceOrder::factory()->create([
            'equipment_id' => $this->equipment->id,
            'status' => MaintenanceStatus::Completed,
            'type' => MaintenanceType::Corrective,
        ]);

        $payload = [
            'resolution' => 'Tentativa de completar novamente',
            'completed_at' => now()->toISOString(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/maintenance-orders/{$order->id}/complete", $payload);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'maintenance_error');
    }

    public function test_cannot_edit_completed_order(): void
    {
        $order = MaintenanceOrder::factory()->create([
            'equipment_id' => $this->equipment->id,
            'status' => MaintenanceStatus::Completed,
        ]);

        $payload = [
            'description' => 'Tentativa de editar ordem concluída',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/maintenance-orders/{$order->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'maintenance_error');
    }

    public function test_maintenance_cancellation(): void
    {
        $order = MaintenanceOrder::factory()->create([
            'equipment_id' => $this->equipment->id,
            'status' => MaintenanceStatus::Open,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/maintenance-orders/{$order->id}/cancel", [
                'reason' => 'Peça não disponível em estoque',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_maintenance_history_by_equipment(): void
    {
        MaintenanceOrder::factory()->count(5)->create([
            'equipment_id' => $this->equipment->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/equipments/{$this->equipment->id}/maintenance");

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total', 'per_page']]);

        $this->assertCount(5, $response->json('data'));
    }
}