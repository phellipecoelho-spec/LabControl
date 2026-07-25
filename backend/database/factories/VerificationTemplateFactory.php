<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\VerificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VerificationTemplate>
 */
class VerificationTemplateFactory extends Factory
{
    protected $model = VerificationTemplate::class;

    private static int $sortOrderCounter = 0;

    public function definition(): array
    {
        self::$sortOrderCounter++;

        return [
            'equipment_category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'parameter_name' => fake()->randomElement([
                'Temperatura', 'Pressão', 'Umidade', 'Vibração',
                'Voltagem', 'Corrente', 'Resistência', 'Frequência',
                'pH', 'Condutividade', 'Vazão', 'Peso',
            ]),
            'parameter_unit' => fake()->randomElement([
                '°C', 'bar', '%RH', 'mm/s', 'V', 'A', 'Ω', 'Hz', 'pH', 'µS/cm', 'L/min', 'g',
            ]),
            'tolerance_min' => fake()->optional(0.7)->randomFloat(2, -10, -0.1),
            'tolerance_max' => fake()->optional(0.7)->randomFloat(2, 0.1, 100),
            'sort_order' => self::$sortOrderCounter,
        ];
    }

    /**
     * State: define tolerâncias específicas.
     */
    public function withTolerance(float $min, float $max): static
    {
        return $this->state(fn(array $attributes) => [
            'tolerance_min' => $min,
            'tolerance_max' => $max,
        ]);
    }

    /**
     * State: sem tolerâncias definidas.
     */
    public function noTolerance(): static
    {
        return $this->state(fn(array $attributes) => [
            'tolerance_min' => null,
            'tolerance_max' => null,
        ]);
    }
}
