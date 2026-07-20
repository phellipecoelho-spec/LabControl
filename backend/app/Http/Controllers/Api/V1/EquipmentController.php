<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    /**
     * Get the middleware that should be applied to the controller.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function middleware(): array
    {
        return [
            ['middleware' => 'auth:sanctum', 'options' => ['only' => ['index', 'show', 'store', 'update', 'destroy']]],
            ['middleware' => 'permission:equipamentos.view', 'options' => ['only' => ['index', 'show']]],
            ['middleware' => 'permission:equipamentos.create', 'options' => ['only' => ['store']]],
            ['middleware' => 'permission:equipamentos.edit', 'options' => ['only' => ['update']]],
            ['middleware' => 'permission:equipamentos.delete', 'options' => ['only' => ['destroy']]],
        ];
    }

    /**
     * Display a listing of equipment.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $category_id = $request->input('category_id');
        $manufacturer_id = $request->input('manufacturer_id');
        $status = $request->input('status');
        $location = $request->input('location');

        $equipments = Equipment::query()
            ->with(['category', 'manufacturer', 'supplier', 'photos'])
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('serial_number', 'ilike', "%{$search}%")
                    ->orWhere('patrimony_id', 'ilike', "%{$search}%");
            }))
            ->when($category_id, fn ($query) => $query->where('category_id', $category_id))
            ->when($manufacturer_id, fn ($query) => $query->where('manufacturer_id', $manufacturer_id))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($location, fn ($query) => $query->where('location', 'ilike', "%{$location}%"))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return EquipmentResource::collection($equipments);
    }

    /**
     * Display the specified equipment.
     */
    public function show(Equipment $equipment): EquipmentResource
    {
        $equipment->load(['category', 'manufacturer', 'supplier', 'photos']);

        return new EquipmentResource($equipment);
    }

    /**
     * Store a newly created equipment.
     */
    public function store(StoreEquipmentRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        $equipment = Equipment::create($data);
        $equipment->load(['category', 'manufacturer', 'supplier', 'photos']);

        return (new EquipmentResource($equipment))->response()->setStatusCode(201);
    }

    /**
     * Update the specified equipment.
     */
    public function update(UpdateEquipmentRequest $request, Equipment $equipment)
    {
        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        $equipment->update($data);
        $equipment->load(['category', 'manufacturer', 'supplier', 'photos']);

        return new EquipmentResource($equipment);
    }

    /**
     * Remove the specified equipment.
     */
    public function destroy(Equipment $equipment): JsonResponse
    {
        $equipment->deleted_by = auth()->id();
        $equipment->save();
        $equipment->delete();

        return response()->json(null, 204);
    }
}