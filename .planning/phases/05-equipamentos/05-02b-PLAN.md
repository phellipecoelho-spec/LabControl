---
wave: 2
depends_on:
  - 05-02a
files_modified:
  - backend/app/Http/Resources/EquipmentResource.php
  - backend/app/Http/Resources/CategoryResource.php
  - backend/app/Http/Resources/ManufacturerResource.php
  - backend/app/Http/Resources/SupplierResource.php
  - backend/app/Http/Controllers/Api/V1/EquipmentController.php
  - backend/tests/Feature/EquipmentApiTest.php
autonomous: true
requirements:
  - EQUIP-01
  - EQUIP-02
---

# 05-02b: Backend CRUD — API Resources e Testes

<objective>
Criar API Resources para formatação JSON padronizada e testes funcionais para os endpoints de equipamentos.
</objective>

## Tasks

### T1: API Resource — EquipmentResource

<read_first>
- backend/app/Http/Resources (diretório, se existir)
- backend/app/Http/Controllers/Api/V1/EquipmentController.php (do 05-02a)
</read_first>

<action>
Criar `backend/app/Http/Resources/EquipmentResource.php`:

toArray:
- id, name, patrimony_id, serial_number
- location, acquisition_date, warranty_end, status
- description, technical_specs, notes
- category (new CategoryResource($this->whenLoaded('category')))
- manufacturer (new ManufacturerResource($this->whenLoaded('manufacturer')))
- supplier (new SupplierResource($this->whenLoaded('supplier')))
- photos (EquipmentPhoto em array simplificado: id, path, url (Storage::url($this->path)), sort_order) — whenLoaded('photos')
- created_at, updated_at

Wrapper: usar `whenLoaded()` para todos os relacionamentos (não aparecem no JSON se não carregados).

Atualizar EquipmentController para usar EquipmentResource:
- index: return EquipmentResource::collection($equipments)
- show: return new EquipmentResource($equipment)
- store: return new EquipmentResource($equipment), 201
- update: return new EquipmentResource($equipment)
</action>

<acceptance_criteria>
- EquipmentResource com campos formatados e whenLoaded
- EquipmentController usa EquipmentResource em todas as actions
</acceptance_criteria>

### T2: API Resource — CategoryResource, ManufacturerResource, SupplierResource

<read_first>
- backend/app/Http/Resources/EquipmentResource.php
</read_first>

<action>
Criar 3 resources em `backend/app/Http/Resources/`:

CategoryResource: id, name, slug, equipments_count (via $this->whenCounted('equipments'))

ManufacturerResource: id, name, country, website

SupplierResource: id, name, cnpj, contact_name, contact_email, contact_phone

Formato: todos retornam array simples via toArray(), mesmo padrão de EquipmentResource
</action>

<acceptance_criteria>
- 3 resources com campos conforme especificado
- CategoryResource com equipments_count (quando carregado)
</acceptance_criteria>

### T3: Testes — EquipmentApiTest

<read_first>
- backend/tests/Feature/AuthControllerTest.php (se existir) ou backend/tests/Feature/ExampleTest.php
- backend/database/factories/EquipmentFactory.php
- backend/app/Http/Controllers/Api/V1/EquipmentController.php
</read_first>

<action>
Criar `backend/tests/Feature/EquipmentApiTest.php`:

8 testes:

1. test_unauthenticated_user_does_not_access_equipment_endpoints: GET /api/v1/equipments sem autenticação → 401
2. test_can_list_equipments: criar 3 equipamentos, GET /api/v1/equipments com usuário autenticado → 200 com dados paginados
3. test_can_create_equipment: POST /api/v1/equipments com dados válidos → 201
4. test_can_show_equipment: criar equipamento, GET /api/v1/equipments/{id} → 200 com dados do equipamento
5. test_can_update_equipment: criar equipamento, PUT /api/v1/equipments/{id} → 200
6. test_can_delete_equipment: criar equipamento, DELETE /api/v1/equipments/{id} → 204
7. test_can_filter_equipments_by_category: criar equipamentos em 2 categorias, filtrar por category_id
8. test_can_search_equipments: criar equipamento com nome "Termômetro", GET /api/v1/equipments?search=Termo → encontra

Usar: RefreshDatabase trait, actingAs com User que tenha permissão equipamentos.* (via RolePermissionSeeder).

Dados via EquipmentFactory ou EquipmentSeeder::run() nos testes.
</action>

<acceptance_criteria>
- 8 testes passando com `php artisan test --filter EquipmentApiTest`
- Todos usam RefreshDatabase e actingAs
- Teste #1 verifica 401 para unauthenticated
- Teste #3 verifica 201 + dados retornados
- Teste #7 verifica filtro por categoria
- Teste #8 verifica search textual
</acceptance_criteria>

## Verification

1. `cd backend && php artisan test --filter EquipmentApiTest` → 8/8 passando
2. Todos os testes usam RefreshDatabase (sem dados persistentes após teste)

## must_haves

truths:
  - EquipmentResource formata saída JSON com relacionamentos condicionais
  - Category/Manufacturer/Supplier Resources padronizam saída das tabelas de apoio
  - 8 testes cobrem autenticação, CRUD completo e filtros

## Artifacts This Phase Produces

- `backend/app/Http/Resources/EquipmentResource.php` — Resource principal com whenLoaded
- `backend/app/Http/Resources/CategoryResource.php` — Resource categorias
- `backend/app/Http/Resources/ManufacturerResource.php` — Resource fabricantes
- `backend/app/Http/Resources/SupplierResource.php` — Resource fornecedores
- `backend/tests/Feature/EquipmentApiTest.php` — 8 testes funcionais