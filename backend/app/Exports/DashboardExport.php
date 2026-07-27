<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DashboardExport implements WithMultipleSheets, ShouldAutoSize
{
    public function __construct(
        private array $dashboardData,
    ) {}

    public function sheets(): array
    {
        $data = $this->dashboardData;

        return [
            new DashboardSheetExport('KPIs', function () use ($data) {
                $rows = [];
                foreach ($data['kpis'] as $key => $value) {
                    $rows[] = [$key, $value];
                }
                return $rows;
            }, ['Indicador', 'Valor']),

            new DashboardSheetExport('Equip. por Categoria', function () use ($data) {
                $rows = [];
                foreach ($data['charts']['equipments_by_category'] ?? [] as $item) {
                    $rows[] = [$item['name'], $item['value']];
                }
                return $rows;
            }, ['Categoria', 'Quantidade']),

            new DashboardSheetExport('Calibrações por Mês', function () use ($data) {
                $rows = [];
                foreach ($data['charts']['calibrations_timeline'] ?? [] as $item) {
                    $rows[] = [$item['month'], $item['scheduled'], $item['completed'], $item['due']];
                }
                return $rows;
            }, ['Mês', 'Agendadas', 'Concluídas', 'Vencendo']),

            new DashboardSheetExport('Movimentações', function () use ($data) {
                $rows = [];
                foreach ($data['charts']['stock_movements'] ?? [] as $item) {
                    $rows[] = [$item['month'], $item['incoming'], $item['outgoing']];
                }
                return $rows;
            }, ['Mês', 'Entradas', 'Saídas']),
        ];
    }
}
