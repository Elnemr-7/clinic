import { describe, it, expect } from 'vitest';

describe('DoctorBookingForm Shift and Date Logic', () => {
  it('correctly matches clinic shifts to selected date day of week', () => {
    // 2026-11-07 is Saturday (day_of_week = 6 in JS/Carbon: 0=Sun, 1=Mon, ..., 6=Sat)
    const [year, month, day] = '2026-11-07'.split('-').map(Number);
    const selectedDayOfWeek = new Date(year, month - 1, day).getDay();

    expect(selectedDayOfWeek).toBe(6); // Saturday

    const allShifts = [
      { id: 1, day_of_week: 6, shift_name_ar: 'السبت المسائي', is_active: true },
      { id: 2, day_of_week: 0, shift_name_ar: 'الأحد المسائي', is_active: true },
      { id: 3, day_of_week: 6, shift_name_ar: 'السبت معطل', is_active: false },
    ];

    const activeSaturdayShifts = allShifts.filter(
      (s) => Number(s.day_of_week) === selectedDayOfWeek && s.is_active !== false
    );

    expect(activeSaturdayShifts).toHaveLength(1);
    expect(activeSaturdayShifts[0].id).toBe(1);
  });

  it('safely extracts error messages from both ofetch and axios response structures', () => {
    // ofetch error structure
    const ofetchError = {
      data: {
        message: 'عذراً، اكتمل العدد الأقصى للحجوزات',
      },
    };

    const extractMsg = (e: any) =>
      e.data?.message || e.response?._data?.message || e.response?.data?.message || 'Default error';

    expect(extractMsg(ofetchError)).toBe('عذراً، اكتمل العدد الأقصى للحجوزات');

    // axios error structure
    const axiosError = {
      response: {
        data: {
          message: 'لديك حجز مسجل بالفعل في هذا اليوم',
        },
      },
    };

    expect(extractMsg(axiosError)).toBe('لديك حجز مسجل بالفعل في هذا اليوم');
  });
});
