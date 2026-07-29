<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Equipamentos", description="Endpoints de gerenciamento de equipamentos")
 */
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
     *
     * @OA\Get(
     *     path="/api/v1/equipments",
     *     summary="Listar equipamentos",
     *     tags={"Equipamentos"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", description="Buscar por nome, número de série ou patrimônio", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category_id", in="query", description="Filtrar por categoria", @OA\Schema(type="string")),
     *     @OA\Parameter(name="manufacturer_id", in="query", description="Filtrar por fabricante", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Filtrar por status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="location", in="query", description="Filtrar por localização", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Itens por página", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Lista paginada de equipamentos", @OA\JsonContent(ref="#/components/schemas/EquipmentCollection")),
     *     @OA\Response(response=401, description="Não autenticado"),
     *     @OA\Response(response=403, description="Sem permissão")
     * )
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
     *
     * @OA\Get(
     *     path="/api/v1/equipments/{id}",
     *     summary="Obter equipamento por ID",
     *     tags={"Equipamentos"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID do equipamento (UUID)", @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Equipamento encontrado", @OA\JsonContent(ref="#/components/schemas/EquipmentResource")),
     *     @OA\Response(response=401, description="Não autenticado"),
     *     @OA\Response(response=403, description="Sem permissão"),
     *     @OA\Response(response=404, description="Equipamento não encontrado")
     * )
     */
    public function show(Equipment $equipment): EquipmentResource
    {
        $equipment->load(['category', 'manufacturer', 'supplier', 'photos']);

        return new EquipmentResource($equipment);
    }

    /**
     * Store a newly created equipment.
     *
     * @OA\Post(
     *     path="/api/v1/equipments",
     *     summary="Criar equipamento",
     *     tags={"Equipamentos"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreEquipmentRequest")
     *     ),
     *     @OA\Response(response=201, description="Equipamento criado", @OA\JsonContent(ref="#/components/schemas/EquipmentResource")),
     *     @OA\Response(response=401, description="Não autenticado"),
     *     @OA\Response(response=403, description="Sem permissão"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
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
     *
     * @OA\Put(
     *     path="/api/v1/equipments/{id}",
     *     summary="Atualizar equipamento",
     *     tags={"Equipamentos"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID do equipamento (UUID)", @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateEquipmentRequest")
     *     ),
     *     @OA\Response(response=200, description="Equipamento atualizado", @OA\JsonContent(ref="#/components/schemas/EquipmentResource")),
     *     @OA\Response(response=401, description="Não autenticado"),
     *     @OA\Response(response=403, description="Sem permissão"),
     *     @OA\Response(response=404, description="Equipamento não encontrado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
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
     *
     * @OA\Delete(
     *     path="/api/v1/equipments/{id}",
     *     summary="Excluir equipamento (soft delete)",
     *     tags={"Equipamentos"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID do equipamento (UUID)", @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=204, description="Equipamento excluído"),
     *     @OA\Response(response=401, description="Não autenticado"),
     *     @OA\Response(response=403, description="Sem permissão"),
     *     @OA\Response(response=404, description="Equipamento não encontrado")
     * )
     */
    public function destroy(Equipment $equipment): JsonResponse
    {
        $equipment->deleted_by = auth()->id();
        $equipment->save();
        $equipment->delete();

        return response()->json(null, 204);
    }
}