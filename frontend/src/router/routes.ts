import type { RouteRecordRaw } from 'vue-router'

export const routes: RouteRecordRaw[] = [
  {
    path: '/admin/logs',
    name: 'admin.logs',
    component: () => import('@/modules/admin/pages/AuditLogsPage.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'auditor'], title: 'Logs de Auditoria', module: 'admin.logs' },
  },
  {
    path: '/',
    name: 'dashboard',
    component: () => import('@/modules/dashboard/pages/DashboardPage.vue'),
    meta: { requiresAuth: true, title: 'Dashboard', module: 'dashboard' },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { guest: true, title: 'Login' },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/auth/RegisterView.vue'),
    meta: { guest: true, title: 'Cadastro' },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/auth/ForgotPasswordView.vue'),
    meta: { guest: true, title: 'Recuperar Senha' },
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/views/auth/ResetPasswordView.vue'),
    meta: { guest: true, title: 'Redefinir Senha' },
  },
  {
    path: '/verify-email/:id?/:hash?',
    name: 'verify-email',
    component: () => import('@/views/auth/VerifyEmailView.vue'),
    meta: { guest: true, title: 'Verificar Email' },
  },
  {
    path: '/verify-email',
    name: 'verify-email.pending',
    component: () => import('@/views/auth/VerifyEmailPendingView.vue'),
    meta: { guest: true, title: 'Verificar Email' },
  },
  {
    path: '/admin/users',
    name: 'admin.users',
    component: () => import('@/modules/admin/pages/UsersPage.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'supervisor'], title: 'Usuários', module: 'admin.users' },
  },
  {
    path: '/admin/roles',
    name: 'admin.roles',
    component: () => import('@/modules/admin/pages/RolesPage.vue'),
    meta: { requiresAuth: true, roles: ['admin'], title: 'Perfis de Acesso', module: 'admin.roles' },
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('@/modules/profile/pages/ProfilePage.vue'),
    meta: {
      requiresAuth: true,
      title: 'Meu Perfil',
      module: 'profile',
    },
  },
  {
    path: '/equipments',
    name: 'equipments',
    component: () => import('@/modules/equipment/pages/EquipmentListPage.vue'),
    meta: { requiresAuth: true, module: 'gestao', title: 'Equipamentos' },
  },
  {
    path: '/equipments/new',
    name: 'equipment-create',
    component: () => import('@/modules/equipment/pages/EquipmentFormPage.vue'),
    meta: { requiresAuth: true, module: 'gestao', title: 'Novo Equipamento' },
  },
  {
    path: '/equipments/:id/edit',
    name: 'equipment-edit',
    component: () => import('@/modules/equipment/pages/EquipmentFormPage.vue'),
    meta: { requiresAuth: true, module: 'gestao', title: 'Editar Equipamento' },
  },
  {
    path: '/equipments/:id',
    name: 'equipment-detail',
    component: () => import('@/modules/equipment/pages/EquipmentDetailPage.vue'),
    meta: { requiresAuth: true, module: 'gestao', title: 'Detalhes do Equipamento' },
  },
  {
    path: '/unauthorized',
    name: 'unauthorized',
    component: () => import('@/views/UnauthorizedView.vue'),
    meta: { requiresAuth: true, title: 'Acesso Negado' },
  },
]
