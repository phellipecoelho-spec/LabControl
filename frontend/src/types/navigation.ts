export interface NavModule {
  label: string
  icon: string
  route: string
  permission: string | null
  roles?: string[]
}

export interface NavCategory {
  key: string
  label: string
  icon: string
  items: NavModule[]
}

export type NavItem = NavCategory | NavModule

export const navigationTree: NavItem[] = [
  {
    label: 'Dashboard',
    icon: 'pi-home',
    route: 'dashboard',
    permission: null,
  },
  {
    key: 'gestao',
    label: 'Gestão',
    icon: 'pi-folder',
    items: [
      {
        label: 'Equipamentos',
        icon: 'pi-box',
        route: 'equipment.index',
        permission: 'equipamentos.view',
      },
      {
        label: 'Estoque',
        icon: 'pi-warehouse',
        route: 'inventory.index',
        permission: 'estoque.view',
      },
    ],
  },
  {
    key: 'operacoes',
    label: 'Operações',
    icon: 'pi-folder',
    items: [
      {
        label: 'Movimentações',
        icon: 'pi-arrow-right-arrow-left',
        route: 'movements.index',
        permission: 'movimentacoes.view',
      },
      {
        label: 'Empréstimos',
        icon: 'pi-share-alt',
        route: 'loans.index',
        permission: 'emprestimos.view',
      },
      {
        label: 'Calibrações',
        icon: 'pi-calendar-clock',
        route: 'calibrations.index',
        permission: 'calibracoes.view',
      },
      {
        label: 'Aferições',
        icon: 'pi-check-circle',
        route: 'verifications.index',
        permission: 'afericoes.view',
      },
      {
        label: 'Manutenções',
        icon: 'pi-wrench',
        route: 'maintenance.index',
        permission: 'manutencoes.view',
      },
    ],
  },
  {
    key: 'admin',
    label: 'Administração',
    icon: 'pi-folder',
    items: [
      {
        label: 'Usuários',
        icon: 'pi-users',
        route: 'admin.users',
        permission: null,
        roles: ['admin', 'supervisor'],
      },
      {
        label: 'Perfis de Acesso',
        icon: 'pi-shield',
        route: 'admin.roles',
        permission: null,
        roles: ['admin'],
      },
      {
        label: 'Logs de Auditoria',
        icon: 'pi-history',
        route: 'admin.logs',
        permission: null,
        roles: ['admin', 'auditor'],
      },
    ],
  },
  {
    key: 'relatorios',
    label: 'Relatórios',
    icon: 'pi-folder',
    items: [
      {
        label: 'Relatórios',
        icon: 'pi-file-pdf',
        route: 'reports.index',
        permission: 'relatorios.view',
      },
    ],
  },
]

export const routeModuleMap: Record<string, string> = {
  dashboard: 'dashboard',
  'equipment.index': 'gestao',
  'equipment.create': 'gestao',
  'equipment.edit': 'gestao',
  'equipment.show': 'gestao',
  'inventory.index': 'gestao',
  'inventory.create': 'gestao',
  'movements.index': 'operacoes',
  'movements.create': 'operacoes',
  'loans.index': 'operacoes',
  'loans.create': 'operacoes',
  'calibrations.index': 'operacoes',
  'calibrations.create': 'operacoes',
  'verifications.index': 'operacoes',
  'verifications.create': 'operacoes',
  'maintenance.index': 'operacoes',
  'maintenance.create': 'operacoes',
  'admin.users': 'admin',
  'admin.users.create': 'admin',
  'admin.users.edit': 'admin',
  'admin.roles': 'admin',
  'admin.roles.create': 'admin',
  'admin.roles.edit': 'admin',
  'admin.logs': 'admin',
  'reports.index': 'relatorios',
  profile: 'admin',
}
