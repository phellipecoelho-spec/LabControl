# 05-02a — Controllers, Form Requests e Rotas ✓

## Result
- **Status**: ✅ Complete  
- **Started**: 2026-07-20  
- **Completed**: 2026-07-20  

## What was built / verified
| Task | Files | Status |
|------|-------|--------|
| api.php routes | `backend/routes/api.php` | ✅ |
| StoreEquipmentRequest | `backend/app/Http/Requests/StoreEquipmentRequest.php` | ✅ |
| UpdateEquipmentRequest | `backend/app/Http/Requests/UpdateEquipmentRequest.php` | ✅ |
| CategoryController | `backend/app/Http/Controllers/Api/V1/CategoryController.php` | ✅ |
| ManufacturerController | `backend/app/Http/Controllers/Api/V1/ManufacturerController.php` | ✅ |
| SupplierController | `backend/app/Http/Controllers/Api/V1/SupplierController.php` | ✅ |
| EquipmentController | `backend/app/Http/Controllers/Api/V1/EquipmentController.php` | ✅ |

## Key details
- **Middleware**: auth:sanctum + permission (equipamentos.view, .create, .edit, .delete) via HasMiddleware
- **EquipmentController**: CRUD completo com filtros (search, category_id, manufacturer_id, status, location), paginate 15, eager load relationships
- **Category/Manufacturer/Supplier controllers**: CRUD com search, paginate 50, destroy bloqueia se houver equipamentos vinculados (409)
- **Form requests**: StoreEquipmentRequest com 5 required fields; UpdateEquipmentRequest com `sometimes`
- **Rotas**: Grupo `api/v1` com apiResource para equipments, categories, manufacturers, suppliers

## Verification
- `php artisan route:list --path=v1` → 17 rotas listadas
- `php artisan migrate:fresh --seed` → executa sem erros
