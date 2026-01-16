import { api } from './api'

export const authService = {
  async getCsrfCookie() {
    await api.get('/sanctum/csrf-cookie')
  },

  async login(credentials) {
    await this.getCsrfCookie()
    return api.post('/api/login', credentials)
  },

  async logout() {
    return api.post('/api/logout')
  },

  async getUser() {
    return api.get('/api/user')
  },

  async forgotPassword(email) {
    await this.getCsrfCookie()
    return api.post('/forgot-password', { email })
  },

  async resetPassword(data) {
    await this.getCsrfCookie()
    return api.post('/reset-password', data)
  },
}
