import { defineStore } from 'pinia';
import client from '../api/client';

export const useLeadsStore = defineStore('superAdminLeads', {
  state: () => ({
    leads: [],
    loading: false,
  }),
  actions: {
    async fetchLeads() {
      this.loading = true;
      try {
        const response = await client.get('/leads');
        this.leads = response.data.data.data || response.data.data;
      } finally {
        this.loading = false;
      }
    },
    async convertLead(id, data) {
      const response = await client.post(`/leads/${id}/convert`, data);
      await this.fetchLeads();
      return response.data;
    },
  },
});
