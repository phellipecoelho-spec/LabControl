<?php

namespace Database\Factories;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Models\Equipment;
use App\Models\MaintenanceOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MaintenanceOrder>
 */
class MaintenanceOrderFactory extends Factory
{
    protected $model = MaintenanceOrder::class;

    public function definition(): array
    {
        $type = fake()->randomElement([MaintenanceType::Preventive, MaintenanceType::Corrective]);
        $hasInterval = $type === MaintenanceType::Preventive;

        return [
            'equipment_id' => Equipment::factory(),
            'type' => $type,
            'status' => MaintenanceStatus::Open,
            'priority' => fake()->randomElement([
                MaintenancePriority::Low,
                MaintenancePriority::Medium,
                MaintenancePriority::High,
                MaintenancePriority::Critical,
            ]),
            'description' => fake()->randomElement([
                'Realizar manutenção preventiva conforme plano anual.',
                'Troca de componentes com desgaste identificado.',
                'Verificação e ajuste de parâmetros operacionais.',
                'Lubrificação e limpeza geral do equipamento.',
                'Substituição de peças com falha identificada.',
                'Reparo emergencial por parada inesperada.',
                'Calibração e alinhamento de sensores.',
                'Inspeção de segurança conforme norma regulamentadora.',
            ]),
            'scheduled_date' => $hasInterval ? fake()->dateTimeBetween('-15 days', '+30 days') : null,
            'assigned_to' => null,
            'opened_by' => null,
            'completed_at' => null,
            'resolution' => null,
            'time_spent' => null,
            'cost' => null,
            'interval_value' => $hasInterval ? fake()->randomElement([1, 3, 6, 12]) : null,
            'interval_unit' => $hasInterval ? 'months' : null,
            'next_due_at' => null,
            'notes' => fake()->optional(0.3)->sentence(),
            'created_by' => null,
        ];
    }

    /**
     * State: ordem aberta.
     */
    public function open(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => MaintenanceStatus::Open,
            'completed_at' => null,
            'resolution' => null,
            'time_spent' => null,
            'cost' => null,
        ]);
    }

    /**
     * State: ordem em andamento.
     */
    public function inProgress(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => MaintenanceStatus::InProgress,
            'assigned_to' => \App\Models\User::factory(),
        ]);
    }

    /**
     * State: ordem concluída.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $start = $attributes['scheduled_date'] ?? null;
            if ($start && $start >= now()) {
                $start = '-60 days';
            }
            $completedAt = fake()->dateTimeBetween(
                $start ?? '-60 days',
                'now'
            );
            $nextDueAt = null;

            if (($attributes['type'] ?? null) === MaintenanceType::Preventive || $attributes['interval_value'] ?? null) {
                $intervalValue = $attributes['interval_value'] ?? 6;
                $intervalUnit = $attributes['interval_unit'] ?? 'months';
                $nextDueAt = Carbon::instance($completedAt)->addMonths($intervalValue);
            }

            return [
                'status' => MaintenanceStatus::Completed,
                'completed_at' => $completedAt,
                'resolution' => fake()->randomElement([
                    'Manutenção concluída com sucesso. Todos os parâmetros dentro da especificação.',
                    'Peças substituídas conforme plano. Equipamento operacional.',
                    'Reparo realizado. Equipamento retornou à operação normal.',
                    'Ajustes realizados. Necessário monitoramento nos próximos dias.',
                ]),
                'time_spent' => fake()->randomFloat(2, 0.5, 16),
                'cost' => fake()->randomFloat(2, 50, 5000),
                'next_due_at' => $nextDueAt,
            ];
        });
    }

    /**
     * State: ordem cancelada.
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => MaintenanceStatus::Cancelled,
            'notes' => fake()->randomElement([
                'Cancelada por falta de peças.',
                'Cancelada a pedido do solicitante.',
                'Manutenção não necessária após inspeção.',
                'Cancelada devido a indisponibilidade de técnico.',
            ]),
        ]);
    }

    /**
     * State: tipo preventivo.
     */
    public function preventive(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => MaintenanceType::Preventive,
            'interval_value' => fake()->randomElement([1, 3, 6, 12]),
            'interval_unit' => 'months',
        ]);
    }

    /**
     * State: tipo corretivo.
     */
    public function corrective(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => MaintenanceType::Corrective,
            'interval_value' => null,
            'interval_unit' => null,
        ]);
    }
}
