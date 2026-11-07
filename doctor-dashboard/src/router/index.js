import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

import LoginView from '../views/LoginView.vue';
import WaitingRoomTvView from '../views/WaitingRoomTvView.vue';
import AppShellLayout from '../components/layout/AppShellLayout.vue';
import DoctorWorkspaceView from '../views/DoctorWorkspaceView.vue';
import ReceptionDeskView from '../views/ReceptionDeskView.vue';
import SettingsLayoutView from '../views/settings/SettingsLayoutView.vue';
import ClinicalSettingsView from '../views/settings/ClinicalSettingsView.vue';
import ClinicBranchesView from '../views/settings/ClinicBranchesView.vue';
import ReceptionistsView from '../views/settings/ReceptionistsView.vue';
import WebsiteSettingsView from '../views/settings/WebsiteSettingsView.vue';

const routes = [
  { path: '/login', name: 'login', component: LoginView, meta: { public: true } },
  { path: '/tv/:tv_screen_code', name: 'tv', component: WaitingRoomTvView, meta: { public: true } },
  {
    path: '/',
    component: AppShellLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/workspace' },
      { path: 'workspace', name: 'workspace', component: DoctorWorkspaceView, meta: { role: 'doctor' } },
      { path: 'reception', name: 'reception', component: ReceptionDeskView },
      {
        path: 'settings',
        component: SettingsLayoutView,
        meta: { role: 'doctor' },
        children: [
          { path: '', redirect: '/settings/clinical' },
          { path: 'clinical', name: 'settings-clinical', component: ClinicalSettingsView },
          { path: 'branches', name: 'settings-branches', component: ClinicBranchesView },
          { path: 'receptionists', name: 'settings-receptionists', component: ReceptionistsView },
          { path: 'website', name: 'settings-website', component: WebsiteSettingsView },
        ],
      },
    ],
  },
  {
    path: '/rx/:token',
    name: 'rx-direct',
    meta: { public: true },
    beforeEnter: (to) => {
      window.location.href = `/rx/${to.params.token}`;
    },
  },
  { path: '/:pathMatch(.*)*', redirect: '/login' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Support ?impersonate_token in query, search, or hash
  const urlParams = new URLSearchParams(window.location.search);
  let impersonateToken = to.query.impersonate_token || urlParams.get('impersonate_token');

  if (!impersonateToken && window.location.hash) {
    const rawHash = window.location.hash;
    const queryPart = rawHash.includes('?') ? rawHash.split('?')[1] : rawHash.replace(/^#\/?/, '');
    const hashParams = new URLSearchParams(queryPart);
    impersonateToken = hashParams.get('impersonate_token');
  }

  if (impersonateToken) {
    if (!authStore.user || authStore.token !== impersonateToken) {
      authStore.setImpersonationToken(impersonateToken);
      try {
        await authStore.fetchProfile();
      } catch (e) {
        console.error('Failed impersonation handshake', e);
      }
    }

    // Clean browser history URL so reload doesn't re-trigger with old token
    window.history.replaceState(null, '', '/workspace');

    // If navigation query still holds the token, redirect cleanly to /workspace without query
    if (to.query.impersonate_token) {
      return next({ path: '/workspace', query: {}, replace: true });
    }
  }

  // If user is already authenticated and visits login route, redirect into their workspace
  if (to.name === 'login' && authStore.isAuthenticated) {
    if (authStore.user) {
      return next(authStore.user.role === 'doctor' ? '/workspace' : '/reception');
    }
  }

  // Public routes (login, /tv/:code, /rx/:token)
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
