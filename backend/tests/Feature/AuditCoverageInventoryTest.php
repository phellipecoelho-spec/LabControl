<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditCoverageInventoryTest extends TestCase
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

    public function test_inventory_creation_logs_activity(): void
    {
        $category = InventoryCategory::factory()->create();
        $supplier = Supplier::factory()->create();

        $this->postJson('/api/v1/inventory-items', [
            'name' => 'Item Auditoria Teste',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'unit' => 'UN',
            'min_stock' => 5,
        ])->assertStatus(201);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'module' => 'InventoryItem',
        ]);
    }

    public function test_inventory_update_logs_activity(): void
    {
        $item = InventoryItem::factory()->create();

        $this->putJson("/api/v1/inventory-items/{$item->id}", [
            'name' => 'Item Atualizado pela Auditoria',
        ])->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'module' => 'InventoryItem',
        ]);
    }

    public function test_inventory_deletion_logs_activity(): void
    {
        $item = InventoryItem::factory()->create();

        $this->deleteJson("/api/v1/inventory-items/{$item->id}")->assertStatus(204);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'deleted',
            'module' => 'InventoryItem',
        ]);
    }
}
