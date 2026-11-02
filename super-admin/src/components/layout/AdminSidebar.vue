<template>
  <aside class="w-64 bg-surface-header border-l border-line-subtle flex flex-col h-screen sticky top-0 select-none font-sans" dir="rtl">
    <!-- Brand Header -->
    <div class="h-16 px-5 border-b border-line-subtle flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-brand-subtle text-brand border border-brand-border flex items-center justify-center font-bold text-lg shadow-xs">
        🩺
      </div>
      <div>
        <h1 class="font-extrabold text-sm text-content-primary leading-tight">المنصة الطبية</h1>
        <p class="text-[11px] text-content-muted">لوحة التحكم السحابية</p>
      </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
      <router-link
        to="/"
        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-xs transition-all"
        :class="$route.name === 'dashboard'
          ? 'bg-brand text-content-inverse shadow-xs'
          : 'text-content-secondary hover:text-content-primary hover:bg-surface-elevated'"
      >
        <span>📊</span>
        <span>لوحة الإحصائيات العامة</span>
      </router-link>

      <router-link
        to="/doctors"
        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-xs transition-all"
        :class="$route.name === 'doctors'
          ? 'bg-brand text-content-inverse shadow-xs'
          : 'text-content-secondary hover:text-content-primary hover:bg-surface-elevated'"
      >
        <span>👨‍⚕️</span>
        <span>إدارة الأطباء (God-Mode)</span>
      </router-link>

      <router-link
        to="/leads"
        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-xs transition-all"
        :class="$route.name === 'leads'
          ? 'bg-brand text-content-inverse shadow-xs'
          : 'text-content-secondary hover:text-content-primary hover:bg-surface-elevated'"
      >
        <span>📥</span>
        <span>طلبات الاشتراك (Leads)</span>
      </router-link>

      <router-link
        to="/plans"
        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-xs transition-all"
        :class="$route.name === 'plans'
          ? 'bg-brand text-content-inverse shadow-xs'
          : 'text-content-secondary hover:text-content-primary hover:bg-surface-elevated'"
      >
        <span>💎</span>
        <span>الباقات والأسعار</span>
      </router-link>

      <router-link
        to="/topups"
        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-xs transition-all"
        :class="$route.name === 'topups'
          ? 'bg-brand text-content-inverse shadow-xs'
          : 'text-content-secondary hover:text-content-primary hover:bg-surface-elevated'"
      >
        <span>💳</span>
        <span>شحن المحافظ وفوري</span>
      </router-link>
    </nav>

    <!-- Admin Profile & Logout -->
    <div class="p-3 border-t border-line-subtle">
      <button
        type="button"
        @click="handleLogout"
        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-status-danger hover:bg-status-danger-bg text-xs font-bold transition-all cursor-pointer"
      >
        <LogOut class="w-4 h-4" />
        <span>تسجيل الخروج</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { LogOut } from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();

const handleLogout = () => {
  authStore.logout();
  router.push('/login');
};
</script>
