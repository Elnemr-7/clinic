import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useSettingsStore } from '../../src/stores/settings';
import client from '../../src/api/client';

describe('Settings Store (Doctor Dashboard)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetchAll retrieves all clinic settings, branches, and resources', async () => {
    const store = useSettingsStore();

    vi.spyOn(client, 'get').mockImplementation((url) => {
      if (url === '/settings') {
        return Promise.resolve({
          data: {
            data: {
              tenant: { id: 1, name: 'د. أشرف' },
              vitals_config: [{ key: 'bp', label: 'ضغط الدم', enabled: true }],
            },
          },
        });
      }
      return Promise.resolve({ data: { data: [] } });
    });

    await store.fetchAll();

    expect(store.settings.tenant.name).toBe('د. أشرف');
    expect(store.settings.vitals_config).toHaveLength(1);
    expect(store.loading).toBe(false);
  });

  it('updateVitals dispatches payload and reloads settings', async () => {
    const store = useSettingsStore();

    vi.spyOn(client, 'post').mockResolvedValue({ data: { success: true } });
    vi.spyOn(client, 'get').mockResolvedValue({ data: { data: {} } });

    await store.updateVitals([{ key: 'sugar', label: 'السكر', enabled: true }]);

    expect(client.post).toHaveBeenCalledWith('/settings/vitals', {
      vitals_config: [{ key: 'sugar', label: 'السكر', enabled: true }],
    });
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
      reason_ar: 'ظرف طارئ',
      action: 'cancel_and_notify',
    };

    const res = await store.saveDayOff(payload);
    expect(res.success).toBe(true);
    expect(client.post).toHaveBeenCalledWith('/settings/days-off', payload);
  });
});
