# 05-04 — Fotos e Histórico de Alterações ✓

## Result
- **Status**: ✅ Complete  
- **Started**: 2026-07-20  
- **Completed**: 2026-07-20  

## What was built
| Task | Files | Status |
|------|-------|--------|
| EquipmentPhotoService | `backend/app/Services/EquipmentPhotoService.php` | ✅ |
| EquipmentPhotoController | `backend/app/Http/Controllers/Api/V1/EquipmentPhotoController.php` | ✅ |
| Photo routes | `backend/routes/api.php` | ✅ |
| EquipmentPhotoUploader | `frontend/src/modules/equipment/components/EquipmentPhotoUploader.vue` | ✅ |
| EquipmentLogsSection | `frontend/src/modules/equipment/components/EquipmentLogsSection.vue` | ✅ |
| EquipmentDetailPage update | `frontend/src/modules/equipment/pages/EquipmentDetailPage.vue` | ✅ |
| EquipmentPhotoTest (6 tests) | `backend/tests/Feature/EquipmentPhotoTest.php` | ✅ |

## Corrections
- `UploadedFile::fake()->image()` não funciona sem GD extension → `->create(..., 'image/jpeg')`
- Route model binding não funciona com `{photo}` em grupo prefixado → `string $photo` com `EquipmentPhotoService::delete($photo)`
- Todos os métodos do controller precisam incluir `Equipment $equipment` por causa do prefixo do grupo de rotas
- Test reorder verifica por id em vez de sort_order numérico

## Verification
- ✅ `php artisan migrate:fresh --seed` sem erros
- ✅ 14 testes (8 EquipmentApi + 6 EquipmentPhoto), 29 assertions
- ✅ 21 rotas registradas (17 CRUD + 4 photos)
