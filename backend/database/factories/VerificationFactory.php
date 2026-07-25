<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Verification>
 */
class VerificationFactory extends Factory
{
    protected $model = Verification::class;

    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::inRandomOrder()->first()?->id ?? Equipment::factory(),
            'verified_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'operator_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Configure the model factory to create verification params after creation.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Verification $verification) {
            // Create 2-5 verification params for this verification
            $count = fake()->numberBetween(2, 5);
            VerificationParamFactory::new()
                ->count($count)
                ->create([
                    'verification_id' => $verification->id,
                ]);
        });
    }
}
