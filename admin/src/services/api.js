import axios from 'axios'
import { useAuthStore } from 'src/stores/auth'
import { useUIStore } from 'src/stores/ui'
import { Notify } from 'quasar'

const api = axios.create({
  baseURL: process.env.API_URL || '',
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    const uiStore = useUIStore()
    uiStore.startLoading()
    return config
  },
  (error) => {
    const uiStore = useUIStore()
    uiStore.stopLoading()
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => {
    const uiStore = useUIStore()
    uiStore.stopLoading()
    return response
  },
  async (error) => {
    const uiStore = useUIStore()
    uiStore.stopLoading()

    const status = error.response?.status
    const message = error.response?.data?.message || 'Erro inesperado'

    // Sessão expirada ou não autenticado
    if (status === 401) {
      const authStore = useAuthStore()
      authStore.clearAuth()

      // Não redireciona se já estiver na página de login ou verificando auth
      const isLoginPage = window.location.pathname === '/login'
      const isAuthCheck = error.config?.url?.includes('/api/user')

      if (!isLoginPage && !isAuthCheck) {
        Notify.create({
          type: 'negative',
          message: 'Sessão expirada. Faça login novamente.',
          position: 'top',
        })
        window.location.href = '/login'
      }

      return Promise.reject(error)
    }

    // Sem permissão
    if (status === 403) {
      Notify.create({
        type: 'warning',
        message: 'Você não tem permissão para esta ação.',
        position: 'top',
      })
      return Promise.reject(error)
    }

    // Validação
    if (status === 422) {
      const errors = error.response?.data?.errors
      if (errors) {
        const firstError = Object.values(errors)[0]
        Notify.create({
          type: 'negative',
          message: Array.isArray(firstError) ? firstError[0] : firstError,
          position: 'top',
        })
      }
      return Promise.reject(error)
    }

    // Erro interno
    if (status >= 500) {
      Notify.create({
        type: 'negative',
        message: 'Erro interno do servidor. Tente novamente.',
        position: 'top',
      })
      return Promise.reject(error)
    }

    // Outros erros
    Notify.create({
      type: 'negative',
      message: message,
      position: 'top',
    })

    return Promise.reject(error)
  }
)

export { api }
