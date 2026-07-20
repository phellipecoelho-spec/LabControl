<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Manufacturer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManufacturerController extends Controller
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
     * Display a listing of manufacturers.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');

        $manufacturers = Manufacturer::query()
            ->when($search, fn ($query) => $query->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name', 'asc')
            ->paginate(50);

        return response()->json($manufacturers);
    }

    /**
     * Store a newly created manufacturer.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country' => 'nullable|string|max:100',
            'website' => 'nullable|url',
        ]);

        $manufacturer = Manufacturer::create($validated);

        return response()->json($manufacturer, 201);
    }

    /**
     * Update the specified manufacturer.
     */
    public function update(Request $request, Manufacturer $manufacturer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'country' => 'nullable|string|max:100',
            'website' => 'nullable|url',
        ]);

        $manufacturer->update($validated);

        return response()->json($manufacturer);
    }

    /**
     * Remove the specified manufacturer.
     */
    public function destroy(Manufacturer $manufacturer): JsonResponse
    {
        // Check if manufacturer has linked equipment
        if (Equipment::where('manufacturer_id', $manufacturer->id)->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir fabricante com equipamentos vinculados',
            ], 409);
        }

        $manufacturer->delete();

        return response()->json(null, 204);
    }
}