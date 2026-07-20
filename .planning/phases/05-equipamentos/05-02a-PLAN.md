---
wave: 1
depends_on:
  - 05-01b
files_modified:
  - backend/app/Http/Controllers/Api/V1/EquipmentController.php
  - backend/app/Http/Controllers/Api/V1/CategoryController.php
  - backend/app/Http/Controllers/Api/V1/ManufacturerController.php
  - backend/app/Http/Controllers/Api/V1/SupplierController.php
  - backend/app/Http/Requests/StoreEquipmentRequest.php
  - backend/app/Http/Requests/UpdateEquipmentRequest.php
  - backend/routes/api.php
autonomous: true
requirements:
  - EQUIP-01
  - EQUIP-02
---

# 05-02a: Backend CRUD — Controllers, Form Requests e Rotas

<objective>
Criar API RESTful para equipamentos e tabelas de apoio: 4 controllers com middleware de permissão, 2 form requests, arquivo de rotas api.php.
</objective>

## Tasks

### T1: Routes — api.php

<read_first>
- backend/app/Http/Controllers/Api/V1/UserController.php (padrão de middleware)
</read_first>

<action>
Criar `backend/routes/api.php` (se não existir) com:

```php
<?php

use App\Http\Controllers\Api\V1\EquipmentController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ManufacturerController;
use App\Http\Controllers\Api\V1\SupplierController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('equipments', EquipmentController::class);
    Route::apiResource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('manufacturers', ManufacturerController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);
});
```
</action>

<acceptance_criteria>
- api.php criado com namespace App\Http\Controllers\Api\V1
- Grupo v1 com auth:sanctum middleware
- Rotas: equipments (full CRUD), categories/manufacturers/suppliers (index, store, update, destroy)
</acceptance_criteria>

### T2: Form Request — StoreEquipmentRequest

<read_first>
- backend/app/Http/Requests/StoreUserRequest.php
</read_first>

<action>
Criar `backend/app/Http/Requests/StoreEquipmentRequest.php`:

Regras de validação:
- name: required, string, max:255
- serial_number: required, string, max:100
- category_id: required, exists:categories,id
- manufacturer_id: required, exists:manufacturers,id
- location: required, string, max:255
- patrimony_id: nullable, string, max:50
- supplier_id: nullable, exists:suppliers,id
- acquisition_date: nullable, date
- warranty_end: nullable, date, after_or_equal:acquisition_date
- status: nullable, string, in:active,inactive,maintenance,retired
- description: nullable, string
- technical_specs: nullable, string
- notes: nullable, string

authorize(): return true (permissão checada via middleware).
messages(): traduções pt-BR para required, exists, max, date.
</action>

<acceptance_criteria>
- StoreEquipmentRequest com 5 campos required (name, serial_number, category_id, manufacturer_id, location)
- Campos opcionais nullable conforme especificado
</acceptance_criteria>

### T3: Form Request — UpdateEquipmentRequest

<read_first>
- backend/app/Http/Requests/UpdateUserRequest.php
</read_first>

<action>
Criar `backend/app/Http/Requests/UpdateEquipmentRequest.php`:

Mesmas regras do StoreEquipmentRequest, mas todos os campos com `sometimes` no lugar de `required`.
</action>

<acceptance_criteria>
- UpdateEquipmentRequest com regras sometimes em vez de required
</acceptance_criteria>

### T4: Controller — CategoryController

<read_first>
- backend/app/Http/Controllers/Api/V1/UserController.php
</read_first>

<action>
Criar `backend/app/Http/Controllers/Api/V1/CategoryController.php`:

Middleware via HasMiddleware interface:
- auth:sanctum em todas
- permission:equipamentos.view em index
- permission:equipamentos.create em store
- permission:equipamentos.edit em update
- permission:equipamentos.delete em destroy

Actions:
- index: paginate 50, ordenado por name ASC, search por name via ilike
- store: validate name required|max:255, slug required|unique:categories; criar Category
- update(Category): validate name sommetimes|max:255, slug sommetimes|unique:categories; atualizar
- destroy(Category): verificar Equipment::where('category_id', $id)->exists(); se sim, retornar 409 'Não é possível excluir categoria com equipamentos vinculados'; senão, deletar

Respostas: JsonResponse com dados ou mensagens.
</action>

<acceptance_criteria>
- CategoryController com middleware de permissão
- Destroy bloqueia exclusão se houver equipamentos vinculados (409 Conflict)
</acceptance_criteria>

### T5: Controller — ManufacturerController

<read_first>
- backend/app/Http/Controllers/Api/V1/CategoryController.php
</read_first>

<action>
Criar `backend/app/Http/Controllers/Api/V1/ManufacturerController.php`:

Mesmo padrão do CategoryController.
Middleware idêntico (equipamentos.view, .create, .edit, .delete).
Store validate: name required|max:255, country nullable|max:100, website nullable|url.
Destroy: verificar Equipment::where('manufacturer_id', $id)->exists(); 409 se sim.
</action>

<acceptance_criteria>
- ManufacturerController com middleware
- Destroy bloqueia se equipamentos vinculados (409)
</acceptance_criteria>

### T6: Controller — SupplierController

<read_first>
- backend/app/Http/Controllers/Api/V1/CategoryController.php
</read_first>

<action>
Criar `backend/app/Http/Controllers/Api/V1/SupplierController.php`:

Mesmo padrão.
Store validate: name required|max:255, cnpj nullable|unique:suppliers, contact_email nullable|email.
Destroy: verificar Equipment::where('supplier_id', $id)->exists(); 409 se sim.
</action>

<acceptance_criteria>
- SupplierController com middleware
- Destroy bloqueia se equipamentos vinculados (409)
</acceptance_criteria>

### T7: Controller — EquipmentController

<read_first>
- backend/app/Http/Controllers/Api/V1/UserController.php
- backend/app/Http/Requests/StoreEquipmentRequest.php
- backend/app/Http/Requests/UpdateEquipmentRequest.php
</read_first>

<action>
Criar `backend/app/Http/Controllers/Api/V1/EquipmentController.php`:

Middleware via HasMiddleware:
- auth:sanctum em todas
- permission:equipamentos.view em index, show
- permission:equipamentos.create em store
- permission:equipamentos.edit em update
- permission:equipamentos.delete em destroy

Actions:
- index(Request): paginate 15, eager load category, manufacturer, supplier, photos.
  Filtros: search (name|serial_number|patrimony_id ilike), category_id, manufacturer_id, status, location.
  Ordenação: created_at desc.
- show(Equipment): load category, manufacturer, supplier, photos.
- store(StoreEquipmentRequest): data['user_id'] = auth()->id(); Equipment::create(data); load relacionamentos; retornar 201.
- update(UpdateEquipmentRequest, Equipment): data['updated_by'] = auth()->id(); equipment->update(data); load relacionamentos; retornar 200.
- destroy(Equipment): equipment->deleted_by = auth()->id(); equipment->save(); equipment->delete(); retornar 204.

Response: JsonResponse. Controller não usa Resources nesta wave — retorna model direto com load.
</action>

<acceptance_criteria>
- EquipmentController com index/show/store/update/destroy
- Filtros: search, category_id, manufacturer_id, status, location
- user_id automático no store, updated_by/deleted_by no update/destroy
- Eager loading de relacionamentos
</acceptance_criteria>

## Verification

1. `cd backend && php artisan route:list --path=v1` lista todas as rotas
2. `cd backend && php artisan migrate:fresh --seed`
3. `cd backend && php artisan tinker --execute="app()->call('App\\Http\\Controllers\\Api\\V1\\CategoryController@index', [request()])"` (teste básico de acesso)

## must_haves

truths:
  - EquipmentController implementa CRUD completo (index, show, store, update, destroy)
  - Controllers de apoio (Category, Manufacturer, Supplier) com CRUD básico
  - Middleware de permissão em todos os controllers
  - Form Requests validam 5 campos obrigatórios (name, serial_number, category_id, manufacturer_id, location)
  - api.php com grupo v1 e auth:sanctum

## Artifacts This Phase Produces

- `backend/app/Http/Controllers/Api/V1/EquipmentController.php` — CRUD equipamentos
- `backend/app/Http/Controllers/Api/V1/CategoryController.php` — CRUD categorias
- `backend/app/Http/Controllers/Api/V1/ManufacturerController.php` — CRUD fabricantes
- `backend/app/Http/Controllers/Api/V1/SupplierController.php` — CRUD fornecedores
- `backend/app/Http/Requests/StoreEquipmentRequest.php` — Validação criação
- `backend/app/Http/Requests/UpdateEquipmentRequest.php` — Validação atualização
- `backend/routes/api.php` — Rotas da API v1