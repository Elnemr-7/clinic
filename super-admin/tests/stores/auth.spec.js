import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAuthStore } from '../../src/stores/auth';
import client from '../../src/api/client';

describe('Super Admin Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    localStorage.clear();
    vi.clearAllMocks();
  });

  it('starts unauthenticated', () => {
    const store = useAuthStore();
    expect(store.token).toBeNull();
    expect(store.admin).toBeNull();
    expect(store.isAuthenticated).toBe(false);
  });

  it('login stores super admin token and profile', async () => {
    const store = useAuthStore();

    vi.spyOn(client, 'post').mockResolvedValue({
      data: {
        success: true,
        data: {
          token: 'super_admin_jwt_secret',
          admin: { id: 1, name: 'المدير العام', email: 'admin@platform.com' },
        },
      },
    });

    const res = await store.login('admin@platform.com', 'SuperSecretPass');

    expect(res.success).toBe(true);
    expect(store.token).toBe('super_admin_jwt_secret');
    expect(store.admin.email).toBe('admin@platform.com');
    expect(store.isAuthenticated).toBe(true);
    expect(localStorage.getItem('super_admin_token')).toBe('super_admin_jwt_secret');
  });

  it('logout removes credentials from store and storage', () => {
    const store = useAuthStore();
    store.token = 'active_token';
    store.admin = { id: 1 };
    localStorage.setItem('super_admin_token', 'active_token');

    store.logout();

    expect(store.token).toBeNull();
    expect(store.admin).toBeNull();
    expect(store.isAuthenticated).toBe(false);
    expect(localStorage.getItem('super_admin_token')).toBeNull();
  });
});
