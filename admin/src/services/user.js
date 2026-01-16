import { api } from './api'

export const userService = {
  async list(params = {}) {
    return api.get('/api/users', { params })
  },

  async get(id) {
    return api.get(`/api/users/${id}`)
  },

  async create(data) {
    return api.post('/api/users', data)
  },

  async update(id, data) {
    return api.put(`/api/users/${id}`, data)
  },

  async delete(id) {
    return api.delete(`/api/users/${id}`)
  },

  // Tenant associations
  async getTenants(userId) {
    return api.get(`/api/users/${userId}/tenants`)
  },

  async attachTenant(userId, data) {
    return api.post(`/api/users/${userId}/tenants`, data)
  },

  async updateTenant(userId, tenantId, data) {
    return api.put(`/api/users/${userId}/tenants/${tenantId}`, data)
  },

  async detachTenant(userId, tenantId) {
    return api.delete(`/api/users/${userId}/tenants/${tenantId}`)
  },
}
