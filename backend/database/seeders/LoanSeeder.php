<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Seeder;

class LoanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        if (!$admin) {
            $this->command->warn('No users found. Skipping LoanSeeder.');
            return;
        }

        // Garantir pelo menos 5 equipamentos existentes para referência
        $equipmentCount = Equipment::count();
        if ($equipmentCount < 5) {
            Equipment::factory(5 - $equipmentCount)->create();
            $this->command->info('  - Created additional equipment for loan seeding.');
        }

        // Criar 10 empréstimos com estados variados: 3 reserved, 3 active, 2 returned, 2 cancelled
        $loanStates = [
            ['state' => 'reserved', 'count' => 3],
            ['state' => 'active', 'count' => 3],
            ['state' => 'returned', 'count' => 2],
            ['state' => 'cancelled', 'count' => 2],
        ];

        $totalCreated = 0;

        foreach ($loanStates as $stateConfig) {
            for ($i = 0; $i < $stateConfig['count']; $i++) {
                $itemsCount = rand(1, 3);

                $factory = \App\Models\Loan::factory()
                    ->{$stateConfig['state']}()
                    ->withItems($itemsCount);

                // Usuário logado para auditoria
                $factory->create([
                    'user_id' => $admin->id,
                    'created_by' => $admin->id,
                ]);

                $totalCreated++;
            }
        }

        $this->command->info("Seed: {$totalCreated} empréstimos criados.");
    }
}
