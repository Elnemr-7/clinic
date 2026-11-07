import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ActivePatientCard from '../../src/components/doctor/ActivePatientCard.vue';

describe('ActivePatientCard Component', () => {
  it('renders patient name, queue number, and formatted phone', () => {
    const fakeAppointment = {
      id: 101,
      queue_number: 5,
      visit_type: 'new_visit',
      patient: {
        id: 201,
        name: 'إبراهيم حسن',
        phone: '01099887766',
        birth_year: 1990,
      },
    };

    const wrapper = mount(ActivePatientCard, {
      props: {
        appointment: fakeAppointment,
      },
    });

    expect(wrapper.text()).toContain('إبراهيم حسن');
    expect(wrapper.text()).toContain('5');
    expect(wrapper.text()).toContain('01099887766');
  });

  it('renders followup badge when appointment_type is followup', () => {
    const fakeAppointment = {
      id: 102,
      queue_number: 6,
      appointment_type: 'followup',
      patient: {
        name: 'منار أحمد',
        phone: '01122334455',
      },
    };

    const wrapper = mount(ActivePatientCard, {
      props: {
        appointment: fakeAppointment,
      },
    });

    expect(wrapper.text()).toContain('استشارة');
  });
});
