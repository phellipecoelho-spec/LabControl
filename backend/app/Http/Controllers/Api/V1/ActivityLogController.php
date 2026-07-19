<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ActivityLogController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
            new Middleware('permission:auditoria.view'),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::with('user:id,name,email')
            ->module($request->module)
            ->action($request->action)
            ->byUser($request->user_id)
            ->dateRange($request->date_from, $request->date_to)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($logs);
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        return response()->json($activityLog->load('user:id,name,email'));
    }

    public function modules(): JsonResponse
    {
        $modules = ActivityLog::select('module')->distinct()->pluck('module');

        return response()->json($modules);
    }
}
