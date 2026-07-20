<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get the middleware that should be applied to the controller.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function middleware(): array
    {
        return [
            ['middleware' => 'auth:sanctum', 'options' => ['only' => ['index', 'store', 'update', 'destroy']]],
            ['middleware' => 'permission', 'options' => ['only' => ['index'], 'permissions' => ['equipamentos.view']]],
            ['middleware' => 'permission', 'options' => ['only' => ['store'], 'permissions' => ['equipamentos.create']]],
            ['middleware' => 'permission', 'options' => ['only' => ['update'], 'permissions' => ['equipamentos.edit']]],
            ['middleware' => 'permission', 'options' => ['only' => ['destroy'], 'permissions' => ['equipamentos.delete']]],
        ];
    }

    /**
     * Display a listing of categories.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');

        $categories = Category::query()
            ->when($search, fn ($query) => $query->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name', 'asc')
            ->paginate(50);

        return response()->json($categories);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories,slug',
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:categories,slug,' . $category->id,
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category): JsonResponse
    {
        // Check if category has linked equipment
        if (Equipment::where('category_id', $category->id)->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir categoria com equipamentos vinculados',
            ], 409);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}