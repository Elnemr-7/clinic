import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useCashboxStore } from '../../src/stores/cashbox';
import client from '../../src/api/client';

describe('Cashbox Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetchSummary calculates total income, expenses, and net drawer balance', async () => {
    const store = useCashboxStore();

    vi.spyOn(client, 'get').mockResolvedValue({
      data: {
        success: true,
        data: {
          total_income: 1200.0,
          total_expense: 350.0,
          net_in_drawer: 850.0,
          transactions: [
            { id: 1, type: 'income', amount: 300 },
            { id: 2, type: 'expense', amount: 350 },
          ],
        },
      },
    });

    await store.fetchSummary(101, '2026-11-07');

    expect(store.summary.total_income).toBe(1200.0);
    expect(store.summary.total_expense).toBe(350.0);
    expect(store.summary.net_in_drawer).toBe(850.0);
    expect(store.summary.transactions).toHaveLength(2);
  });

  it('recordExpense posts expense transaction and updates summary', async () => {
    const store = useCashboxStore();

    vi.spyOn(client, 'post').mockResolvedValue({
      data: { success: true, message: 'Expense recorded' },
    });
    vi.spyOn(client, 'get').mockResolvedValue({
      data: {
        success: true,
        data: { total_income: 0, total_expense: 50, net_in_drawer: -50, transactions: [] },
      },
    });

    const res = await store.recordExpense({
      clinic_id: 101,
      amount: 50.0,
      category: 'clinic_supplies',
      description_ar: 'شراء كحول ومطهرات',
    });

    expect(res.success).toBe(true);
    expect(client.post).toHaveBeenCalledWith('/reception/expense', expect.any(Object));
  });
});
