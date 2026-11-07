import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useDoctorsStore } from '../../src/stores/doctors';
import client from '../../src/api/client';

describe('Super Admin Doctors Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetchDoctors populates doctor list', async () => {
    const store = useDoctorsStore();

    vi.spyOn(client, 'get').mockResolvedValue({
      data: {
        success: true,
        data: {
          data: [
            { id: 1, name: 'د. محمود بدوي', subdomain: 'dr-badawi' },
            { id: 2, name: 'د. إيمان سالم', subdomain: 'dr-eman' },
          ],
        },
      },
    });

    await store.fetchDoctors();

    expect(store.doctors).toHaveLength(2);
    expect(store.doctors[0].name).toBe('د. محمود بدوي');
    expect(store.loading).toBe(false);
  });

  it('updateDoctorWallet updates limits and balances', async () => {
    const store = useDoctorsStore();

    vi.spyOn(client, 'put').mockResolvedValue({
      data: { success: true, message: 'Wallet updated' },
    });
    vi.spyOn(client, 'get').mockResolvedValue({
      data: { success: true, data: [] },
    });

    const res = await store.updateDoctorWallet(1, {
      whatsapp_balance: 2000,
      sms_balance: 500,
    });

    expect(res.success).toBe(true);
    expect(client.put).toHaveBeenCalledWith('/tenants/1/wallet', {
      whatsapp_balance: 2000,
      sms_balance: 500,
    });
  });

  it('impersonateDoctor requests tech support token', async () => {
    const store = useDoctorsStore();

    vi.spyOn(client, 'post').mockResolvedValue({
      data: {
        success: true,
        data: {
          token: 'impersonate_support_token_xyz',
          redirect_url: '/doctor/workspace',
        },
      },
    });

    const result = await store.impersonateDoctor(1);

    expect(result.token).toBe('impersonate_support_token_xyz');
    expect(client.post).toHaveBeenCalledWith('/tenants/1/impersonate');
  });
});
