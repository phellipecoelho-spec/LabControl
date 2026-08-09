<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryCategoryRequest;
use App\Http\Requests\UpdateInventoryCategoryRequest;
use App\Http\Resources\InventoryCategoryResource;
use App\Models\InventoryCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class InventoryCategoryController extends Controller implements HasMiddleware
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
     * Display a listing of inventory categories.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $categories = InventoryCategory::query()
            ->when($search, fn ($query) => $query->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15);

        return InventoryCategoryResource::collection($categories);
    }

    /**
     * Display the specified inventory category.
     */
    public function show(InventoryCategory $inventoryCategory): InventoryCategoryResource
    {
        return new InventoryCategoryResource($inventoryCategory);
    }

    /**
     * Store a newly created inventory category.
     */
    public function store(StoreInventoryCategoryRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $category = InventoryCategory::create($data);

        return (new InventoryCategoryResource($category))->response()->setStatusCode(201);
    }

    /**
     * Update the specified inventory category.
     */
    public function update(UpdateInventoryCategoryRequest $request, InventoryCategory $inventoryCategory): InventoryCategoryResource
    {
        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        $inventoryCategory->update($data);

        return new InventoryCategoryResource($inventoryCategory);
    }

    /**
     * Remove the specified inventory category.
     */
    public function destroy(InventoryCategory $inventoryCategory): JsonResponse
    {
        $inventoryCategory->deleted_by = auth()->id();
        $inventoryCategory->save();
        $inventoryCategory->delete();

        return response()->json(null, 204);
    }
}
