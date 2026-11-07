import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAuthStore } from '../../src/stores/auth';
import client from '../../src/api/client';

describe('Auth Store (Doctor Dashboard)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    localStorage.clear();
    sessionStorage.clear();
    vi.clearAllMocks();
  });

  it('initializes with default unauthenticated state', () => {
    const store = useAuthStore();
    expect(store.token).toBeNull();
    expect(store.user).toBeNull();
    expect(store.isAuthenticated).toBe(false);
    expect(store.isDoctor).toBe(false);
    expect(store.isReceptionist).toBe(false);
  });

  it('logs in successfully and sets user, tokens, and storage', async () => {
    const store = useAuthStore();
    const fakeResponse = {
      data: {
        success: true,
        data: {
          token: 'fake_jwt_token_123',
          socket_token: 'socket_hmac_456',
          user: { id: 1, name: 'د. وائل', role: 'doctor' },
          tenant: { id: 10, name: 'عيادة الشفاء' },
          clinic: { id: 101, name: 'الفرع الرئيسي' },
        },
      },
    };

    vi.spyOn(client, 'post').mockResolvedValue(fakeResponse);

    const result = await store.login('doctor@clinic.com', 'password123');

    expect(result.success).toBe(true);
    expect(store.token).toBe('fake_jwt_token_123');
    expect(store.socketToken).toBe('socket_hmac_456');
    expect(store.user.name).toBe('د. وائل');
    expect(store.isAuthenticated).toBe(true);
    expect(store.isDoctor).toBe(true);
    expect(localStorage.getItem('clinic_token')).toBe('fake_jwt_token_123');
  });

  it('sets impersonation mode cleanly and stores in session', () => {
    const store = useAuthStore();
    store.setImpersonationToken('impersonate_god_mode_token');

    expect(store.token).toBe('impersonate_god_mode_token');
    expect(store.isImpersonating).toBe(true);
    expect(sessionStorage.getItem('clinic_token')).toBe('impersonate_god_mode_token');
    expect(sessionStorage.getItem('is_impersonating')).toBe('true');
  });

  it('logout cleans state and revokes tokens', async () => {
    const store = useAuthStore();
    store.token = 'existing_token';
    store.user = { id: 1, role: 'doctor' };
    localStorage.setItem('clinic_token', 'existing_token');

    vi.spyOn(client, 'post').mockResolvedValue({ data: { success: true } });

    await store.logout();

    expect(store.token).toBeNull();
    expect(store.user).toBeNull();
    expect(store.isAuthenticated).toBe(false);
    expect(localStorage.getItem('clinic_token')).toBeNull();
    expect(sessionStorage.getItem('clinic_token')).toBeNull();
  });
});
