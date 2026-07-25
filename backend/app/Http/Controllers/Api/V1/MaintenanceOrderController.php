<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MaintenanceStatus;
use App\Exceptions\MaintenanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteMaintenanceOrderRequest;
use App\Http\Requests\StoreMaintenanceOrderRequest;
use App\Http\Requests\UpdateMaintenanceOrderRequest;
use App\Http\Resources\MaintenanceOrderCollection;
use App\Http\Resources\MaintenanceOrderResource;
use App\Models\MaintenanceOrder;
use App\Models\User;
use App\Notifications\MaintenanceOrderCreated;
use App\Services\MaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class MaintenanceOrderController extends Controller
{
    /**
     * Get the middleware that should be applied to the controller.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function middleware(): array
    {
        return [
            ['middleware' => 'auth:sanctum', 'options' => ['only' => [
                'index', 'show', 'store', 'update', 'destroy', 'complete', 'cancel', 'byEquipment',
            ]]],
            ['middleware' => 'permission:manutencoes.view', 'options' => ['only' => ['index', 'show', 'byEquipment']]],
            ['middleware' => 'permission:manutencoes.create', 'options' => ['only' => ['store']]],
            ['middleware' => 'permission:manutencoes.edit', 'options' => ['only' => ['update', 'destroy']]],
            ['middleware' => 'permission:manutencoes.concluir', 'options' => ['only' => ['complete', 'cancel']]],
        ];
    }

    /**
     * Display a paginated listing of maintenance orders with filters.
     */
    public function index(Request $request)
    {
        $equipment_id = $request->input('equipment_id');
        $type = $request->input('type');
        $status = $request->input('status');
        $priority = $request->input('priority');
        $from = $request->input('from');
        $to = $request->input('to');

        $orders = MaintenanceOrder::query()
            ->with(['equipment:id,name,patrimony_id'])
            ->when($equipment_id, fn ($q) => $q->byEquipment($equipment_id))
            ->when($status, fn ($q) => $q->byStatus($status))
            ->when($type, fn ($q) => $q->byType($type))
            ->when($priority, fn ($q) => $q->byPriority($priority))
            ->when($from && $to, fn ($q) => $q->byDateRange($from, $to))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return new MaintenanceOrderCollection($orders);
    }

    /**
     * Display the specified maintenance order with all relationships.
     */
    public function show(MaintenanceOrder $maintenanceOrder): MaintenanceOrderResource
    {
        $maintenanceOrder->load([
            'equipment',
            'assignedTo:id,name',
            'openedBy:id,name',
            'parts.item',
            'createdBy:id,name',
        ]);

        return new MaintenanceOrderResource($maintenanceOrder);
    }

    /**
     * Store a newly created maintenance order.
     */
    public function store(StoreMaintenanceOrderRequest $request)
    {
        $data = $request->validated();

        try {
            $order = app(MaintenanceService::class)->create($data);
        } catch (MaintenanceException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'maintenance_error',
            ], 422);
        }

        // Load equipment for notification
        $order->load(['equipment:id,name,patrimony_id']);

        // Dispatch notification to users with manutencoes.edit permission (D-17, D-18)
        $supervisors = User::whereHas('roles.permissions', function ($q) {
            $q->where('slug', 'manutencoes.edit');
        })->get();

        if ($supervisors->isNotEmpty()) {
            Notification::send($supervisors, new MaintenanceOrderCreated($order));
        }

        return (new MaintenanceOrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the specified maintenance order.
     * Only allowed when status is not completed or cancelled.
     */
    public function update(UpdateMaintenanceOrderRequest $request, MaintenanceOrder $maintenanceOrder)
    {
        $data = $request->validated();

        try {
            $order = app(MaintenanceService::class)->update($maintenanceOrder, $data);
        } catch (MaintenanceException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'maintenance_error',
            ], 422);
        }

        $order->load(['equipment:id,name,patrimony_id', 'assignedTo:id,name']);

        return new MaintenanceOrderResource($order);
    }

    /**
     * Remove the specified maintenance order (soft delete).
     */
    public function destroy(MaintenanceOrder $maintenanceOrder): JsonResponse
    {
        $maintenanceOrder->deleted_by = auth()->id();
        $maintenanceOrder->save();
        $maintenanceOrder->delete();

        return response()->json(null, 204);
    }

    /**
     * Complete a maintenance order.
     */
    public function complete(CompleteMaintenanceOrderRequest $request, MaintenanceOrder $maintenanceOrder)
    {
        try {
            $order = app(MaintenanceService::class)->complete($maintenanceOrder, $request->validated());
        } catch (MaintenanceException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'maintenance_error',
            ], $e->getCode());
        }

        $order->load([
            'equipment:id,name,patrimony_id',
            'parts.item',
        ]);

        return new MaintenanceOrderResource($order);
    }

    /**
     * Cancel a maintenance order.
     */
    public function cancel(Request $request, MaintenanceOrder $maintenanceOrder)
    {
        $reason = $request->input('reason');

        try {
            $order = app(MaintenanceService::class)->cancel($maintenanceOrder, $reason);
        } catch (MaintenanceException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'maintenance_error',
            ], $e->getCode());
        }

        $order->load(['equipment:id,name,patrimony_id']);

        return new MaintenanceOrderResource($order);
    }

    /**
     * Get paginated maintenance history for a specific equipment.
     */
    public function byEquipment(string $equipment, Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        $history = app(MaintenanceService::class)->getHistoryByEquipment($equipment, $perPage);

        return new MaintenanceOrderCollection($history);
    }
}
