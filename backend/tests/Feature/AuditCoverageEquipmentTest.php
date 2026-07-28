<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Manufacturer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditCoverageEquipmentTest extends TestCase
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

    public function test_equipment_creation_logs_activity(): void
    {
        $category = Category::factory()->create();
        $manufacturer = Manufacturer::factory()->create();

        $this->postJson('/api/v1/equipments', [
            'name' => 'Termômetro Digital Teste',
            'serial_number' => 'SN-AUDIT-001',
            'category_id' => $category->id,
            'manufacturer_id' => $manufacturer->id,
            'location' => 'Laboratório de Auditoria',
            'status' => 'active',
        ])->assertStatus(201);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'module' => 'Equipment',
        ]);
    }

    public function test_equipment_update_logs_activity(): void
    {
        $equipment = Equipment::factory()->create();

        $this->putJson("/api/v1/equipments/{$equipment->id}", [
            'name' => 'Nome Atualizado pela Auditoria',
        ])->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'module' => 'Equipment',
        ]);
    }

    public function test_equipment_deletion_logs_activity(): void
    {
        $equipment = Equipment::factory()->create();

        $this->deleteJson("/api/v1/equipments/{$equipment->id}")->assertStatus(204);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'deleted',
            'module' => 'Equipment',
        ]);
    }
}
