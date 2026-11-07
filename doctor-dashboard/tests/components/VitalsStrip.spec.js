import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import VitalsStrip from '../../src/components/doctor/VitalsStrip.vue';

describe('VitalsStrip Component', () => {
  it('renders configured biological vitals values and units', () => {
    const currentVitals = {
      bp: '120/80',
      weight: '78',
      sugar: '110',
    };

    const vitalsConfig = [
      { key: 'bp', label: 'ضغط الدم', unit: 'mmHg', enabled: true },
      { key: 'weight', label: 'الوزن', unit: 'كجم', enabled: true },
      { key: 'sugar', label: 'السكر العشوائي', unit: 'mg/dL', enabled: true },
    ];

    const wrapper = mount(VitalsStrip, {
      props: {
        current: currentVitals,
        previousVitals: null,
        vitalsConfig: vitalsConfig,
      },
    });

    expect(wrapper.text()).toContain('120/80');
    expect(wrapper.text()).toContain('78');
    expect(wrapper.text()).toContain('110');
  });

  it('renders comparison difference when previous vitals exist', () => {
    const currentVitals = { weight: '80' };
    const prevVitals = { weight: '75' };
    const vitalsConfig = [{ key: 'weight', label: 'الوزن', unit: 'كجم', enabled: true }];

    const wrapper = mount(VitalsStrip, {
      props: {
        current: currentVitals,
        previousVitals: prevVitals,
        vitalsConfig: vitalsConfig,
      },
    });

    expect(wrapper.text()).toContain('80');
    expect(wrapper.text()).toContain('75');
  });
});
