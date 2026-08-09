<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
            new Middleware('permission:dashboard.view'),
        ];
    }

    public function __invoke(Request $request, DashboardService $service)
    {
        $startDate = $request->date('start_date') ?? now()->subMonths(12);
        $endDate = $request->date('end_date') ?? now();

        $data = $service->aggregate($startDate, $endDate);

        return response()->json($data);
    }
}
