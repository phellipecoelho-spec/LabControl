<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\MaintenanceOrder;
use Illuminate\Database\Seeder;

class MaintenanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 50 mixed orders with various types, statuses, and priorities
        $orders = MaintenanceOrder::factory()
            ->count(20)
            ->create();

        // 15 completed orders (mix of preventive and corrective)
        MaintenanceOrder::factory()
            ->completed()
            ->count(15)
            ->create([
                'equipment_id' => fn () => Equipment::inRandomOrder()->first()?->id
                    ?? Equipment::factory()->create()->id,
            ]);

        // 5 in-progress orders
        MaintenanceOrder::factory()
            ->inProgress()
            ->count(5)
            ->create([
                'equipment_id' => fn () => Equipment::inRandomOrder()->first()?->id
                    ?? Equipment::factory()->create()->id,
            ]);

        // 5 cancelled orders
        MaintenanceOrder::factory()
            ->cancelled()
            ->count(5)
            ->create([
                'equipment_id' => fn () => Equipment::inRandomOrder()->first()?->id
                    ?? Equipment::factory()->create()->id,
            ]);

        // 5 completed preventive orders with intervals and future next_due_at
        MaintenanceOrder::factory()
            ->preventive()
            ->completed()
            ->count(5)
            ->create([
                'equipment_id' => fn () => Equipment::inRandomOrder()->first()?->id
                    ?? Equipment::factory()->create()->id,
            ]);

        // 2 open preventive orders with scheduled_date in future
        MaintenanceOrder::factory()
            ->preventive()
            ->open()
            ->count(2)
            ->create([
                'equipment_id' => fn () => Equipment::inRandomOrder()->first()?->id
                    ?? Equipment::factory()->create()->id,
                'scheduled_date' => fn () => now()->addDays(fake()->numberBetween(1, 30)),
            ]);

        // 3 open corrective orders with high priority (simulating urgent)
        MaintenanceOrder::factory()
            ->corrective()
            ->open()
            ->count(3)
            ->create([
                'equipment_id' => fn () => Equipment::inRandomOrder()->first()?->id
                    ?? Equipment::factory()->create()->id,
                'priority' => 'high',
            ]);
    }
}
