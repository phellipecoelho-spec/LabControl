<?php

namespace App\Services;

use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Exceptions\MaintenanceException;
use App\Models\MaintenanceOrder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    /**
     * Create a new maintenance order (transactional, D-06).
     *
     * @param  array  $data  {
     *     equipment_id: string,
     *     type: string,
     *     priority: string,
     *     description: string,
     *     scheduled_date?: string,
     * }
     * @return MaintenanceOrder
     */
    public function create(array $data): MaintenanceOrder
    {
        return DB::transaction(function () use ($data) {
            $order = MaintenanceOrder::create([
                'equipment_id' => $data['equipment_id'],
                'type' => $data['type'],
                'status' => MaintenanceStatus::Open,
                'priority' => $data['priority'] ?? 'medium',
                'description' => $data['description'],
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'interval_value' => $data['interval_value'] ?? null,
                'interval_unit' => $data['interval_unit'] ?? null,
                'notes' => $data['notes'] ?? null,
                'opened_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]);

            return $order->fresh(['equipment:id,name,patrimony_id', 'openedBy:id,name']);
        });
    }

    /**
     * Assign a technician to an order and optionally transition to in_progress
     * if the order is currently open.
     *
     * @param  array  $data  { assigned_to: string }
     * @param  MaintenanceOrder  $order
     * @return MaintenanceOrder
     */
    public function assign(array $data, MaintenanceOrder $order): MaintenanceOrder
    {
        return DB::transaction(function () use ($data, $order) {
            $order->update([
                'assigned_to' => $data['assigned_to'],
                'updated_by' => auth()->id(),
            ]);

            // Auto-transition to in_progress if currently open
            if ($order->status === MaintenanceStatus::Open && $data['assigned_to'] !== null) {
                $order->update([
                    'status' => MaintenanceStatus::InProgress,
                ]);
            }

            return $order->fresh(['equipment:id,name,patrimony_id', 'assignedTo:id,name']);
        });
    }

    /**
     * Update a maintenance order (transactional).
     * Only allowed if order is not completed or cancelled.
     *
     * @param  MaintenanceOrder  $order
     * @param  array  $data
     * @return MaintenanceOrder
     *
     * @throws MaintenanceException
     */
    public function update(MaintenanceOrder $order, array $data): MaintenanceOrder
    {
        return DB::transaction(function () use ($order, $data) {
            if (in_array($order->status, [MaintenanceStatus::Completed, MaintenanceStatus::Cancelled], true)) {
                throw new MaintenanceException(
                    'Ordens concluídas ou canceladas não podem ser editadas.'
                );
            }

            $fillable = [
                'type', 'priority', 'description', 'scheduled_date',
                'assigned_to', 'interval_value', 'interval_unit', 'notes',
            ];

            $updateData = array_intersect_key($data, array_flip($fillable));
            $updateData['updated_by'] = auth()->id();

            $order->update($updateData);

            return $order->fresh(['equipment:id,name,patrimony_id', 'assignedTo:id,name']);
        });
    }

    /**
     * Complete a maintenance order (transactional, D-09, D-10).
     *
     * @param  MaintenanceOrder  $order
     * @param  array  $data  {
     *     completed_at?: string,
     *     resolution?: string,
     *     time_spent?: float,
     *     cost?: float,
     *     parts?: array<{inventory_item_id: string, quantity: float, unit_cost?: float}>,
     * }
     * @return MaintenanceOrder
     *
     * @throws MaintenanceException
     */
    public function complete(MaintenanceOrder $order, array $data): MaintenanceOrder
    {
        return DB::transaction(function () use ($order, $data) {
            if (!$order->status->canTransitionTo(MaintenanceStatus::Completed)) {
                throw new MaintenanceException(
                    'Apenas ordens em andamento podem ser concluídas.'
                );
            }

            $completedAt = isset($data['completed_at'])
                ? Carbon::parse($data['completed_at'])
                : now();

            $nextDueAt = null;
            if ($order->type === MaintenanceType::Preventive) {
                $nextDueAt = $this->calculateNextDue(
                    $completedAt,
                    $order->interval_value,
                    $order->interval_unit
                );
            }

            $order->update([
                'status' => MaintenanceStatus::Completed,
                'completed_at' => $completedAt,
                'resolution' => $data['resolution'] ?? null,
                'time_spent' => $data['time_spent'] ?? null,
                'cost' => $data['cost'] ?? null,
                'next_due_at' => $nextDueAt,
                'updated_by' => auth()->id(),
            ]);

            // Attach parts from inventory (pivot) (D-05)
            if (!empty($data['parts'])) {
                foreach ($data['parts'] as $part) {
                    $order->parts()->create([
                        'inventory_item_id' => $part['inventory_item_id'],
                        'quantity' => $part['quantity'],
                        'unit_cost' => $part['unit_cost'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // Auto-create next preventive order (D-10)
            if ($order->type === MaintenanceType::Preventive && $nextDueAt) {
                $this->createNextPreventive($order, $nextDueAt);
            }

            return $order->fresh(['equipment:id,name,patrimony_id', 'parts.item']);
        });
    }

    /**
     * Cancel a maintenance order (transactional, D-11).
     *
     * @param  MaintenanceOrder  $order
     * @param  string|null  $reason
     * @return MaintenanceOrder
     *
     * @throws MaintenanceException
     */
    public function cancel(MaintenanceOrder $order, ?string $reason = null): MaintenanceOrder
    {
        return DB::transaction(function () use ($order, $reason) {
            if (!$order->status->canTransitionTo(MaintenanceStatus::Cancelled)) {
                throw new MaintenanceException(
                    'Apenas ordens abertas ou em andamento podem ser canceladas.'
                );
            }

            $updateData = [
                'status' => MaintenanceStatus::Cancelled,
                'updated_by' => auth()->id(),
            ];

            if ($reason !== null) {
                $updateData['notes'] = $reason;
            }

            $order->update($updateData);

            return $order->fresh();
        });
    }

    /**
     * Get paginated history of maintenance orders for a given equipment (D-12).
     *
     * @param  string  $equipmentId
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function getHistoryByEquipment(string $equipmentId, int $perPage = 15): LengthAwarePaginator
    {
        return MaintenanceOrder::byEquipment($equipmentId)
            ->orderBy('created_at', 'desc')
            ->with(['assignedTo:id,name', 'openedBy:id,name'])
            ->paginate($perPage);
    }

    /**
     * Calculate the next due date based on interval (D-10).
     *
     * @param  Carbon  $completedAt
     * @param  int|null  $value
     * @param  string|null  $unit
     * @return Carbon
     */
    private function calculateNextDue(Carbon $completedAt, ?int $value, ?string $unit): Carbon
    {
        $value = $value ?? 6;
        $unit = $unit ?? 'months';

        return match ($unit) {
            'months' => $completedAt->copy()->addMonths($value),
            'days' => $completedAt->copy()->addDays($value),
            'hours' => $completedAt->copy()->addHours($value),
            default => $completedAt->copy()->addMonths($value),
        };
    }

    /**
     * Auto-create the next preventive maintenance order (D-10).
     *
     * @param  MaintenanceOrder  $order
     * @param  Carbon  $nextDueAt
     * @return MaintenanceOrder
     */
    private function createNextPreventive(MaintenanceOrder $order, Carbon $nextDueAt): MaintenanceOrder
    {
        return DB::transaction(function () use ($order, $nextDueAt) {
            return MaintenanceOrder::create([
                'equipment_id' => $order->equipment_id,
                'type' => MaintenanceType::Preventive,
                'status' => MaintenanceStatus::Open,
                'priority' => $order->priority,
                'description' => 'Manutenção preventiva recorrente — gerada automaticamente.',
                'scheduled_date' => $nextDueAt,
                'interval_value' => $order->interval_value,
                'interval_unit' => $order->interval_unit,
                'notes' => 'Criada automaticamente ao concluir ordem #' . $order->getKey(),
            ]);
        });
    }
}
