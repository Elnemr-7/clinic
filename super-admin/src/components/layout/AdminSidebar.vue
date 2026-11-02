<template>
  <aside class="w-64 bg-slate-950 border-l border-slate-800 flex flex-col min-h-screen">
    <div class="p-6 border-b border-slate-800 flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xl border border-emerald-500/30">
        🩺
      </div>
      <div>
        <h1 class="font-bold text-lg text-white">المنصة الطبية</h1>
        <p class="text-xs text-slate-400">لوحة التحكم السحابية</p>
      </div>
    </div>

    <nav class="flex-1 p-4 space-y-1.5">
      <router-link
        to="/"
        class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all"
        :class="$route.name === 'dashboard' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'"
      >
        <span>📊</span>
        <span>لوحة الإحصائيات العامة</span>
      </router-link>

      <router-link
        to="/doctors"
        class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all"
        :class="$route.name === 'doctors' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'"
      >
        <span>👨‍⚕️</span>
        <span>إدارة الأطباء والعيادات</span>
      </router-link>

      <router-link
        to="/leads"
        class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all"
        :class="$route.name === 'leads' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'"
      >
        <span>📥</span>
        <span>طلبات الاشتراك (Leads)</span>
      </router-link>

      <router-link
        to="/plans"
        class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all"
        :class="$route.name === 'plans' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'"
      >
        <span>💳</span>
        <span>الباقات والأسعار</span>
      </router-link>

      <router-link
        to="/topups"
        class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all"
        :class="$route.name === 'topups' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'"
      >
        <span>💰</span>
        <span>شحن الرصيد وفوري</span>
      </router-link>
    </nav>

    <div class="p-4 border-t border-slate-800">
      <button
        @click="logout"
        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-rose-400 hover:bg-rose-500/10 font-medium text-sm transition"
      >
        <span>🚪</span>
        <span>تسجيل الخروج</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const logout = () => {
  authStore.logout();
  router.push('/login');
};
</script>
