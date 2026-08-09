<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 100);

        return [
            'item_id' => InventoryItem::factory(),
            'type' => $this->faker->randomElement(['purchase', 'consumption', 'adjustment', 'disposal', 'return']),
            'quantity' => $quantity,
            'balance_after' => $quantity,
            'reason' => $this->faker->optional(0.7)->sentence(),
            'notes' => $this->faker->optional(0.5)->sentence(),
            'user_id' => User::factory(),
            'created_by' => User::factory(),
        ];
    }
}
