import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import LoginView from '../../src/views/LoginView.vue';
import { useAuthStore } from '../../src/stores/auth';
import * as alerts from '../../src/utils/alerts';

const pushMock = vi.fn();
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: pushMock,
  }),
}));

vi.mock('../../src/utils/alerts', () => ({
  showToast: vi.fn(),
}));

describe('Doctor Dashboard LoginView Component', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('renders login form with email, password fields and stethoscope branding', () => {
    const wrapper = mount(LoginView);
    expect(wrapper.find('input[type="email"]').exists()).toBe(true);
    expect(wrapper.find('input[type="password"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('منصة العيادة الذكية');
    expect(wrapper.text()).toContain('تسجيل الدخول للطبيب وطاقم الاستقبال');
  });

  it('toggles password visibility between password and text input', async () => {
    const wrapper = mount(LoginView);
    const passwordInput = wrapper.find('input[placeholder="••••••••"]');
    expect(passwordInput.attributes('type')).toBe('password');

    // Click show password toggle button
    const toggleBtn = wrapper.find('button[title="إظهار كلمة المرور"]');
    expect(toggleBtn.exists()).toBe(true);
    await toggleBtn.trigger('click');

    expect(wrapper.find('input[placeholder="••••••••"]').attributes('type')).toBe('text');
  });

  it('displays visible error banner, triggers card shake, and shows toast when login fails', async () => {
    const authStore = useAuthStore();
    vi.spyOn(authStore, 'login').mockResolvedValue(false);
    authStore.error = 'بيانات الدخول غير صحيحة';

    const wrapper = mount(LoginView);
    await wrapper.find('form').trigger('submit.prevent');

    // Error banner must be visible
    expect(wrapper.text()).toContain('تعذر تسجيل الدخول');
    expect(wrapper.text()).toContain('بيانات الدخول غير صحيحة');

    // Shake animation class must be applied to the card
    expect(wrapper.find('.animate-shake').exists()).toBe(true);

    // Toast alert must be fired
    expect(alerts.showToast).toHaveBeenCalledWith('بيانات الدخول غير صحيحة', 'error');

    // Must NOT navigate away on failure
    expect(pushMock).not.toHaveBeenCalled();
  });

  it('successfully redirects doctor role to /workspace on valid login', async () => {
    const authStore = useAuthStore();
    vi.spyOn(authStore, 'login').mockImplementation(async () => {
      authStore.user = { id: 1, role: 'doctor', name: 'Dr. Test' };
      authStore.token = 'mock_jwt_token';
      return true;
    });

    const wrapper = mount(LoginView);
    await wrapper.find('form').trigger('submit.prevent');

    expect(pushMock).toHaveBeenCalledWith('/workspace');
  });

  it('successfully redirects receptionist role to /reception on valid login', async () => {
    const authStore = useAuthStore();
    vi.spyOn(authStore, 'login').mockImplementation(async () => {
      authStore.user = { id: 2, role: 'receptionist', name: 'Receptionist Test' };
      authStore.token = 'mock_jwt_token';
      return true;
    });

    const wrapper = mount(LoginView);
    await wrapper.find('form').trigger('submit.prevent');

    expect(pushMock).toHaveBeenCalledWith('/reception');
  });
});
