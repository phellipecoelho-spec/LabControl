<?php

namespace App\Services;

use App\Enums\LoanStatus;
use App\Exceptions\LoanException;
use App\Models\EquipmentLoan;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LoanService
{
    /**
     * Create a new loan with equipment attachments (transactional).
     *
     * @param  array  $data  {
     *     borrower_id: string,
     *     borrowed_at: string,
     *     expected_return_at: string,
     *     equipment_ids: string[],
     *     reason?: string,
     *     destination?: string,
     *     contact?: string,
     *     notes?: string,
     *     approved_by?: string,
     * }
     * @return Loan
     *
     * @throws LoanException
     */
    public function create(array $data): Loan
    {
        return DB::transaction(function () use ($data) {
            // Valida: borrower existe (D-02)
            $borrower = User::find($data['borrower_id']);
            if (!$borrower) {
                throw new LoanException('Mutuário não encontrado.');
            }

            // Valida: equipment_ids não vazio (D-01)
            if (empty($data['equipment_ids'])) {
                throw new LoanException('Selecione pelo menos um equipamento para o empréstimo.');
            }

            // Valida: nenhum equipment_id está em loan ativo/reservado no período
            $conflictingIds = $this->findConflictingEquipment($data['equipment_ids'], $data['borrowed_at'], $data['expected_return_at']);
            if (!empty($conflictingIds)) {
                throw new LoanException(
                    'Os seguintes equipamentos já estão emprestados no período informado: ' . implode(', ', $conflictingIds) . '.'
                );
            }

            // Cria o loan
            $loan = Loan::create([
                'borrower_id' => $data['borrower_id'],
                'status' => LoanStatus::Reserved,
                'borrowed_at' => $data['borrowed_at'],
                'expected_return_at' => $data['expected_return_at'],
                'reason' => $data['reason'] ?? null,
                'destination' => $data['destination'] ?? null,
                'contact' => $data['contact'] ?? null,
                'notes' => $data['notes'] ?? null,
                'approved_by' => $data['approved_by'] ?? null,
                'created_by' => auth()->id(),
                'user_id' => auth()->id() ?? $data['borrower_id'],
            ]);

            // Attach equipamentos com returned_at=null (D-19)
            $pivotData = [];
            foreach ($data['equipment_ids'] as $equipmentId) {
                $pivotData[$equipmentId] = [
                    'returned_at' => null,
                    'notes' => null,
                ];
            }
            $loan->equipment()->attach($pivotData);

            // Recarrega relacionamentos
            return $loan->load(['borrower', 'equipment', 'items']);
        });
    }

    /**
     * Activate a reserved loan (transactional).
     *
     * @param  Loan  $loan
     * @return Loan
     *
     * @throws LoanException
     */
    public function activate(Loan $loan): Loan
    {
        return DB::transaction(function () use ($loan) {
            // Valida: status atual é Reserved (D-03)
            if ($loan->status !== LoanStatus::Reserved) {
                throw new LoanException(
                    'Apenas empréstimos com status "Reservado" podem ser ativados. Status atual: ' . $loan->status->label() . '.'
                );
            }

            $loan->status = LoanStatus::Active;

            // Se borrowed_at não foi preenchido, usa now()
            if ($loan->borrowed_at === null) {
                $loan->borrowed_at = now();
            }

            $loan->save();

            return $loan->fresh(['borrower', 'equipment', 'items']);
        });
    }

    /**
     * Return a single equipment item from a loan (transactional, partial return - D-04).
     *
     * @param  Loan     $loan
     * @param  string   $equipmentId
     * @param  string|null  $returnedAt  Data da devolução (default: now())
     * @param  string|null  $notes       Observações da devolução
     * @return EquipmentLoan
     *
     * @throws LoanException
     */
    public function returnItem(Loan $loan, string $equipmentId, ?string $returnedAt = null, ?string $notes = null): EquipmentLoan
    {
        return DB::transaction(function () use ($loan, $equipmentId, $returnedAt, $notes) {
            // Valida: status do loan é Active (D-03)
            if ($loan->status !== LoanStatus::Active) {
                throw new LoanException(
                    'Apenas empréstimos ativos podem ter itens devolvidos. Status atual: ' . $loan->status->label() . '.'
                );
            }

            // Valida: equipment pertence ao loan
            /** @var EquipmentLoan $pivot */
            $pivot = $loan->items()
                ->where('equipment_id', $equipmentId)
                ->first();

            if (!$pivot) {
                throw new LoanException('O equipamento informado não faz parte deste empréstimo.');
            }

            // Valida: item ainda não foi devolvido
            if ($pivot->returned_at !== null) {
                throw new LoanException('Este equipamento já foi devolvido em ' . $pivot->returned_at->format('d/m/Y H:i') . '.');
            }

            // Atualiza devolução na pivot
            $pivot->returned_at = $returnedAt ?: now();
            $pivot->notes = $notes;
            $pivot->save();

            // Verifica se TODOS os itens foram devolvidos
            $allReturned = $loan->items()
                ->whereNull('returned_at')
                ->count() === 0;

            if ($allReturned) {
                $loan->status = LoanStatus::Returned;
                $loan->returned_at = now();
                $loan->save();
            }

            return $pivot->fresh(['equipment']);
        });
    }

    /**
     * Cancel a loan (transactional).
     *
     * @param  Loan  $loan
     * @return Loan
     *
     * @throws LoanException
     */
    public function cancel(Loan $loan): Loan
    {
        return DB::transaction(function () use ($loan) {
            // Valida: status atual é Reserved (D-03)
            if ($loan->status !== LoanStatus::Reserved) {
                throw new LoanException(
                    'Apenas empréstimos com status "Reservado" podem ser cancelados. Status atual: ' . $loan->status->label() . '.'
                );
            }

            $loan->status = LoanStatus::Cancelled;
            $loan->save();

            return $loan->fresh(['borrower', 'equipment', 'items']);
        });
    }

    /**
     * Auto-return all items in a loan (transactional).
     * Used when performing a full return of all remaining items.
     *
     * @param  Loan  $loan
     * @return Loan
     *
     * @throws LoanException
     */
    public function autoReturnAll(Loan $loan): Loan
    {
        return DB::transaction(function () use ($loan) {
            if ($loan->status !== LoanStatus::Active) {
                throw new LoanException(
                    'Apenas empréstimos ativos podem ser totalmente devolvidos. Status atual: ' . $loan->status->label() . '.'
                );
            }

            $now = now();

            // Devolve todos os itens pendentes
            $loan->items()
                ->whereNull('returned_at')
                ->update(['returned_at' => $now]);

            // Atualiza o loan como devolvido
            $loan->status = LoanStatus::Returned;
            $loan->returned_at = $now;
            $loan->save();

            return $loan->fresh(['borrower', 'equipment', 'items']);
        });
    }

    /**
     * Find conflicting equipment IDs that are already in active/reserved loans
     * during the requested period.
     *
     * @param  array   $equipmentIds
     * @param  string  $borrowedAt
     * @param  string  $expectedReturnAt
     * @return array   List of conflicting equipment IDs
     */
    private function findConflictingEquipment(array $equipmentIds, string $borrowedAt, string $expectedReturnAt): array
    {
        $conflictingLoans = Loan::whereIn('status', [LoanStatus::Reserved, LoanStatus::Active])
            ->whereHas('equipment', function (Builder $query) use ($equipmentIds) {
                $query->whereIn('equipment_loan.equipment_id', $equipmentIds);
            })
            ->where(function (Builder $query) use ($borrowedAt, $expectedReturnAt) {
                // Conflito: período solicitado sobrepõe loan existente
                $query->whereBetween('borrowed_at', [$borrowedAt, $expectedReturnAt])
                      ->orWhereBetween('expected_return_at', [$borrowedAt, $expectedReturnAt])
                      ->orWhere(function (Builder $q) use ($borrowedAt, $expectedReturnAt) {
                          $q->where('borrowed_at', '<=', $borrowedAt)
                            ->where('expected_return_at', '>=', $expectedReturnAt);
                      });
            })
            ->with('equipment')
            ->get();

        $conflictingIds = [];
        foreach ($conflictingLoans as $loan) {
            foreach ($loan->equipment as $equipment) {
                if (in_array($equipment->id, $equipmentIds)) {
                    $conflictingIds[] = $equipment->id . ' (' . $equipment->name . ')';
                }
            }
        }

        return array_unique($conflictingIds);
    }

    /**
     * Query all overdue loans (status=active, expected_return_at < now).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function checkOverdue(): \Illuminate\Database\Eloquent\Collection
    {
        return Loan::overdue()
            ->with(['borrower', 'equipment'])
            ->get();
    }
}
