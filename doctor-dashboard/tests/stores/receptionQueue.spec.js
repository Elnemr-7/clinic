import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useReceptionQueueStore } from '../../src/stores/receptionQueue';
import client from '../../src/api/client';

describe('Reception Queue Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetchQueue populates appointment list correctly', async () => {
    const store = useReceptionQueueStore();

    const fakeList = [
      { id: 1, queue_number: 1, status: 'in_consultation' },
      { id: 2, queue_number: 2, status: 'arrived_waiting' },
    ];

    vi.spyOn(client, 'get').mockResolvedValue({
      data: { success: true, data: fakeList },
    });

    await store.fetchQueue(10, '2026-11-07');

    expect(store.appointments).toHaveLength(2);
    expect(store.appointments[0].queue_number).toBe(1);
    expect(store.loading).toBe(false);
  });

  it('updateStatus dispatches correct payload and refreshes queue', async () => {
    const store = useReceptionQueueStore();

    vi.spyOn(client, 'post').mockResolvedValue({
      data: { success: true, data: { id: 2, status: 'arrived_waiting' } },
    });
    vi.spyOn(client, 'get').mockResolvedValue({
      data: { success: true, data: [] },
    });

    await store.updateStatus(2, 'arrived_waiting', true, false);

    expect(client.post).toHaveBeenCalledWith('/reception/status/2', {
      status: 'arrived_waiting',
      mark_paid: true,
      refund_money: false,
    });
  });

  it('saveVitals updates patient biological vitals on arrival', async () => {
    const store = useReceptionQueueStore();

    vi.spyOn(client, 'post').mockResolvedValue({
      data: { success: true, data: { id: 2, vitals_data: { bp: '130/85', sugar: 110 } } },
    });
    vi.spyOn(client, 'get').mockResolvedValue({
      data: { success: true, data: [] },
    });

    const result = await store.saveVitals(2, { bp: '130/85', sugar: 110 });

    expect(result.success).toBe(true);
    expect(client.post).toHaveBeenCalledWith('/reception/vitals/2', {
      vitals_data: { bp: '130/85', sugar: 110 },
    });
  });
});
