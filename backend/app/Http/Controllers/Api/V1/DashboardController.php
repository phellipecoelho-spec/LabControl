<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public static function middleware(): array
    {
        return [
            ['middleware' => 'auth:sanctum'],
            ['middleware' => 'permission:dashboard.view'],
        ];
    }

    public function __invoke(Request $request, DashboardService $service)
    {
        $startDate = $request->date('start_date', now()->subMonths(12));
        $endDate = $request->date('end_date', now());

        $data = $service->aggregate($startDate, $endDate);

        return response()->json($data);
    }
}
