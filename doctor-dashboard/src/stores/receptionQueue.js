import { defineStore } from 'pinia';
import client from '../api/client';

export const useReceptionQueueStore = defineStore('receptionQueue', {
  state: () => ({
    appointments: [],
    loading: false,
    selectedClinicId: null,
  }),
  actions: {
    async fetchQueue(clinicId = null, date = null, shiftId = null) {
      this.loading = true;
      try {
        const params = {
          clinic_id: clinicId || this.selectedClinicId,
          date: date || new Date().toISOString().substring(0, 10),
        };
        if (shiftId) params.shift_id = shiftId;

        const response = await client.get('/reception/queue', { params });
        this.appointments = response.data.data;
      } finally {
        this.loading = false;
      }
    },
    async quickBook(payload) {
      const response = await client.post('/reception/quick-book', payload);
      await this.fetchQueue();
      return response.data;
    },
    async checkFollowup(phone, clinicId) {
      const response = await client.get('/reception/check-followup', {
        params: { phone, clinic_id: clinicId || this.selectedClinicId },
      });
      return response.data.data;
    },
    async updateStatus(appointmentId, status, markPaid = false) {
      const response = await client.post(`/reception/status/${appointmentId}`, {
        status,
        mark_paid: markPaid,
      });
      await this.fetchQueue();
      return response.data;
    },
    async collectExtraServices(appointmentId) {
      const response = await client.post(`/reception/collect-extra/${appointmentId}`);
      await this.fetchQueue();
      return response.data;
    },
    async saveVitals(appointmentId, vitalsData) {
      const response = await client.post(`/reception/vitals/${appointmentId}`, {
        vitals_data: vitalsData,
      });
      await this.fetchQueue();
      return response.data;
    },
  },
});
