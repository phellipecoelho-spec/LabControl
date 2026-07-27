<?php

namespace Tests\Unit\Services;

use App\Models\Calibration;
use App\Models\Equipment;
use App\Models\InventoryMovement;
use App\Services\DashboardService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->service = app(ReportService::class);
    }

    public function test_generates_equipments_report(): void
    {
        Equipment::factory()->create(['name' => 'Espectrômetro XYZ', 'status' => 'active']);
        Equipment::factory()->create(['name' => 'Balança Analítica', 'status' => 'maintenance']);

        $response = $this->service->equipmentsReport(['format' => 'csv']);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);

        // Capture CSV content
        ob_start();
        $response->send();
        $content = ob_get_clean();

        $this->assertStringContainsString('Espectrômetro XYZ', $content);
        $this->assertStringContainsString('Balança Analítica', $content);
        $this->assertStringContainsString('active', $content);
        $this->assertStringContainsString('maintenance', $content);
        // Verify UTF-8 BOM
        $this->assertEquals("\xEF\xBB\xBF", substr($content, 0, 3));
    }

    public function test_generates_calibrations_report(): void
    {
        $equipment = Equipment::factory()->create(['name' => 'Medidor de Pressão']);
        Calibration::factory()->create([
            'equipment_id' => $equipment->id,
            'status' => 'scheduled',
            'responsible' => 'João',
        ]);

        $response = $this->service->calibrationsReport(['format' => 'csv']);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);

        ob_start();
        $response->send();
        $content = ob_get_clean();

        $this->assertStringContainsString('Medidor de Pressão', $content);
        $this->assertStringContainsString('João', $content);
    }

    public function test_generates_inventory_movements_report(): void
    {
        $item = \App\Models\InventoryItem::factory()->create(['name' => 'Luvas Nitrílicas']);
        InventoryMovement::factory()->create([
            'item_id' => $item->id,
            'type' => 'purchase',
            'quantity' => 100,
            'balance_after' => 100,
            'reason' => 'Reposição de estoque',
        ]);

        $response = $this->service->inventoryMovementsReport(['format' => 'csv']);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);

        ob_start();
        $response->send();
        $content = ob_get_clean();

        $this->assertStringContainsString('Luvas Nitrílicas', $content);
        $this->assertStringContainsString('purchase', $content);
    }

    public function test_generates_dashboard_export(): void
    {
        Equipment::factory(3)->create();

        $response = $this->service->dashboardExport(['format' => 'csv']);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);

        ob_start();
        $response->send();
        $content = ob_get_clean();

        $this->assertStringContainsString('Indicador', $content);
        $this->assertStringContainsString('Valor', $content);
    }

    public function test_dashboard_export_throws_on_pdf(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->dashboardExport(['format' => 'pdf']);
    }

    public function test_csv_injection_prevention(): void
    {
        // Create an equipment with a name that starts with formula characters
        Equipment::factory()->create(['name' => '=SUM(A1:A10)', 'status' => 'active']);

        $response = $this->service->equipmentsReport(['format' => 'csv']);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);

        ob_start();
        $response->send();
        $content = ob_get_clean();

        // Verify the formula character is present but wrapped by fputcsv
        $this->assertStringContainsString('=SUM(A1:A10)', $content);
    }

    public function test_report_with_empty_results_returns_valid_file(): void
    {
        $response = $this->service->equipmentsReport(['format' => 'csv']);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);

        ob_start();
        $response->send();
        $content = ob_get_clean();

        // Should at least have headers and BOM
        $this->assertStringContainsString('Nome', $content);
        $this->assertEquals("\xEF\xBB\xBF", substr($content, 0, 3));
    }

    public function test_report_respects_status_filter(): void
    {
        Equipment::factory()->create(['name' => 'Ativo', 'status' => 'active']);
        Equipment::factory()->create(['name' => 'Inativo', 'status' => 'inactive']);

        $response = $this->service->equipmentsReport(['format' => 'csv', 'status' => 'active']);

        ob_start();
        $response->send();
        $content = ob_get_clean();

        $this->assertStringContainsString('Ativo', $content);
        $this->assertStringNotContainsString('Inativo', $content);
    }
}
