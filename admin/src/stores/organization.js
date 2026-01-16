import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { organizationService } from 'src/services/organization'

export const useOrganizationStore = defineStore('organization', () => {
  // State
  const current = ref(null)
  const list = ref([])
  const loading = ref(false)

  // Getters
  const currentId = computed(() => current.value?.id || null)
  const currentName = computed(() => current.value?.name || '')
  const hasOrganization = computed(() => !!current.value)

  const organizationOptions = computed(() => {
    return list.value.map((org) => ({
      label: org.name,
      value: org.id,
    }))
  })

  // Actions
  async function fetchOrganizations() {
    loading.value = true
    try {
      const response = await organizationService.list()
      list.value = response.data.data || response.data
      return list.value
    } finally {
      loading.value = false
    }
  }

  async function switchOrganization(organizationId) {
    loading.value = true
    try {
      await organizationService.switch(organizationId)
      const org = list.value.find((o) => o.id === organizationId)
      if (org) {
        current.value = org
      }
      return current.value
    } finally {
      loading.value = false
    }
  }

  function setCurrentOrganization(organization) {
    current.value = organization
  }

  function clearOrganization() {
    current.value = null
  }

  function $reset() {
    current.value = null
    list.value = []
    loading.value = false
  }

  return {
    // State
    current,
    list,
    loading,

    // Getters
    currentId,
    currentName,
    hasOrganization,
    organizationOptions,

    // Actions
    fetchOrganizations,
    switchOrganization,
    setCurrentOrganization,
    clearOrganization,
    $reset,
  }
})
