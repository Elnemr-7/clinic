import { defineStore } from 'pinia';
import client from '../api/client';

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    settings: null,
    branches: [],
    drugs: [],
    templates: [],
    labs: [],
    receptionists: [],
    loading: false,
  }),
  actions: {
    async fetchAll() {
      this.loading = true;
      try {
        const [settingsRes, branchesRes, drugsRes, templatesRes, labsRes, receptionistsRes] = await Promise.all([
          client.get('/settings'),
          client.get('/settings/branches'),
          client.get('/settings/drugs'),
          client.get('/settings/templates'),
          client.get('/settings/labs'),
          client.get('/settings/receptionists'),
        ]);

        this.settings = settingsRes.data.data;
        this.branches = branchesRes.data.data;
        this.drugs = drugsRes.data.data;
        this.templates = templatesRes.data.data;
        this.labs = labsRes.data.data;
        this.receptionists = receptionistsRes.data.data;
      } finally {
        this.loading = false;
      }
    },
    async fetchBranches() {
      try {
        const res = await client.get('/settings/branches');
        this.branches = res.data.data;
        return this.branches;
      } catch (e) {
        return [];
      }
    },
    async updateVitals(vitals) {
      await client.post('/settings/vitals', { vitals_config: vitals });
      await this.fetchAll();
    },
    async updatePrintMargins(margins) {
      await client.post('/settings/print-margins', { print_settings: margins });
      await this.fetchAll();
    },
    async saveDrug(drug) {
      await client.post('/settings/drugs', drug);
      const res = await client.get('/settings/drugs');
      this.drugs = res.data.data;
    },
    async saveTemplate(template) {
      await client.post('/settings/templates', template);
      const res = await client.get('/settings/templates');
      this.templates = res.data.data;
    },
    async deleteTemplate(id) {
      await client.delete(`/settings/templates/${id}`);
      const res = await client.get('/settings/templates');
      this.templates = res.data.data;
    },
    async saveLab(lab) {
      await client.post('/settings/labs', lab);
      const res = await client.get('/settings/labs');
      this.labs = res.data.data;
    },
    async saveBranch(branch) {
      await client.post('/settings/branches', branch);
      const res = await client.get('/settings/branches');
      this.branches = res.data.data;
    },
    async saveShift(shift) {
      await client.post('/settings/shifts', shift);
      const res = await client.get('/settings/branches');
      this.branches = res.data.data;
    },
    async deleteShift(id) {
      await client.delete(`/settings/shifts/${id}`);
      const res = await client.get('/settings/branches');
      this.branches = res.data.data;
    },
    async saveDayOff(payload) {
      const res = await client.post('/settings/days-off', payload);
      return res.data;
    },
    async saveReceptionist(receptionist) {
      await client.post('/settings/receptionists', receptionist);
      const res = await client.get('/settings/receptionists');
      this.receptionists = res.data.data;
    },
    async requestWhatsAppPairing(phone) {
      const res = await client.get('/settings/whatsapp-qr', { params: { phone } });
      return res.data.data;
    },
    async requestTopup(payload) {
      const res = await client.post('/settings/topup', payload);
      return res.data;
    },
    async saveWebsiteSettings(payload) {
      const res = await client.post('/settings/website', payload);
      await this.fetchAll();
      return res.data;
    },
    async uploadImage(file, type = 'og_image') {
      const formData = new FormData();
      formData.append('image', file);
      formData.append('type', type);
      const res = await client.post('/settings/upload-image', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      return res.data;
    },
  },
});
