import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { Notify } from 'quasar'

export const useUIStore = defineStore('ui', () => {
  // State
  const loadingCount = ref(0)
  const sidebarOpen = ref(true)
  const sidebarMini = ref(false)

  // Getters
  const isLoading = computed(() => loadingCount.value > 0)

  // Actions
  function startLoading() {
    loadingCount.value++
  }

  function stopLoading() {
    if (loadingCount.value > 0) {
      loadingCount.value--
    }
  }

  function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
  }

  function toggleSidebarMini() {
    sidebarMini.value = !sidebarMini.value
  }

  function notify(options) {
    const defaults = {
      position: 'top-right',
      timeout: 3000,
    }
    Notify.create({ ...defaults, ...options })
  }

  function notifySuccess(message) {
    notify({
      type: 'positive',
      message,
      icon: 'check_circle',
    })
  }

  function notifyError(message) {
    notify({
      type: 'negative',
      message,
      icon: 'error',
    })
  }

  function notifyWarning(message) {
    notify({
      type: 'warning',
      message,
      icon: 'warning',
    })
  }

  function notifyInfo(message) {
    notify({
      type: 'info',
      message,
      icon: 'info',
    })
  }

  function $reset() {
    loadingCount.value = 0
    sidebarOpen.value = true
    sidebarMini.value = false
  }

  return {
    // State
    loadingCount,
    sidebarOpen,
    sidebarMini,

    // Getters
    isLoading,

    // Actions
    startLoading,
    stopLoading,
    toggleSidebar,
    toggleSidebarMini,
    notify,
    notifySuccess,
    notifyError,
    notifyWarning,
    notifyInfo,
    $reset,
  }
})
