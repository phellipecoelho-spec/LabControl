<?php

namespace Tests\Feature;

use App\Models\Calibration;
use App\Models\Equipment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditCoverageCalibrationTest extends TestCase
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

    public function test_calibration_creation_logs_activity(): void
    {
        $equipment = Equipment::factory()->create();

        $this->postJson('/api/v1/calibrations', [
            'equipment_id' => $equipment->id,
            'scheduled_date' => now()->addDays(30)->format('Y-m-d'),
            'interval_value' => 12,
            'interval_unit' => 'months',
            'responsible' => 'Técnico de Auditoria',
            'laboratory' => 'Lab Auditoria',
        ])->assertStatus(201);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'module' => 'Calibration',
        ]);
    }

    public function test_calibration_update_logs_activity(): void
    {
        $calibration = Calibration::factory()->create();

        $this->putJson("/api/v1/calibrations/{$calibration->id}", [
            'responsible' => 'Responsável Atualizado pela Auditoria',
        ])->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'module' => 'Calibration',
        ]);
    }

    public function test_calibration_deletion_logs_activity(): void
    {
        $calibration = Calibration::factory()->create();

        $this->deleteJson("/api/v1/calibrations/{$calibration->id}")->assertStatus(204);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'deleted',
            'module' => 'Calibration',
        ]);
    }

    public function test_calibration_complete_logs_activity(): void
    {
        $calibration = Calibration::factory()->create();

        $this->postJson("/api/v1/calibrations/{$calibration->id}/complete", [
            'completed_at' => now()->format('Y-m-d'),
            'certificate_number' => 'CERT-AUDIT-001',
            'result' => 'approved',
        ])->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'module' => 'Calibration',
        ]);
    }
}
