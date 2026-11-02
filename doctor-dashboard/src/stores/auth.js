import { defineStore } from 'pinia';
import client from '../api/client';

export const useAuthStore = defineStore('clinicAuth', {
  state: () => ({
    token: localStorage.getItem('clinic_token') || null,
    user: null,
    tenant: null,
    clinic: null,
    isImpersonating: false,
    loading: false,
    error: null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    isDoctor: (state) => state.user?.role === 'doctor',
    isReceptionist: (state) => state.user?.role === 'receptionist',
  },
  actions: {
    setToken(token) {
      this.token = token;
      localStorage.setItem('clinic_token', token);
    },
    async login(email, password) {
      this.loading = true;
      this.error = null;
      try {
        const response = await client.post('/login', { email, password });
        const data = response.data.data;
        this.token = data.token;
        this.user = data.user;
        this.tenant = data.tenant;
        this.clinic = data.clinic;
        localStorage.setItem('clinic_token', this.token);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'بيانات الدخول غير صحيحة';
        return false;
      } finally {
        this.loading = false;
      }
    },
    async fetchProfile() {
      if (!this.token) return;
      try {
        const response = await client.get('/me');
        const data = response.data.data;
        this.user = data.user;
        this.tenant = data.tenant;
        this.clinic = data.clinic;
      } catch (err) {
        this.logout();
      }
    },
    logout() {
      try {
        client.post('/logout');
      } catch (e) {}
      this.token = null;
      this.user = null;
      this.tenant = null;
      this.clinic = null;
      this.isImpersonating = false;
      localStorage.removeItem('clinic_token');
    },
  },
});
