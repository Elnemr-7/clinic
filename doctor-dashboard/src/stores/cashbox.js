import { defineStore } from 'pinia';
import client from '../api/client';

export const useCashboxStore = defineStore('cashbox', {
  state: () => ({
    summary: {
      total_income: 0,
      total_expense: 0,
      net_in_drawer: 0,
      transactions: [],
    },
    loading: false,
  }),
  actions: {
    async fetchSummary(clinicId = null, date = null) {
      this.loading = true;
      try {
        const params = {};
        if (clinicId) params.clinic_id = clinicId;
        if (date) params.date = date;

        const response = await client.get('/reception/cashbox', { params });
        this.summary = response.data.data;
      } finally {
        this.loading = false;
      }
    },
    async recordExpense(payload) {
      const response = await client.post('/reception/expense', payload);
      await this.fetchSummary(payload.clinic_id);
      return response.data;
    },
  },
});
