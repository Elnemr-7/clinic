<template>
  <header class="h-16 bg-slate-950/80 backdrop-blur border-b border-slate-800 px-8 flex items-center justify-between sticky top-0 z-30">
    <div class="flex items-center gap-4">
      <h2 class="text-lg font-bold text-white">{{ title }}</h2>
    </div>

    <div class="flex items-center gap-4">
      <div class="flex items-center gap-3 bg-slate-900 border border-slate-800 px-4 py-2 rounded-xl">
        <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 font-bold flex items-center justify-center text-sm border border-emerald-500/30">
          👑
        </div>
        <div class="text-right">
          <p class="text-xs font-bold text-white">{{ authStore.admin?.name || 'مدير المنصة' }}</p>
          <p class="text-[10px] text-emerald-400">تحكم كامل (Super Admin)</p>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth';

defineProps({
  title: {
    type: String,
    default: 'لوحة التحكم',
  },
});

const authStore = useAuthStore();
</script>
