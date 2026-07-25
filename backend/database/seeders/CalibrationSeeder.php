<?php

namespace Database\Seeders;

use App\Models\Calibration;
use App\Models\Equipment;
use Illuminate\Database\Seeder;

class CalibrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 15 sample calibrations
        $calibrations = Calibration::factory()
            ->count(9)
            ->create();

        // Create 3 due calibrations (completed with past next_due_at)
        Calibration::factory()
            ->due()
            ->count(3)
            ->create([
                'equipment_id' => fn () => Equipment::inRandomOrder()->first()?->id
                    ?? Equipment::factory()->create()->id,
            ]);

        // Create 3 due-soon calibrations (completed with next_due_at within 30 days)
        Calibration::factory()
            ->dueSoon()
            ->count(3)
            ->create([
                'equipment_id' => fn () => Equipment::inRandomOrder()->first()?->id
                    ?? Equipment::factory()->create()->id,
            ]);
    }
}
