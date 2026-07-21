<?php

namespace Database\Factories;

use App\Models\InventoryCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryCategory>
 */
class InventoryCategoryFactory extends Factory
{
    protected $model = InventoryCategory::class;

    /** @var list<string> */
    private static array $categoryNames = [
        'Reagentes',
        'Vidraria',
        'EPIs',
        'Peças de Reposição',
        'Consumíveis',
    ];

    /** @var array<string, bool> */
    private static array $usedNames = [];

    public function definition(): array
    {
        // Pick a unique name from the predefined list
        $available = array_values(array_filter(
            self::$categoryNames,
            fn(string $name) => !isset(self::$usedNames[$name])
        ));

        $name = !empty($available)
            ? $available[0]
            : $this->faker->unique()->word() . ' ' . fake()->randomNumber(3);

        self::$usedNames[$name] = true;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }

    /**
     * Create a category with a specific name.
     */
    public function named(string $name): static
    {
        return $this->state(fn(array $attrs) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }
}
