---
wave: 1
depends_on:
  - 05-01a
files_modified:
  - backend/app/Models/Equipment.php
  - backend/app/Models/Category.php
  - backend/app/Models/Manufacturer.php
  - backend/app/Models/Supplier.php
  - backend/app/Models/EquipmentPhoto.php
  - backend/database/factories/EquipmentFactory.php
  - backend/database/seeders/EquipmentSeeder.php
  - backend/database/seeders/DatabaseSeeder.php
autonomous: true
requirements:
  - EQUIP-01
  - EQUIP-02
---

# 05-01b: Database — Models, Relacionamentos, Factories e Seeders

<objective>
Criar 5 models com traits, relacionamentos, casts e scopes; factory para testes; seeder com dados de desenvolvimento realistas.
</objective>

## Tasks

### T1: Models — Category, Manufacturer, Supplier, EquipmentPhoto

<read_first>
- backend/app/Models/Role.php (model pattern)
- backend/app/Models/User.php (UUID, casts pattern)
- backend/database/migrations/2026_07_19_000002_create_equipments_tables.php
</read_first>

<action>
Criar 4 models em `backend/app/Models/`:

**Category.php**: HasFactory, SoftDeletes. Fillable: name, slug. Relacionamento: hasMany Equipment.

**Manufacturer.php**: HasFactory, SoftDeletes. Fillable: name, country, website, logo_path.

**Supplier.php**: HasFactory, SoftDeletes. Fillable: name, cnpj, contact_name, contact_email, contact_phone, address. Casts: cnpj => string.

**EquipmentPhoto.php**: Fillable: equipment_id, path, original_name, size, mime_type, sort_order. BelongsTo Equipment. Casts: size => integer, sort_order => integer.
</action>

<acceptance_criteria>
- 4 models em backend/app/Models/
- Todos com HasFactory e SoftDeletes (exceto EquipmentPhoto que não tem softDeletes)
- UUID implícito da migration (sem definição extra no model)
</acceptance_criteria>

### T2: Model — Equipment

<read_first>
- backend/app/Models/User.php
- backend/app/Traits/LogsActivity.php
- backend/app/Models/Category.php
- backend/app/Models/Manufacturer.php
- backend/app/Models/Supplier.php
- backend/app/Models/EquipmentPhoto.php
</read_first>

<action>
Criar `backend/app/Models/Equipment.php`:

Traits: HasFactory, SoftDeletes, LogsActivity (existe de Phase 03).

Fillable: name, patrimony_id, serial_number, category_id, manufacturer_id, supplier_id, location, acquisition_date, warranty_end, status, description, technical_specs, notes, user_id.

Relacionamentos:
- belongsTo('App\Models\Category', 'category_id')
- belongsTo('App\Models\Manufacturer', 'manufacturer_id')
- belongsTo('App\Models\Supplier', 'supplier_id')
- belongsTo('App\Models\User', 'user_id')
- belongsTo('App\Models\User', 'deleted_by')->withDefault()
- hasMany('App\Models\EquipmentPhoto', 'equipment_id')->orderBy('sort_order')

Casts: acquisition_date => date, warranty_end => date.

Scopes:
- scopeActive(Builder $query): $query->where('status', 'active')
- scopeByCategory(Builder $query, string $categoryId): $query->where('category_id', $categoryId)
</action>

<acceptance_criteria>
- Equipment.php com LogsActivity trait
- 6 relacionamentos: 3 belongsTo entidades, 1 belongsTo creator, 1 belongsTo deleted_by, 1 hasMany fotos
- Scopes active e byCategory
- Casts de datas
</acceptance_criteria>

### T3: Factory — EquipmentFactory

<read_first>
- backend/database/factories/UserFactory.php
</read_first>

<action>
Criar `backend/database/factories/EquipmentFactory.php`:

definition(): array retorna dados realistas de equipamentos de laboratório:
- name: fake()->randomElement(['Termômetro Digital', 'Balança Analítica', 'Cronômetro', 'Paquímetro', 'Micrômetro', 'Multímetro', 'Osciloscópio', 'Medidor de pH', 'Estufa', 'Autoclave'])
- patrimony_id: 'PAT-' . fake()->unique()->numerify('####')
- serial_number: fake()->unique()->bothify('SN-###-????')
- category_id: Category::factory()
- manufacturer_id: Manufacturer::factory()
- supplier_id: Supplier::factory()->nullable()
- location: fake()->randomElement(['Laboratório de Temperatura', 'Sala de Metrologia', 'Laboratório Químico', 'Almoxarifado Central', 'Laboratório de Calibração'])
- acquisition_date: fake()->dateBetween('-5 years', '-1 month')
- warranty_end: fake()->optional(0.7)->dateBetween('now', '+3 years')
- status: fake()->randomElement(['active', 'active', 'active', 'inactive', 'maintenance'])
- description: fake()->optional(0.5)->paragraph()
- user_id: User::factory()
</action>

<acceptance_criteria>
- EquipmentFactory gera equipamentos com dados realistas
- Relacionamentos usam factory() para criar entidades dependentes
</acceptance_criteria>

### T4: Seeder — EquipmentSeeder

<read_first>
- backend/database/seeders/RolePermissionSeeder.php (seeder pattern)
- backend/database/seeders/DatabaseSeeder.php
</read_first>

<action>
Criar `backend/database/seeders/EquipmentSeeder.php`:

run():
1. Criar 5 categorias fixas com slug (não via factory):
   - Medição de Temperatura (slug: medicao-temperatura)
   - Medição de Pressão (slug: medicao-pressao)
   - Medição Elétrica (slug: medicao-eletrica)
   - Medição Dimensional (slug: medicao-dimensional)
   - Balanças (slug: balancas)

2. Criar 3 fabricantes via array fixo:
   - Testo Instruments (Alemanha)
   - Fluke Corporation (EUA)
   - Mitutoyo (Japão)

3. Criar 2 fornecedores via array fixo:
   - Instrulab Comércio Ltda — CNPJ 00.000.000/0001-00
   - MedTech Suprimentos — CNPJ 11.111.111/0001-11

4. Criar 10 equipamentos via EquipmentFactory

No `DatabaseSeeder.php`, adicionar call ao final:
`$this->call(EquipmentSeeder::class);`
</action>

<acceptance_criteria>
- EquipmentSeeder cria 5 categories, 3 manufacturers, 2 suppliers, 10 equipments
- DatabaseSeeder chama EquipmentSeeder após RolePermissionSeeder
- `php artisan db:seed` sem erros
</acceptance_criteria>

## Verification

1. `cd backend && php artisan migrate:fresh --seed` cria 5+3+2+10 registros
2. `cd backend && php artisan tinker --execute="echo Equipment::count()"` retorna 10
3. `cd backend && php artisan tinker --execute="echo Equipment::with('category')->first()->category->name"` retorna nome da categoria

## must_haves

truths:
  - 5 models criados com HasFactory, SoftDeletes e relacionamentos
  - Equipment model com LogsActivity trait para auditoria automática
  - Scopes active e byCategory em Equipment
  - Factory gera dados de laboratório realistas
  - Seeder cria todos os dados de desenvolvimento

## Artifacts This Phase Produces

- `backend/app/Models/Equipment.php` — Model principal com 6 relacionamentos e LogsActivity
- `backend/app/Models/Category.php` — Model de categoria
- `backend/app/Models/Manufacturer.php` — Model de fabricante
- `backend/app/Models/Supplier.php` — Model de fornecedor
- `backend/app/Models/EquipmentPhoto.php` — Model de foto
- `backend/database/factories/EquipmentFactory.php` — Factory com dados realistas
- `backend/database/seeders/EquipmentSeeder.php` — Seeder de desenvolvimento