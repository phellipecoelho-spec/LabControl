<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CalibrationStatus;
use App\Exceptions\CalibrationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteCalibrationRequest;
use App\Http\Requests\StoreCalibrationRequest;
use App\Http\Requests\UpdateCalibrationRequest;
use App\Http\Resources\CalibrationCollection;
use App\Http\Resources\CalibrationResource;
use App\Models\Calibration;
use App\Services\CalibrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Calibrações", description="Endpoints de gerenciamento de calibrações")
 */
class CalibrationController extends Controller
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
                'index', 'show', 'store', 'update', 'destroy', 'complete', 'cancel',
            ]]],
            ['middleware' => 'permission:calibracoes.view', 'options' => ['only' => ['index', 'show']]],
            ['middleware' => 'permission:calibracoes.create', 'options' => ['only' => ['store']]],
            ['middleware' => 'permission:calibracoes.edit', 'options' => ['only' => ['update', 'destroy']]],
            ['middleware' => 'permission:calibracoes.concluir', 'options' => ['only' => ['complete']]],
            ['middleware' => 'permission:calibracoes.cancel', 'options' => ['only' => ['cancel']]],
        ];
    }

    /**
     * Display a paginated listing of calibrations with filters.
     */
    /**
     * Display a paginated listing of calibrations with filters.
     *
     * @OA\Get(
     *     path="/api/v1/calibrations",
     *     summary="Listar calibrações",
     *     tags={"Calibrações"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="equipment_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="laboratory", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Lista paginada de calibrações"),
     *     @OA\Response(response=401, description="Não autenticado"),
     *     @OA\Response(response=403, description="Sem permissão")
     * )
     */
    public function index(Request $request)
    {
        $equipment_id = $request->input('equipment_id');
        $status = $request->input('status');
        $from = $request->input('from');
        $to = $request->input('to');
        $laboratory = $request->input('laboratory');

        $calibrations = Calibration::query()
            ->with(['equipment:id,name,patrimony_id'])
            ->when($equipment_id, fn ($q) => $q->byEquipment($equipment_id))
            ->when($status, fn ($q) => $q->byStatus($status))
            ->when($from && $to, fn ($q) => $q->byDateRange($from, $to))
            ->when($laboratory, fn ($q) => $q->byLaboratory($laboratory))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return new CalibrationCollection($calibrations);
    }

    /**
     * Display the specified calibration with all relationships.
     *
     * @OA\Get(
     *     path="/api/v1/calibrations/{calibration}",
     *     summary="Obter detalhes de uma calibração",
     *     tags={"Calibrações"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="calibration", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Detalhes da calibração"),
     *     @OA\Response(response=401, description="Não autenticado"),
     *     @OA\Response(response=403, description="Sem permissão"),
     *     @OA\Response(response=404, description="Não encontrado")
     * )
     */
    public function show(Calibration $calibration): CalibrationResource
    {
        $calibration->load(['equipment', 'createdBy', 'certificates']);

        return new CalibrationResource($calibration);
    }

    /**
     * Store a newly created calibration.
     *
     * @OA\Post(
     *     path="/api/v1/calibrations",
     *     summary="Criar calibração",
     *     tags={"Calibrações"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreCalibrationRequest")),
     *     @OA\Response(response=201, description="Calibração criada"),
     *     @OA\Response(response=401, description="Não autenticado"),
     *     @OA\Response(response=403, description="Sem permissão"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function store(StoreCalibrationRequest $request)
    {
        $data = $request->validated();

        try {
            $calibration = app(CalibrationService::class)->create($data);
        } catch (CalibrationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'calibration_error',
            ], 422);
        }

        return (new CalibrationResource($calibration))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the specified calibration.
     * Only allowed when status is scheduled.
     *
     * @OA\Put(
     *     path="/api/v1/calibrations/{calibration}",
     *     summary="Atualizar calibração (apenas status agendada)",
     *     tags={"Calibrações"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="calibration", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateCalibrationRequest")),
     *     @OA\Response(response=200, description="Calibração atualizada"),
     *     @OA\Response(response=401, description="Não autenticado"),
     *     @OA\Response(response=403, description="Sem permissão"),
     *     @OA\Response(response=422, description="Dados inválidos ou status não permite edição")
     * )
     */
    public function update(UpdateCalibrationRequest $request, Calibration $calibration)
    {
        if ($calibration->status !== CalibrationStatus::Scheduled) {
            return response()->json([
                'message' => 'Apenas calibrações com status "Agendada" podem ser editadas.',
                'error' => 'calibration_error',
            ], 422);
        }

        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        $calibration->update($data);
        $calibration->load(['equipment:id,name,patrimony_id']);

        return new CalibrationResource($calibration);
    }

    /**
     * Remove the specified calibration (soft delete).
     *
     * @OA\Delete(
     *     path="/api/v1/calibrations/{calibration}",
     *     summary="Excluir calibração (soft delete)",
     *     tags={"Calibrações"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="calibration", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=204, description="Calibração excluída"),
     *     @OA\Response(response=401, description="Não autenticado"),
     *     @OA\Response(response=403, description="Sem permissão"),
     *     @OA\Response(response=404, description="Não encontrado")
     * )
     */
    public function destroy(Calibration $calibration): JsonResponse
    {
        $calibration->deleted_by = auth()->id();
        $calibration->save();
        $calibration->delete();

        return response()->json(null, 204);
    }

    /**
     * Complete a scheduled calibration.
     *
     * @OA\Post(
     *     path="/api/v1/calibrations/{calibration}/complete",
     *     summary="Concluir calibração",
     *     tags={"Calibrações"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="calibration", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CompleteCalibrationRequest")),
     *     @OA\Response(response=200, description="Calibração concluída"),
     *     @OA\Response(response=401, description="Não autenticado"),
     *     @OA\Response(response=403, description="Sem permissão"),
     *     @OA\Response(response=422, description="Calibração não pode ser concluída")
     * )
     */
    public function complete(CompleteCalibrationRequest $request, Calibration $calibration)
    {
        try {
            $calibration = app(CalibrationService::class)->complete($calibration, $request->validated());
        } catch (CalibrationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'calibration_error',
            ], $e->getCode());
        }

        $calibration->load(['equipment:id,name,patrimony_id']);

        return new CalibrationResource($calibration);
    }

    /**
     * Cancel a scheduled calibration.
     *
     * @OA\Post(
     *     path="/api/v1/calibrations/{calibration}/cancel",
     *     summary="Cancelar calibração",
     *     tags={"Calibrações"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="calibration", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Calibração cancelada"),
     *     @OA\Response(response=401, description="Não autenticado"),
     *     @OA\Response(response=403, description="Sem permissão"),
     *     @OA\Response(response=422, description="Calibração não pode ser cancelada")
     * )
     */
    public function cancel(Calibration $calibration)
    {
        try {
            $calibration = app(CalibrationService::class)->cancel($calibration);
        } catch (CalibrationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'calibration_error',
            ], $e->getCode());
        }

        $calibration->load(['equipment:id,name,patrimony_id']);

        return new CalibrationResource($calibration);
    }
}
