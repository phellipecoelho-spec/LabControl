---
wave: 1
depends_on: []
files_modified:
  - backend/database/migrations/xxxx_xx_xx_create_equipments_tables.php
autonomous: true
requirements:
  - EQUIP-01
  - EQUIP-02
---

# 05-01a: Database — Migrations (5 tabelas)

<objective>
Criar migration única com 5 tabelas do módulo de equipamentos: categories, manufacturers, suppliers, equipments, equipment_photos — todas com UUID, softDeletes e deleted_by.
</objective>

## Tasks

### T1: Migration — categories, manufacturers, suppliers, equipments, equipment_photos

<read_first>
- backend/database/migrations/0001_01_01_000003_create_roles_and_permissions_tables.php
- backend/database/migrations/0001_01_01_000000_create_users_table.php (UUID pattern)
</read_first>

<action>
Criar migration `backend/database/migrations/2026_07_19_000002_create_equipments_tables.php` com timestamp após a última migration existente.

Todas as tabelas usam:
- id: uuid primary key via `uuid('id')->primary()->default(DB::raw('gen_random_uuid()'))`
- SoftDeletes: `softDeletes()`
- Audit: `foreignUuid('deleted_by')->nullable()->constrained('users')`

**Tabela `categories`:**
- name: string(255)
- slug: string(255)->unique()

**Tabela `manufacturers`:**
- name: string(255)
- country: string(100)->nullable()
- website: string(255)->nullable()
- logo_path: string(255)->nullable()

**Tabela `suppliers`:**
- name: string(255)
- cnpj: string(18)->nullable()->unique()
- contact_name: string(255)->nullable()
- contact_email: string(255)->nullable()
- contact_phone: string(20)->nullable()
- address: text()->nullable()

**Tabela `equipments`:**
- name: string(255)
- patrimony_id: string(50)->nullable()
- serial_number: string(100)->nullable()
- category_id: foreignUuid('category_id')->nullable()->constrained('categories')
- manufacturer_id: foreignUuid('manufacturer_id')->nullable()->constrained('manufacturers')
- supplier_id: foreignUuid('supplier_id')->nullable()->constrained('suppliers')
- location: string(255)->nullable()
- acquisition_date: date()->nullable()
- warranty_end: date()->nullable()
- status: string(20)->default('active')
- description: text()->nullable()
- technical_specs: text()->nullable()
- notes: text()->nullable()
- user_id: foreignUuid('user_id')->constrained('users')
- Índices: index(['status']), index(['category_id']), index(['manufacturer_id']), index(['supplier_id']), index(['user_id'])

**Tabela `equipment_photos`:**
- equipment_id: foreignUuid('equipment_id')->constrained('equipments')->onDelete('cascade')
- path: string(255)
- original_name: string(255)
- size: integer()
- mime_type: string(50)
- sort_order: integer()->default(0)
- created_at apenas (sem updated_at, sem softDeletes)
- Índices: index(['equipment_id', 'sort_order'])
</action>

<acceptance_criteria>
- Migration rodando com `php artisan migrate` sem erros
- Todas as 5 tabelas criadas com UUIDs, softDeletes e FKs
- `php artisan migrate:fresh; php artisan migrate` executa sem erros
</acceptance_criteria>

## Verification

1. `cd backend && php artisan migrate:fresh` sem erros
2. Verificar tabelas via psql: `\dt categories` | `\dt manufacturers` | `\dt suppliers` | `\dt equipments` | `\dt equipment_photos`

## must_haves

truths:
  - Migration cria 5 tabelas com UUIDs, softDeletes e deleted_by
  - Todas as FKs definidas (category_id, manufacturer_id, supplier_id, user_id, equipment_id)
  - Índices nos campos de filtro (status, category_id, manufacturer_id, supplier_id, user_id)

## Artifacts This Phase Produces

- `backend/database/migrations/2026_07_19_000002_create_equipments_tables.php` — 5 tabelas em uma migration