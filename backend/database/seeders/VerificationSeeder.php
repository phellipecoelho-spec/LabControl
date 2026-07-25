<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Verification;
use App\Models\VerificationTemplate;
use Illuminate\Database\Seeder;

class VerificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have categories with equipment
        $categories = Category::inRandomOrder()->limit(3)->get();

        if ($categories->isEmpty()) {
            $categories = Category::factory()->count(3)->create();
        }

        // Create 3 verification templates for each category
        foreach ($categories as $category) {
            VerificationTemplate::factory()
                ->count(3)
                ->create([
                    'equipment_category_id' => $category->id,
                ]);
        }

        // Create 5-10 equipment with verification_frequency set
        $equipmentWithFrequency = Equipment::factory()
            ->count(8)
            ->create([
                'verification_frequency' => fn () => fake()->randomElement(['daily', 'weekly', 'shift']),
            ]);

        // Also update some existing equipment to have frequency
        Equipment::inRandomOrder()->limit(5)->get()->each(function ($equipment) {
            if ($equipment->verification_frequency === null) {
                $equipment->update([
                    'verification_frequency' => fake()->randomElement(['daily', 'weekly', 'shift']),
                ]);
            }
        });

        // For each equipment, create 3-5 verification records
        $allEquipment = Equipment::whereNotNull('verification_frequency')->get();

        foreach ($allEquipment as $equipment) {
            $verificationCount = fake()->numberBetween(3, 5);

            Verification::factory()
                ->count($verificationCount)
                ->create([
                    'equipment_id' => $equipment->id,
                ]);
        }

        // Ensure at least one verification has an outside_range param
        $latestVerification = Verification::latest()->first();
        if ($latestVerification) {
            $param = $latestVerification->params()->first();
            if ($param) {
                $param->update([
                    'value' => 999999,
                    'result' => 'outside_range',
                ]);
            }
        }
    }
}
