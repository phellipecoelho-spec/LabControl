<?php

namespace App\Services;

use App\Exports\CalibrationsExport;
use App\Exports\DashboardExport;
use App\Exports\EquipmentsExport;
use App\Exports\InventoryMovementsExport;
use App\Models\Calibration;
use App\Models\Equipment;
use App\Models\InventoryMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    private const VALID_FORMATS = ['pdf', 'xlsx', 'csv'];

    /**
     * Generate the equipments report in the requested format.
     *
     * @param  array  $filters  Format, status, date_from, date_to
     * @return StreamedResponse|BinaryFileResponse
     */
    public function equipmentsReport(array $filters): StreamedResponse|BinaryFileResponse
    {
        $format = $this->resolveFormat($filters);
        $filename = sprintf('equipments_%s.%s', now()->format('Y-m-d'), $format);

        $query = Equipment::with(['category', 'manufacturer', 'supplier'])
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->where('created_at', '>=', Carbon::parse($v)))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()));

        return match ($format) {
            'pdf' => $this->streamPdf('reports.equipments', [
                'rows' => $query->get(),
                'columns' => ['Nome', 'Patrimônio', 'Nº Série', 'Categoria', 'Fabricante', 'Status', 'Localização', 'Data Aquisição'],
                'title' => 'Equipamentos',
            ], $filename),
            'xlsx' => Excel::download(new EquipmentsExport(
                status: $filters['status'] ?? null,
                dateFrom: $filters['date_from'] ?? null,
                dateTo: $filters['date_to'] ?? null,
            ), $filename),
            'csv' => $this->streamCsv($filename, function ($handle) use ($filters) {
                fputcsv($handle, ['Nome', 'Patrimônio', 'Nº Série', 'Categoria', 'Fabricante', 'Status', 'Localização', 'Data Aquisição']);
                Equipment::with(['category', 'manufacturer', 'supplier'])
                    ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
                    ->when($filters['date_from'] ?? null, fn($q, $v) => $q->where('created_at', '>=', Carbon::parse($v)))
                    ->when($filters['date_to'] ?? null, fn($q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()))
                    ->chunkById(500, function ($equipments) use ($handle) {
                        foreach ($equipments as $eq) {
                            fputcsv($handle, [
                                $eq->name,
                                $eq->patrimony_id ?? '-',
                                $eq->serial_number ?? '-',
                                $eq->category?->name ?? '-',
                                $eq->manufacturer?->name ?? '-',
                                $eq->status,
                                $eq->location ?? '-',
                                $eq->acquisition_date?->format('d/m/Y') ?? '-',
                            ]);
                        }
                        flush();
                    });
            }),
        };
    }

    /**
     * Generate the calibrations report in the requested format.
     *
     * @param  array  $filters  Format, status, date_from, date_to
     * @return StreamedResponse|BinaryFileResponse
     */
    public function calibrationsReport(array $filters): StreamedResponse|BinaryFileResponse
    {
        $format = $this->resolveFormat($filters);
        $filename = sprintf('calibrations_%s.%s', now()->format('Y-m-d'), $format);

        $query = Calibration::with(['equipment'])
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->where('scheduled_date', '>=', Carbon::parse($v)))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->where('scheduled_date', '<=', Carbon::parse($v)));

        return match ($format) {
            'pdf' => $this->streamPdf('reports.calibrations', [
                'rows' => $query->get(),
                'columns' => ['Equipamento', 'Status', 'Data Agendada', 'Data Conclusão', 'Próximo Vencimento', 'Responsável', 'Laboratório'],
                'title' => 'Calibrações',
            ], $filename),
            'xlsx' => Excel::download(new CalibrationsExport(
                status: $filters['status'] ?? null,
                dateFrom: $filters['date_from'] ?? null,
                dateTo: $filters['date_to'] ?? null,
            ), $filename),
            'csv' => $this->streamCsv($filename, function ($handle) use ($filters) {
                fputcsv($handle, ['Equipamento', 'Status', 'Data Agendada', 'Data Conclusão', 'Próximo Vencimento', 'Responsável', 'Laboratório']);
                Calibration::with(['equipment'])
                    ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
                    ->when($filters['date_from'] ?? null, fn($q, $v) => $q->where('scheduled_date', '>=', Carbon::parse($v)))
                    ->when($filters['date_to'] ?? null, fn($q, $v) => $q->where('scheduled_date', '<=', Carbon::parse($v)))
                    ->chunkById(500, function ($calibrations) use ($handle) {
                        foreach ($calibrations as $cal) {
                            fputcsv($handle, [
                                $cal->equipment?->name ?? '-',
                                $cal->status?->label() ?? $cal->status ?? '-',
                                $cal->scheduled_date?->format('d/m/Y') ?? '-',
                                $cal->completed_at?->format('d/m/Y H:i') ?? '-',
                                $cal->next_due_at?->format('d/m/Y') ?? '-',
                                $cal->responsible ?? '-',
                                $cal->laboratory ?? '-',
                            ]);
                        }
                        flush();
                    });
            }),
        };
    }

    /**
     * Generate the inventory movements report in the requested format.
     *
     * @param  array  $filters  Format, type, date_from, date_to
     * @return StreamedResponse|BinaryFileResponse
     */
    public function inventoryMovementsReport(array $filters): StreamedResponse|BinaryFileResponse
    {
        $format = $this->resolveFormat($filters);
        $filename = sprintf('inventory-movements_%s.%s', now()->format('Y-m-d'), $format);

        $query = InventoryMovement::with(['item', 'user'])
            ->when($filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->where('created_at', '>=', Carbon::parse($v)))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()));

        return match ($format) {
            'pdf' => $this->streamPdf('reports.inventory-movements', [
                'rows' => $query->get(),
                'columns' => ['Item', 'Código', 'Tipo', 'Quantidade', 'Saldo após', 'Motivo', 'Data'],
                'title' => 'Movimentações de Estoque',
            ], $filename),
            'xlsx' => Excel::download(new InventoryMovementsExport(
                type: $filters['type'] ?? null,
                dateFrom: $filters['date_from'] ?? null,
                dateTo: $filters['date_to'] ?? null,
            ), $filename),
            'csv' => $this->streamCsv($filename, function ($handle) use ($filters) {
                fputcsv($handle, ['Item', 'Código', 'Tipo', 'Quantidade', 'Saldo após', 'Motivo', 'Usuário', 'Data']);
                InventoryMovement::with(['item', 'user'])
                    ->when($filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
                    ->when($filters['date_from'] ?? null, fn($q, $v) => $q->where('created_at', '>=', Carbon::parse($v)))
                    ->when($filters['date_to'] ?? null, fn($q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()))
                    ->chunkById(500, function ($movements) use ($handle) {
                        foreach ($movements as $mov) {
                            fputcsv($handle, [
                                $mov->item?->name ?? '-',
                                $mov->item?->code ?? '-',
                                $mov->type,
                                $mov->quantity,
                                $mov->balance_after,
                                $mov->reason ?? '-',
                                $mov->user?->name ?? '-',
                                $mov->created_at->format('d/m/Y H:i'),
                            ]);
                        }
                        flush();
                    });
            }),
        };
    }

    /**
     * Generate the dashboard export in the requested format.
     *
     * @param  array  $filters  Format, date_from, date_to
     * @return StreamedResponse|BinaryFileResponse
     */
    public function dashboardExport(array $filters): StreamedResponse|BinaryFileResponse
    {
        $format = $this->resolveFormat($filters);

        if ($format === 'pdf') {
            throw new \InvalidArgumentException('Dashboard export does not support PDF format. Use xlsx or csv.');
        }

        $filename = sprintf('dashboard_%s.%s', now()->format('Y-m-d'), $format);

        $dashboardService = app(DashboardService::class);
        $startDate = isset($filters['date_from']) ? Carbon::parse($filters['date_from']) : now()->subMonths(12);
        $endDate = isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : now();
        $dashboardData = $dashboardService->aggregate($startDate, $endDate);

        return match ($format) {
            'xlsx' => Excel::download(new DashboardExport($dashboardData), $filename),
            'csv' => $this->streamCsv($filename, function ($handle) use ($dashboardData) {
                // Section 1: KPIs
                fputcsv($handle, ['Indicador', 'Valor']);
                foreach ($dashboardData['kpis'] as $key => $value) {
                    fputcsv($handle, [$key, $value]);
                }
                fputcsv($handle, []); // blank row

                // Section 2: Equipments by category
                fputcsv($handle, ['Categoria', 'Quantidade']);
                foreach ($dashboardData['charts']['equipments_by_category'] ?? [] as $item) {
                    fputcsv($handle, [$item['name'], $item['value']]);
                }
                fputcsv($handle, []);

                // Section 3: Calibrations timeline
                fputcsv($handle, ['Mês', 'Agendadas', 'Concluídas', 'Vencendo']);
                foreach ($dashboardData['charts']['calibrations_timeline'] ?? [] as $item) {
                    fputcsv($handle, [$item['month'], $item['scheduled'], $item['completed'], $item['due']]);
                }
                fputcsv($handle, []);

                // Section 4: Stock movements
                fputcsv($handle, ['Mês', 'Entradas', 'Saídas']);
                foreach ($dashboardData['charts']['stock_movements'] ?? [] as $item) {
                    fputcsv($handle, [$item['month'], $item['incoming'], $item['outgoing']]);
                }
            }),
        };
    }

    /**
     * Resolve the output format from filters, defaulting to 'pdf'.
     *
     * @param  array  $filters
     * @return string
     */
    private function resolveFormat(array $filters): string
    {
        return in_array($filters['format'] ?? 'pdf', self::VALID_FORMATS, true)
            ? $filters['format']
            : 'pdf';
    }

    /**
     * Stream a PDF from a Blade view using DomPDF.
     *
     * @param  string  $view
     * @param  array   $data
     * @param  string  $filename
     * @return StreamedResponse
     */
    private function streamPdf(string $view, array $data, string $filename): StreamedResponse
    {
        $generatedAt = now()->format('d/m/Y H:i');
        $rows = $data['rows'] ?? collect();
        $title = $data['title'] ?? 'Relatório';

        $pdf = Pdf::loadView($view, compact('rows', 'title', 'generatedAt'))
            ->setPaper('a4', 'portrait')
            ->setOption(['dpi' => 150, 'defaultFont' => 'sans-serif']);

        return $pdf->download($filename);
    }

    /**
     * Stream a CSV file with UTF-8 BOM for Excel compatibility.
     *
     * @param  string    $filename
     * @param  callable  $writer  Function that receives the file handle
     * @return StreamedResponse
     */
    private function streamCsv(string $filename, callable $writer): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($writer) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");
            $writer($handle);
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}
