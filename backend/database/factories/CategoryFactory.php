<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Medição de Temperatura', 'Medição de Pressão', 'Medição Elétrica',
                'Medição Dimensional', 'Balanças', 'Medição de pH',
            ]),
            'slug' => fake()->slug(2) . '-' . fake()->unique()->randomNumber(4),
        ];
    }
}
