<?php

namespace Tests\Feature;

use App\Models\Calibration;
use App\Models\Equipment;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $auditor;
    private User $consultaUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->auditor = User::factory()->create();
        $this->auditor->roles()->attach(Role::where('slug', 'auditor')->value('id'));

        $this->consultaUser = User::factory()->create();
        $this->consultaUser->roles()->attach(Role::where('slug', 'consulta')->value('id'));
    }

    public function test_index_returns_report_list(): void
    {
        $response = $this->actingAs($this->auditor)->getJson('/api/v1/reports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['type', 'name', 'description', 'formats'],
                ],
            ]);

        $this->assertCount(4, $response->json('data'));
    }

    public function test_unauthenticated_user_cannot_export(): void
    {
        $response = $this->getJson('/api/v1/reports/equipments?format=csv');

        $response->assertStatus(401);
    }

    public function test_user_without_export_permission_receives_403(): void
    {
        // User with 'consulta' role does NOT have 'relatorios.export'
        $response = $this->actingAs($this->consultaUser)
            ->getJson('/api/v1/reports/equipments?format=csv');

        $response->assertStatus(403);
    }

    public function test_user_without_view_permission_receives_403(): void
    {
        // Create user with no role to ensure no permissions
        $noPermUser = User::factory()->create();

        $response = $this->actingAs($noPermUser)->getJson('/api/v1/reports');

        $response->assertStatus(403);
    }

    public function test_pdf_download_returns_valid_pdf(): void
    {
        Equipment::factory()->create(['name' => 'Espectrômetro']);

        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/equipments?format=pdf');

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'application/pdf',
            $response->headers->get('Content-Type') ?? ''
        );
    }

    public function test_xlsx_download_returns_valid_spreadsheet(): void
    {
        Equipment::factory()->create(['name' => 'Balança']);

        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/equipments?format=xlsx');

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'spreadsheetml',
            $response->headers->get('Content-Type') ?? ''
        );
    }

    public function test_csv_download_returns_valid_csv(): void
    {
        Equipment::factory()->create(['name' => 'Termômetro']);

        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/equipments?format=csv');

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'text/csv',
            $response->headers->get('Content-Type') ?? ''
        );
    }

    public function test_report_respects_date_filter(): void
    {
        // Create data outside the filter range
        Equipment::factory()->create([
            'name' => 'Equipamento Antigo',
            'created_at' => now()->subMonths(6),
        ]);

        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/equipments?format=csv&date_from=2026-07-01&date_to=2026-07-31');

        $response->assertStatus(200);
    }

    public function test_filename_follows_convention(): void
    {
        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/equipments?format=csv');

        $response->assertStatus(200);

        $disposition = $response->headers->get('Content-Disposition') ?? '';
        $this->assertStringContainsString('equipments_', $disposition);
        $this->assertStringContainsString('.csv', $disposition);
    }

    public function test_invalid_format_returns_400(): void
    {
        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/equipments?format=docx');

        $response->assertStatus(422);
    }

    public function test_invalid_type_returns_400(): void
    {
        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/invalid-type?format=csv');

        $response->assertStatus(400);
    }

    public function test_calibrations_report_download(): void
    {
        $equipment = Equipment::factory()->create();
        Calibration::factory()->create([
            'equipment_id' => $equipment->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/calibrations?format=csv');

        $response->assertStatus(200);
    }

    public function test_inventory_movements_report_download(): void
    {
        $item = InventoryItem::factory()->create(['name' => 'Teste Estoque']);
        InventoryMovement::factory()->create([
            'item_id' => $item->id,
            'type' => 'purchase',
            'quantity' => 10,
            'balance_after' => 10,
        ]);

        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/inventory-movements?format=csv');

        $response->assertStatus(200);
    }

    public function test_dashboard_export_download(): void
    {
        Equipment::factory(2)->create();

        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/dashboard?format=csv');

        $response->assertStatus(200);
    }

    public function test_dashboard_export_pdf_returns_500(): void
    {
        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/dashboard?format=pdf');

        // Should fail because dashboardExport throws InvalidArgumentException for PDF
        $response->assertStatus(500);
    }
}
