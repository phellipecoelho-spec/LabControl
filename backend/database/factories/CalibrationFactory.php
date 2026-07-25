<?php

namespace Database\Factories;

use App\Enums\CalibrationStatus;
use App\Models\Calibration;
use App\Models\Equipment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Calibration>
 */
class CalibrationFactory extends Factory
{
    protected $model = Calibration::class;

    public function definition(): array
    {
        $scheduledDate = fake()->dateTimeBetween('-15 days', '+30 days');
        $intervalValue = fake()->randomElement([6, 12]);
        $intervalUnit = 'months';

        return [
            'equipment_id' => Equipment::factory(),
            'part_name' => fake()->optional(0.5)->randomElement([
                'Sensor de temperatura',
                'Braço robótico',
                'Módulo de pressão',
                'Célula de carga',
                'Transdutor',
            ]),
            'status' => CalibrationStatus::Scheduled,
            'scheduled_date' => $scheduledDate,
            'completed_at' => null,
            'next_due_at' => null,
            'interval_value' => $intervalValue,
            'interval_unit' => $intervalUnit,
            'responsible' => fake()->name(),
            'laboratory' => fake()->company() . ' Lab',
            'certificate_number' => null,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * State: calibração concluída.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $completedAt = fake()->dateTimeBetween(
                $attributes['scheduled_date'] ?? '-15 days',
                'now'
            );
            $intervalValue = $attributes['interval_value'] ?? 6;
            $intervalUnit = $attributes['interval_unit'] ?? 'months';
            $nextDueAt = Carbon::instance($completedAt)
                ->addMonths($intervalValue);

            return [
                'status' => CalibrationStatus::Completed,
                'completed_at' => $completedAt,
                'next_due_at' => $nextDueAt,
                'certificate_number' => 'CERT-' . strtoupper(fake()->bothify('??#####')),
            ];
        });
    }

    /**
     * State: calibração vencida (completed com next_due_at no passado).
     */
    public function due(): static
    {
        return $this->state(function (array $attributes) {
            $completedAt = fake()->dateTimeBetween('-90 days', '-60 days');
            $nextDueAt = Carbon::instance($completedAt)->addMonths(1);

            return [
                'status' => CalibrationStatus::Completed,
                'completed_at' => $completedAt,
                'next_due_at' => $nextDueAt,
                'certificate_number' => 'CERT-' . strtoupper(fake()->bothify('??#####')),
            ];
        });
    }

    /**
     * State: calibração a vencer em breve (completed com next_due_at nos próximos 30 dias).
     */
    public function dueSoon(): static
    {
        return $this->state(function (array $attributes) {
            $daysUntilDue = fake()->numberBetween(1, 29);
            $nextDueAt = now()->addDays($daysUntilDue);
            $intervalValue = $attributes['interval_value'] ?? 6;
            $completedAt = (clone $nextDueAt)->subMonths($intervalValue);

            return [
                'status' => CalibrationStatus::Completed,
                'completed_at' => $completedAt,
                'next_due_at' => $nextDueAt,
                'certificate_number' => 'CERT-' . strtoupper(fake()->bothify('??#####')),
            ];
        });
    }

    /**
     * State: calibração cancelada.
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => CalibrationStatus::Cancelled,
        ]);
    }
}
