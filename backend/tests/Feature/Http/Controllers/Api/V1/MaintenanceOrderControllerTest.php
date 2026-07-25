<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Models\Equipment;
use App\Models\InventoryItem;
use App\Models\MaintenanceOrder;
use App\Models\MaintenanceOrderPart;
use App\Models\Role;
use App\Models\User;
use App\Notifications\MaintenanceOrderCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MaintenanceOrderControllerTest extends TestCase
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

    public function test_unauthenticated_user_cannot_access_endpoints(): void
    {
        $response = $this->getJson('/api/v1/maintenance-orders');
        $response->assertStatus(401);
    }

    public function test_store_creates_order_with_valid_data(): void
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

    public function test_store_returns_422_when_missing_equipment_id(): void
    {
        $payload = [
            'type' => 'preventive',
            'priority' => 'medium',
            'description' => 'Teste sem equipamento',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/maintenance-orders', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['equipment_id']);
    }

    public function test_store_dispatches_notification_to_supervisors(): void
    {
        Notification::fake();

        // Create a supervisor user with manutencoes.edit permission
        $supervisor = User::factory()->create();
        $supervisorRole = Role::where('slug', 'supervisor')->first();
        if ($supervisorRole) {
            $supervisor->roles()->attach($supervisorRole->id);
        } else {
            // Fallback: attach admin role to supervisor
            $supervisor->roles()->attach(Role::where('slug', 'admin')->value('id'));
        }

        $payload = [
            'equipment_id' => $this->equipment->id,
            'type' => 'corrective',
            'priority' => 'high',
            'description' => 'Manutenção corretiva urgente',
        ];

        $this->actingAs($this->user)->postJson('/api/v1/maintenance-orders', $payload);

        Notification::assertSentTo(
            [$supervisor],
            MaintenanceOrderCreated::class
        );
    }

    public function test_index_returns_paginated_list(): void
    {
        MaintenanceOrder::factory()->count(5)->create([
            'equipment_id' => $this->equipment->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/maintenance-orders');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total', 'per_page']]);
    }

    public function test_index_filters_by_status_and_type(): void
    {
        MaintenanceOrder::factory()->create([
            'equipment_id' => $this->equipment->id,
            'status' => MaintenanceStatus::Open,
            'type' => MaintenanceType::Preventive,
        ]);
        MaintenanceOrder::factory()->create([
            'equipment_id' => $this->equipment->id,
            'status' => MaintenanceStatus::Completed,
            'type' => MaintenanceType::Corrective,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/maintenance-orders?status=open&type=preventive');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_show_returns_order_with_relationships(): void
    {
        $order = MaintenanceOrder::factory()->create([
            'equipment_id' => $this->equipment->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/maintenance-orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonStructure(['data' => ['equipment', 'opened_by', 'created_by']]);
    }

    public function test_complete_transitions_to_completed(): void
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

    public function test_complete_with_parts_attaches_pivot_records(): void
    {
        $order = MaintenanceOrder::factory()->create([
            'equipment_id' => $this->equipment->id,
            'status' => MaintenanceStatus::InProgress,
            'type' => MaintenanceType::Corrective,
        ]);

        $payload = [
            'resolution' => 'Troca concluída',
            'time_spent' => 1.0,
            'parts' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'quantity' => 2,
                    'unit_cost' => 25.50,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/maintenance-orders/{$order->id}/complete", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('maintenance_order_parts', [
            'maintenance_order_id' => $order->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 2,
        ]);
    }

    public function test_cancel_transitions_to_cancelled(): void
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

    public function test_by_equipment_returns_paginated_history(): void
    {
        MaintenanceOrder::factory()->count(3)->create([
            'equipment_id' => $this->equipment->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/equipments/{$this->equipment->id}/maintenance");

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_preventive_complete_auto_creates_next_order(): void
    {
        $order = MaintenanceOrder::factory()->create([
            'equipment_id' => $this->equipment->id,
            'status' => MaintenanceStatus::InProgress,
            'type' => MaintenanceType::Preventive,
            'interval_value' => 6,
            'interval_unit' => 'months',
            'priority' => MaintenancePriority::Medium,
        ]);

        $payload = [
            'resolution' => 'Preventiva concluída',
            'time_spent' => 3.0,
        ];

        $this->actingAs($this->user)
            ->postJson("/api/v1/maintenance-orders/{$order->id}/complete", $payload);

        // Assert the next preventive was auto-created
        $nextOrder = MaintenanceOrder::where('equipment_id', $this->equipment->id)
            ->where('type', MaintenanceType::Preventive->value)
            ->where('status', MaintenanceStatus::Open->value)
            ->where('id', '!=', $order->id)
            ->first();

        $this->assertNotNull($nextOrder, 'Next preventive order was not auto-created');
        $this->assertNotNull($nextOrder->scheduled_date);
    }

    public function test_permission_middleware_blocks_unauthorized(): void
    {
        // Create a user with no permissions
        $unauthorizedUser = User::factory()->create();

        $response = $this->actingAs($unauthorizedUser)
            ->getJson('/api/v1/maintenance-orders');

        $response->assertStatus(403);
    }

    public function test_update_modifies_order(): void
    {
        $order = MaintenanceOrder::factory()->create([
            'equipment_id' => $this->equipment->id,
            'status' => MaintenanceStatus::Open,
            'priority' => MaintenancePriority::Low,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/maintenance-orders/{$order->id}", [
                'priority' => 'high',
                'description' => 'Descrição atualizada',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.priority', 'high');
    }

    public function test_destroy_soft_deletes_order(): void
    {
        $order = MaintenanceOrder::factory()->create([
            'equipment_id' => $this->equipment->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/maintenance-orders/{$order->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted($order);
    }
}
