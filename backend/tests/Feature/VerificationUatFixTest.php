<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\Manufacturer;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Verification;
use App\Models\VerificationParam;
use App\Models\VerificationTemplate;
use App\Notifications\ToleranceExceeded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class VerificationUatFixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'email' => 'admin@test.com',
            'email_verified_at' => now(),
        ]);
    }

    private function createEquipmentWithTemplate(): array
    {
        $admin = $this->createAdminUser();
        
        $category = EquipmentCategory::factory()->create([
            'name' => 'Multímetro',
            'verification_frequency' => 'daily',
        ]);

        $manufacturer = Manufacturer::factory()->create(['name' => 'Fluke']);
        $supplier = Supplier::factory()->create(['name' => 'Supplier Test']);

        $equipment = Equipment::factory()->create([
            'name' => 'Multímetro Digital',
            'patrimony_id' => 'MULT-001',
            'category_id' => $category->id,
            'manufacturer_id' => $manufacturer->id,
            'supplier_id' => $supplier->id,
            'verification_frequency' => 'daily',
        ]);

        $template = VerificationTemplate::factory()->create([
            'equipment_category_id' => $category->id,
            'name' => 'Tensão DC',
            'parameter' => 'voltage_dc',
            'unit' => 'V',
            'tolerance_min' => '4.9',
            'tolerance_max' => '5.1',
        ]);

        return [$admin, $equipment, $template];
    }

    public function test_can_create_verification(): void
    {
        [$admin, $equipment, $template] = $this->createEquipmentWithTemplate();

        $response = $this->actingAs($admin, 'sanctum')
            ->withHeader('Accept', 'application/json')
            ->postJson('/api/v1/verifications', [
                'equipment_id' => $equipment->id,
                'verified_at' => now()->toISOString(),
                'notes' => 'Teste de aferição',
                'params' => [
                    $template->id => '5.0',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'equipment_id',
                    'verified_at',
                    'notes',
                    'params',
                ],
            ]);

        $this->assertDatabaseHas('verifications', [
            'equipment_id' => $equipment->id,
            'notes' => 'Teste de aferição',
        ]);
    }

    public function test_verification_within_tolerance_passes(): void
    {
        [$admin, $equipment, $template] = $this->createEquipmentWithTemplate();
        // Template has tolerance_min=4.9, tolerance_max=5.1
        // Value 5.0 is within range

        $response = $this->actingAs($admin, 'sanctum')
            ->withHeader('Accept', 'application/json')
            ->postJson('/api/v1/verifications', [
                'equipment_id' => $equipment->id,
                'verified_at' => now()->toISOString(),
                'params' => [
                    $template->id => '5.0',
                ],
            ]);

        $response->assertStatus(201);

        $verification = Verification::latest()->first();
        $this->assertNotNull($verification);

        // Check that the param result is WithinRange
        $param = $verification->params->first();
        $this->assertEquals('within_range', $param->result->value);
    }

    public function test_verification_exceeds_tolerance_fails(): void
    {
        [$admin, $equipment, $template] = $this->createEquipmentWithTemplate();
        // Template has tolerance_min=4.9, tolerance_max=5.1
        // Value 6.0 is outside range (above max)

        $response = $this->actingAs($admin, 'sanctum')
            ->withHeader('Accept', 'application/json')
            ->postJson('/api/v1/verifications', [
                'equipment_id' => $equipment->id,
                'verified_at' => now()->toISOString(),
                'params' => [
                    $template->id => '6.0',
                ],
            ]);

        $response->assertStatus(201);

        $verification = Verification::latest()->first();
        $param = $verification->params->first();
        $this->assertEquals('outside_range', $param->result->value);
    }

    public function test_tolerance_exceeded_notification_is_sent(): void
    {
        Notification::fake();

        [$admin, $equipment, $template] = $this->createEquipmentWithTemplate();

        // Create a supervisor user with afericoes.edit permission
        $supervisor = User::factory()->create([
            'email' => 'supervisor@test.com',
            'email_verified_at' => now(),
        ]);
        $supervisor->assignRole('supervisor');

        // Value outside range should trigger notification
        $response = $this->actingAs($admin, 'sanctum')
            ->withHeader('Accept', 'application/json')
            ->postJson('/api/v1/verifications', [
                'equipment_id' => $equipment->id,
                'verified_at' => now()->toISOString(),
                'params' => [
                    $template->id => '6.0', // Outside range
                ],
            ]);

        $response->assertStatus(201);

        // Verify ToleranceExceeded notification was sent to operator (admin)
        Notification::assertSentTo($admin, ToleranceExceeded::class);

        // Verify ToleranceExceeded notification was sent to supervisors
        Notification::assertSentTo($supervisor, ToleranceExceeded::class);
    }

    public function test_verification_history_by_equipment(): void
    {
        [$admin, $equipment, $template] = $this->createEquipmentWithTemplate();

        // Create multiple verifications
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($admin, 'sanctum')
                ->withHeader('Accept', 'application/json')
                ->postJson('/api/v1/verifications', [
                    'equipment_id' => $equipment->id,
                    'verified_at' => now()->subDays($i)->toISOString(),
                    'params' => [
                        $template->id => '5.0',
                    ],
                ]);
        }

        // Get history by equipment
        $response = $this->actingAs($admin, 'sanctum')
            ->withHeader('Accept', 'application/json')
            ->getJson("/api/v1/verifications/by-equipment/{$equipment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'equipment_id',
                        'verified_at',
                        'params',
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }
}