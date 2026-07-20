# 05-01b — Models, Factories, Seeders ✓

## Result
- **Status**: ✅ Complete  
- **Started**: 2026-07-20  
- **Completed**: 2026-07-20  

## What was built
| Task | Files | Status |
|------|-------|--------|
| Category model | `app/Models/Category.php` | ✅ |
| Manufacturer model | `app/Models/Manufacturer.php` | ✅ |
| Supplier model | `app/Models/Supplier.php` | ✅ |
| EquipmentPhoto model | `app/Models/EquipmentPhoto.php` | ✅ |
| Equipment model | `app/Models/Equipment.php` | ✅ |
| EquipmentFactory | `database/factories/EquipmentFactory.php` | ✅ |
| CategoryFactory | `database/factories/CategoryFactory.php` | ✅ |
| ManufacturerFactory | `database/factories/ManufacturerFactory.php` | ✅ |
| SupplierFactory | `database/factories/SupplierFactory.php` | ✅ |
| EquipmentSeeder | `database/seeders/EquipmentSeeder.php` | ✅ |
| DatabaseSeeder updated | `database/seeders/DatabaseSeeder.php` | ✅ |

## Corrections made during execution
- `dateBetween` → `dateTimeBetween` (Faker v2.6 compat)
- Duplicate `'location'` array key removed
- `$table = 'equipments'` added to Equipment model (English pluralizer treats "equipment" as uncountable)
- Category/Manufacturer/Supplier factories created (needed for EquipmentFactory)
- Factory relations changed from `::factory()` to `inRandomOrder()->first()?->id` (avoids unique constraint exhaustion with seed data)

## Verification
- `php artisan migrate:fresh --seed` runs clean
- 5 categories, 3 manufacturers, 2 suppliers, 10 equipments created
