import { api } from './api'

export const tenantService = {
  async list(params = {}) {
    return api.get('/api/tenants', { params })
  },

  async get(id) {
    return api.get(`/api/tenants/${id}`)
  },

  async create(data) {
    return api.post('/api/tenants', data)
  },

  async update(id, data) {
    return api.put(`/api/tenants/${id}`, data)
  },

  async delete(id) {
    return api.delete(`/api/tenants/${id}`)
  },

  async current() {
    return api.get('/api/tenant/current')
  },
}
