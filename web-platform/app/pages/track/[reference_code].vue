<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { useClinicBooking } from '../../composables/useClinicBooking';
import { Clock, Users, CheckCircle, MessageSquare, Download, AlertCircle, ArrowRight } from 'lucide-vue-next';

const route = useRoute();
const referenceCode = route.params.reference_code;
const { fetchLiveQueueStatus } = useClinicBooking();

const statusData = ref(null);
const loading = ref(true);
const error = ref(null);
let pollTimer = null;

const loadStatus = async () => {
  try {
    statusData.value = await fetchLiveQueueStatus(referenceCode);
  } catch (e) {
    error.value = 'تعذر العثور على بيانات الحجز بهذا الكود';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadStatus();
  pollTimer = setInterval(loadStatus, 10000);
});

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer);
});
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-right select-none" dir="rtl">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-30 shadow-sm">
      <div class="flex items-center gap-2">
        <nuxt-link to="/" class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition">
          <ArrowRight class="w-5 h-5" />
        </nuxt-link>
        <span class="text-sm font-black text-slate-900">متابعة الطابور المباشر</span>
      </div>

      <span class="text-xs font-mono font-bold text-teal-600 bg-teal-50 px-3 py-1 rounded-lg border border-teal-200">
        كود الحجز: #{{ referenceCode }}
      </span>
    </header>

    <!-- Main -->
    <main class="flex-1 p-6 max-w-xl mx-auto w-full space-y-6">
      <div v-if="loading" class="text-center py-16 text-slate-400 text-xs">
        جارٍ جلب حالة الطابور المباشرة...
      </div>

      <div v-else-if="error" class="bg-white rounded-3xl p-8 border border-slate-200 text-center space-y-3">
        <AlertCircle class="w-8 h-8 text-rose-500 mx-auto" />
        <h3 class="text-sm font-bold text-slate-800">{{ error }}</h3>
        <nuxt-link to="/" class="inline-block px-4 py-2 bg-teal-600 text-white rounded-xl text-xs font-bold">
          العودة للرئيسية
        </nuxt-link>
      </div>

      <div v-else-if="statusData" class="space-y-6">
        <!-- Patient Turn Card -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-slate-400">اسم المريض</p>
              <h2 class="text-lg font-bold text-slate-900">{{ statusData.appointment?.patient?.name }}</h2>
            </div>
            <div class="text-left">
              <p class="text-xs text-slate-400">رقم دورك</p>
              <span class="font-mono text-3xl font-black text-teal-600">#{{ statusData.appointment?.queue_number }}</span>
            </div>
          </div>

          <!-- Queue Status Indicator -->
          <div class="grid grid-cols-2 gap-3 pt-2">
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-center">
              <span class="text-[11px] font-bold text-emerald-800 block mb-1">الدور الحالي بالغرفة</span>
              <span class="font-mono font-black text-2xl text-emerald-700">
                {{ statusData.current_in_room_queue ? '#' + statusData.current_in_room_queue : 'بانتظار البدء' }}
              </span>
            </div>

            <div class="p-4 bg-blue-50 border border-blue-200 rounded-2xl text-center">
              <span class="text-[11px] font-bold text-blue-800 block mb-1">المتبقي أمامك</span>
              <span class="font-mono font-black text-2xl text-blue-700">
                {{ statusData.remaining_ahead || 0 }} مرضى
              </span>
            </div>
          </div>

          <!-- Status Alert -->
          <div v-if="statusData.appointment?.status === 'completed'" class="p-4 bg-slate-900 text-white rounded-2xl space-y-3">
            <div class="flex items-center gap-2 text-emerald-400 text-xs font-bold">
              <CheckCircle class="w-4 h-4" />
              <span>تم الانتهاء من الكشف الطبي بنجاح!</span>
            </div>
            <p class="text-[11px] text-slate-300">
              يمكنك تحميل الروشتة الإلكترونية المعتمدة الخاصة بك الآن:
            </p>
            <a
              v-if="statusData.prescription_token"
              :href="`http://localhost:8000/api/rx/${statusData.prescription_token}`"
              target="_blank"
              class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition"
            >
              <Download class="w-4 h-4" />
              <span>تحميل الروشتة الطبية (PDF)</span>
            </a>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
