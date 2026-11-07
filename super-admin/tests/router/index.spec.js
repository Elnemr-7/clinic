import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import router from '../../src/router/index';
import { useAuthStore } from '../../src/stores/auth';

describe('Super Admin Router Navigation Guards', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    localStorage.clear();
    vi.clearAllMocks();
  });

  it('redirects unauthenticated access from dashboard to /login', async () => {
    localStorage.removeItem('super_admin_token');
    const authStore = useAuthStore();
    authStore.token = null;
    authStore.admin = null;

    await router.push('/');
    expect(router.currentRoute.value.name).toBe('login');
  });

  it('redirects authenticated super-admin from /login to dashboard', async () => {
    localStorage.setItem('super_admin_token', 'valid_admin_token');
    const authStore = useAuthStore();
    authStore.token = 'valid_admin_token';
    authStore.admin = { id: 1, name: 'مدير المنصة' };

    await router.push('/login');
    expect(router.currentRoute.value.name).toBe('dashboard');
  });
});
