<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { showToast } from '../utils/alerts';
import { Stethoscope, Lock, Mail, ArrowLeft, AlertCircle, Eye, EyeOff, X } from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('doctor@clinic.com');
const password = ref('password123');
const showPassword = ref(false);
const errorMessage = ref('');
const loading = ref(false);
const isShaking = ref(false);

const triggerShake = () => {
  isShaking.value = true;
  setTimeout(() => {
    isShaking.value = false;
  }, 650);
};

const handleLogin = async () => {
  errorMessage.value = '';
  loading.value = true;

  try {
    const success = await authStore.login(email.value, password.value);
    if (!success) {
      errorMessage.value = authStore.error || 'بيانات الدخول غير صحيحة. يرجى التحقق من البريد وكلمة المرور.';
      triggerShake();
      showToast(errorMessage.value, 'error');
      return;
    }

    if (authStore.user?.role === 'doctor') {
      router.push('/workspace');
    } else {
      router.push('/reception');
    }
  } catch (e) {
    errorMessage.value = e.response?.data?.message || authStore.error || 'فشل تسجيل الدخول. تحقق من الاتصال بالخادم وبيانات الدخول.';
    triggerShake();
    showToast(errorMessage.value, 'error');
  } finally {
    loading.value = false;
  }
};

const clearError = () => {
  if (errorMessage.value) {
    errorMessage.value = '';
  }
};
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-teal-950 flex items-center justify-center p-4 font-sans text-right select-none" dir="rtl">
    <div
      class="w-full max-w-md bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8 space-y-6 transition-transform duration-200"
      :class="{ 'animate-shake border-rose-300 shadow-rose-950/20': isShaking }"
    >
      <!-- Logo & Title -->
      <div class="text-center space-y-2">
        <div class="inline-flex p-3.5 bg-gradient-to-br from-teal-500 to-emerald-600 rounded-2xl shadow-lg shadow-teal-500/30 text-white mb-2">
          <Stethoscope class="w-8 h-8" />
        </div>
        <h2 class="text-2xl font-black text-slate-900 tracking-tight">منصة العيادة الذكية</h2>
        <p class="text-xs text-slate-500 font-medium">تسجيل الدخول للطبيب وطاقم الاستقبال</p>
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
          class="p-3.5 bg-rose-50 border-2 border-rose-200 rounded-2xl text-rose-800 text-xs flex items-start justify-between gap-2 shadow-sm"
          role="alert"
        >
          <div class="flex items-start gap-2.5">
            <AlertCircle class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
            <div class="space-y-0.5">
              <p class="font-black text-rose-900">تعذر تسجيل الدخول</p>
              <p class="text-rose-700 leading-relaxed font-medium">{{ errorMessage }}</p>
            </div>
          </div>
          <button
            @click="errorMessage = ''"
            type="button"
            class="text-rose-400 hover:text-rose-700 p-1 rounded-lg hover:bg-rose-100 transition shrink-0 cursor-pointer"
            title="إغلاق التنبيه"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
      </transition>

      <!-- Form -->
      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">البريد الإلكتروني</label>
          <div class="relative">
            <input
              v-model="email"
              @input="clearError"
              type="email"
              required
              dir="ltr"
              placeholder="doctor@example.com"
              class="w-full pl-3 pr-10 py-2.5 text-xs bg-slate-50 rounded-xl border text-right font-medium transition focus:bg-white focus:outline-none focus:ring-2"
              :class="errorMessage ? 'border-rose-300 focus:ring-rose-400 bg-rose-50/30 text-rose-900' : 'border-slate-300 focus:ring-teal-500 text-slate-900'"
            />
            <Mail
              class="w-4 h-4 absolute right-3 top-3 transition"
              :class="errorMessage ? 'text-rose-400' : 'text-slate-400'"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">كلمة المرور</label>
          <div class="relative">
            <input
              v-model="password"
              @input="clearError"
              :type="showPassword ? 'text' : 'password'"
              required
              dir="ltr"
              placeholder="••••••••"
              class="w-full pl-10 pr-10 py-2.5 text-xs bg-slate-50 rounded-xl border text-right transition focus:bg-white focus:outline-none focus:ring-2 font-mono"
              :class="errorMessage ? 'border-rose-300 focus:ring-rose-400 bg-rose-50/30 text-rose-900' : 'border-slate-300 focus:ring-teal-500 text-slate-900'"
            />
            <Lock
              class="w-4 h-4 absolute right-3 top-3 transition"
              :class="errorMessage ? 'text-rose-400' : 'text-slate-400'"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute left-3 top-2.5 text-slate-400 hover:text-slate-600 transition p-0.5 cursor-pointer"
              :title="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
            >
              <EyeOff v-if="showPassword" class="w-4 h-4" />
              <Eye v-else class="w-4 h-4" />
            </button>
          </div>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white rounded-xl text-xs font-bold transition duration-200 shadow-lg shadow-teal-500/25 flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer"
        >
          <span v-if="loading" class="flex items-center gap-2">
            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>جارٍ التحقق...</span>
          </span>
          <span v-else class="flex items-center gap-2">
            <span>تسجيل الدخول</span>
            <ArrowLeft class="w-4 h-4" />
          </span>
        </button>
      </form>

      <!-- Quick Fill Helper for Demo -->
      <div class="pt-4 border-t border-slate-100 flex items-center justify-center gap-3 text-[11px] text-slate-400">
        <button
          @click="email = 'doctor@clinic.com'; password = 'password123'; clearError()"
          class="hover:text-teal-600 underline cursor-pointer"
        >
          حساب الطبيب التجريبي
        </button>
        <span>•</span>
        <button
          @click="email = 'reception1@clinic.com'; password = 'password123'; clearError()"
          class="hover:text-teal-600 underline cursor-pointer"
        >
          حساب الاستقبال التجريبي
        </button>
      </div>
    </div>
  </div>
</template>

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
