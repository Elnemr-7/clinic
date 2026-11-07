import { defineStore } from 'pinia';
import client from '../api/client';

export const useReceptionQueueStore = defineStore('receptionQueue', {
  state: () => ({
    appointments: [],
    loading: false,
    selectedClinicId: null,
  }),
  actions: {
    async fetchQueue(clinicId = null, date = null, shiftId = null, silent = false) {
      if (!silent) this.loading = true;
      try {
        const params = {
          clinic_id: clinicId || this.selectedClinicId,
          date: date || new Date().toISOString().substring(0, 10),
        };
        if (shiftId) params.shift_id = shiftId;

        const response = await client.get('/reception/queue', { params, skipGlobalError: silent });
        this.appointments = response.data.data;
      } finally {
        if (!silent) this.loading = false;
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
    async updateStatus(appointmentId, status, markPaid = false, refundMoney = false) {
      const payload = {};
      if (status !== null && status !== undefined) payload.status = status;
      if (markPaid) payload.mark_paid = true;
      if (refundMoney !== undefined && refundMoney !== null) payload.refund_money = Boolean(refundMoney);

      const response = await client.post(`/reception/status/${appointmentId}`, payload);
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
