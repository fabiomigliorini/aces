const routes = [
  // Rotas públicas (sem autenticação)
  {
    path: '/',
    component: () => import('layouts/AuthLayout.vue'),
    meta: { guest: true },
    children: [
      {
        path: 'login',
        name: 'login',
        component: () => import('pages/auth/LoginPage.vue'),
      },
      {
        path: 'forgot-password',
        name: 'forgot-password',
        component: () => import('pages/auth/ForgotPasswordPage.vue'),
      },
      {
        path: 'reset-password',
        name: 'reset-password',
        component: () => import('pages/auth/ResetPasswordPage.vue'),
      },
    ],
  },

  // Rotas protegidas (requer autenticação)
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('pages/DashboardPage.vue'),
      },
      {
        path: 'profile',
        name: 'profile',
        component: () => import('pages/ProfilePage.vue'),
      },
      // Cadastros
      {
        path: 'organizations',
        name: 'organizations',
        component: () => import('pages/organizations/OrganizationsPage.vue'),
      },
      {
        path: 'tenants',
        name: 'tenants',
        component: () => import('pages/tenants/TenantsPage.vue'),
      },
      {
        path: 'users',
        name: 'users',
        component: () => import('pages/users/UsersPage.vue'),
      },
      {
        path: 'roles',
        name: 'roles',
        component: () => import('pages/roles/RolesPage.vue'),
      },
    ],
  },

  // 404
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue'),
  },
]

export default routes
