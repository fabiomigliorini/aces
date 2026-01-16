import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from 'src/services/auth'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const roles = ref([])
  const permissions = ref([])
  const initialized = ref(false)

  // Getters
  const isAuthenticated = computed(() => !!user.value)

  const hasRole = computed(() => (role) => {
    return roles.value.includes(role)
  })

  const hasPermission = computed(() => (permission) => {
    return permissions.value.includes(permission)
  })

  const isSuperAdmin = computed(() => {
    return roles.value.includes('super-admin')
  })

  // Actions
  async function login(credentials) {
    const response = await authService.login(credentials)
    await fetchUser()
    return response
  }

  async function logout() {
    try {
      await authService.logout()
    } finally {
      clearAuth()
    }
  }

  async function fetchUser() {
    try {
      const response = await authService.getUser()
      user.value = response.data.user || response.data
      roles.value = response.data.roles || []
      permissions.value = response.data.permissions || []
      initialized.value = true
      return response.data
    } catch (error) {
      clearAuth()
      throw error
    }
  }

  async function checkAuth() {
    if (initialized.value) {
      return isAuthenticated.value
    }

    try {
      await fetchUser()
      return true
    } catch {
      return false
    }
  }

  function clearAuth() {
    user.value = null
    roles.value = []
    permissions.value = []
    initialized.value = true
  }

  function $reset() {
    user.value = null
    roles.value = []
    permissions.value = []
    initialized.value = false
  }

  return {
    // State
    user,
    roles,
    permissions,
    initialized,

    // Getters
    isAuthenticated,
    hasRole,
    hasPermission,
    isSuperAdmin,

    // Actions
    login,
    logout,
    fetchUser,
    checkAuth,
    clearAuth,
    $reset,
  }
})
