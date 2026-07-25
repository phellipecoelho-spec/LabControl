<?php

namespace App\Services;

use App\Enums\CalibrationStatus;
use App\Exceptions\CalibrationException;
use App\Models\Calibration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CalibrationService
{
    /**
     * Create a new calibration (transactional).
     *
     * @param  array  $data  {
     *     equipment_id: string,
     *     scheduled_date: string,
     *     interval_value: int,
     *     interval_unit: string,
     *     part_name?: string,
     *     responsible?: string,
     *     laboratory?: string,
     *     notes?: string,
     * }
     * @return Calibration
     */
    public function create(array $data): Calibration
    {
        return DB::transaction(function () use ($data) {
            $calibration = Calibration::create([
                'equipment_id' => $data['equipment_id'],
                'part_name' => $data['part_name'] ?? null,
                'status' => CalibrationStatus::Scheduled,
                'scheduled_date' => $data['scheduled_date'],
                'interval_value' => $data['interval_value'],
                'interval_unit' => $data['interval_unit'],
                'responsible' => $data['responsible'] ?? null,
                'laboratory' => $data['laboratory'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            return $calibration->load(['equipment:id,name,patrimony_id']);
        });
    }

    /**
     * Complete a calibration (transactional, D-02, D-20).
     *
     * @param  Calibration  $calibration
     * @param  array  $data  {
     *     completed_at?: string,
     *     certificate_number?: string,
     *     responsible?: string,
     *     laboratory?: string,
     *     notes?: string,
     * }
     * @return Calibration
     *
     * @throws CalibrationException
     */
    public function complete(Calibration $calibration, array $data): Calibration
    {
        return DB::transaction(function () use ($calibration, $data) {
            if ($calibration->status !== CalibrationStatus::Scheduled) {
                throw new CalibrationException(
                    'Apenas calibrações com status "Agendada" podem ser concluídas.'
                );
            }

            $completedAt = isset($data['completed_at'])
                ? Carbon::parse($data['completed_at'])
                : now();

            $nextDueAt = $this->calculateNextDue(
                $completedAt,
                $calibration->interval_value,
                $calibration->interval_unit
            );

            $calibration->update([
                'status' => CalibrationStatus::Completed,
                'completed_at' => $completedAt,
                'next_due_at' => $nextDueAt,
                'certificate_number' => $data['certificate_number'] ?? null,
                'responsible' => $data['responsible'] ?? $calibration->responsible,
                'laboratory' => $data['laboratory'] ?? $calibration->laboratory,
                'notes' => $data['notes'] ?? $calibration->notes,
            ]);

            return $calibration->fresh(['equipment:id,name,patrimony_id']);
        });
    }

    /**
     * Cancel a calibration (transactional).
     *
     * @param  Calibration  $calibration
     * @return Calibration
     *
     * @throws CalibrationException
     */
    public function cancel(Calibration $calibration): Calibration
    {
        return DB::transaction(function () use ($calibration) {
            if ($calibration->status !== CalibrationStatus::Scheduled) {
                throw new CalibrationException(
                    'Apenas calibrações com status "Agendada" podem ser canceladas.'
                );
            }

            $calibration->update([
                'status' => CalibrationStatus::Cancelled,
            ]);

            return $calibration->fresh(['equipment:id,name,patrimony_id']);
        });
    }

    /**
     * Calculate the next due date based on interval (D-02).
     *
     * @param  Carbon  $completedAt
     * @param  int     $value
     * @param  string  $unit
     * @return Carbon
     */
    private function calculateNextDue(Carbon $completedAt, int $value, string $unit): Carbon
    {
        return match ($unit) {
            'months' => $completedAt->copy()->addMonths($value),
            'days' => $completedAt->copy()->addDays($value),
            'hours' => $completedAt->copy()->addHours($value),
            default => $completedAt->copy()->addMonths($value),
        };
    }

    /**
     * Query calibrations due within a given number of days (D-11).
     *
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function checkDueSoon(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Calibration::dueSoon($days)
            ->with(['equipment:id,name,patrimony_id'])
            ->get();
    }
}
