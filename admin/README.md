# ACES Admin

Painel administrativo da ACES - admin.aces.org.br

## Stack

- **Quasar 2** - Framework Vue.js
- **Vue 3** - Composition API
- **JavaScript** - Sem TypeScript
- **Pinia** - State management
- **Axios** - HTTP client

## Estrutura

```
src/
├── boot/
│   └── axios.js              # Configuração global do Axios
├── components/
│   └── NavItem.vue           # Item de menu (suporta submenu)
├── layouts/
│   ├── AuthLayout.vue        # Layout para páginas de auth
│   └── MainLayout.vue        # Layout admin (Sidebar + Topbar)
├── pages/
│   ├── auth/
│   │   ├── LoginPage.vue
│   │   ├── ForgotPasswordPage.vue
│   │   └── ResetPasswordPage.vue
│   ├── DashboardPage.vue
│   ├── ProfilePage.vue
│   └── ErrorNotFound.vue
├── router/
│   ├── index.js              # Router + Auth Guards
│   └── routes.js             # Definição de rotas
├── services/
│   ├── api.js                # Axios + Interceptors (401/403/422/500)
│   ├── auth.js               # AuthService (login, logout, etc)
│   └── organization.js       # OrganizationService
└── stores/
    ├── auth.js               # AuthStore (user, roles, permissions)
    ├── organization.js       # OrganizationStore (tenant)
    └── ui.js                 # UIStore (loading, sidebar, notify)
```

## Executar

### Com Docker

```bash
docker compose up admin
```

### Sem Docker

```bash
cd admin
npm install
quasar dev
```

Acesse: **http://localhost:9700**

## Variáveis de Ambiente

```bash
cp .env.example .env
```

| Variável | Descrição | Default |
|----------|-----------|---------|
| API_URL | URL da API Laravel | http://localhost:8080 |

## Stores (Pinia)

### AuthStore

```javascript
import { useAuthStore } from 'src/stores/auth'

const authStore = useAuthStore()

// State
authStore.user           // Usuário logado
authStore.roles          // Roles globais
authStore.permissions    // Permissões
authStore.isAuthenticated // Getter: está autenticado?

// Actions
await authStore.login({ email, password })
await authStore.logout()
await authStore.fetchUser()
authStore.hasRole('admin')
authStore.hasPermission('users.create')
```

### OrganizationStore

```javascript
import { useOrganizationStore } from 'src/stores/organization'

const orgStore = useOrganizationStore()

// State
orgStore.current         // Organização selecionada
orgStore.list            // Lista de organizações
orgStore.currentId       // ID da organização atual

// Actions
await orgStore.fetchOrganizations()
await orgStore.switchOrganization(orgId)
```

### UIStore

```javascript
import { useUIStore } from 'src/stores/ui'

const uiStore = useUIStore()

// State
uiStore.isLoading        // Loading global ativo?
uiStore.sidebarOpen      // Sidebar aberto?

// Actions
uiStore.notifySuccess('Sucesso!')
uiStore.notifyError('Erro!')
uiStore.toggleSidebar()
```

## Fluxo de Login

1. Usuário acessa `/` → Guard redireciona para `/login`
2. Login via Laravel Sanctum (cookie-based)
3. AuthStore busca `/api/user` e armazena dados
4. Redireciona para Dashboard
5. Interceptor trata 401 (sessão expirada) automaticamente

## Interceptors HTTP

O arquivo `src/services/api.js` configura interceptors para:

- **401** - Sessão expirada → limpa auth e redireciona para login
- **403** - Sem permissão → exibe notificação
- **422** - Validação → exibe primeiro erro
- **500+** - Erro interno → exibe notificação genérica

## Proteger Rotas

```javascript
// src/router/routes.js

// Rota que requer autenticação
{
  path: '/admin',
  meta: { requiresAuth: true },
  component: () => import('pages/AdminPage.vue')
}

// Rota que requer role específica
{
  path: '/users',
  meta: { requiresAuth: true, role: 'admin' },
  component: () => import('pages/UsersPage.vue')
}

// Rota que requer permissão específica
{
  path: '/reports',
  meta: { requiresAuth: true, permission: 'reports.view' },
  component: () => import('pages/ReportsPage.vue')
}
```

## Adicionar Menu

Edite `src/layouts/MainLayout.vue`:

```javascript
const menuItems = [
  {
    title: 'Dashboard',
    icon: 'dashboard',
    to: { name: 'dashboard' },
  },
  {
    title: 'Usuários',
    icon: 'people',
    children: [
      { title: 'Listar', icon: 'list', to: { name: 'users' } },
      { title: 'Criar', icon: 'add', to: { name: 'users-create' } },
    ],
  },
]
```

## Comandos

```bash
# Desenvolvimento
npm run dev

# Lint
npm run lint

# Formatar código
npm run format

# Build produção
quasar build
```

Os arquivos de build ficam em `dist/spa/`.
