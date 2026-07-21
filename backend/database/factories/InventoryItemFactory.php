<?php

namespace Database\Factories;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    /** @var list<array{name: string, unit: string}> */
    private static array $itemTemplates = [
        ['name' => 'Luvas Nitrílicas M', 'unit' => 'CX'],
        ['name' => 'Luvas Nitrílicas G', 'unit' => 'CX'],
        ['name' => 'Luvas Nitrílicas PP', 'unit' => 'CX'],
        ['name' => 'Ácido Clorídrico PA', 'unit' => 'L'],
        ['name' => 'Ácido Sulfúrico PA', 'unit' => 'L'],
        ['name' => 'Metanol Grau HPLC', 'unit' => 'L'],
        ['name' => 'Etanol Absoluto', 'unit' => 'L'],
        ['name' => 'Pipeta Volumétrica 10mL', 'unit' => 'UN'],
        ['name' => 'Pipeta Volumétrica 25mL', 'unit' => 'UN'],
        ['name' => 'Pipeta Graduada 5mL', 'unit' => 'UN'],
        ['name' => 'Proveta 100mL', 'unit' => 'UN'],
        ['name' => 'Proveta 500mL', 'unit' => 'UN'],
        ['name' => 'Béquer 250mL', 'unit' => 'UN'],
        ['name' => 'Béquer 600mL', 'unit' => 'UN'],
        ['name' => 'Filtro de Seringa 0.22µm', 'unit' => 'PC'],
        ['name' => 'Filtro de Seringa 0.45µm', 'unit' => 'PC'],
        ['name' => 'Papel Filtro Quantitativo', 'unit' => 'PCT'],
        ['name' => 'Papel Filtro Qualitativo', 'unit' => 'PCT'],
        ['name' => 'Máscara Descartável PFF2', 'unit' => 'CX'],
        ['name' => 'Touca Descartável', 'unit' => 'PCT'],
        ['name' => 'Jaleco Descartável M', 'unit' => 'UN'],
        ['name' => 'Jaleco Descartável G', 'unit' => 'UN'],
        ['name' => 'Seringa 5mL', 'unit' => 'PC'],
        ['name' => 'Seringa 10mL', 'unit' => 'PC'],
        ['name' => 'Agulha Descarpack', 'unit' => 'CX'],
        ['name' => 'Cloreto de Sódio PA', 'unit' => 'KG'],
        ['name' => 'Glicerina Pura', 'unit' => 'L'],
        ['name' => 'Hidróxido de Sódio PA', 'unit' => 'KG'],
    ];

    /** @var array<string, bool> */
    private static array $usedTemplates = [];

    /** @var list<string> */
    private static array $locations = [
        'Armário A - Prateleira 1',
        'Armário A - Prateleira 2',
        'Armário A - Prateleira 3',
        'Armário B - Prateleira 1',
        'Armário B - Prateleira 2',
        'Armário C - Prateleira 1',
        'Geladeira 1 - Superior',
        'Geladeira 1 - Inferior',
        'Freezer - Caixa 1',
        'Freezer - Caixa 2',
        'Capela - Lado Esquerdo',
        'Capela - Lado Direito',
        'Estante Metálica - Seção A',
        'Estante Metálica - Seção B',
    ];

    public function definition(): array
    {
        // Pick a unique template
        $available = array_values(array_filter(
            self::$itemTemplates,
            fn(array $t) => !isset(self::$usedTemplates[$t['name']])
        ));

        if (empty($available)) {
            $template = [
                'name' => $this->faker->unique()->word() . ' ' . $this->faker->randomNumber(4),
                'unit' => $this->faker->randomElement(['UN', 'KG', 'L', 'CX', 'M', 'PC', 'PCT', 'CJ']),
            ];
        } else {
            $template = $this->faker->randomElement($available);
        }

        self::$usedTemplates[$template['name']] = true;

        return [
            'name' => $template['name'],
            'code' => $this->faker->optional(0.7)->regexify('[A-Z]{3}[0-9]{4}'),
            'description' => $this->faker->optional(0.6)->sentence(),
            'category_id' => InventoryCategory::factory(),
            'supplier_id' => Supplier::factory(),
            'unit' => $template['unit'],
            'min_stock' => $this->faker->numberBetween(2, 20),
            'batch_lot' => $this->faker->optional(0.7)->regexify('[A-Z]{2}[0-9]{6}'),
            'expiry_date' => $this->faker->optional(0.5)->dateTimeBetween('+6 months', '+2 years'),
            'physical_location' => $this->faker->optional(0.8)->randomElement(self::$locations),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Configure the item with a specific category.
     */
    public function withCategory(string $categoryId): static
    {
        return $this->state(fn(array $attrs) => [
            'category_id' => $categoryId,
        ]);
    }

    /**
     * Configure the item with a specific supplier.
     */
    public function withSupplier(string $supplierId): static
    {
        return $this->state(fn(array $attrs) => [
            'supplier_id' => $supplierId,
        ]);
    }
}
