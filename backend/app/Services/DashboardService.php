<?php

namespace App\Services;

use App\Enums\CalibrationStatus;
use App\Enums\LoanStatus;
use App\Models\Calibration;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\InventoryMovement;
use App\Models\Loan;
use App\Models\MaintenanceOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Aggregate all dashboard KPIs and chart data with Redis cache (TTL 300s).
     *
     * @param  Carbon  $startDate  Período inicial para gráficos (default: 12 meses atrás)
     * @param  Carbon  $endDate    Período final para gráficos (default: now)
     * @return array  { kpis: {...}, charts: {...} }
     */
    public function aggregate(Carbon $startDate, Carbon $endDate): array
    {
        return Cache::remember('dashboard', 300, function () use ($startDate, $endDate): array {
            return [
                'kpis' => $this->kpis(),
                'charts' => [
                    'equipments_by_category' => $this->equipmentsByCategory(),
                    'calibrations_timeline' => $this->calibrationsTimeline($startDate, $endDate),
                    'stock_movements' => $this->stockMovements($startDate, $endDate),
                ],
            ];
        });
    }

    /**
     * Calculate 5 main KPIs.
     *
     * @return array<string, int>
     */
    private function kpis(): array
    {
        $pendingVerifications = app(VerificationService::class)->getPendingVerifications();

        return [
            'total_equipments' => Equipment::count(),
            'calibrations_due_soon' => Calibration::where('status', CalibrationStatus::Completed)
                ->whereBetween('next_due_at', [now(), now()->addDays(30)])
                ->count(),
            'active_loans' => Loan::where('status', LoanStatus::Active)->count(),
            'pending_verifications_today' => $pendingVerifications->count(),
            'open_maintenance_orders' => MaintenanceOrder::whereIn('status', ['open', 'in_progress'])->count(),
        ];
    }

    /**
     * Equipments grouped by category for donut chart.
     *
     * @return array<int, array{name: string, value: int}>
     */
    private function equipmentsByCategory(): array
    {
        return Category::withCount('equipments')
            ->get()
            ->map(fn ($cat) => ['name' => $cat->name, 'value' => $cat->equipments_count])
            ->toArray();
    }

    /**
     * Calibrations timeline grouped by month for stacked bar chart.
     *
     * Usa selectRaw com CASE WHEN para agregar scheduled/completed/due por mês
     * em uma única query, sem N+1.
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @return array<int, array{month: string, scheduled: int, completed: int, due: int}>
     */
    private function calibrationsTimeline(Carbon $startDate, Carbon $endDate): array
    {
        return Calibration::selectRaw("
            to_char(created_at, 'YYYY-MM') as month,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'completed' AND next_due_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as due
        ", [now(), now()->addMonths(6)])
            ->whereBetween('next_due_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    /**
     * Inventory movements grouped by month for area/line chart.
     *
     * Separa incoming (purchase, return) e outgoing (consumption, adjustment, disposal)
     * por mês em uma única query agregada.
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @return array<int, array{month: string, incoming: int, outgoing: int}>
     */
    private function stockMovements(Carbon $startDate, Carbon $endDate): array
    {
        return InventoryMovement::selectRaw("
            to_char(created_at, 'YYYY-MM') as month,
            SUM(CASE WHEN type IN ('purchase', 'return') THEN quantity ELSE 0 END) as incoming,
            SUM(CASE WHEN type IN ('consumption', 'adjustment', 'disposal') THEN quantity ELSE 0 END) as outgoing
        ")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }
}
