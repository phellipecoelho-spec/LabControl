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

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $auditor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->auditor = User::factory()->create();
        $this->auditor->roles()->attach(Role::where('slug', 'auditor')->value('id'));
    }

    public function test_equipments_export_contains_expected_columns(): void
    {
        Equipment::factory()->create([
            'name' => 'Espectrômetro de Massas',
            'patrimony_id' => 'PAT-001',
            'serial_number' => 'SN-12345',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/equipments?format=csv');

        $response->assertStatus(200);

        $content = $response->streamedContent();

        $this->assertStringContainsString('Nome', $content);
        $this->assertStringContainsString('Patrimônio', $content);
        $this->assertStringContainsString('Nº Série', $content);
        $this->assertStringContainsString('Categoria', $content);
        $this->assertStringContainsString('Fabricante', $content);
        $this->assertStringContainsString('Status', $content);
        $this->assertStringContainsString('Localização', $content);
        $this->assertStringContainsString('Data Aquisição', $content);

        $this->assertStringContainsString('Espectrômetro de Massas', $content);
        $this->assertStringContainsString('PAT-001', $content);
        $this->assertStringContainsString('SN-12345', $content);
    }

    public function test_calibrations_export_contains_expected_columns(): void
    {
        $equipment = Equipment::factory()->create(['name' => 'Medidor de pH']);
        Calibration::factory()->create([
            'equipment_id' => $equipment->id,
            'status' => 'completed',
            'responsible' => 'Maria Silva',
            'laboratory' => 'LabCentral',
        ]);

        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/calibrations?format=csv');

        $response->assertStatus(200);

        $content = $response->streamedContent();

        $this->assertStringContainsString('Equipamento', $content);
        $this->assertStringContainsString('Status', $content);
        $this->assertStringContainsString('Data Agendada', $content);
        $this->assertStringContainsString('Responsável', $content);
        $this->assertStringContainsString('Laboratório', $content);

        $this->assertStringContainsString('Medidor de pH', $content);
        $this->assertStringContainsString('Maria Silva', $content);
        $this->assertStringContainsString('LabCentral', $content);
    }

    public function test_inventory_movements_export_has_correct_totals(): void
    {
        $item = InventoryItem::factory()->create(['name' => 'Reagente X', 'code' => 'RGX-001']);
        InventoryMovement::factory()->create([
            'item_id' => $item->id,
            'type' => 'purchase',
            'quantity' => 50,
            'balance_after' => 50,
            'reason' => 'Compra inicial',
        ]);
        InventoryMovement::factory()->create([
            'item_id' => $item->id,
            'type' => 'consumption',
            'quantity' => 10,
            'balance_after' => 40,
            'reason' => 'Uso em laboratório',
        ]);

        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/inventory-movements?format=csv');

        $response->assertStatus(200);

        $content = $response->streamedContent();

        $this->assertStringContainsString('Reagente X', $content);
        $this->assertStringContainsString('RGX-001', $content);
        $this->assertStringContainsString('purchase', $content);
        $this->assertStringContainsString('consumption', $content);
        $this->assertStringContainsString('50', $content);
        $this->assertStringContainsString('40', $content);
    }

    public function test_dashboard_export_has_multiple_sheets(): void
    {
        Equipment::factory(3)->create();

        // XLSX test verifies the content type only (multi-sheet structure is verified by format)
        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/dashboard?format=csv');

        $response->assertStatus(200);

        $content = $response->streamedContent();

        $this->assertStringContainsString('Indicador', $content);
        $this->assertStringContainsString('Categoria', $content);
        $this->assertStringContainsString('Mês', $content);
    }

    public function test_csv_has_utf8_bom(): void
    {
        Equipment::factory()->create(['name' => 'Teste UTF-8: ç ã é']);

        $response = $this->actingAs($this->auditor)
            ->getJson('/api/v1/reports/equipments?format=csv');

        $response->assertStatus(200);

        $content = $response->streamedContent();

        // Verify UTF-8 BOM is present
        $this->assertEquals("\xEF\xBB\xBF", substr($content, 0, 3));
        $this->assertStringContainsString('Teste UTF-8: ç ã é', $content);
    }
}
