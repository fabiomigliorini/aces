import { api } from './api'

export const organizationService = {
  async list() {
    return api.get('/api/organizations')
  },

  async get(id) {
    return api.get(`/api/organizations/${id}`)
  },

  async switch(organizationId) {
    return api.post('/api/organizations/switch', {
      organization_id: organizationId,
    })
  },
}
