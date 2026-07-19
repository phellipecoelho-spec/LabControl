import type { RouteRecordRaw } from 'vue-router'

export const routes: RouteRecordRaw[] = [
  {
    path: '/admin/logs',
    name: 'admin.logs',
    component: () => import('@/modules/admin/pages/AuditLogsPage.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'auditor'], title: 'Logs de Auditoria' },
  },
  {
    path: '/',
    name: 'dashboard',
    component: () => import('@/modules/dashboard/pages/DashboardPage.vue'),
    meta: { requiresAuth: true, title: 'Dashboard' },
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
    meta: { requiresAuth: true, roles: ['admin', 'supervisor'], title: 'Usuários' },
  },
  {
    path: '/admin/roles',
    name: 'admin.roles',
    component: () => import('@/modules/admin/pages/RolesPage.vue'),
    meta: { requiresAuth: true, roles: ['admin'], title: 'Perfis de Acesso' },
  },
]
