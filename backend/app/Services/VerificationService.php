<?php

namespace App\Services;

use App\Enums\VerificationResult;
use App\Models\Equipment;
use App\Models\Verification;
use App\Models\VerificationParam;
use App\Models\VerificationTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VerificationService
{
    /**
     * Create a new verification with params (transactional, D-05, D-10).
     *
     * @param  array  $data  {
     *     equipment_id: string,
     *     verified_at?: string,
     *     notes?: string,
     *     params: array<string, string|null>,  // template_id => value
     * }
     * @return Verification
     */
    public function create(array $data): Verification
    {
        return DB::transaction(function () use ($data) {
            $equipment = Equipment::findOrFail($data['equipment_id']);

            $verification = Verification::create([
                'equipment_id' => $data['equipment_id'],
                'verified_at' => $data['verified_at'] ?? now(),
                'operator_id' => auth()->id(),
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Load templates for the equipment's category
            $templates = VerificationTemplate::where('equipment_category_id', $equipment->category_id)
                ->get()
                ->keyBy('id');

            // Create params with auto-calculated result
            $hasOutsideRange = false;

            foreach ($data['params'] as $templateId => $value) {
                $template = $templates->get($templateId);
                $result = $this->calculateResult($value !== null ? (float) $value : null, $template);

                if ($result === VerificationResult::OutsideRange) {
                    $hasOutsideRange = true;
                }

                VerificationParam::create([
                    'verification_id' => $verification->id,
                    'template_id' => $templateId,
                    'value' => $value,
                    'result' => $result,
                    'created_by' => auth()->id(),
                ]);
            }

            // Notification dispatch moved to controller layer (Plan 09-02) to avoid
            // cross-plan dependency on ToleranceExceeded notification class.
            // Service returns $hasOutsideRange on the verification for the controller to check.

            return $verification->load([
                'equipment:id,name,patrimony_id',
                'params.template',
            ]);
        });
    }

    /**
     * Get equipment with pending verifications (D-08).
     *
     * Equipment where verification_frequency is not null
     * AND (no verifications exist OR last verified_at + frequency < now())
     *
     * @return Collection
     */
    public function getPendingVerifications(): Collection
    {
        $now = now();

        return Equipment::query()
            ->whereNotNull('verification_frequency')
            ->where(function ($query) use ($now) {
                $query->whereDoesntHave('verifications')
                    ->orWhereHas('verifications', function ($q) use ($now) {
                        $q->select('equipment_id')
                            ->groupBy('equipment_id')
                            ->havingRaw('MAX(verified_at) < ?', [
                                // Convert frequency to hours for comparison
                                $now->copy()->subHours(
                                    DB::raw("CASE verification_frequency
                                        WHEN 'daily' THEN 24
                                        WHEN 'weekly' THEN 168
                                        WHEN 'shift' THEN 12
                                        ELSE 0
                                    END")
                                ),
                            ]);
                    });
            })
            ->with(['category', 'lastVerification'])
            ->limit(100)
            ->get();
    }

    /**
     * Get paginated verification history for a specific equipment.
     *
     * @param  string  $equipmentId
     * @param  int|null  $perPage
     * @return LengthAwarePaginator
     */
    public function getHistoryByEquipment(string $equipmentId, ?int $perPage = 15): LengthAwarePaginator
    {
        return Verification::byEquipment($equipmentId)
            ->with(['operator:id,name', 'params.template'])
            ->orderBy('verified_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Update verification notes and optionally recalculate params.
     *
     * @param  Verification  $verification
     * @param  array  $data
     * @return Verification
     */
    public function update(Verification $verification, array $data): Verification
    {
        return DB::transaction(function () use ($verification, $data) {
            $verification->update([
                'notes' => $data['notes'] ?? $verification->notes,
                'updated_by' => $data['updated_by'] ?? auth()->id(),
            ]);

            // If params provided, recalculate and update
            if (isset($data['params']) && is_array($data['params'])) {
                $templates = VerificationTemplate::whereIn('id', array_keys($data['params']))
                    ->get()
                    ->keyBy('id');

                foreach ($data['params'] as $templateId => $value) {
                    $param = VerificationParam::where('verification_id', $verification->id)
                        ->where('template_id', $templateId)
                        ->first();

                    if ($param) {
                        $template = $templates->get($templateId);
                        $result = $this->calculateResult($value !== null ? (float) $value : null, $template);

                        $param->update([
                            'value' => $value,
                            'result' => $result,
                            'updated_by' => $data['updated_by'] ?? auth()->id(),
                        ]);
                    }
                }
            }

            return $verification->fresh(['equipment', 'operator', 'params.template']);
        });
    }

    /**
     * Calculate the verification result based on tolerance limits (D-05).
     *
     * @param  float|null  $value
     * @param  VerificationTemplate  $template
     * @return VerificationResult
     */
    private function calculateResult(?float $value, VerificationTemplate $template): VerificationResult
    {
        if ($value === null) {
            return VerificationResult::NotMeasured;
        }

        if ($template->tolerance_min !== null && $value < (float) $template->tolerance_min) {
            return VerificationResult::OutsideRange;
        }

        if ($template->tolerance_max !== null && $value > (float) $template->tolerance_max) {
            return VerificationResult::OutsideRange;
        }

        return VerificationResult::WithinRange;
    }
}
