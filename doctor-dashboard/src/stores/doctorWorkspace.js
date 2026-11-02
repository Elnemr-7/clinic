import { defineStore } from 'pinia';
import client from '../api/client';

export const useDoctorWorkspaceStore = defineStore('doctorWorkspace', {
  state: () => ({
    currentPatient: null,
    previousVitals: null,
    counts: {
      waiting: 0,
      completed: 0,
      postponed: 0,
    },
    pastVisits: [],
    loading: false,
    selectedClinicId: null,
  }),
  actions: {
    async fetchCurrentPatient(clinicId = null) {
      this.loading = true;
      try {
        const params = {};
        if (clinicId || this.selectedClinicId) {
          params.clinic_id = clinicId || this.selectedClinicId;
        }
        const response = await client.get('/doctor/current-patient', { params });
        const data = response.data.data;
        this.currentPatient = data.appointment;
        this.previousVitals = data.previous_vitals;
        this.counts = data.counts;
      } finally {
        this.loading = false;
      }
    },
    async savePrescription(appointmentId, payload) {
      const response = await client.post(`/doctor/prescription/${appointmentId}`, payload);
      await this.fetchCurrentPatient();
      return response.data;
    },
    async saveLabs(appointmentId, payload) {
      const response = await client.post(`/doctor/labs/${appointmentId}`, payload);
      await this.fetchCurrentPatient();
      return response.data;
    },
    async saveExtraServices(appointmentId, payload) {
      const response = await client.post(`/doctor/services-and-followup/${appointmentId}`, payload);
      await this.fetchCurrentPatient();
      return response.data;
    },
    async completeAndNext(clinicId = null) {
      this.loading = true;
      try {
        const response = await client.post('/doctor/next-patient', {
          clinic_id: clinicId || this.selectedClinicId,
        });
        await this.fetchCurrentPatient();
        return response.data;
      } finally {
        this.loading = false;
      }
    },
    async fetchPatientHistory(patientId) {
      const response = await client.get(`/doctor/patient-history/${patientId}`);
      this.pastVisits = response.data.data.visits || [];
      return response.data.data;
    },
    async uploadAttachment(patientId, formData) {
      const response = await client.post(`/doctor/attachments/${patientId}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      return response.data;
    },
    async searchDrugs(query) {
      const response = await client.get('/doctor/drugs/autocomplete', { params: { q: query } });
      return response.data.data;
    },
    async searchLabs(query) {
      const response = await client.get('/doctor/labs/autocomplete', { params: { q: query } });
      return response.data.data;
    },
  },
});
