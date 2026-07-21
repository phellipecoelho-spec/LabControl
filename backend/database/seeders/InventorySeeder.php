<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing admin user for seed data
        $admin = User::first();
        if (!$admin) {
            $this->command->warn('No users found. Skipping InventorySeeder.');
            return;
        }

        // Create 5 categories (D-04: flat categories, no hierarchy)
        $categories = [];
        $categoryNames = [
            'Reagentes' => 'reagentes',
            'Vidraria' => 'vidraria',
            'EPIs' => 'epis',
            'Peças de Reposição' => 'pecas-de-reposicao',
            'Consumíveis' => 'consumiveis',
        ];

        foreach ($categoryNames as $name => $slug) {
            $categories[$slug] = InventoryCategory::create([
                'name' => $name,
                'slug' => $slug,
                'created_by' => $admin->id,
            ]);
            $this->command->info("  - Category: {$name}");
        }

        // Get existing suppliers
        $suppliers = Supplier::all();
        if ($suppliers->isEmpty()) {
            $this->command->warn('No suppliers found. Skipping item creation.');
            return;
        }

        // Define sample items distributed across categories
        $itemsData = [
            // Reagentes
            ['name' => 'Ácido Clorídrico PA', 'category' => 'reagentes', 'unit' => 'L', 'min_stock' => 2, 'initial_qty' => 10],
            ['name' => 'Metanol Grau HPLC', 'category' => 'reagentes', 'unit' => 'L', 'min_stock' => 3, 'initial_qty' => 15],
            ['name' => 'Hidróxido de Sódio PA', 'category' => 'reagentes', 'unit' => 'KG', 'min_stock' => 2, 'initial_qty' => 8],
            // Vidraria
            ['name' => 'Pipeta Volumétrica 10mL', 'category' => 'vidraria', 'unit' => 'UN', 'min_stock' => 5, 'initial_qty' => 30],
            ['name' => 'Béquer 250mL', 'category' => 'vidraria', 'unit' => 'UN', 'min_stock' => 5, 'initial_qty' => 20],
            ['name' => 'Proveta 100mL', 'category' => 'vidraria', 'unit' => 'UN', 'min_stock' => 3, 'initial_qty' => 12],
            // EPIs
            ['name' => 'Luvas Nitrílicas M', 'category' => 'epis', 'unit' => 'CX', 'min_stock' => 5, 'initial_qty' => 25],
            ['name' => 'Máscara Descartável PFF2', 'category' => 'epis', 'unit' => 'CX', 'min_stock' => 10, 'initial_qty' => 40],
            ['name' => 'Jaleco Descartável M', 'category' => 'epis', 'unit' => 'UN', 'min_stock' => 3, 'initial_qty' => 15],
            // Consumíveis
            ['name' => 'Filtro de Seringa 0.22µm', 'category' => 'consumiveis', 'unit' => 'PC', 'min_stock' => 20, 'initial_qty' => 100],
            ['name' => 'Papel Filtro Quantitativo', 'category' => 'consumiveis', 'unit' => 'PCT', 'min_stock' => 5, 'initial_qty' => 30],
        ];

        foreach ($itemsData as $data) {
            $supplier = $suppliers->random();

            $item = InventoryItem::create([
                'name' => $data['name'],
                'category_id' => $categories[$data['category']]->id,
                'supplier_id' => $supplier->id,
                'unit' => $data['unit'],
                'min_stock' => $data['min_stock'],
                'user_id' => $admin->id,
                'created_by' => $admin->id,
            ]);

            // Create initial purchase movement (D-10: first movement records initial stock)
            InventoryMovement::create([
                'item_id' => $item->id,
                'type' => 'purchase',
                'quantity' => $data['initial_qty'],
                'balance_after' => $data['initial_qty'],
                'reason' => 'Saldo inicial',
                'user_id' => $admin->id,
                'created_by' => $admin->id,
            ]);

            $this->command->info("  - Item: {$data['name']} ({$data['initial_qty']} {$data['unit']})");
        }

        $this->command->info('Inventory seed completed: ' . count($itemsData) . ' items with initial movements.');
    }
}
