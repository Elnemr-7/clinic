<template>
  <div class="p-4 lg:p-6 max-w-[1440px] mx-auto w-full select-none" dir="rtl">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- RIGHT MENU RAIL (3 Cols) -->
      <aside class="lg:col-span-3 bg-surface-card border border-line-subtle rounded-2xl p-3 shadow-xs sticky top-4">
        <div class="px-3 py-2 border-b border-line-subtle mb-2">
          <h2 class="text-xs font-black text-content-primary">إعدادات العيادة</h2>
          <p class="text-[11px] text-content-muted">التحكم الشامل في المنظومة الطبية</p>
        </div>

        <nav class="space-y-1">
          <router-link
            to="/settings/clinical"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-xs transition-all"
            :class="isTabActive('/settings/clinical')
              ? 'bg-brand text-content-inverse shadow-xs'
              : 'text-content-secondary hover:text-content-primary hover:bg-surface-elevated'"
          >
            <Pill class="w-4 h-4 shrink-0" />
            <div class="text-right">
              <div>1. الإعدادات السريرية</div>
              <div class="text-[10px] opacity-80 font-normal">العلامات، الأدوية، وهوامش الطباعة</div>
            </div>
          </router-link>

          <router-link
            to="/settings/branches"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-xs transition-all"
            :class="isTabActive('/settings/branches')
              ? 'bg-brand text-content-inverse shadow-xs'
              : 'text-content-secondary hover:text-content-primary hover:bg-surface-elevated'"
          >
            <Building2 class="w-4 h-4 shrink-0" />
            <div class="text-right">
              <div>2. الفروع والورديات</div>
              <div class="text-[10px] opacity-80 font-normal">مواعيد العمل، الأسعار، والعطلات</div>
            </div>
          </router-link>

          <router-link
            to="/settings/receptionists"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-xs transition-all"
            :class="isTabActive('/settings/receptionists')
              ? 'bg-brand text-content-inverse shadow-xs'
              : 'text-content-secondary hover:text-content-primary hover:bg-surface-elevated'"
          >
            <Users class="w-4 h-4 shrink-0" />
            <div class="text-right">
              <div>3. طاقم الاستقبال</div>
              <div class="text-[10px] opacity-80 font-normal">حسابات السكرتارية وصلاحيات الفروع</div>
            </div>
          </router-link>

          <router-link
            to="/settings/website"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-xs transition-all"
            :class="isTabActive('/settings/website')
              ? 'bg-brand text-content-inverse shadow-xs'
              : 'text-content-secondary hover:text-content-primary hover:bg-surface-elevated'"
          >
            <Globe class="w-4 h-4 shrink-0" />
            <div class="text-right">
              <div>4. الموقع والواتساب</div>
              <div class="text-[10px] opacity-80 font-normal">النطاق المخصص ورسائل الإشعارات</div>
            </div>
          </router-link>
        </nav>
      </aside>

      <!-- ACTIVE SETTINGS CONTENT PANEL (9 Cols) -->
      <main class="lg:col-span-9 bg-surface-card border border-line-subtle rounded-2xl p-5 lg:p-6 shadow-xs min-h-[500px]">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router';
import { Pill, Building2, Users, Globe } from 'lucide-vue-next';

const route = useRoute();

const isTabActive = (path) => {
  return route.path.startsWith(path);
};
</script>
