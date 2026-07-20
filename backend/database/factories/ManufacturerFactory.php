<?php

namespace Database\Factories;

use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManufacturerFactory extends Factory
{
    protected $model = Manufacturer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' ' . fake()->unique()->randomNumber(3),
            'country' => fake()->country(),
            'website' => fake()->url(),
        ];
    }
}
