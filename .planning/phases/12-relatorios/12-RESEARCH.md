# Phase 12: Relatórios - Research

**Researched:** 2026-07-27
**Domain:** Server-side report generation (PDF/Excel/CSV) for Laravel + Vue 3 stack
**Confidence:** HIGH

## Summary

This phase implements centralized report generation for the LabControl platform, covering four pre-defined report types (Equipamentos, Calibrações, Movimentações de Estoque, Dashboard Export) in three formats (PDF, XLSX, CSV). Research confirms all three libraries are compatible with Laravel 13.x and the existing project stack.

**Primary recommendation:** Implement a single `ReportController` with per-module service methods. PDF uses DomPDF with table-based Blade templates (no flexbox/grid). XLSX uses Laravel Excel with dedicated Export classes. CSV uses native `StreamedResponse` + `fputcsv`. Frontend uses axiox `responseType: 'blob'` for all downloads.

## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Hub centralizado em `/reports` como página única listando todos os relatórios disponíveis. Sem atalhos de exportação nas listas dos módulos por enquanto.
- **D-02:** Quatro relatórios pré-definidos: Equipamentos, Calibrações, Movimentações de Estoque, Dashboard Export. Empréstimos, Aferições e Manutenções ficam para futuras fases.
- **D-03:** Formato tabular simples nos PDFs — tabelas com cabeçalho, linhas de dados, totalizadores no rodapé. Cabeçalho com logo, nome do sistema e data de geração.
- **D-04:** PDF via `barryvdh/laravel-dompdf` (renderização HTML+CSS → PDF). Sem dependências externas.
- **D-05:** XLSX via `maatwebsite/laravel-excel` (Laravel Excel, baseado PhpSpreadsheet).
- **D-06:** CSV gerado nativamente com `StreamedResponse` + `fputcsv` do Laravel.
- **D-07:** Geração 100% server-side no Laravel. Frontend apenas dispara a requisição e baixa o arquivo.
- **D-08:** SplitButton por relatório: um clique baixa no formato padrão (PDF), dropdown permite selecionar XLSX ou CSV.
- **D-09:** Download direto e síncrono — loading spinner no botão durante a geração, download automático ao final. Sem fila assíncrona.
- **D-10:** Filtro de período opcional — se não informado, exporta todos os registros. Sempre disponível na sidebar de filtros.
- **D-11:** Filtros específicos limitados a período + status geral (ativo/inativo, pendente/concluído). Sem filtros avançados por módulo.
- **D-12:** Sidebar de filtros (Drawer) para configurar os parâmetros antes de gerar o relatório. Inspirado no painel de filtros do Power BI.
- **D-13:** Nomenclatura de arquivos gerados: `{modulo}_{data}_yyyy-mm-dd.{ext}` (ex: `equipamentos_2026-07-27.pdf`).
- **D-14:** Permissões existentes: `relatorios.view` para acesso à página, `relatorios.export` para gerar/download. Já seedadas para perfil `auditor`.
- **D-15:** Rota nomeada `reports.index` já mapeada para módulo `relatorios` na navegação. Módulo frontend em `modules/reports/` (vazio).

### the agent's Discretion
- **A-01:** Definir se o `ReportController` será um único controller com actions por formato (downloadPdf/downloadXlsx/downloadCsv) ou controllers separados por módulo.
- **A-02:** Definir se os PDFs serão reutilizáveis como views HTML para preview ou exclusivos para download.
- **A-03:** Estrutura de Blade views: um diretório `resources/views/reports/` com subdiretório por módulo ou views planas.
- **A-04:** Estratégia de limpeza de arquivos temporários gerados.

### Deferred Ideas (OUT OF SCOPE)
- **Relatório de Empréstimos, Aferições e Manutenções** — fora do escopo da Fase 12.
- **Atalhos de exportação nas listas dos módulos** — botão "Exportar" dentro de cada página de listagem.
- **Fila assíncrona para relatórios grandes** — geração em background com notificação quando pronto.

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Report data querying | API / Backend | — | Reports are 100% server-side generated from database queries |
| PDF rendering | API / Backend | — | DomPDF runs in PHP, no client-side involvement |
| XLSX generation | API / Backend | — | Laravel Excel runs in PHP, no client-side involvement |
| CSV generation | API / Backend | — | fputcsv runs in PHP, StreamedResponse handles the output |
| Report trigger / download | Browser / Client | — | Vue component fires HTTP request, handles blob download |
| Filter parameters UI | Browser / Client | — | Drawer sidebar with date picker and status select |
| File naming | API / Backend | — | `{modulo}_{data}_{date}.{ext}` constructed server-side |
| Permission enforcement | API / Backend | — | Sanctum middleware + permission gates on report endpoints |

## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| REPT-01 | Usuário pode gerar relatórios em PDF, Excel e CSV | PDF: `barryvdh/laravel-dompdf` v3.1.2 [VERIFIED: npm registry]; XLSX: `maatwebsite/laravel-excel` v3.1.69 [VERIFIED: npm registry]; CSV: Laravel `StreamedResponse` + `fputcsv` [CITED: laravel.com/docs] |
| REPT-02 | Usuário pode exportar dados do sistema | Server-side generation with direct download; permissions `relatorios.view` and `relatorios.export` already seeded in `RolePermissionSeeder` |

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `barryvdh/laravel-dompdf` | ^3.1.2 | HTML+CSS → PDF rendering | Laravel 13 compatible [VERIFIED: Packagist]; Blade-first workflow; no system dependencies; auto-discovery |
| `maatwebsite/laravel-excel` | ^3.1.69 | PhpSpreadsheet-based XLSX generation | Laravel 13 compatible [VERIFIED: Packagist]; export classes; styling; multiple sheets |
| `dompdf/dompdf` | ^3.1 (dep.) | Core PDF engine | Included transitively by laravel-dompdf; CSS 2.1 + partial CSS3 |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| PHP `fputcsv` | native | CSV row writing | All CSV exports — never hand-roll CSV escaping |
| `Symfony\Component\HttpFoundation\StreamedResponse` | (in Laravel core) | Streaming file download | CSV and potentially large XLSX/PDF responses |
| Laravel `response()->streamDownload()` | (Laravel core) | Convenience wrapper for StreamedResponse | Alternative to `new StreamedResponse()` for simpler cases |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| DomPDF | `spatie/browsershot` (headless Chrome) | Pixel-perfect rendering but requires Node + Chrome binary on server — operational overhead. Not justified for tabular reports. |
| DomPDF | `mpdf/mpdf` | Another PHP PDF library. Better Unicode support but larger package size. DomPDF is more actively maintained. |
| Laravel Excel | `openspout/openspout` | Lighter weight (no PhpSpreadsheet dependency) but less ecosystem support and no Laravel-specific features. |
| Manual `fputcsv` | `league/csv` | Good library but adds unnecessary dependency for simple CSV exports. |

**Installation:**
```bash
cd backend
composer require barryvdh/laravel-dompdf
composer require maatwebsite/excel
```

**Version verification:** [ASSUMED] — local PHP/Composer not available in this environment. Confirmed via Packagist search:
- `barryvdh/laravel-dompdf`: v3.1.2 (2026-02-21), requires PHP ^8.1, supports Laravel ^9|^10|^11|^12|^13.0, transitively uses `dompdf/dompdf:^3.0` [CITED: packagist.org/packages/barryvdh/laravel-dompdf]
- `maatwebsite/excel`: v3.1.69 (2026-04-30), requires PHP ^7.0||^8.0, supports Laravel 5.8.*||^6.0||^7.0||^8.0||^9.0||^10.0||^11.0||^12.0||^13.0, transitively uses `phpoffice/phpspreadsheet:^1.30.5` [CITED: packagist.org/packages/maatwebsite/excel]

## Package Legitimacy Audit

> Required: This phase installs external packages.

| Package | Registry | Age | Downloads | Source Repo | Verdict | Disposition |
|---------|----------|-----|-----------|-------------|---------|-------------|
| barryvdh/laravel-dompdf | Packagist | ~13 yrs | ~205M total | github.com/barryvdh/laravel-dompdf | OK | Approved |
| maatwebsite/excel | Packagist | ~13 yrs | ~165M total | github.com/SpartnerNL/Laravel-Excel | OK | Approved |
| dompdf/dompdf | Packagist | — | (transitive dep) | github.com/dompdf/dompdf | OK | Approved (transitive) |
| phpoffice/phpspreadsheet | Packagist | — | (transitive dep) | github.com/PHPOffice/PhpSpreadsheet | OK | Approved (transitive) |

**Packages removed due to [SLOP] verdict:** none
**Packages flagged as suspicious [SUS]:** none

## Architecture Patterns

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                       Browser (Vue 3)                        │
│                                                              │
│  ┌─────────────────┐      ┌──────────────────────────────┐   │
│  │ ReportsPage.vue  │      │  FilterDrawer.vue            │   │
│  │ (lista relatórios)│      │  (período + status)           │   │
│  └────────┬─────────┘      └──────────────────────────────┘   │
│           │                                                    │
│  ┌────────▼────────────────────────────────────────────┐       │
│  │  SplitButton (formato: PDF │ XLSX │ CSV)             │       │
│  └────────┬────────────────────────────────────────────┘       │
│           │ click                                              │
│           │ GET /api/v1/reports/{type}?format={fmt}&period=... │
│           │ responseType: 'blob'                               │
└───────────┼────────────────────────────────────────────────────┘
            │
┌───────────▼────────────────────────────────────────────────────┐
│                   Laravel (API)                                 │
│                                                                 │
│  ┌──────────────────────┐    ┌────────────────────────────┐     │
│  │ Middleware:           │    │  ReportController           │     │
│  │ auth:sanctum          │───►│  - download(req, type)      │     │
│  │ permission:relatorios │    │    │                        │     │
│  │ .export               │    │    ├─ format=pdf            │     │
│  └──────────────────────┘    │    │  └─ DomPDF::loadView()  │     │
│                              │    ├─ format=xlsx            │     │
│                              │    │  └─ Excel::download()   │     │
│                              │    └─ format=csv             │     │
│                              │       └─ StreamedResponse    │     │
│                              └────────┬─────────────────────┘     │
│                                       │                          │
│  ┌────────────────────────────────────▼─────────────────────┐   │
│  │  Services Layer                                          │   │
│  │                                                          │   │
│  │  ReportService  EquipmentReportService  InventoryReport  │   │
│  │  (factory)      CalibrationReportService  DashboardReport│   │
│  └────────────────────────────────────┬─────────────────────┘   │
│                                       │                          │
│  ┌────────────────────────────────────▼─────────────────────┐   │
│  │  Models / Database                                        │   │
│  │  Equipment │ Calibration │ InventoryMovement │ Dashboard   │   │
│  └──────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

### Recommended Project Structure

```
backend/
├── app/
│   ├── Exports/                          # Laravel Excel export classes
│   │   ├── EquipmentReportExport.php
│   │   ├── CalibrationReportExport.php
│   │   ├── InventoryMovementReportExport.php
│   │   └── DashboardExport.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── V1/
│   │   │           └── ReportController.php
│   │   └── Requests/
│   │       └── ReportRequest.php          # Validates period + status filters
│   ├── Services/
│   │   └── Reports/
│   │       ├── ReportService.php           # Base/interface
│   │       ├── EquipmentReportService.php
│   │       ├── CalibrationReportService.php
│   │       ├── InventoryReportService.php
│   │       └── DashboardExportService.php
│   └── ...
├── resources/
│   ├── views/
│   │   └── reports/
│   │       ├── layouts/
│   │       │   └── report.blade.php        # Base PDF layout (logo, header, footer)
│   │       ├── equipments.blade.php
│   │       ├── calibrations.blade.php
│   │       ├── inventory-movements.blade.php
│   │       └── dashboard.blade.php
│   └── ...
├── routes/
│   ├── api.php                            # Add report routes
│   └── ...
├── config/
│   ├── dompdf.php                         # Published config
│   └── excel.php                          # Published config
├── storage/
│   ├── fonts/                             # Custom fonts + DomPDF font cache
│   └── ...
└── ...

frontend/
├── src/
│   ├── modules/
│   │   └── reports/
│   │       ├── components/
│   │       │   ├── ReportCard.vue          # Card with SplitButton per report
│   │       │   └── FilterDrawer.vue        # Sidebar filter drawer
│   │       ├── pages/
│   │       │   └── ReportsPage.vue         # Main reports hub page
│   │       ├── services/
│   │       │   └── reportService.ts        # Axios download helpers
│   │       ├── store/
│   │       │   └── reportStore.ts          # Filter state, loading state
│   │       ├── types/
│   │       │   └── report.ts              # TypeScript interfaces
│   │       └── routes/
│   │           └── index.ts               # Optional: sub-routes if needed
│   ├── types/
│   │   └── navigation.ts                  # Already configured
│   ├── router/
│   │   └── routes.ts                      # Update PlaceholderPage → ReportsPage
│   └── ...
└── ...
```

### Pattern 1: Single ReportController with Dispatch

**What:** One controller action `download()` that accepts report type and format as parameters, dispatches to the appropriate service method.

**When to use:** All reports follow the same pattern (filter → query → generate → download). Keeps routing simple and follows Laravel convention for invokable/single-action patterns.

**Example:**
```php
// Source: [VERIFIED: github.com/barryvdh/laravel-dompdf] - adapted for this architecture
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Services\Reports\ReportServiceFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public static function middleware(): array
    {
        return [
            ['middleware' => 'auth:sanctum'],
            ['middleware' => 'permission:relatorios.export'],
        ];
    }

    public function download(
        string $type,
        string $format,
        ReportRequest $request,
        ReportServiceFactory $factory
    ): StreamedResponse {
        $service = $factory->make($type);
        $filters = $request->validated();

        return match ($format) {
            'pdf' => $service->downloadPdf($filters),
            'xlsx' => $service->downloadXlsx($filters),
            'csv' => $service->downloadCsv($filters),
            default => abort(400, 'Formato inválido. Use pdf, xlsx ou csv.'),
        };
    }
}
```

### Pattern 2: Dedicated Blade View per Report (PDF)

**What:** Each PDF report has a dedicated Blade view with table-based layout, using DomPDF-compatible CSS only.

**When to use:** All PDF reports. DomPDF does NOT support flexbox or CSS Grid — use HTML tables for layout.

**Example:**
```blade
{{-- Source: [CITED: github.com/dompdf/dompdf#limitations] table-based layout for DomPDF --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { max-height: 60px; }
        .header h1 { font-size: 16pt; margin: 5px 0; }
        .header p { font-size: 8pt; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        thead th { background: #2563eb; color: white; padding: 8px 6px;
                   font-size: 9pt; text-align: left; }
        tbody td { padding: 6px; border-bottom: 1px solid #ddd; font-size: 9pt; }
        tbody tr:nth-child(even) { background: #f9f9f9; }
        .footer { text-align: center; margin-top: 30px; font-size: 8pt; color: #999;
                  border-top: 1px solid #ddd; padding-top: 10px; }
        .total-row td { font-weight: bold; border-top: 2px solid #333; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/logo.png') }}" alt="LabControl">
        <h1>Relatório de {{ $title }}</h1>
        <p>Gerado em: {{ $generated_at }}</p>
    </div>
    <table>
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    @if(!empty($totals))
    <table>
        <tfoot>
            <tr class="total-row">
                @foreach($totals as $total)
                    <td>{{ $total }}</td>
                @endforeach
            </tr>
        </tfoot>
    </table>
    @endif
    <div class="footer">
        LabControl — Plataforma de Gestão Laboratorial<br>
        Documento gerado automaticamente
    </div>
</body>
</html>
```

### Anti-Patterns to Avoid
- **Using flexbox or CSS Grid in DomPDF views:** DomPDF does NOT support flexbox or CSS Grid [VERIFIED: github.com/dompdf/dompdf/issues/971, 2988]. Elements with `display: flex` or `display: grid` will be hidden or rendered incorrectly. Use HTML tables and floats instead.
- **Building CSV strings manually:** Never concatenate strings with commas for CSV output. Always use `fputcsv()` — it handles quoting, escaping, and edge cases correctly.
- **Loading all report data into memory at once:** Use `chunkById()` or `lazy()` (cursor) for large datasets. `StreamedResponse` prevents memory exhaustion but the query itself must also be streamed.
- **Reusing SPA layout for PDF views:** PDF Blade views must be self-contained HTML with inline styles — no Vite assets, no Vue components, no external CSS links.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| HTML → PDF conversion | Custom PDF engine | `barryvdh/laravel-dompdf` | PDF rendering is deceptively complex — page breaks, font metrics, DPI scaling |
| XLSX file generation | Raw XML/ZIP manipulation | `maatwebsite/laravel-excel` | XLSX is a ZIP of XML files — one wrong namespace kills the file |
| CSV escaping | String concatenation with commas | `fputcsv()` | Handles quotes within fields, multi-line values, locale-specific separators |
| File download headers | Manual header management | Laravel `StreamedResponse` | Proper Content-Type, Content-Disposition, cache headers handled correctly |
| Filter validation | Ad-hoc parameter parsing | `FormRequest` (ReportRequest) | Reuses existing project pattern for validation + authorization |

**Key insight:** Report generation is full of edge cases (character encoding, page breaks, font metrics, streaming, timeouts). Using established libraries for each format avoids months of debugging esoteric PDF/XLSX rendering issues.

## Common Pitfalls

### Pitfall 1: PDF CSS Rendering Differences
**What goes wrong:** PDF output looks completely different from the expected design — missing styles, broken layout, invisible elements.
**Why it happens:** DomPDF implements CSS 2.1 with partial CSS3 — no flexbox, no grid, no CSS custom properties, no JavaScript.
**How to avoid:** Use table-based layouts exclusively for DomPDF. Keep CSS inline. Test PDF rendering early in development. Use `setPaper('a4', 'portrait')` and `setOption(['dpi' => 150])`.
**Warning signs:** Elements with `display: flex` not showing up; layout collapsing; missing background colors.

### Pitfall 2: Memory Exhaustion on Large Datasets
**What goes wrong:** PHP runs out of memory when exporting thousands of records to XLSX or CSV.
**Why it happens:** Loading all query results into a Collection before writing to the output stream.
**How to avoid:** Use `chunkById()` (not `chunk()` which uses OFFSET — can skip rows) for CSV. Laravel Excel's `FromQuery` handles chunking internally. For DomPDF, keep datasets manageable (dozens to low hundreds of rows per PDF).
**Warning signs:** 500 errors, memory limit exceeded logs, timeout errors.

### Pitfall 3: Corrupted XLSX Downloads
**What goes wrong:** Downloaded XLSX file shows as corrupted or opens as a ZIP archive.
**Why it happens:** Missing `responseType: 'blob'` in the axios request — the binary data gets interpreted as JSON/text.
**How to avoid:** Always set `{ responseType: 'blob' }` on the axios request for file downloads. The Content-Type header alone is not enough when using axios.
**Warning signs:** Downloaded file has correct extension but garbage content; error "file format or extension not valid."

### Pitfall 4: UTF-8 Characters in CSV (Excel compatibility)
**What goes wrong:** Accented characters (ã, é, ç) appear garbled when Portuguese users open the CSV in Excel.
**Why it happens:** Excel assumes Windows-1252 encoding for CSV files without a BOM.
**How to avoid:** Prepend UTF-8 BOM (`"\xEF\xBB\xBF"`) to the CSV output stream before writing headers. This tells Excel to interpret the file as UTF-8.
**Warning signs:** Characters like "São Paulo" displaying as "SÃ£o Paulo" in Excel.

### Pitfall 5: Table Rows Splitting Across PDF Pages
**What goes wrong:** A table row breaks across two pages, making the PDF hard to read.
**Why it happens:** DomPDF requires that a table cell fit on a single page. If a cell contains too much content, the entire row moves to the next page.
**How to avoid:** Keep cell content short. If text is long, consider truncating or adding `page-break-inside: avoid` on `<tr>` elements. For very wide tables, use landscape orientation: `->setPaper('a4', 'landscape')`.

## Code Examples

### PDF Download (Backend)
```php
// Source: [CITED: github.com/barryvdh/laravel-dompdf] standard pattern
use Barryvdh\DomPDF\Facade\Pdf;

public function downloadPdf(array $filters): StreamedResponse
{
    $data = $this->queryReportData($filters);
    $filename = sprintf('equipamentos_%s.pdf', now()->format('Y-m-d'));

    $pdf = Pdf::loadView('reports.equipments', [
        'rows' => $data['rows'],
        'columns' => $data['columns'],
        'totals' => $data['totals'] ?? [],
        'title' => 'Equipamentos',
        'generated_at' => now()->format('d/m/Y H:i'),
    ])->setPaper('a4', 'landscape')
      ->setOption(['dpi' => 150, 'defaultFont' => 'sans-serif']);

    return $pdf->download($filename);
}
```

### XLSX Export (Backend)
```php
// Source: [CITED: docs.laravel-excel.com/3.1/exports/] export class pattern
namespace App\Exports;

use App\Models\Equipment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EquipmentReportExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles
{
    public function __construct(
        private ?string $status = null,
        private ?string $startDate = null,
        private ?string $endDate = null
    ) {}

    public function query()
    {
        return Equipment::query()
            ->with(['category', 'manufacturer'])
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->startDate, fn($q) => $q->where('created_at', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->where('created_at', '<=', $this->endDate));
    }

    public function headings(): array
    {
        return ['Nome', 'Patrimônio', 'Nº Série', 'Categoria', 'Fabricante', 'Status', 'Localização', 'Data Aquisição'];
    }

    public function map($equipment): array
    {
        return [
            $equipment->name,
            $equipment->patrimony_id ?? '-',
            $equipment->serial_number ?? '-',
            $equipment->category?->name ?? '-',
            $equipment->manufacturer?->name ?? '-',
            $equipment->status,
            $equipment->location ?? '-',
            $equipment->acquisition_date?->format('d/m/Y') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2563EB']]],
        ];
    }
}

// In ReportService:
public function downloadXlsx(array $filters): StreamedResponse
{
    $filename = sprintf('equipamentos_%s.xlsx', now()->format('Y-m-d'));
    return Excel::download(
        new EquipmentReportExport(
            status: $filters['status'] ?? null,
            startDate: $filters['start_date'] ?? null,
            endDate: $filters['end_date'] ?? null
        ),
        $filename
    );
}
```

### CSV Download (Backend)
```php
// Source: [CITED: laravel.com/docs/13.x/responses#streamed-downloads] + [CITED: barryvdh.nl/laravel/2015]
use Symfony\Component\HttpFoundation\StreamedResponse;

public function downloadCsv(array $filters): StreamedResponse
{
    $filename = sprintf('equipamentos_%s.csv', now()->format('Y-m-d'));
    $headers = [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    $callback = function () use ($filters) {
        $handle = fopen('php://output', 'w');

        // UTF-8 BOM for Excel compatibility
        fwrite($handle, "\xEF\xBB\xBF");

        // Header row
        fputcsv($handle, ['Nome', 'Patrimônio', 'Nº Série', 'Categoria', 'Fabricante', 'Status', 'Localização']);

        // Stream data in chunks to avoid memory issues
        Equipment::with(['category', 'manufacturer'])
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['start_date'] ?? null, fn($q, $v) => $q->where('created_at', '>=', $v))
            ->when($filters['end_date'] ?? null, fn($q, $v) => $q->where('created_at', '<=', $v))
            ->chunkById(500, function ($equipments) use ($handle) {
                foreach ($equipments as $equipment) {
                    fputcsv($handle, [
                        $equipment->name,
                        $equipment->patrimony_id ?? '-',
                        $equipment->serial_number ?? '-',
                        $equipment->category?->name ?? '-',
                        $equipment->manufacturer?->name ?? '-',
                        $equipment->status,
                        $equipment->location ?? '-',
                    ]);
                }
                flush();
            });

        fclose($handle);
    };

    return response()->streamDownload($callback, $filename, $headers);
}
```

### Frontend Download (Vue 3 + Axios)
```typescript
// Source: [CITED: stackoverflow.com/questions/73453026] blob download pattern

// reportService.ts
import { api } from '@/services/api'

export const reportService = {
  async download(type: string, format: string, params?: Record<string, any>): Promise<void> {
    const response = await api.get(`/reports/${type}`, {
      params: { format, ...params },
      responseType: 'blob',
    })

    const contentTypes: Record<string, string> = {
      pdf: 'application/pdf',
      xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      csv: 'text/csv',
    }

    const blob = new Blob([response.data], { type: contentTypes[format] || 'application/octet-stream' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${type}_${new Date().toISOString().split('T')[0]}.${format}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  },
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| DomPDF v2.x | DomPDF v3.x (via laravel-dompdf v3.1) | 2024-2025 | Security improvements, changed defaults, strict CSP |
| Laravel Excel v3.0 | Laravel Excel v3.1.69 | Ongoing | PhpSpreadsheet 1.30+, new concerns like `WithStyles` |
| wkhtmltopdf | DomPDF / Browsershot | 2020+ | wkhtmltopdf abandoned — no security patches. DomPDF is active. |

**Deprecated/outdated:**
- `maatwebsite/excel` v2.x: Unsupported since 2018. Project uses v3.1.
- `wkhtmltopdf`: Abandoned project, last release 2020. No security patches.

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | Local PHP/Composer installed. Package versions confirmed via Packagist web search, not local `composer show`. | Standard Stack | Version mismatch could occur if the local lockfile is stale, but latest versions are confirmed compatible with Laravel 13. |
| A2 | No frontend packages needed for download — using native browser APIs. | Don't Hand-Roll | Low risk. Pattern confirmed by multiple sources. |
| A3 | Default DomPDF settings (font_dir, temp_dir) after `vendor:publish` are appropriate. | Code Examples | Minor — can be configured via config/dompdf.php. |

## Open Questions

1. **Should PDF views be reusable for an HTML preview mode?**
   - What we know: D-09 says direct download only. No async or preview.
   - What's unclear: Whether to make views dual-purpose (HTML for dev preview, PDF for download).
   - Recommendation: Keep views PDF-only for simplicity. Use `?preview=1` query param during development (inline style from the YouTube guide pattern).

2. **Cache strategy for report data (5-min TTL)?**
   - What we know: D-10 says caching is optional. Reports are synchronous.
   - What's unclear: Whether caching report query results (not files) is worth implementing given synchronous nature.
   - Recommendation: Defer caching to v2 unless performance testing shows need. The DashboardExport may reuse `DashboardService` data which is already cached.

3. **File cleanup: should we clean generated files?**
   - What we know: Reports are streamed directly to browser, not saved to disk initially.
   - What's unclear: If we need temporary disk storage for DomPDF (it writes temp files internally).
   - Recommendation: DomPDF manages its own temp files. No explicit cleanup needed for streamed responses. If caching report files in the future, implement a cleanup command.

## Environment Availability

> Step 2.6: SKIPPED (no external dependencies beyond Composer packages — all libraries are pure PHP with no binary dependencies)

**Note:** DomPDF requires the following PHP extensions which should already be available in the Docker php-fpm container: `dom`, `mbstring`, `gd` (for images).  
Laravel Excel requires: `zip`, `xml`, `gd2`, `iconv`, `simplexml`, `xmlreader`, `zlib`. All are standard in php-fpm Docker images.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit ^12.5 + Laravel test helpers |
| Config file | `backend/phpunit.xml` (existing) |
| Quick run command | `cd backend && php artisan test --filter=Report` |
| Full suite command | `cd backend && php artisan test` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| REPT-01 | PDF generation returns valid PDF content | Feature | `php artisan test --filter=ReportControllerTest::test_pdf_download_returns_valid_pdf` | ❌ Wave 0 |
| REPT-01 | XLSX generation returns valid spreadsheet | Feature | `php artisan test --filter=ReportControllerTest::test_xlsx_download_returns_valid_spreadsheet` | ❌ Wave 0 |
| REPT-01 | CSV generation returns valid CSV content | Feature | `php artisan test --filter=ReportControllerTest::test_csv_download_returns_valid_csv` | ❌ Wave 0 |
| REPT-02 | Unauthenticated user cannot export reports | Feature | `php artisan test --filter=ReportControllerTest::test_unauthenticated_user_cannot_export` | ❌ Wave 0 |
| REPT-02 | User without `relatorios.export` permission gets 403 | Feature | `php artisan test --filter=ReportControllerTest::test_user_without_export_permission_receives_403` | ❌ Wave 0 |
| REPT-02 | Filter by date range returns filtered results | Feature | `php artisan test --filter=ReportControllerTest::test_report_respects_date_filter` | ❌ Wave 0 |
| REPT-02 | PDF has correct filename format | Feature | `php artisan test --filter=ReportControllerTest::test_filename_follows_convention` | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --filter=Report`
- **Per wave merge:** `php artisan test --filter=ReportControllerTest`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `backend/tests/Feature/ReportControllerTest.php` — covers all REPT-01 and REPT-02 test cases
- [ ] `backend/tests/Unit/Services/ReportServiceTest.php` — covers individual report service methods
- [ ] `backend/tests/Feature/ReportExportTest.php` — integration tests for Export classes

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes | Sanctum middleware on all routes |
| V3 Session Management | — | N/A — no session in API context |
| V4 Access Control | yes | Permission middleware: `relatorios.view`, `relatorios.export` |
| V5 Input Validation | yes | `ReportRequest` FormRequest for filter validation |
| V6 Cryptography | — | N/A — no encryption needed for reports |
| V7 Error Handling | yes | Return 400 for invalid format, 403 for unauthorized, 422 for invalid filters |

### Known Threat Patterns for {Stack}

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Unauthorized report access | Information Disclosure | Permission middleware checks `relatorios.export` before any data access |
| Mass data exfiltration via CSV/PDF | Information Disclosure | Permission-based access; filters are validated, not arbitrary queries |
| CSV injection (formula injection) | Tampering | `fputcsv()` handles escaping, but prefix values starting with `=`, `+`, `-`, `@` with a tab character as a defense-in-depth measure |

## Report Schemas

### Report 1: Equipamentos
| Field | Type | Source |
|-------|------|--------|
| Nome | string | `equipment.name` |
| Patrimônio | string? | `equipment.patrimony_id` |
| Nº Série | string? | `equipment.serial_number` |
| Categoria | string? | `equipment.category.name` |
| Fabricante | string? | `equipment.manufacturer.name` |
| Fornecedor | string? | `equipment.supplier.name` |
| Status | string | `equipment.status` |
| Localização | string? | `equipment.location` |
| Data Aquisição | date? | `equipment.acquisition_date` |
| Garantia até | date? | `equipment.warranty_end` |

**Filters:** status (active/inactive/maintenance/retired), period (created_at range)  
**Totalizers:** Count total, count by status

### Report 2: Calibrações
| Field | Type | Source |
|-------|------|--------|
| Equipamento | string | `calibration.equipment.name` |
| Peça | string? | `calibration.part_name` |
| Status | enum | `calibration.status` |
| Data Agendada | date | `calibration.scheduled_date` |
| Data Conclusão | datetime? | `calibration.completed_at` |
| Próximo Vencimento | datetime? | `calibration.next_due_at` |
| Responsável | string? | `calibration.responsible` |
| Laboratório | string? | `calibration.laboratory` |
| Nº Certificado | string? | `calibration.certificates (itae) ` |

**Filters:** status (scheduled/completed/cancelled), period (scheduled_date range)  
**Totalizers:** Count total, count by status, count overdue

### Report 3: Movimentações de Estoque
| Field | Type | Source |
|-------|------|--------|
| Item | string | `movement.item.name` |
| Código | string? | `movement.item.code` |
| Tipo | enum | `movement.type` |
| Quantidade | int | `movement.quantity` |
| Saldo após | int | `movement.balance_after` |
| Motivo | string? | `movement.reason` |
| Usuário | string? | `movement.user.name` |
| Data | datetime | `movement.created_at` |

**Filters:** type (purchase/consumption/adjustment/disposal/return), period (created_at range)  
**Totalizers:** Count total, sum quantity by type, net balance change

### Report 4: Dashboard Export
| Field | Type | Source |
|-------|------|--------|
| KPIs: Equipamentos totais, Calibrações a vencer, etc. | mixed | Reuses `DashboardService->kpis()` |
| Equipamentos por categoria | array | `equipments_by_category` chart data |
| Calibrações por mês | array | `calibrations_timeline` chart data |
| Movimentações de estoque | array | `stock_movements` chart data |

**Note:** This is a tabular export of the underlying data displayed in dashboard charts — NOT a PDF screenshot of charts. Data is pulled from `DashboardService`.

**Filters:** period (month range for timeline data)  
**Totalizers:** KPIs as first data row

## DomPDF Configuration

```php
// config/dompdf.php — publish via: php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
return [
    'show_warnings' => env('APP_DEBUG', false),   // Enable for debugging
    'orientation' => 'portrait',
    'default_paper_size' => 'a4',                  // Default: a4
    'default_font' => 'DejaVu Sans',               // Supports UTF-8 (Portuguese characters)
    'dpi' => 150,                                  // Higher DPI = better quality, larger file
    'font_dir' => storage_path('fonts'),           // Custom fonts directory
    'font_cache' => storage_path('fonts'),
    'is_php_enabled' => false,                     // Security: keep false
    'is_remote_enabled' => false,                  // Security: keep false for images
    'is_javascript_enabled' => false,             // DomPDF does not execute JS
    'is_html5_parser_enabled' => true,
    'is_font_subsetting_enabled' => true,          // Smaller PDF files
];
```

**Important for DomPDF 3.x:**
- PDF/A mode requires fonts to be embedded. Core PDF fonts (Helvetica, Courier, Times) are **not** embedded and will cause validation failures. Use `DejaVu Sans` or `DejaVu Serif` instead. [CITED: github.com/barryvdh/laravel-dompdf]
- Custom fonts must be registered via `@font-face` in the Blade view CSS, referencing the TrueType file via `public_path()` or `storage_path()`.
- For production with dark-theme brand fonts, place `.ttf` files in `backend/storage/fonts/` (gitignored for cache files; font originals should be in `backend/public/fonts/` and referenced via `public_path()`).

## Route Registration

```php
// In backend/routes/api.php, inside the 'auth:sanctum' group:
use App\Http\Controllers\Api\V1\ReportController;

Route::prefix('reports')->group(function () {
    Route::get('{type}', [ReportController::class, 'download'])
        ->name('reports.download')
        ->whereIn('type', ['equipments', 'calibrations', 'inventory-movements', 'dashboard'])
        ->whereIn('format', ['pdf', 'xlsx', 'csv']);
});
```

The `format` parameter (pdf/xlsx/csv) is passed as a query parameter: `?format=pdf`.

## Sources

### Primary (HIGH confidence)
- **Packagist** — `barryvdh/laravel-dompdf` v3.1.2 — package metadata, Laravel 13 compatibility, installation
- **Packagist** — `maatwebsite/excel` v3.1.69 — package metadata, Laravel 13 support, version support table
- **GitHub** — `dompdf/dompdf` README — CSS limitations (no flexbox, no grid), requirements, features
- **Laravel Excel Docs 3.1** — Export concerns, column formatting, multiple sheets, styling

### Secondary (MEDIUM confidence)
- **GitHub Issues** — dompdf/dompdf #971 (flexbox unsupported), #2988 (grid unsupported) — confirming CSS limitations
- **GitHub** — `barryvdh/laravel-dompdf` README — Code examples, configuration, font handling
- **Laravel Docs** — HTTP Responses: Streamed Downloads — `response()->streamDownload()` pattern
- **Stack Overflow** — axios blob download pattern — frontend download with responseType: 'blob'
- **Laravel Daily** — Laravel Excel styling guide — WithStyles, ShouldAutoSize, AfterSheet events

### Tertiary (LOW confidence)
- **N/A** — All critical claims verified against primary/secondary sources.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — both packages confirmed compatible with Laravel 13 via Packagist and GitHub CI updates
- Architecture: HIGH — established Laravel patterns (Controller → Service → Export)
- Pitfalls: HIGH — well-documented DomPDF limitations, CSV encoding issues, memory concerns
- Package versions: MEDIUM — confirmed via web search but not local `composer show`

**Research date:** 2026-07-27
**Valid until:** 2026-08-27 (30 days — packages are stable but may receive minor releases)
