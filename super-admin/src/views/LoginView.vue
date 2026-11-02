<template>
  <div class="min-h-screen bg-slate-950 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl relative overflow-hidden">
      <div class="absolute -top-24 -right-24 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-cyan-500/10 rounded-full blur-3xl"></div>

      <div class="text-center mb-8 relative">
        <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-3xl mx-auto mb-4 shadow-inner">
          👑
        </div>
        <h1 class="text-2xl font-bold text-white mb-2">بوابة الإدارة العامة</h1>
        <p class="text-xs text-slate-400">سجل الدخول بحساب مالك المنصة (Super Admin)</p>
      </div>

      <div v-if="errorMessage" class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs flex items-center gap-2">
        <span>⚠️</span>
        <span>{{ errorMessage }}</span>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-5 relative">
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-2">البريد الإلكتروني للـ Super Admin</label>
          <input
            v-model="email"
            type="email"
            required
            placeholder="admin@platform.com"
            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-emerald-500 transition"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-2">كلمة المرور</label>
          <input
            v-model="password"
            type="password"
            required
            placeholder="••••••••"
            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-emerald-500 transition"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3.5 rounded-xl text-sm transition shadow-lg shadow-emerald-900/30 flex items-center justify-center gap-2 disabled:opacity-50"
        >
          <span v-if="loading">جاري التحقق...</span>
          <span v-else>دخول اللوحة الرئيسية</span>
        </button>

        <p class="text-[11px] text-center text-slate-500 mt-4">
          تنبيه: محاولات الدخول بحسابات الأطباء أو السكرتارية سيتم رفضها تلقائياً.
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('admin@platform.com');
const password = ref('password123');
const loading = ref(false);
const errorMessage = ref('');

const handleLogin = async () => {
  loading.value = true;
  errorMessage.value = '';

  const success = await authStore.login(email.value, password.value);
  loading.value = false;

  if (success) {
    router.push('/');
  } else {
    errorMessage.value = authStore.error || 'فشل تسجيل الدخول';
  }
};

onMounted(() => {
  // Direct access for super admin without retyping credentials
  handleLogin();
});
</script>
