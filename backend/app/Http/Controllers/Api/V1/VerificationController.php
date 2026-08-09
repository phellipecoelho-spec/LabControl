<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\VerificationResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVerificationRequest;
use App\Http\Requests\UpdateVerificationRequest;
use App\Http\Resources\VerificationCollection;
use App\Http\Resources\VerificationResource;
use App\Models\User;
use App\Models\Verification;
use App\Notifications\ToleranceExceeded;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Notification;

class VerificationController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be applied to the controller.
     *
     * @return array<int, \Illuminate\Routing\Controllers\Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum', only: [
                'index', 'show', 'store', 'update', 'destroy', 'pending', 'byEquipment',
            ]),
            new Middleware('permission:afericoes.view', only: ['index', 'show', 'pending', 'byEquipment']),
            new Middleware('permission:afericoes.create', only: ['store']),
            new Middleware('permission:afericoes.edit', only: ['update', 'destroy']),
        ];
    }

    /**
     * Display a paginated listing of verifications with filters.
     */
    public function index(Request $request)
    {
        $verifications = Verification::query()
            ->with(['equipment:id,name,patrimony_id'])
            ->when($request->filled('equipment_id'), fn ($q) => $q->byEquipment($request->equipment_id))
            ->when($request->filled('date_from') && $request->filled('date_to'), fn ($q) => $q->byDateRange($request->date_from, $request->date_to))
            ->orderBy('verified_at', 'desc')
            ->paginate(15);

        return new VerificationCollection($verifications);
    }

    /**
     * Get equipment with pending verifications (D-08).
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $pending = app(VerificationService::class)->getPendingVerifications();

            return response()->json([
                'data' => $pending->map(fn ($equipment) => [
                    'id' => $equipment->id,
                    'name' => $equipment->name,
                    'patrimony_id' => $equipment->patrimony_id,
                    'serial_number' => $equipment->serial_number,
                    'category' => $equipment->category ? [
                        'id' => $equipment->category->id,
                        'name' => $equipment->category->name,
                    ] : null,
                    'last_verification_at' => $equipment->lastVerification?->verified_at?->toISOString(),
                    'verification_frequency' => $equipment->verification_frequency,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar equipamentos pendentes.',
                'error' => 'verification_error',
            ], 500);
        }
    }

    /**
     * Store a newly created verification.
     */
    public function store(StoreVerificationRequest $request)
    {
        $data = $request->validated();
        $data['verified_at'] = $data['verified_at'] ?? now()->toISOString();

        $verification = app(VerificationService::class)->create($data);

        // Reload with all relationships for the resource
        $verification->load(['equipment', 'operator', 'params.template']);

        // Check for outside_range and dispatch notifications synchronously (D-11, D-12, D-13)
        $hasOutsideRange = $verification->params->some(
            fn ($p) => $p->result === VerificationResult::OutsideRange
        );

        if ($hasOutsideRange) {
            // Notify the operator
            if ($verification->operator) {
                $verification->operator->notify(new ToleranceExceeded($verification));
            }

            // Notify all supervisors
            $supervisors = User::whereHas('roles.permissions', function ($q) {
                $q->where('slug', 'afericoes.edit');
            })->get();

            if ($supervisors->isNotEmpty()) {
                Notification::send($supervisors, new ToleranceExceeded($verification));
            }
        }

        return (new VerificationResource($verification))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified verification with all relationships.
     */
    public function show(Verification $verification): VerificationResource
    {
        $verification->load(['equipment', 'operator', 'params.template', 'createdBy']);

        return new VerificationResource($verification);
    }

    /**
     * Update the specified verification.
     */
    public function update(UpdateVerificationRequest $request, Verification $verification)
    {
        $data = $request->validated();

        // Only operator or user with afericoes.edit permission can update
        if ($verification->operator_id !== auth()->id() && ! auth()->user()->hasPermission('afericoes.edit')) {
            return response()->json([
                'message' => 'Você não tem permissão para editar esta aferição.',
                'error' => 'verification_error',
            ], 403);
        }

        $data['updated_by'] = auth()->id();

        $verification = app(VerificationService::class)->update($verification, $data);
        $verification->load(['equipment', 'operator', 'params.template']);

        return new VerificationResource($verification);
    }

    /**
     * Remove the specified verification (soft delete).
     */
    public function destroy(Verification $verification): JsonResponse
    {
        $verification->deleted_by = auth()->id();
        $verification->save();
        $verification->delete();

        return response()->json(null, 204);
    }

    /**
     * Get paginated verification history for a specific equipment.
     */
    public function byEquipment(string $equipment, Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        $history = app(VerificationService::class)->getHistoryByEquipment($equipment, $perPage);

        return new VerificationCollection($history);
    }
}
