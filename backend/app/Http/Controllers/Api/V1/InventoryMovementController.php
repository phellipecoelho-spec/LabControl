<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryMovementRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Services\InventoryMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryMovementController extends Controller
{
    /**
     * Get the middleware that should be applied to the controller.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function middleware(): array
    {
        return [
            ['middleware' => 'auth:sanctum', 'options' => ['only' => ['index', 'show', 'store', 'byItem']]],
            ['middleware' => 'permission:movimentacoes.view', 'options' => ['only' => ['index', 'show', 'byItem']]],
            ['middleware' => 'permission:movimentacoes.create', 'options' => ['only' => ['store']]],
        ];
    }

    /**
     * Display a listing of inventory movements.
     */
    public function index(Request $request)
    {
        $item_id = $request->input('item_id');
        $type = $request->input('type');
        $from = $request->input('from');
        $to = $request->input('to');
        $user_id = $request->input('user_id');

        $movements = InventoryMovement::query()
            ->with(['item:id,name', 'user:id,name'])
            ->when($item_id, fn ($query) => $query->byItem($item_id))
            ->when($type, fn ($query) => $query->byType($type))
            ->when($user_id, fn ($query) => $query->byUser($user_id))
            ->when($from && $to, fn ($query) => $query->byDateRange($from, $to))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return InventoryMovementResource::collection($movements);
    }

    /**
     * Store a newly created inventory movement.
     *
     * Delegates to InventoryMovementService for transactional integrity (D-06).
     * Returns is_critical flag in meta when item reaches critical stock (D-11).
     */
    public function store(StoreInventoryMovementRequest $request)
    {
        $data = $request->validated();

        try {
            $movement = app(InventoryMovementService::class)->recordMovement($data);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'insufficient_stock',
            ], 422);
        }

        $movement->load(['item:id,name', 'user:id,name']);

        // Check if item is now at critical stock level
        $item = InventoryItem::find($data['item_id']);
        $isCritical = $item->is_critical;

        return (new InventoryMovementResource($movement))
            ->additional([
                'meta' => [
                    'is_critical' => $isCritical,
                ],
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified inventory movement.
     */
    public function show(InventoryMovement $inventoryMovement): InventoryMovementResource
    {
        $inventoryMovement->load(['item:id,name', 'user:id,name']);

        return new InventoryMovementResource($inventoryMovement);
    }

    /**
     * Display movements for a specific inventory item.
     */
    public function byItem(InventoryItem $item)
    {
        $movements = $item->movements()
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return InventoryMovementResource::collection($movements);
    }
}
