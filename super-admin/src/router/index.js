import { createRouter, createWebHistory } from 'vue-router';
import LoginView from '../views/LoginView.vue';
import AdminShellLayout from '../components/layout/AdminShellLayout.vue';
import DashboardView from '../views/DashboardView.vue';
import DoctorsListView from '../views/DoctorsListView.vue';
import LeadsListView from '../views/LeadsListView.vue';
import PlansListView from '../views/PlansListView.vue';
import TopupsListView from '../views/TopupsListView.vue';
import { useAuthStore } from '../stores/auth';

const routes = [
  { path: '/login', name: 'login', component: LoginView, meta: { guest: true } },
  {
    path: '/',
    component: AdminShellLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'dashboard', component: DashboardView },
      { path: 'doctors', name: 'doctors', component: DoctorsListView },
      { path: 'leads', name: 'leads', component: LeadsListView },
      { path: 'plans', name: 'plans', component: PlansListView },
      { path: 'topups', name: 'topups', component: TopupsListView },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const token = localStorage.getItem('super_admin_token');

  if (to.meta.requiresAuth && !token) {
    return next({ name: 'login' });
  }

  if (to.meta.guest && token) {
    return next({ name: 'dashboard' });
  }

  next();
});

export default router;
