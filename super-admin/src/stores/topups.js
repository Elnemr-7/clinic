import { defineStore } from 'pinia';
import client from '../api/client';

export const useTopupsStore = defineStore('superAdminTopups', {
  state: () => ({
    topups: [],
    loading: false,
  }),
  actions: {
    async fetchTopups() {
      this.loading = true;
      try {
        const response = await client.get('/topups');
        this.topups = response.data.data.data || response.data.data;
      } finally {
        this.loading = false;
      }
    },
    async approveTopup(id) {
      await client.put(`/topups/${id}/approve`);
      await this.fetchTopups();
    },
    async rejectTopup(id) {
      await client.put(`/topups/${id}/reject`);
      await this.fetchTopups();
    },
  },
});
