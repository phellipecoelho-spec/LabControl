<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Manufacturer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Termômetro Digital', 'Balança Analítica', 'Cronômetro',
                'Paquímetro', 'Micrômetro', 'Multímetro', 'Osciloscópio',
                'Medidor de pH', 'Estufa', 'Autoclave',
            ]),
            'patrimony_id' => 'PAT-' . fake()->unique()->numerify('####'),
            'serial_number' => fake()->unique()->bothify('SN-###-????'),
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'manufacturer_id' => Manufacturer::inRandomOrder()->first()?->id ?? Manufacturer::factory(),
            'supplier_id' => Supplier::inRandomOrder()->first()?->id,
            'location' => fake()->randomElement([
                'Laboratório de Temperatura', 'Sala de Metrologia',
                'Laboratório Químico', 'Almoxarifado Central',
                'Laboratório de Calibração',
            ]),
            'acquisition_date' => fake()->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d'),
            'warranty_end' => fake()->optional(0.7, null)->dateTimeBetween('now', '+3 years')?->format('Y-m-d'),
            'status' => fake()->randomElement(['active', 'active', 'active', 'inactive', 'maintenance']),
            'description' => fake()->optional(0.5)->paragraph(),
            'user_id' => User::factory(),
        ];
    }
}
