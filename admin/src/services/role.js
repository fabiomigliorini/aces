import { api } from './api'

export const roleService = {
  async list(params = {}) {
    return api.get('/api/roles', { params })
  },

  async get(id) {
    return api.get(`/api/roles/${id}`)
  },

  async create(data) {
    return api.post('/api/roles', data)
  },

  async update(id, data) {
    return api.put(`/api/roles/${id}`, data)
  },

  async delete(id) {
    return api.delete(`/api/roles/${id}`)
  },

  async permissions() {
    return api.get('/api/roles/permissions')
  },
}
