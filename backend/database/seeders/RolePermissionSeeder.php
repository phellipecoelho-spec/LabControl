<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'Visualizar Dashboard', 'slug' => 'dashboard.view', 'group' => 'dashboard'],
            ['name' => 'Visualizar Relatórios', 'slug' => 'relatorios.view', 'group' => 'relatorios'],
            ['name' => 'Exportar Relatórios', 'slug' => 'relatorios.export', 'group' => 'relatorios'],
            ['name' => 'Visualizar Equipamentos', 'slug' => 'equipamentos.view', 'group' => 'equipamentos'],
            ['name' => 'Criar Equipamentos', 'slug' => 'equipamentos.create', 'group' => 'equipamentos'],
            ['name' => 'Editar Equipamentos', 'slug' => 'equipamentos.edit', 'group' => 'equipamentos'],
            ['name' => 'Excluir Equipamentos', 'slug' => 'equipamentos.delete', 'group' => 'equipamentos'],
            ['name' => 'Visualizar Estoque', 'slug' => 'estoque.view', 'group' => 'estoque'],
            ['name' => 'Criar Estoque', 'slug' => 'estoque.create', 'group' => 'estoque'],
            ['name' => 'Editar Estoque', 'slug' => 'estoque.edit', 'group' => 'estoque'],
            ['name' => 'Excluir Estoque', 'slug' => 'estoque.delete', 'group' => 'estoque'],
            ['name' => 'Visualizar Movimentações', 'slug' => 'movimentacoes.view', 'group' => 'movimentacoes'],
            ['name' => 'Criar Movimentações', 'slug' => 'movimentacoes.create', 'group' => 'movimentacoes'],
            ['name' => 'Visualizar Empréstimos', 'slug' => 'emprestimos.view', 'group' => 'emprestimos'],
            ['name' => 'Criar Empréstimos', 'slug' => 'emprestimos.create', 'group' => 'emprestimos'],
            ['name' => 'Editar Empréstimos', 'slug' => 'emprestimos.edit', 'group' => 'emprestimos'],
            ['name' => 'Finalizar Empréstimos', 'slug' => 'emprestimos.finalizar', 'group' => 'emprestimos'],
            ['name' => 'Visualizar Metrologia', 'slug' => 'metrologia.view', 'group' => 'metrologia'],
            ['name' => 'Criar Calibrações', 'slug' => 'metrologia.calibracoes.create', 'group' => 'metrologia'],
            ['name' => 'Editar Calibrações', 'slug' => 'metrologia.calibracoes.edit', 'group' => 'metrologia'],
            ['name' => 'Visualizar Aferições', 'slug' => 'afericoes.view', 'group' => 'afericoes'],
            ['name' => 'Criar Aferições', 'slug' => 'afericoes.create', 'group' => 'afericoes'],
            ['name' => 'Editar Aferições', 'slug' => 'afericoes.edit', 'group' => 'afericoes'],
            ['name' => 'Visualizar Certificados', 'slug' => 'certificados.view', 'group' => 'certificados'],
            ['name' => 'Upload Certificados', 'slug' => 'certificados.upload', 'group' => 'certificados'],
            ['name' => 'Visualizar Usuários', 'slug' => 'usuarios.view', 'group' => 'usuarios'],
            ['name' => 'Criar Usuários', 'slug' => 'usuarios.create', 'group' => 'usuarios'],
            ['name' => 'Editar Usuários', 'slug' => 'usuarios.edit', 'group' => 'usuarios'],
            ['name' => 'Excluir Usuários', 'slug' => 'usuarios.delete', 'group' => 'usuarios'],
            ['name' => 'Visualizar Logs de Auditoria', 'slug' => 'auditoria.view', 'group' => 'auditoria'],
            ['name' => 'Gerenciar Configurações', 'slug' => 'configuracoes.manage', 'group' => 'configuracoes'],
        ];

        $roles = [
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Acesso total ao sistema',
                'is_system' => true,
                'permissions' => collect($permissions)->pluck('slug')->all(),
            ],
            [
                'name' => 'Supervisor',
                'slug' => 'supervisor',
                'description' => 'Supervisão operacional',
                'is_system' => true,
                'permissions' => collect($permissions)->pluck('slug')->reject(fn ($s) =>
                    $s === 'configuracoes.manage' || $s === 'usuarios.delete'
                )->all(),
            ],
            [
                'name' => 'Laboratorista',
                'slug' => 'laboratorista',
                'description' => 'Operações de laboratório',
                'is_system' => false,
                'permissions' => [
                    'dashboard.view',
                    'equipamentos.view', 'equipamentos.create', 'equipamentos.edit',
                    'estoque.view', 'estoque.create', 'estoque.edit',
                    'movimentacoes.view', 'movimentacoes.create',
                    'emprestimos.view', 'emprestimos.create', 'emprestimos.edit', 'emprestimos.finalizar',
                    'metrologia.view', 'metrologia.calibracoes.create', 'metrologia.calibracoes.edit',
                    'afericoes.view', 'afericoes.create', 'afericoes.edit',
                    'certificados.view', 'certificados.upload',
                ],
            ],
            [
                'name' => 'Técnico',
                'slug' => 'tecnico',
                'description' => 'Operações básicas',
                'is_system' => false,
                'permissions' => [
                    'dashboard.view',
                    'equipamentos.view',
                    'estoque.view',
                    'movimentacoes.view', 'movimentacoes.create',
                    'emprestimos.view', 'emprestimos.create',
                    'certificados.view', 'certificados.upload',
                ],
            ],
            [
                'name' => 'Consulta',
                'slug' => 'consulta',
                'description' => 'Apenas consulta',
                'is_system' => false,
                'permissions' => [
                    'dashboard.view',
                    'equipamentos.view',
                    'estoque.view',
                    'movimentacoes.view',
                    'emprestimos.view',
                    'metrologia.view',
                    'afericoes.view',
                    'certificados.view',
                ],
            ],
            [
                'name' => 'Auditor',
                'slug' => 'auditor',
                'description' => 'Auditoria e consulta',
                'is_system' => false,
                'permissions' => [
                    'dashboard.view',
                    'relatorios.view', 'relatorios.export',
                    'auditoria.view',
                    'equipamentos.view',
                    'estoque.view',
                    'movimentacoes.view',
                    'emprestimos.view',
                    'metrologia.view',
                    'afericoes.view',
                    'certificados.view',
                    'usuarios.view',
                ],
            ],
        ];

        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        foreach ($permissions as $perm) {
            DB::table('permissions')->insert([
                'id' => (string) Str::uuid(),
                'name' => $perm['name'],
                'slug' => $perm['slug'],
                'group' => $perm['group'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permMap = DB::table('permissions')->pluck('id', 'slug');

        foreach ($roles as $role) {
            $roleId = (string) Str::uuid();
            DB::table('roles')->insert([
                'id' => $roleId,
                'name' => $role['name'],
                'slug' => $role['slug'],
                'description' => $role['description'],
                'is_system' => $role['is_system'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $uniquePerms = array_unique($role['permissions']);
            $rolePerms = [];
            foreach ($uniquePerms as $slug) {
                if (isset($permMap[$slug])) {
                    $rolePerms[] = [
                        'permission_id' => $permMap[$slug],
                        'role_id' => $roleId,
                    ];
                }
            }

            if (!empty($rolePerms)) {
                DB::table('permission_role')->insert($rolePerms);
            }
        }
    }
}
