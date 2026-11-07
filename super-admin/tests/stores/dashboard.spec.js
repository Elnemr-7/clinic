import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useDashboardStore } from '../../src/stores/dashboard';
import client from '../../src/api/client';

describe('Super Admin Dashboard Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetchStats retrieves platform KPIs and updates state', async () => {
    const store = useDashboardStore();

    vi.spyOn(client, 'get').mockResolvedValue({
      data: {
        success: true,
        data: {
          total_doctors: 15,
          active_clinics: 28,
          bookings_today: 142,
          monthly_revenue_egp: 75000,
          pending_leads: 4,
        },
      },
    });

    await store.fetchStats();

    expect(store.stats.total_doctors).toBe(15);
    expect(store.stats.bookings_today).toBe(142);
    expect(store.stats.monthly_revenue_egp).toBe(75000);
    expect(store.loading).toBe(false);
  });
});
