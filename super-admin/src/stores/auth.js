import { defineStore } from 'pinia';
import client from '../api/client';

export const useAuthStore = defineStore('superAdminAuth', {
  state: () => ({
    token: localStorage.getItem('super_admin_token') || null,
    admin: null,
    loading: false,
    error: null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
  },
  actions: {
    async login(email, password) {
      this.loading = true;
      this.error = null;
      try {
        const response = await client.post('/login', { email, password });
        this.token = response.data.data.token;
        this.admin = response.data.data.admin;
        localStorage.setItem('super_admin_token', this.token);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'فشل تسجيل الدخول';
        return false;
      } finally {
        this.loading = false;
      }
    },
    async fetchProfile() {
      if (!this.token) return;
      try {
        const response = await client.get('/me');
        this.admin = response.data.data;
      } catch (err) {
        this.logout();
      }
    },
    logout() {
      try {
        client.post('/logout');
      } catch (e) {}
      this.token = null;
      this.admin = null;
      localStorage.removeItem('super_admin_token');
    },
  },
});
