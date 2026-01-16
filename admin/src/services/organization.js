import { api } from './api'

export const organizationService = {
  async list(params = {}) {
    return api.get('/api/organizations', { params })
  },

  async get(id) {
    return api.get(`/api/organizations/${id}`)
  },

  async create(data) {
    return api.post('/api/organizations', data)
  },

  async update(id, data) {
    return api.put(`/api/organizations/${id}`, data)
  },

  async delete(id) {
    return api.delete(`/api/organizations/${id}`)
  },
}
