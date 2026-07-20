<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
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
            ['middleware' => 'permission:equipamentos.view', 'options' => ['only' => ['index']]],
            ['middleware' => 'permission:equipamentos.create', 'options' => ['only' => ['store']]],
            ['middleware' => 'permission:equipamentos.edit', 'options' => ['only' => ['update']]],
            ['middleware' => 'permission:equipamentos.delete', 'options' => ['only' => ['destroy']]],
        ];
    }

    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');

        $suppliers = Supplier::query()
            ->when($search, fn ($query) => $query->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name', 'asc')
            ->paginate(50);

        return response()->json($suppliers);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cnpj' => 'nullable|string|unique:suppliers,cnpj',
            'contact_email' => 'nullable|email',
        ]);

        $supplier = Supplier::create($validated);

        return response()->json($supplier, 201);
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'cnpj' => 'sometimes|string|unique:suppliers,cnpj,' . $supplier->id,
            'contact_email' => 'nullable|email',
        ]);

        $supplier->update($validated);

        return response()->json($supplier);
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        // Check if supplier has linked equipment
        if (Equipment::where('supplier_id', $supplier->id)->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir fornecedor com equipamentos vinculados',
            ], 409);
        }

        $supplier->delete();

        return response()->json(null, 204);
    }
}