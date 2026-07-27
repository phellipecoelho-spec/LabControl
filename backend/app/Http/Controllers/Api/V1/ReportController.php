<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller implements HasMiddleware
{
    private const VALID_TYPES = ['equipments', 'calibrations', 'inventory-movements', 'dashboard'];

    /**
     * Get the middleware that should be applied to the controller.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function middleware(): array
    {
        return [
            ['middleware' => 'auth:sanctum'],
            ['middleware' => 'permission:relatorios.view', 'only' => ['index']],
            ['middleware' => 'permission:relatorios.export', 'only' => ['download']],
        ];
    }

    /**
     * List available report types with metadata.
     */
    public function index(): JsonResponse
    {
        $reports = [
            [
                'type' => 'equipments',
                'name' => 'Equipamentos',
                'description' => 'Relatório completo de equipamentos cadastrados',
                'formats' => ['pdf', 'xlsx', 'csv'],
            ],
            [
                'type' => 'calibrations',
                'name' => 'Calibrações',
                'description' => 'Relatório de calibrações realizadas e agendadas',
                'formats' => ['pdf', 'xlsx', 'csv'],
            ],
            [
                'type' => 'inventory-movements',
                'name' => 'Movimentações de Estoque',
                'description' => 'Relatório de movimentações de entrada e saída',
                'formats' => ['pdf', 'xlsx', 'csv'],
            ],
            [
                'type' => 'dashboard',
                'name' => 'Dashboard',
                'description' => 'Exportação dos dados do dashboard (KPIs, gráficos)',
                'formats' => ['xlsx', 'csv'],
            ],
        ];

        return response()->json(['data' => $reports]);
    }

    /**
     * Download a report in the requested format.
     *
     * @param  string        $type     Report type (equipments, calibrations, inventory-movements, dashboard)
     * @param  ReportRequest $request  Validated request with format and filter parameters
     * @return StreamedResponse|BinaryFileResponse
     */
    public function download(string $type, ReportRequest $request): StreamedResponse|BinaryFileResponse
    {
        if (!in_array($type, self::VALID_TYPES, true)) {
            abort(400, 'Tipo de relatório inválido.');
        }

        $filters = $request->validated();

        /** @var ReportService $service */
        $service = app(ReportService::class);

        return match ($type) {
            'equipments' => $service->equipmentsReport($filters),
            'calibrations' => $service->calibrationsReport($filters),
            'inventory-movements' => $service->inventoryMovementsReport($filters),
            'dashboard' => $service->dashboardExport($filters),
        };
    }
}
