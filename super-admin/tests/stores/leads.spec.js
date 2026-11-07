import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useLeadsStore } from '../../src/stores/leads';
import client from '../../src/api/client';

describe('Super Admin Leads Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetchLeads populates inbound doctor demo requests list', async () => {
    const store = useLeadsStore();

    vi.spyOn(client, 'get').mockResolvedValue({
      data: {
        success: true,
        data: {
          data: [
            { id: 1, doctor_name: 'د. خالد زكي', phone: '01012341234', status: 'new' },
            { id: 2, doctor_name: 'د. مروة شاكر', phone: '01122334455', status: 'converted' },
          ],
        },
      },
    });

    await store.fetchLeads();

    expect(store.leads).toHaveLength(2);
    expect(store.leads[0].doctor_name).toBe('د. خالد زكي');
    expect(store.loading).toBe(false);
  });

  it('convertLead posts conversion payload and refreshes leads list', async () => {
    const store = useLeadsStore();

    vi.spyOn(client, 'post').mockResolvedValue({
      data: { success: true, message: 'Lead converted' },
    });
    vi.spyOn(client, 'get').mockResolvedValue({
      data: { success: true, data: { data: [] } },
    });

    const res = await store.convertLead(1, {
      plan_id: 2,
      subdomain: 'dr-khaled-zaki',
    });

    expect(res.success).toBe(true);
    expect(client.post).toHaveBeenCalledWith('/leads/1/convert', {
      plan_id: 2,
      subdomain: 'dr-khaled-zaki',
    });
  });
});
