import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import LoginView from '../../src/views/LoginView.vue';
import { useAuthStore } from '../../src/stores/auth';
import * as alerts from '../../src/utils/alerts';

const pushMock = vi.fn();
vi.mock('vue-router', async (importOriginal) => {
  const actual = await importOriginal();
  return {
    ...actual,
    useRouter: () => ({
      push: pushMock,
    }),
  };
});

vi.mock('../../src/utils/alerts', () => ({
  showToast: vi.fn(),
  showError: vi.fn(),
}));

describe('Super Admin LoginView Component', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('renders login form with super admin title and branding', () => {
    const wrapper = mount(LoginView);
    expect(wrapper.text()).toContain('بوابة الإدارة العامة');
    expect(wrapper.text()).toContain('مالك المنصة');
    expect(wrapper.find('input[type="email"]').exists()).toBe(true);
    expect(wrapper.find('input[type="password"]').exists()).toBe(true);
  });

  it('toggles password visibility between password and text input', async () => {
    const wrapper = mount(LoginView);
    const passwordInput = wrapper.find('input[placeholder="••••••••"]');
    expect(passwordInput.attributes('type')).toBe('password');

    const toggleBtn = wrapper.find('button[title="إظهار كلمة المرور"]');
    expect(toggleBtn.exists()).toBe(true);
    await toggleBtn.trigger('click');

    expect(wrapper.find('input[placeholder="••••••••"]').attributes('type')).toBe('text');
  });

  it('displays error banner, triggers card shake, and shows toast when login fails', async () => {
    const authStore = useAuthStore();
    vi.spyOn(authStore, 'login').mockResolvedValue(false);
    authStore.error = 'بيانات المشرف غير صحيحة';

    const wrapper = mount(LoginView);
    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.text()).toContain('خطأ في الدخول');
    expect(wrapper.text()).toContain('بيانات المشرف غير صحيحة');
    expect(wrapper.find('.animate-shake').exists()).toBe(true);
    expect(alerts.showToast).toHaveBeenCalledWith('بيانات المشرف غير صحيحة', 'error');
    expect(pushMock).not.toHaveBeenCalled();
  });

  it('redirects to dashboard / on successful login', async () => {
    const authStore = useAuthStore();
    vi.spyOn(authStore, 'login').mockResolvedValue(true);

    const wrapper = mount(LoginView);
    await wrapper.find('form').trigger('submit.prevent');

    expect(pushMock).toHaveBeenCalledWith('/');
  });
});
