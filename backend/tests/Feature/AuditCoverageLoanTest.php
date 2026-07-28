<?php

namespace Tests\Feature;

use App\Enums\LoanStatus;
use App\Models\Equipment;
use App\Models\Loan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditCoverageLoanTest extends TestCase
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

    public function test_loan_creation_logs_activity(): void
    {
        $equipment = Equipment::factory()->create();
        $borrower = User::factory()->create();

        $this->postJson('/api/v1/loans', [
            'borrower_id' => $borrower->id,
            'equipment_ids' => [$equipment->id],
            'borrowed_at' => now()->toISOString(),
            'expected_return_at' => now()->addDays(7)->toISOString(),
            'reason' => 'Auditoria de empréstimo',
            'destination' => 'Lab Teste',
        ])->assertStatus(201);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'module' => 'Loan',
        ]);
    }

    public function test_loan_update_logs_activity(): void
    {
        $equipment = Equipment::factory()->create();
        $borrower = User::factory()->create();

        $loan = Loan::factory()->reserved()->create([
            'borrower_id' => $borrower->id,
        ]);
        $loan->equipment()->attach($equipment->id, ['returned_at' => null]);

        $this->putJson("/api/v1/loans/{$loan->id}", [
            'reason' => 'Motivo atualizado pela auditoria',
        ])->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'module' => 'Loan',
        ]);
    }

    public function test_loan_deletion_logs_activity(): void
    {
        $loan = Loan::factory()->reserved()->create();
        $equipment = Equipment::factory()->create();
        $loan->equipment()->attach($equipment->id, ['returned_at' => null]);

        $this->deleteJson("/api/v1/loans/{$loan->id}")->assertStatus(204);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'deleted',
            'module' => 'Loan',
        ]);
    }

    public function test_loan_activate_logs_activity(): void
    {
        $equipment = Equipment::factory()->create();
        $borrower = User::factory()->create();

        $loan = Loan::factory()->reserved()->create([
            'borrower_id' => $borrower->id,
            'borrowed_at' => now(),
        ]);
        $loan->equipment()->attach($equipment->id, ['returned_at' => null]);

        $this->postJson("/api/v1/loans/{$loan->id}/activate")->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'module' => 'Loan',
        ]);
    }
}
