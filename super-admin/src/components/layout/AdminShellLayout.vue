<template>
  <div class="min-h-screen bg-surface-app text-content-primary flex select-none font-sans" dir="rtl">
    <!-- Persistent Right Sidebar -->
    <AdminSidebar />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <AdminHeader :title="currentTitle" />
      <main class="flex-1 p-6 overflow-auto">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import AdminSidebar from './AdminSidebar.vue';
import AdminHeader from './AdminHeader.vue';
import { useTheme } from '../../composables/useTheme';

const route = useRoute();
const theme = useTheme();

const titles = {
  dashboard: 'لوحة الإحصائيات العامة للمنصة',
  doctors: 'إدارة الأطباء والعيادات (God-Mode)',
  leads: 'طلبات الاشتراك وتأسيس العيادات (Leads)',
  plans: 'إدارة الباقات والاشتراكات السنوية والشهرية',
  topups: 'شحن رصيد المحافظ والرسائل وفوري',
};

const currentTitle = computed(() => {
  return titles[route.name] || 'لوحة التحكم';
});

onMounted(() => {
  theme.initTheme();
});
</script>
