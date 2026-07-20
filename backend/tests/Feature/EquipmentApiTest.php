<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Manufacturer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentApiTest extends TestCase
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

    public function test_unauthenticated_user_does_not_access_equipment_endpoints(): void
    {
        $response = $this->getJson('/api/v1/equipments');

        $response->assertStatus(401);
    }

    public function test_can_list_equipments(): void
    {
        Equipment::factory(3)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/equipments');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_can_create_equipment(): void
    {
        $category = Category::factory()->create();
        $manufacturer = Manufacturer::factory()->create();

        $payload = [
            'name' => 'Termômetro Digital Teste',
            'serial_number' => 'SN-TEST-001',
            'category_id' => $category->id,
            'manufacturer_id' => $manufacturer->id,
            'location' => 'Laboratório de Testes',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/equipments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Termômetro Digital Teste');
    }

    public function test_can_show_equipment(): void
    {
        $equipment = Equipment::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/v1/equipments/{$equipment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $equipment->id);
    }

    public function test_can_update_equipment(): void
    {
        $equipment = Equipment::factory()->create();

        $response = $this->actingAs($this->user)->putJson("/api/v1/equipments/{$equipment->id}", [
            'name' => 'Nome Atualizado',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nome Atualizado');
    }

    public function test_can_delete_equipment(): void
    {
        $equipment = Equipment::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/equipments/{$equipment->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted($equipment);
    }

    public function test_can_filter_equipments_by_category(): void
    {
        $categoryA = Category::factory()->create(['name' => 'Cat A']);
        $categoryB = Category::factory()->create(['name' => 'Cat B']);

        Equipment::factory()->create(['category_id' => $categoryA->id]);
        Equipment::factory()->create(['category_id' => $categoryB->id]);
        Equipment::factory()->create(['category_id' => $categoryA->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/equipments?category_id={$categoryA->id}");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_search_equipments(): void
    {
        Equipment::factory()->create(['name' => 'Termômetro Digital Teste']);
        Equipment::factory()->create(['name' => 'Paquímetro Universal']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/equipments?search=Termômetro');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Termômetro Digital Teste', $response->json('data.0.name'));
    }
}
