import { defineStore } from 'pinia';
import client from '../api/client';

export const useDashboardStore = defineStore('superAdminDashboard', {
  state: () => ({
    stats: null,
    loading: false,
  }),
  actions: {
    async fetchStats() {
      this.loading = true;
      try {
        const response = await client.get('/stats');
        this.stats = response.data.data;
      } finally {
        this.loading = false;
      }
    },
  },
});
