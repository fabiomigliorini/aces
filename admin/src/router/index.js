import { defineRouter } from '#q-app/wrappers'
import {
  createRouter,
  createMemoryHistory,
  createWebHistory,
  createWebHashHistory,
} from 'vue-router'
import routes from './routes'
import { useAuthStore } from 'src/stores/auth'

export default defineRouter(function (/* { store, ssrContext } */) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : process.env.VUE_ROUTER_MODE === 'history'
      ? createWebHistory
      : createWebHashHistory

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createHistory(process.env.VUE_ROUTER_BASE),
  })

  // Navigation Guard
  Router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore()

    // Verificar autenticação uma vez
    if (!authStore.initialized) {
      await authStore.checkAuth()
    }

    const requiresAuth = to.matched.some((record) => record.meta.requiresAuth)
    const isGuestRoute = to.matched.some((record) => record.meta.guest)

    // Rota requer autenticação
    if (requiresAuth && !authStore.isAuthenticated) {
      return next({
        name: 'login',
        query: { redirect: to.fullPath },
      })
    }

    // Rota de guest (login, etc) - redirecionar se já autenticado
    if (isGuestRoute && authStore.isAuthenticated) {
      return next({ name: 'dashboard' })
    }

    // Verificar permissão específica
    if (to.meta.permission) {
      if (!authStore.hasPermission(to.meta.permission)) {
        return next({ name: 'dashboard' })
      }
    }

    // Verificar role específica
    if (to.meta.role) {
      if (!authStore.hasRole(to.meta.role)) {
        return next({ name: 'dashboard' })
      }
    }

    next()
  })

  return Router
})
