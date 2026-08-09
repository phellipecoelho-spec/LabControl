<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class InventoryItemController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be applied to the controller.
     *
     * @return array<int, \Illuminate\Routing\Controllers\Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum', only: ['index', 'show', 'store', 'update', 'destroy']),
            new Middleware('permission:estoque.view', only: ['index', 'show']),
            new Middleware('permission:estoque.create', only: ['store']),
            new Middleware('permission:estoque.edit', only: ['update']),
            new Middleware('permission:estoque.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of inventory items.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $category_id = $request->input('category_id');
        $supplier_id = $request->input('supplier_id');
        $unit = $request->input('unit');
        $critical = $request->input('critical');

        $items = InventoryItem::query()
            ->with(['category', 'supplier'])
            ->when($search, fn ($query) => $query->search($search))
            ->when($category_id, fn ($query) => $query->byCategory($category_id))
            ->when($supplier_id, fn ($query) => $query->bySupplier($supplier_id))
            ->when($unit, fn ($query) => $query->byUnit($unit))
            ->when($critical, fn ($query) => $query->critical())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return InventoryItemResource::collection($items);
    }

    /**
     * Display the specified inventory item.
     */
    public function show(InventoryItem $inventoryItem): InventoryItemResource
    {
        $inventoryItem->load(['category', 'supplier']);

        return new InventoryItemResource($inventoryItem);
    }

    /**
     * Store a newly created inventory item.
     *
     * Creates an initial purchase movement when initial_quantity > 0 (D-10).
     */
    public function store(StoreInventoryItemRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        $item = DB::transaction(function () use ($data) {
            $item = InventoryItem::create($data);

            // Create initial purchase movement if initial_quantity > 0 (D-10)
            if (!empty($data['initial_quantity']) && $data['initial_quantity'] > 0) {
                $item->movements()->create([
                    'item_id' => $item->id,
                    'type' => 'purchase',
                    'quantity' => $data['initial_quantity'],
                    'balance_after' => $data['initial_quantity'],
                    'reason' => 'Saldo inicial',
                    'user_id' => auth()->id(),
                ]);
            }

            return $item;
        });

        $item->load(['category', 'supplier']);

        return (new InventoryItemResource($item))->response()->setStatusCode(201);
    }

    /**
     * Update the specified inventory item.
     */
    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): InventoryItemResource
    {
        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        $inventoryItem->update($data);
        $inventoryItem->load(['category', 'supplier']);

        return new InventoryItemResource($inventoryItem);
    }

    /**
     * Remove the specified inventory item.
     */
    public function destroy(InventoryItem $inventoryItem): JsonResponse
    {
        $inventoryItem->deleted_by = auth()->id();
        $inventoryItem->save();
        $inventoryItem->delete();

        return response()->json(null, 204);
    }

}
