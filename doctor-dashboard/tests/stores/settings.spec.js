import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useSettingsStore } from '../../src/stores/settings';
import client from '../../src/api/client';

describe('Settings Store (Doctor Dashboard)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetchSettings retrieves tenant details, vitals, margins, and website mode', async () => {
    const store = useSettingsStore();

    vi.spyOn(client, 'get').mockResolvedValue({
      data: {
        success: true,
        data: {
          tenant: { id: 1, name: 'د. أشرف' },
          vitals_config: [{ key: 'bp', label: 'ضغط الدم', enabled: true }],
          print_settings: { margin_top: 30, paper_size: 'A5' },
          website_mode: 1,
          whatsapp_status: { status: 'connected' },
        },
      },
    });

    await store.fetchSettings();

    expect(store.tenant.name).toBe('د. أشرف');
    expect(store.vitalsConfig).toHaveLength(1);
    expect(store.printSettings.paper_size).toBe('A5');
    expect(store.websiteMode).toBe(1);
    expect(store.whatsappStatus.status).toBe('connected');
  });

  it('updateVitals dispatches payload and updates store state', async () => {
    const store = useSettingsStore();

    vi.spyOn(client, 'post').mockResolvedValue({
      data: { success: true, data: [{ key: 'sugar', label: 'السكر', enabled: true }] },
    });

    await store.updateVitals([{ key: 'sugar', label: 'السكر', enabled: true }]);

    expect(client.post).toHaveBeenCalledWith('/settings/vitals', {
      vitals_config: [{ key: 'sugar', label: 'السكر', enabled: true }],
    });
    expect(store.vitalsConfig[0].key).toBe('sugar');
  });

  it('saveDrug posts drug and refreshes drug list', async () => {
    const store = useSettingsStore();

    vi.spyOn(client, 'post').mockResolvedValue({ data: { success: true } });
    vi.spyOn(client, 'get').mockResolvedValue({
      data: { success: true, data: [{ id: 1, drug_name: 'Amoxil' }] },
    });

    await store.saveDrug({ drug_name: 'Amoxil', form_type: 'كبسولات' });

    expect(client.post).toHaveBeenCalledWith('/settings/drugs', {
      drug_name: 'Amoxil',
      form_type: 'كبسولات',
    });
    expect(store.drugs).toHaveLength(1);
  });

  it('saveDayOff posts emergency clinic closure', async () => {
    const store = useSettingsStore();

    vi.spyOn(client, 'post').mockResolvedValue({ data: { success: true } });

    const payload = {
      clinic_id: 101,
      date: '2026-11-10',
      reason_ar' => 'ظرف طارئ',
      action: 'cancel_and_notify',
    };

    const res = await store.saveDayOff(payload);
    expect(res.success).toBe(true);
    expect(client.post).toHaveBeenCalledWith('/settings/days-off', payload);
  });
});
