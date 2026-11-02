import { defineStore } from 'pinia';
import client from '../api/client';

export const usePlansStore = defineStore('superAdminPlans', {
  state: () => ({
    plans: [],
    loading: false,
  }),
  actions: {
    async fetchPlans() {
      this.loading = true;
      try {
        const response = await client.get('/plans');
        this.plans = response.data.data;
      } finally {
        this.loading = false;
      }
    },
    async savePlan(planData) {
      if (planData.id) {
        await client.put(`/plans/${planData.id}`, planData);
      } else {
        await client.post('/plans', planData);
      }
      await this.fetchPlans();
    },
    async deletePlan(id) {
      await client.delete(`/plans/${id}`);
      await this.fetchPlans();
    },
  },
});
