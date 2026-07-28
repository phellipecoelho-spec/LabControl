<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Role;
use App\Models\User;
use App\Models\Verification;
use App\Models\VerificationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditCoverageVerificationTest extends TestCase
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

    public function test_verification_creation_logs_activity(): void
    {
        // Create category, template, and equipment with that category
        $category = Category::factory()->create();
        $template = VerificationTemplate::factory()->create([
            'equipment_category_id' => $category->id,
        ]);
        $equipment = Equipment::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->postJson('/api/v1/verifications', [
            'equipment_id' => $equipment->id,
            'verified_at' => now()->toISOString(),
            'notes' => 'Aferição de auditoria',
            'params' => [
                $template->id => 25.5,
            ],
        ])->assertStatus(201);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'module' => 'Verification',
        ]);
    }

    public function test_verification_update_logs_activity(): void
    {
        $category = Category::factory()->create();
        $template = VerificationTemplate::factory()->create([
            'equipment_category_id' => $category->id,
        ]);
        $equipment = Equipment::factory()->create([
            'category_id' => $category->id,
        ]);

        // Create verification via API so operator_id = auth()->id()
        $this->postJson('/api/v1/verifications', [
            'equipment_id' => $equipment->id,
            'verified_at' => now()->toISOString(),
            'notes' => 'Aferição inicial',
            'params' => [
                $template->id => 25.5,
            ],
        ])->assertStatus(201);

        $verification = Verification::first();

        $this->putJson("/api/v1/verifications/{$verification->id}", [
            'notes' => 'Notas atualizadas pela auditoria',
        ])->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'module' => 'Verification',
        ]);
    }

    public function test_verification_deletion_logs_activity(): void
    {
        $verification = Verification::factory()->create();

        $this->deleteJson("/api/v1/verifications/{$verification->id}")->assertStatus(204);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'deleted',
            'module' => 'Verification',
        ]);
    }
}
