import { createRouter, createWebHashHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

import LoginView from '../views/LoginView.vue';
import DoctorWorkspaceView from '../views/DoctorWorkspaceView.vue';
import ReceptionDeskView from '../views/ReceptionDeskView.vue';
import WaitingRoomTvView from '../views/WaitingRoomTvView.vue';
import ClinicalSettingsView from '../views/settings/ClinicalSettingsView.vue';
import ClinicBranchesView from '../views/settings/ClinicBranchesView.vue';
import ReceptionistsView from '../views/settings/ReceptionistsView.vue';
import WebsiteSettingsView from '../views/settings/WebsiteSettingsView.vue';

const routes = [
  { path: '/', redirect: '/workspace' },
  { path: '/login', name: 'login', component: LoginView, meta: { public: true } },
  { path: '/tv/:tv_screen_code', name: 'tv', component: WaitingRoomTvView, meta: { public: true } },
  { path: '/workspace', name: 'workspace', component: DoctorWorkspaceView, meta: { requiresAuth: true, role: 'doctor' } },
  { path: '/reception', name: 'reception', component: ReceptionDeskView, meta: { requiresAuth: true } },
  { path: '/settings/clinical', name: 'settings-clinical', component: ClinicalSettingsView, meta: { requiresAuth: true, role: 'doctor' } },
  { path: '/settings/branches', name: 'settings-branches', component: ClinicBranchesView, meta: { requiresAuth: true, role: 'doctor' } },
  { path: '/settings/receptionists', name: 'settings-receptionists', component: ReceptionistsView, meta: { requiresAuth: true, role: 'doctor' } },
  { path: '/settings/website', name: 'settings-website', component: WebsiteSettingsView, meta: { requiresAuth: true, role: 'doctor' } },
  { path: '/:pathMatch(.*)*', redirect: '/login' },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Support ?impersonate_token in hash route query OR window.location.search
  const urlParams = new URLSearchParams(window.location.search);
  const impersonateToken = to.query.impersonate_token || urlParams.get('impersonate_token');

  if (impersonateToken) {
    localStorage.setItem('clinic_token', impersonateToken);
    authStore.token = impersonateToken;
    authStore.isImpersonating = true;
    try {
      await authStore.fetchProfile();
      // Clean query parameter from URL
      return next({ path: '/workspace', replace: true });
    } catch (e) {
      console.error('Failed impersonation handshake', e);
    }
  }

  // Public routes (login, /tv/:code)
  if (to.meta.public) {
    return next();
  }

  // Check auth
  if (authStore.token && !authStore.user) {
    try {
      await authStore.fetchProfile();
    } catch (e) {
      authStore.logout();
      return next({ name: 'login' });
    }
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'login' });
  }

  // Role check: receptionist accessing doctor-only routes
  if (to.meta.role && authStore.user && authStore.user.role !== to.meta.role) {
    if (authStore.user.role === 'receptionist') {
      return next({ name: 'reception' });
    }
  }

  next();
});

export default router;
