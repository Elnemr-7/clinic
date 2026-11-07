import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import router from '../../src/router/index';
import { useAuthStore } from '../../src/stores/auth';

describe('Doctor Dashboard Router Navigation Guards', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    localStorage.clear();
    sessionStorage.clear();
    vi.clearAllMocks();
  });

  it('allows unauthenticated access to public login route', async () => {
    await router.push('/login');
    expect(router.currentRoute.value.name).toBe('login');
  });

  it('redirects unauthenticated users trying to access protected workspace to /login', async () => {
    const authStore = useAuthStore();
    authStore.token = null;
    authStore.user = null;

    await router.push('/workspace');
    expect(router.currentRoute.value.name).toBe('login');
  });

  it('redirects receptionist away from doctor-only workspace to reception desk', async () => {
    const authStore = useAuthStore();
    authStore.token = 'valid_receptionist_token';
    authStore.user = { id: 2, name: 'موظف الاستقبال', role: 'receptionist' };

    await router.push('/workspace');
    expect(router.currentRoute.value.name).toBe('reception');
  });

  it('allows doctor role to access /workspace', async () => {
    const authStore = useAuthStore();
    authStore.token = 'valid_doctor_token';
    authStore.user = { id: 1, name: 'د. كريم', role: 'doctor' };

    await router.push('/workspace');
    expect(router.currentRoute.value.name).toBe('workspace');
  });
});
