import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useDoctorWorkspaceStore } from '../../src/stores/doctorWorkspace';
import client from '../../src/api/client';

describe('Doctor Workspace Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetchCurrentPatient populates active patient, counts, and previous vitals', async () => {
    const store = useDoctorWorkspaceStore();

    const fakePayload = {
      data: {
        success: true,
        data: {
          appointment: { id: 50, queue_number: 3, status: 'in_consultation' },
          previous_vitals: { bp: '120/80', weight: 75 },
          counts: { waiting: 4, completed: 8, postponed: 1 },
        },
      },
    };

    vi.spyOn(client, 'get').mockResolvedValue(fakePayload);

    await store.fetchCurrentPatient(101);

    expect(store.currentPatient).toBeDefined();
    expect(store.currentPatient.id).toBe(50);
    expect(store.previousVitals.bp).toBe('120/80');
    expect(store.counts.waiting).toBe(4);
    expect(store.counts.completed).toBe(8);
  });

  it('savePrescription dispatches post and re-fetches current patient', async () => {
    const store = useDoctorWorkspaceStore();

    vi.spyOn(client, 'post').mockResolvedValue({
      data: { success: true, message: 'Prescription saved' },
    });
    vi.spyOn(client, 'get').mockResolvedValue({
      data: {
        success: true,
        data: { appointment: { id: 50 }, previous_vitals: null, counts: { waiting: 0, completed: 1, postponed: 0 } },
      },
    });

    const result = await store.savePrescription(50, {
      diagnosis: 'نزلة برد',
      prescribed_drugs: [{ drug_name: 'Paracetamol', dosage: 'قرص عند اللزوم' }],
    });

    expect(result.success).toBe(true);
    expect(client.post).toHaveBeenCalledWith('/doctor/prescription/50', expect.any(Object));
  });

  it('completeAndNext calls next patient and updates loading state', async () => {
    const store = useDoctorWorkspaceStore();

    vi.spyOn(client, 'post').mockResolvedValue({
      data: {
        success: true,
        data: {
          completed_appointment: { id: 50, status: 'completed' },
          next_patient: { id: 51, queue_number: 4, status: 'in_consultation' },
        },
      },
    });
    vi.spyOn(client, 'get').mockResolvedValue({
      data: {
        success: true,
        data: { appointment: { id: 51, queue_number: 4 }, previous_vitals: null, counts: { waiting: 3, completed: 9, postponed: 0 } },
      },
    });

    const res = await store.completeAndNext(101);

    expect(res.success).toBe(true);
    expect(store.loading).toBe(false);
  });
});
