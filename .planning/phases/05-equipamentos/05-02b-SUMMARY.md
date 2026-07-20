# 05-02b — API Resources e Testes ✓

## Result
- **Status**: ✅ Complete  
- **Started**: 2026-07-20  
- **Completed**: 2026-07-20  

## What was built
| Task | Files | Status |
|------|-------|--------|
| EquipmentResource | `backend/app/Http/Resources/EquipmentResource.php` | ✅ |
| CategoryResource | `backend/app/Http/Resources/CategoryResource.php` | ✅ |
| ManufacturerResource | `backend/app/Http/Resources/ManufacturerResource.php` | ✅ |
| SupplierResource | `backend/app/Http/Resources/SupplierResource.php` | ✅ |
| EquipmentApiTest (8 tests) | `backend/tests/Feature/EquipmentApiTest.php` | ✅ |

## Corrections made
- Middleware syntax: `'permission'` → `'permission:slug'` inline (string format) em todos os controllers
- Bypassed `assignRole()` não existente → attachment via `$user->roles()->attach(Role::where('slug', 'admin')->value('id'))`
- Return type `: JsonResponse` removido de `index()` (retorna `AnonymousResourceCollection`)
- Store/update: `response()->json(new Resource(...))` → `new Resource(...)` e `(new Resource(...))->response()->setStatusCode(201)`
- Test search usa nomes explícitos (factory random não garante match)

## Verification
- ✅ `php artisan test --filter EquipmentApiTest` → 8/8 passed (18 assertions)
