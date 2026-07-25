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
     */
    public function show(Calibration $calibration): CalibrationResource
    {
        $calibration->load(['equipment', 'createdBy', 'certificates']);

        return new CalibrationResource($calibration);
    }

    /**
     * Store a newly created calibration.
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
