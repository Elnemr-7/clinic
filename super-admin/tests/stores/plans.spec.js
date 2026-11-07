import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { usePlansStore } from '../../src/stores/plans';
import client from '../../src/api/client';

describe('Super Admin Plans Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetchPlans retrieves all subscription tiers', async () => {
    const store = usePlansStore();

    vi.spyOn(client, 'get').mockResolvedValue({
      data: {
        success: true,
        data: [
          { id: 1, name_ar: 'الخطة الفضية', price_monthly: 400 },
          { id: 2, name_ar: 'الخطة الذهبية', price_monthly: 800 },
        ],
      },
    });

    await store.fetchPlans();

    expect(store.plans).toHaveLength(2);
    expect(store.plans[0].name_ar).toBe('الخطة الفضية');
  });

  it('savePlan creates or updates plan and refreshes store list', async () => {
    const store = usePlansStore();

    vi.spyOn(client, 'post').mockResolvedValue({ data: { success: true } });
    vi.spyOn(client, 'get').mockResolvedValue({ data: { success: true, data: [] } });

    await store.savePlan({
      name_ar' => 'باقة المشافي',
      price_monthly: 1500,
      max_clinics: 10,
    });

    expect(client.post).toHaveBeenCalledWith('/plans', expect.any(Object));
  });

  it('deletePlan removes plan from backend', async () => {
    const store = usePlansStore();

    vi.spyOn(client, 'delete').mockResolvedValue({ data: { success: true } });
    vi.spyOn(client, 'get').mockResolvedValue({ data: { success: true, data: [] } });

    await store.deletePlan(1);

    expect(client.delete).toHaveBeenCalledWith('/plans/1');
  });
});
