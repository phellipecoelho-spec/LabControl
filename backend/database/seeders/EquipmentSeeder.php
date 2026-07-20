<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Manufacturer;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Medição de Temperatura', 'slug' => 'medicao-temperatura'],
            ['name' => 'Medição de Pressão', 'slug' => 'medicao-pressao'],
            ['name' => 'Medição Elétrica', 'slug' => 'medicao-eletrica'],
            ['name' => 'Medição Dimensional', 'slug' => 'medicao-dimensional'],
            ['name' => 'Balanças', 'slug' => 'balancas'],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }

        $manufacturers = [
            ['name' => 'Testo Instruments', 'country' => 'Alemanha', 'website' => 'https://www.testo.com'],
            ['name' => 'Fluke Corporation', 'country' => 'EUA', 'website' => 'https://www.fluke.com'],
            ['name' => 'Mitutoyo', 'country' => 'Japão', 'website' => 'https://www.mitutoyo.com'],
        ];

        foreach ($manufacturers as $man) {
            Manufacturer::create($man);
        }

        $suppliers = [
            [
                'name' => 'Instrulab Comércio Ltda',
                'cnpj' => '00.000.000/0001-00',
                'contact_name' => 'Carlos Silva',
                'contact_email' => 'carlos@instrulab.com.br',
                'contact_phone' => '(11) 99999-0001',
            ],
            [
                'name' => 'MedTech Suprimentos',
                'cnpj' => '11.111.111/0001-11',
                'contact_name' => 'Ana Oliveira',
                'contact_email' => 'ana@medtech.com.br',
                'contact_phone' => '(21) 98888-0002',
            ],
        ];

        foreach ($suppliers as $sup) {
            Supplier::create($sup);
        }

        Equipment::factory(10)->create();
    }
}
