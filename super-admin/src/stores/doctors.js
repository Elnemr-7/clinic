import { defineStore } from 'pinia';
import client from '../api/client';

export const useDoctorsStore = defineStore('superAdminDoctors', {
  state: () => ({
    doctors: [],
    loading: false,
    selectedDoctor: null,
  }),
  actions: {
    async fetchDoctors() {
      this.loading = true;
      try {
        const response = await client.get('/tenants');
        this.doctors = response.data.data.data || response.data.data;
      } finally {
        this.loading = false;
      }
    },
    async updateDoctor(id, data) {
      const response = await client.put(`/tenants/${id}`, data);
      await this.fetchDoctors();
      return response.data;
    },
    async updateDoctorWallet(id, data) {
      const response = await client.put(`/tenants/${id}/wallet`, data);
      await this.fetchDoctors();
      return response.data;
    },
    async impersonateDoctor(id) {
      const response = await client.post(`/tenants/${id}/impersonate`);
      return response.data.data;
    },
  },
});
