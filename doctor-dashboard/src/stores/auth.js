import { defineStore } from 'pinia';
import client from '../api/client';

export const useAuthStore = defineStore('clinicAuth', {
  state: () => ({
    token: sessionStorage.getItem('clinic_token') || localStorage.getItem('clinic_token') || null,
    user: null,
    tenant: null,
    clinic: null,
    socketToken: localStorage.getItem('socket_token') || null,
    isImpersonating: sessionStorage.getItem('is_impersonating') === 'true' || false,
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
    setImpersonationToken(token) {
      this.token = token;
      this.isImpersonating = true;
      sessionStorage.setItem('clinic_token', token);
      localStorage.setItem('clinic_token', token);
      sessionStorage.setItem('is_impersonating', 'true');
    },
    async login(email, password) {
      this.loading = true;
      this.error = null;
      try {
        const response = await client.post('/login', { email, password }, { skipGlobalError: true });
        const data = response.data.data;
        this.token = data.token;
        this.socketToken = data.socket_token || null;
        this.user = data.user;
        this.tenant = data.tenant;
        this.clinic = data.clinic;
        localStorage.setItem('clinic_token', this.token);
        if (this.socketToken) localStorage.setItem('socket_token', this.socketToken);
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
        if (data.socket_token) {
          this.socketToken = data.socket_token;
          localStorage.setItem('socket_token', data.socket_token);
        }
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
      this.socketToken = null;
      this.isImpersonating = false;
      localStorage.removeItem('clinic_token');
      localStorage.removeItem('socket_token');
      sessionStorage.removeItem('clinic_token');
      sessionStorage.removeItem('is_impersonating');
    },
  },
});
