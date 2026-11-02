<template>
  <header class="bg-white border-b border-slate-200 px-6 h-16 flex items-center justify-between shadow-sm sticky top-0 z-30 font-sans text-right select-none" dir="rtl">
    <!-- Left Section: Brand & Navigation Links -->
    <div class="flex items-center gap-4">
      <div class="flex items-center gap-2.5">
        <div class="w-10 h-10 bg-teal-50 text-teal-700 rounded-xl flex items-center justify-center shrink-0 shadow-sm border border-teal-100">
          <Stethoscope class="w-5 h-5" />
        </div>
        <div>
          <h1 class="text-sm font-extrabold text-slate-900 leading-tight">{{ title }}</h1>
          <p class="text-[11px] text-slate-400 font-medium">{{ subtitle || authStore.user?.name || 'د. استشاري' }}</p>
        </div>
      </div>

      <!-- Unified Navigation Bar (Identical height, styling, and active indicator across all pages) -->
      <nav class="hidden lg:flex items-center gap-1 bg-slate-100/80 p-1 rounded-xl border border-slate-200/80 text-xs">
        <router-link
          to="/workspace"
          class="px-3.5 py-1.5 font-bold rounded-lg transition-all flex items-center gap-1.5"
          :class="isRouteActive('/workspace') ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/80'"
        >
          <span>غرفة الكشف</span>
        </router-link>

        <router-link
          to="/reception"
          class="px-3.5 py-1.5 font-bold rounded-lg transition-all flex items-center gap-1.5"
          :class="isRouteActive('/reception') ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/80'"
        >
          <span>الاستقبال والطابور</span>
        </router-link>

        <router-link
          to="/settings/clinical"
          class="px-3.5 py-1.5 font-bold rounded-lg transition-all flex items-center gap-1.5"
          :class="isRouteActive('/settings/clinical') ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/80'"
        >
          <span>العلامات والروشتة</span>
        </router-link>

        <router-link
          to="/settings/branches"
          class="px-3.5 py-1.5 font-bold rounded-lg transition-all flex items-center gap-1.5"
          :class="isRouteActive('/settings/branches') ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/80'"
        >
          <span>الفروع والورديات</span>
        </router-link>

        <router-link
          to="/settings/receptionists"
          class="px-3.5 py-1.5 font-bold rounded-lg transition-all flex items-center gap-1.5"
          :class="isRouteActive('/settings/receptionists') ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/80'"
        >
          <span>طاقم السكرتارية</span>
        </router-link>

        <router-link
          to="/settings/website"
          class="px-3.5 py-1.5 font-bold rounded-lg transition-all flex items-center gap-1.5"
          :class="isRouteActive('/settings/website') ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/80'"
        >
          <span>الموقع والواتساب</span>
        </router-link>
      </nav>

      <slot name="after-title" />
    </div>

    <!-- Right Section: Actions & Utilities -->
    <div class="flex items-center gap-2.5">
      <slot name="actions" />

      <!-- Direct Doctor Website Link -->
      <a
        v-if="doctorWebsiteUrl"
        :href="doctorWebsiteUrl"
        target="_blank"
        class="h-9 px-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-teal-700 border border-slate-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm"
        title="عرض موقع الحجز المباشر لمرضى العيادة"
      >
        <Globe class="w-3.5 h-3.5 text-teal-600" />
        <span>موقع العيادة</span>
      </a>

      <!-- Logout Button -->
      <button
        @click="handleLogout"
        class="h-9 w-9 flex items-center justify-center text-rose-500 hover:text-rose-600 hover:bg-rose-50 border border-rose-100 rounded-xl transition-all shadow-sm"
        title="تسجيل الخروج"
      >
        <LogOut class="w-4 h-4" />
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { Stethoscope, LogOut, Globe } from 'lucide-vue-next';

defineProps({
  title: {
    type: String,
    default: 'لوحة تحكم الطبيب',
  },
  subtitle: {
    type: String,
    default: '',
  },
});

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const isRouteActive = (targetPath) => {
  return route.path === targetPath;
};

const doctorWebsiteUrl = computed(() => {
  const subdomain = authStore.user?.tenant?.subdomain || 'dr-ahmed';
  return `http://localhost:3000/d/${subdomain}`;
});

const handleLogout = () => {
  authStore.logout();
  router.push('/login');
};
</script>
