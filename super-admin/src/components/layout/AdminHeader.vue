<template>
  <header class="h-16 bg-surface-header border-b border-line-subtle px-6 flex items-center justify-between sticky top-0 z-30 select-none font-sans" dir="rtl">
    <div class="flex items-center gap-3">
      <h2 class="text-sm lg:text-base font-black text-content-primary">{{ title }}</h2>
    </div>

    <div class="flex items-center gap-3">
      <!-- Dark/Light Mode Switcher -->
      <button
        type="button"
        @click="theme.toggleTheme()"
        class="h-9 w-9 flex items-center justify-center text-content-secondary hover:text-content-primary hover:bg-surface-elevated border border-line-subtle rounded-xl transition-all shadow-xs cursor-pointer"
        :title="theme.isDark.value ? 'التبديل إلى الوضع النهاري' : 'التبديل إلى الوضع الليلي'"
      >
        <Sun v-if="theme.isDark.value" class="w-4 h-4 text-amber-400" />
        <Moon v-else class="w-4 h-4 text-content-secondary" />
      </button>

      <!-- Admin Profile Pill -->
      <div class="flex items-center gap-2.5 bg-surface-elevated border border-line-subtle px-3 py-1.5 rounded-xl">
        <div class="w-7 h-7 rounded-lg bg-brand-subtle text-brand border border-brand-border font-bold flex items-center justify-center text-xs">
          👑
        </div>
        <div class="text-right">
          <p class="text-xs font-bold text-content-primary">{{ authStore.admin?.name || 'مدير المنصة' }}</p>
          <p class="text-[10px] text-brand font-medium">تحكم كامل (Super Admin)</p>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth';
import { useTheme } from '../../composables/useTheme';
import { Sun, Moon } from 'lucide-vue-next';

defineProps({
  title: {
    type: String,
    default: 'لوحة التحكم',
  },
});

const authStore = useAuthStore();
const theme = useTheme();
</script>
