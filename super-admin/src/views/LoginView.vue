<template>
  <div class="min-h-screen bg-slate-950 flex items-center justify-center p-4 font-sans select-none" dir="rtl">
    <div
      class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl relative overflow-hidden transition-all duration-200"
      :class="{ 'animate-shake border-rose-500/50 shadow-rose-950/20': isShaking }"
    >
      <div class="absolute -top-24 -right-24 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <div class="text-center mb-8 relative">
        <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-3xl mx-auto mb-4 shadow-inner">
          👑
        </div>
        <h1 class="text-2xl font-bold text-white mb-2">بوابة الإدارة العامة</h1>
        <p class="text-xs text-slate-400">سجل الدخول بحساب مالك المنصة (Super Admin)</p>
      </div>

      <!-- Error Alert Banner -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 -translate-y-2 scale-95"
      >
        <div
          v-if="errorMessage"
          class="mb-6 p-4 rounded-2xl bg-rose-500/10 border-2 border-rose-500/30 text-rose-300 text-xs flex items-start justify-between gap-2 shadow-sm"
          role="alert"
        >
          <div class="flex items-start gap-2.5">
            <span class="text-rose-400 text-base leading-none mt-0.5">⚠️</span>
            <div>
              <p class="font-bold text-rose-200">خطأ في الدخول</p>
              <p class="text-rose-300/90 leading-relaxed font-medium mt-0.5">{{ errorMessage }}</p>
            </div>
          </div>
          <button
            @click="errorMessage = ''"
            type="button"
            class="text-rose-400/80 hover:text-rose-200 p-1 rounded-lg hover:bg-rose-500/20 transition shrink-0 cursor-pointer text-sm font-bold"
            title="إغلاق التنبيه"
          >
            ✕
          </button>
        </div>
      </transition>

      <form @submit.prevent="handleLogin" class="space-y-5 relative">
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-2">البريد الإلكتروني للـ Super Admin</label>
          <input
            v-model="email"
            @input="clearError"
            type="email"
            required
            dir="ltr"
            placeholder="admin@platform.com"
            class="w-full bg-slate-950 border rounded-xl px-4 py-3 text-sm text-white focus:outline-none transition font-medium text-left"
            :class="errorMessage ? 'border-rose-500/60 focus:border-rose-400 focus:ring-1 focus:ring-rose-500/30' : 'border-slate-800 focus:border-emerald-500'"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-2">كلمة المرور</label>
          <div class="relative">
            <input
              v-model="password"
              @input="clearError"
              :type="showPassword ? 'text' : 'password'"
              required
              dir="ltr"
              placeholder="••••••••"
              class="w-full bg-slate-950 border rounded-xl pl-10 pr-4 py-3 text-sm text-white focus:outline-none transition font-mono text-left"
              :class="errorMessage ? 'border-rose-500/60 focus:border-rose-400 focus:ring-1 focus:ring-rose-500/30' : 'border-slate-800 focus:border-emerald-500'"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute left-3 top-3 text-slate-400 hover:text-slate-200 transition p-0.5 cursor-pointer text-xs font-bold"
              :title="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
            >
              {{ showPassword ? '🙈' : '👁️' }}
            </button>
          </div>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3.5 rounded-xl text-sm transition shadow-lg shadow-emerald-900/30 flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer"
        >
          <span v-if="loading" class="flex items-center gap-2">
            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>جاري التحقق...</span>
          </span>
          <span v-else>دخول اللوحة الرئيسية</span>
        </button>

        <p class="text-[11px] text-center text-slate-500 mt-4 leading-relaxed">
          تنبيه: محاولات الدخول بحسابات الأطباء أو السكرتارية سيتم رفضها تلقائياً.
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { showToast } from '../utils/alerts';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('admin@platform.com');
const password = ref('password123');
const showPassword = ref(false);
const loading = ref(false);
const errorMessage = ref('');
const isShaking = ref(false);

const triggerShake = () => {
  isShaking.value = true;
  setTimeout(() => {
    isShaking.value = false;
  }, 650);
};

const clearError = () => {
  if (errorMessage.value) errorMessage.value = '';
};

const handleLogin = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const success = await authStore.login(email.value, password.value);
    loading.value = false;

    if (success) {
      router.push('/');
    } else {
      errorMessage.value = authStore.error || 'بيانات الدخول غير صحيحة، يرجى التحقق من البريد وكلمة المرور';
      triggerShake();
      showToast(errorMessage.value, 'error');
    }
  } catch (err) {
    loading.value = false;
    errorMessage.value = err.response?.data?.message || authStore.error || 'فشل الاتصال بالخادم، يرجى المحاولة لاحقاً';
    triggerShake();
    showToast(errorMessage.value, 'error');
  }
};
</script>

<style scoped>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-8px); }
  40%, 80% { transform: translateX(8px); }
}
.animate-shake {
  animation: shake 0.6s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}
</style>
