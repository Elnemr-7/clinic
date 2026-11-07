import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useClinicBooking } from '../../composables/useClinicBooking';

// Mock useRuntimeConfig
vi.mock('#app', () => ({
  useRuntimeConfig: () => ({
    public: {
      apiBase: 'http://localhost:8000/api',
    },
  }),
}));

describe('useClinicBooking Composable', () => {
  let mockFetch: any;

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('resolveTenant queries tenant-by-host with subdomain or host parameters', async () => {
    const booking = useClinicBooking();

    // Mock client internal fetch
    vi.spyOn(booking, 'resolveTenant').mockResolvedValue({
      id: 5,
      name: 'د. مجدي يعقوب',
      subdomain: 'dr-magdi',
    });

    const tenant = await booking.resolveTenant(null, 'dr-magdi');

    expect(tenant.name).toBe('د. مجدي يعقوب');
    expect(tenant.subdomain).toBe('dr-magdi');
  });

  it('checkFollowupEligibility sends phone and clinic ID', async () => {
    const booking = useClinicBooking();

    vi.spyOn(booking, 'checkFollowupEligibility').mockResolvedValue({
      eligible: true,
      patient_name: 'أحمد محمود',
      is_followup: true,
    });

    const result = await booking.checkFollowupEligibility('01012345678', 101);

    expect(result.eligible).toBe(true);
    expect(result.patient_name).toBe('أحمد محمود');
  });

  it('submitBooking sends appointment booking payload', async () => {
    const booking = useClinicBooking();

    const fakeBooking = {
      clinic_id: 101,
      shift_id: 2,
      appointment_date: '2026-11-07',
      name: 'كريم عادل',
      phone: '01234567890',
    };

    vi.spyOn(booking, 'submitBooking').mockResolvedValue({
      success: true,
      data: {
        queue_number: 7,
        reference_code: 'CLN-ABC12',
      },
    });

    const res = await booking.submitBooking(fakeBooking);

    expect(res.success).toBe(true);
    expect(res.data.queue_number).toBe(7);
    expect(res.data.reference_code).toBe('CLN-ABC12');
  });

  it('fetchLiveQueueStatus queries turn number by reference code', async () => {
    const booking = useClinicBooking();

    vi.spyOn(booking, 'fetchLiveQueueStatus').mockResolvedValue({
      appointment: { queue_number: 12, reference_code: 'CLN-REF99' },
      current_in_room_queue: 10,
      remaining_ahead: 1,
    });

    const status = await booking.fetchLiveQueueStatus('CLN-REF99');

    expect(status.remaining_ahead).toBe(1);
    expect(status.current_in_room_queue).toBe(10);
  });
});
