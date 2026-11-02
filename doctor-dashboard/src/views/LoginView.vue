<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { Stethoscope, Lock, Mail, ArrowLeft, AlertCircle } from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('doctor@clinic.com');
const password = ref('password123');
const errorMessage = ref('');
const loading = ref(false);

const handleLogin = async () => {
  errorMessage.value = '';
  loading.value = true;
  try {
    await authStore.login(email.value, password.value);
    if (authStore.user?.role === 'doctor') {
      router.push('/workspace');
    } else {
      router.push('/reception');
    }
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'فشل تسجيل الدخول. تحقق من البيانات.';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-teal-950 flex items-center justify-center p-4 font-sans text-right" dir="rtl">
    <div class="w-full max-w-md bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8 space-y-6">
      <!-- Logo & Title -->
      <div class="text-center space-y-2">
        <div class="inline-flex p-3.5 bg-gradient-to-br from-teal-500 to-emerald-600 rounded-2xl shadow-lg shadow-teal-500/30 text-white mb-2">
          <Stethoscope class="w-8 h-8" />
        </div>
        <h2 class="text-2xl font-black text-slate-900 tracking-tight">منصة العيادة الذكية</h2>
        <p class="text-xs text-slate-500 font-medium">تسجيل الدخول للطبيب وطاقم الاستقبال</p>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs flex items-center gap-2">
        <AlertCircle class="w-4 h-4 shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">البريد الإلكتروني</label>
          <div class="relative">
            <input
              v-model="email"
              type="email"
              required
              dir="ltr"
              placeholder="doctor@example.com"
              class="w-full pl-3 pr-10 py-2.5 text-xs bg-slate-50 rounded-xl border border-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-500 text-right font-medium"
            />
            <Mail class="w-4 h-4 text-slate-400 absolute right-3 top-3" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">كلمة المرور</label>
          <div class="relative">
            <input
              v-model="password"
              type="password"
              required
              dir="ltr"
              placeholder="••••••••"
              class="w-full pl-3 pr-10 py-2.5 text-xs bg-slate-50 rounded-xl border border-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-500 text-right"
            />
            <Lock class="w-4 h-4 text-slate-400 absolute right-3 top-3" />
          </div>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white rounded-xl text-xs font-bold transition duration-200 shadow-lg shadow-teal-500/25 flex items-center justify-center gap-2 disabled:opacity-50"
        >
          <span>{{ loading ? 'جارٍ التحقق...' : 'تسجيل الدخول' }}</span>
          <ArrowLeft class="w-4 h-4" />
        </button>
      </form>

      <!-- Quick Fill Helper for Demo -->
      <div class="pt-4 border-t border-slate-100 flex items-center justify-center gap-3 text-[11px] text-slate-400">
        <button
          @click="email = 'doctor@clinic.com'; password = 'password123'"
          class="hover:text-teal-600 underline"
        >
          حساب الطبيب التجريبي
        </button>
        <span>•</span>
        <button
          @click="email = 'reception1@clinic.com'; password = 'password123'"
          class="hover:text-teal-600 underline"
        >
          حساب الاستقبال التجريبي
        </button>
      </div>
    </div>
  </div>
</template>
