<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\LoanException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReturnLoanItemRequest;
use App\Http\Requests\StoreLoanRequest;
use App\Http\Requests\UpdateLoanRequest;
use App\Http\Resources\LoanCollection;
use App\Http\Resources\LoanResource;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
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
                'index', 'show', 'store', 'update', 'destroy',
                'activate', 'returnItem', 'cancel',
            ]]],
            ['middleware' => 'permission:emprestimos.view', 'options' => ['only' => ['index', 'show']]],
            ['middleware' => 'permission:emprestimos.create', 'options' => ['only' => ['store']]],
            ['middleware' => 'permission:emprestimos.edit', 'options' => ['only' => ['update', 'destroy']]],
            ['middleware' => 'permission:emprestimos.finalizar', 'options' => ['only' => ['activate', 'returnItem', 'cancel']]],
        ];
    }

    /**
     * Display a paginated listing of loans with filters.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $equipment_id = $request->input('equipment_id');
        $borrower_id = $request->input('borrower_id');
        $from = $request->input('from');
        $to = $request->input('to');

        $loans = Loan::query()
            ->with(['borrower:id,name', 'equipment:id,name,patrimony_id'])
            ->when($search, fn ($query) => $query->whereHas('borrower', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%");
            }))
            ->when($status, fn ($query) => $query->byStatus($status))
            ->when($equipment_id, fn ($query) => $query->byEquipment($equipment_id))
            ->when($borrower_id, fn ($query) => $query->byBorrower($borrower_id))
            ->when($from && $to, fn ($query) => $query->byDateRange($from, $to))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return new LoanCollection($loans);
    }

    /**
     * Display the specified loan with all relationships.
     */
    public function show(Loan $loan): LoanResource
    {
        $loan->load(['borrower', 'equipment', 'approvedBy', 'createdBy']);

        return new LoanResource($loan);
    }

    /**
     * Store a newly created loan.
     */
    public function store(StoreLoanRequest $request)
    {
        $data = $request->validated();

        try {
            $loan = app(LoanService::class)->create($data);
        } catch (LoanException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'loan_error',
            ], 422);
        }

        $loan->load(['borrower', 'equipment', 'approvedBy', 'createdBy']);

        return (new LoanResource($loan))
            ->additional(['meta' => ['is_critical' => false]])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the specified loan.
     * Only allowed when status is reserved.
     */
    public function update(UpdateLoanRequest $request, Loan $loan)
    {
        if ($loan->status !== \App\Enums\LoanStatus::Reserved) {
            return response()->json([
                'message' => 'Apenas empréstimos com status "Reservado" podem ser editados. Status atual: ' . $loan->status->label() . '.',
                'error' => 'loan_error',
            ], 422);
        }

        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        $loan->update($data);
        $loan->load(['borrower', 'equipment', 'approvedBy', 'createdBy']);

        return new LoanResource($loan);
    }

    /**
     * Remove the specified loan (soft delete).
     */
    public function destroy(Loan $loan): JsonResponse
    {
        $loan->deleted_by = auth()->id();
        $loan->save();
        $loan->delete();

        return response()->json(null, 204);
    }

    /**
     * Activate a reserved loan.
     */
    public function activate(Loan $loan)
    {
        try {
            $loan = app(LoanService::class)->activate($loan);
        } catch (LoanException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'loan_error',
            ], $e->getCode());
        }

        $loan->load(['borrower', 'equipment', 'approvedBy', 'createdBy']);

        return new LoanResource($loan);
    }

    /**
     * Return an equipment item from a loan.
     */
    public function returnItem(ReturnLoanItemRequest $request, Loan $loan)
    {
        $data = $request->validated();

        try {
            app(LoanService::class)->returnItem(
                $loan,
                $data['equipment_id'],
                $data['returned_at'] ?? null,
                $data['notes'] ?? null
            );
        } catch (LoanException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'loan_error',
            ], $e->getCode());
        }

        $loan->load(['borrower', 'equipment', 'approvedBy', 'createdBy']);

        return new LoanResource($loan);
    }

    /**
     * Cancel a reserved loan.
     */
    public function cancel(Loan $loan)
    {
        try {
            $loan = app(LoanService::class)->cancel($loan);
        } catch (LoanException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'loan_error',
            ], $e->getCode());
        }

        $loan->load(['borrower', 'equipment', 'approvedBy', 'createdBy']);

        return new LoanResource($loan);
    }
}
