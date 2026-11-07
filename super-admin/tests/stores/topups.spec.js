import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useTopupsStore } from '../../src/stores/topups';
import client from '../../src/api/client';

describe('Super Admin Topups Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetchTopups retrieves balance recharge requests', async () => {
    const store = useTopupsStore();

    vi.spyOn(client, 'get').mockResolvedValue({
      data: {
        success: true,
        data: {
          data: [
            { id: 10, amount_egp: 500, package_type: 'whatsapp', status: 'pending' },
          ],
        },
      },
    });

    await store.fetchTopups();

    expect(store.topups).toHaveLength(1);
    expect(store.topups[0].package_type).toBe('whatsapp');
  });

  it('approveTopup calls approve endpoint and refreshes list', async () => {
    const store = useTopupsStore();

    vi.spyOn(client, 'put').mockResolvedValue({
      data: { success: true, message: 'Topup approved' },
    });
    vi.spyOn(client, 'get').mockResolvedValue({
      data: { success: true, data: { data: [] } },
    });

    await store.approveTopup(10);

    expect(client.put).toHaveBeenCalledWith('/topups/10/approve');
    expect(client.get).toHaveBeenCalledWith('/topups');
  });

  it('rejectTopup calls reject endpoint and refreshes list', async () => {
    const store = useTopupsStore();

    vi.spyOn(client, 'put').mockResolvedValue({
      data: { success: true, message: 'Topup rejected' },
    });
    vi.spyOn(client, 'get').mockResolvedValue({
      data: { success: true, data: { data: [] } },
    });

    await store.rejectTopup(10);

    expect(client.put).toHaveBeenCalledWith('/topups/10/reject');
    expect(client.get).toHaveBeenCalledWith('/topups');
  });
});
