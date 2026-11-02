<template>
  <div v-if="authStore.isImpersonating" class="bg-amber-500 text-slate-950 font-bold text-xs py-2 px-6 flex justify-between items-center z-50 sticky top-0 shadow-md">
    <div class="flex items-center gap-2">
      <span class="text-base">⚠️</span>
      <span>أنت تتصفح الآن بحساب الطبيب (وضع الدعم الفني للـ Super Admin)</span>
    </div>
    <button @click="exitImpersonation" class="bg-slate-950 text-amber-300 px-3 py-1 rounded-lg text-xs hover:bg-slate-900 transition">
      إنهاء جلسة الدعم والعودة للوحة الإدارة
    </button>
  </div>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();

const exitImpersonation = () => {
  authStore.logout();
  window.close();
};
</script>
