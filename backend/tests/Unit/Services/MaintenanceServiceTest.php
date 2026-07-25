<?php

namespace Tests\Unit\Services;

use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Exceptions\MaintenanceException;
use App\Models\Equipment;
use App\Models\InventoryItem;
use App\Models\MaintenanceOrder;
use App\Models\MaintenanceOrderPart;
use App\Models\Role;
use App\Models\User;
use App\Services\MaintenanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private MaintenanceService $service;
    private User $user;
    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('slug', 'admin')->value('id'));

        $this->actingAs($this->user);

        $this->service = app(MaintenanceService::class);

        $this->equipment = Equipment::factory()->create();
    }

    public function test_can_create_order(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Corrective->value,
            'priority' => 'high',
            'description' => 'Testar criação de ordem de manutenção.',
        ]);

        $this->assertDatabaseHas('maintenance_orders', [
            'id' => $order->id,
            'equipment_id' => $this->equipment->id,
            'status' => MaintenanceStatus::Open->value,
            'priority' => 'high',
            'type' => MaintenanceType::Corrective->value,
        ]);

        $this->assertEquals(MaintenanceStatus::Open, $order->status);
        $this->assertEquals($this->user->id, $order->opened_by);
        $this->assertEquals($this->user->id, $order->created_by);
    }

    public function test_cannot_create_order_without_equipment(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Equipment_id is required - foreign key violation when non-existent UUID provided
        // Using a fake UUID that doesn't exist in equipments table
        $this->service->create([
            'equipment_id' => '00000000-0000-0000-0000-000000000000',
            'type' => MaintenanceType::Corrective->value,
            'description' => 'Ordem sem equipamento válido.',
        ]);
    }

    public function test_can_assign_technician_and_transition_to_in_progress(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Corrective->value,
            'description' => 'Ordem para designar técnico.',
        ]);

        $this->assertEquals(MaintenanceStatus::Open, $order->status);

        $technician = User::factory()->create();

        $updated = $this->service->assign(['assigned_to' => $technician->id], $order);

        $this->assertEquals($technician->id, $updated->assigned_to);
        $this->assertEquals(MaintenanceStatus::InProgress, $updated->status);
    }

    public function test_can_complete_in_progress_order(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Corrective->value,
            'description' => 'Ordem para concluir.',
        ]);

        // Transition to in_progress first by assigning
        $technician = User::factory()->create();
        $this->service->assign(['assigned_to' => $technician->id], $order);

        $completed = $this->service->complete($order->fresh(), [
            'resolution' => 'Reparo concluído com sucesso.',
            'time_spent' => 2.5,
            'cost' => 350.00,
            'completed_at' => now()->toDateTimeString(),
        ]);

        $this->assertEquals(MaintenanceStatus::Completed, $completed->status);
        $this->assertNotNull($completed->completed_at);
        $this->assertEquals('Reparo concluído com sucesso.', $completed->resolution);
        $this->assertEquals(2.5, (float) $completed->time_spent);
        $this->assertEquals(350.00, (float) $completed->cost);
    }

    public function test_cannot_complete_open_order(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Corrective->value,
            'description' => 'Ordem que não pode ser concluída diretamente.',
        ]);

        $this->expectException(MaintenanceException::class);
        $this->expectExceptionMessage('Apenas ordens em andamento podem ser concluídas.');

        $this->service->complete($order, [
            'resolution' => 'Tentando concluir ordem aberta.',
        ]);
    }

    public function test_complete_preventive_calculates_next_due(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Preventive->value,
            'description' => 'Manutenção preventiva com intervalo.',
            'interval_value' => 6,
            'interval_unit' => 'months',
        ]);

        $technician = User::factory()->create();
        $this->service->assign(['assigned_to' => $technician->id], $order);

        $completedAt = Carbon::parse('2026-01-15 10:00:00');

        $completed = $this->service->complete($order->fresh(), [
            'completed_at' => $completedAt->toDateTimeString(),
            'resolution' => 'Preventiva concluída.',
        ]);

        $this->assertEquals(MaintenanceStatus::Completed, $completed->status);
        $this->assertNotNull($completed->next_due_at);

        $expectedNextDue = $completedAt->copy()->addMonths(6);
        $this->assertEquals(
            $expectedNextDue->toDateTimeString(),
            $completed->next_due_at->toDateTimeString()
        );
    }

    public function test_complete_preventive_auto_creates_next_order(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Preventive->value,
            'description' => 'Preventiva que gerará próxima ordem.',
            'interval_value' => 6,
            'interval_unit' => 'months',
        ]);

        $technician = User::factory()->create();
        $this->service->assign(['assigned_to' => $technician->id], $order);

        $completedAt = Carbon::parse('2026-01-15 10:00:00');

        $this->service->complete($order->fresh(), [
            'completed_at' => $completedAt->toDateTimeString(),
            'resolution' => 'Concluída.',
        ]);

        // Assert a new order was auto-created with scheduled_date = completed_at + 6 months
        $nextOrder = MaintenanceOrder::where('equipment_id', $this->equipment->id)
            ->where('type', MaintenanceType::Preventive->value)
            ->where('status', MaintenanceStatus::Open->value)
            ->where('description', 'Manutenção preventiva recorrente — gerada automaticamente.')
            ->first();

        $this->assertNotNull($nextOrder, 'Next preventive order should have been auto-created.');

        $expectedDue = $completedAt->copy()->addMonths(6);
        $this->assertEquals(
            $expectedDue->toDateTimeString(),
            $nextOrder->scheduled_date->toDateTimeString()
        );
    }

    public function test_complete_corrective_does_not_create_next_order(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Corrective->value,
            'description' => 'Corretiva sem geração de próxima ordem.',
        ]);

        $technician = User::factory()->create();
        $this->service->assign(['assigned_to' => $technician->id], $order);

        $this->service->complete($order->fresh(), [
            'resolution' => 'Concluída.',
        ]);

        // Assert no auto-created order exists
        $nextOrder = MaintenanceOrder::where('equipment_id', $this->equipment->id)
            ->where('type', MaintenanceType::Preventive->value)
            ->first();

        $this->assertNull($nextOrder, 'Corrective maintenance should NOT auto-create a next order.');
    }

    public function test_can_cancel_open_order(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Corrective->value,
            'description' => 'Ordem a ser cancelada.',
        ]);

        $cancelled = $this->service->cancel($order, 'Motivo: peças não disponíveis.');

        $this->assertEquals(MaintenanceStatus::Cancelled, $cancelled->status);
        $this->assertEquals('Motivo: peças não disponíveis.', $cancelled->notes);
    }

    public function test_cannot_cancel_completed_order(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Corrective->value,
            'description' => 'Ordem que não pode ser cancelada após concluída.',
        ]);

        $technician = User::factory()->create();
        $this->service->assign(['assigned_to' => $technician->id], $order);

        $this->service->complete($order->fresh(), [
            'resolution' => 'Concluída.',
        ]);

        $this->expectException(MaintenanceException::class);
        $this->expectExceptionMessage('Apenas ordens abertas ou em andamento podem ser canceladas.');

        $this->service->cancel($order->fresh(), 'Tentando cancelar ordem concluída.');
    }

    public function test_can_attach_parts_on_complete(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Corrective->value,
            'description' => 'Ordem com peças.',
        ]);

        $technician = User::factory()->create();
        $this->service->assign(['assigned_to' => $technician->id], $order);

        $inventoryItem = InventoryItem::factory()->create();

        $completed = $this->service->complete($order->fresh(), [
            'resolution' => 'Concluída com peças.',
            'parts' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => 2,
                    'unit_cost' => 25.50,
                ],
            ],
        ]);

        $this->assertEquals(MaintenanceStatus::Completed, $completed->status);

        // Verify parts were attached
        $this->assertDatabaseHas('maintenance_order_parts', [
            'maintenance_order_id' => $order->id,
            'inventory_item_id' => $inventoryItem->id,
            'quantity' => 2.0000,
        ]);

        $this->assertCount(1, $completed->parts);
        $this->assertEquals($inventoryItem->id, $completed->parts->first()->inventory_item_id);
    }

    public function test_can_cancel_in_progress_order(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Corrective->value,
            'description' => 'Ordem em andamento a ser cancelada.',
        ]);

        $technician = User::factory()->create();
        $this->service->assign(['assigned_to' => $technician->id], $order);

        $cancelled = $this->service->cancel($order->fresh(), 'Cancelamento durante execução.');

        $this->assertEquals(MaintenanceStatus::Cancelled, $cancelled->status);
    }

    public function test_cannot_cancel_cancelled_order(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Corrective->value,
            'description' => 'Ordem já cancelada.',
        ]);

        $this->service->cancel($order, 'Cancelando.');

        $this->expectException(MaintenanceException::class);

        $this->service->cancel($order->fresh(), 'Tentando cancelar novamente.');
    }

    public function test_get_history_by_equipment(): void
    {
        // Create multiple orders for the same equipment
        MaintenanceOrder::factory()
            ->count(5)
            ->create([
                'equipment_id' => $this->equipment->id,
            ]);

        $result = $this->service->getHistoryByEquipment($this->equipment->id, 15);

        $this->assertCount(5, $result->items());
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);

        // Assert they are sorted newest first
        $dates = collect($result->items())->pluck('created_at')->toArray();
        for ($i = 0; $i < count($dates) - 1; $i++) {
            $this->assertGreaterThanOrEqual($dates[$i + 1], $dates[$i]);
        }
    }

    public function test_cannot_update_completed_order(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Corrective->value,
            'description' => 'Ordem que será concluída.',
        ]);

        $technician = User::factory()->create();
        $this->service->assign(['assigned_to' => $technician->id], $order);

        $this->service->complete($order->fresh(), [
            'resolution' => 'Concluída.',
        ]);

        $this->expectException(MaintenanceException::class);

        $this->service->update($order->fresh(), [
            'description' => 'Tentativa de editar ordem concluída.',
        ]);
    }

    public function test_cannot_update_cancelled_order(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Corrective->value,
            'description' => 'Ordem que será cancelada.',
        ]);

        $this->service->cancel($order, 'Cancelando.');

        $this->expectException(MaintenanceException::class);

        $this->service->update($order->fresh(), [
            'description' => 'Tentativa de editar ordem cancelada.',
        ]);
    }

    public function test_can_update_open_order(): void
    {
        $order = $this->service->create([
            'equipment_id' => $this->equipment->id,
            'type' => MaintenanceType::Corrective->value,
            'description' => 'Ordem original.',
        ]);

        $updated = $this->service->update($order, [
            'description' => 'Descrição atualizada.',
            'priority' => 'critical',
        ]);

        $this->assertEquals('Descrição atualizada.', $updated->description);
        $this->assertEquals('critical', $updated->priority->value);
    }
}
