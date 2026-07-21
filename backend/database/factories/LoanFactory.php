<?php

namespace Database\Factories;

use App\Enums\LoanStatus;
use App\Models\Equipment;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Loan>
 */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $borrowedAt = fake()->dateTimeBetween('-30 days', 'now');
        $expectedReturnAt = (clone $borrowedAt)->modify('+' . rand(1, 14) . ' days');

        return [
            'borrower_id' => User::factory(),
            'status' => fake()->randomElement([LoanStatus::Reserved, LoanStatus::Active]),
            'borrowed_at' => $borrowedAt,
            'expected_return_at' => $expectedReturnAt,
            'returned_at' => null,
            'reason' => fake()->sentence(3),
            'destination' => fake()->word() . ' Lab',
            'contact' => fake()->phoneNumber(),
            'notes' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }

    /**
     * State: empréstimo reservado.
     */
    public function reserved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => LoanStatus::Reserved,
        ]);
    }

    /**
     * State: empréstimo ativo.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => LoanStatus::Active,
        ]);
    }

    /**
     * State: empréstimo devolvido.
     */
    public function returned(): static
    {
        return $this->state(function (array $attributes) {
            $returnedAt = fake()->dateTimeBetween(
                $attributes['borrowed_at'] ?? '-15 days',
                $attributes['expected_return_at'] ?? 'now'
            );

            return [
                'status' => LoanStatus::Returned,
                'returned_at' => $returnedAt,
            ];
        });
    }

    /**
     * State: empréstimo cancelado.
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => LoanStatus::Cancelled,
        ]);
    }

    /**
     * Attach equipamentos após criar o empréstimo.
     *
     * @param  int  $count  Número de equipamentos para attach
     * @return static
     */
    public function withItems(int $count = 2): static
    {
        return $this->afterCreating(function (Loan $loan) use ($count) {
            $equipmentIds = Equipment::inRandomOrder()->limit($count)->pluck('id');

            // Se não houver equipamentos suficientes, criar alguns
            if ($equipmentIds->isEmpty()) {
                $equipmentIds = Equipment::factory($count)->create()->pluck('id');
            }

            $pivotData = [];
            foreach ($equipmentIds as $equipmentId) {
                $data = [
                    'returned_at' => null,
                    'notes' => null,
                ];

                // Se o empréstimo foi devolvido, preencher returned_at nos itens
                if ($loan->status === LoanStatus::Returned) {
                    $data['returned_at'] = $loan->returned_at ?? now();
                }

                $pivotData[$equipmentId] = $data;
            }

            $loan->equipment()->attach($pivotData);
        });
    }
}
