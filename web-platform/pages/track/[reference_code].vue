<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { io } from 'socket.io-client';
import { useClinicBooking } from '../../composables/useClinicBooking';
import { Clock, Users, CheckCircle, MessageSquare, Download, AlertCircle, ArrowRight } from 'lucide-vue-next';

const route = useRoute();
const config = useRuntimeConfig();
const referenceCode = route.params.reference_code;
const { fetchLiveQueueStatus } = useClinicBooking();

const statusData = ref(null);
const loading = ref(true);
const error = ref(null);
let pollTimer = null;
let trackSocket = null;

const loadStatus = async () => {
  try {
    statusData.value = await fetchLiveQueueStatus(referenceCode);
  } catch (e) {
    error.value = 'تعذر العثور على بيانات الحجز بهذا الكود';
  } finally {
    loading.value = false;
  }
};

const initTrackSocket = () => {
  const socketUrl = (config.public.apiBase || 'http://localhost:8000/api').replace('/api', '').replace(':8000', ':3000');
  trackSocket = io(socketUrl, {
    transports: ['websocket', 'polling'],
  });

  trackSocket.on('connect', () => {
    trackSocket.emit('join_track', { referenceCode });
  });

  trackSocket.on('queue:updated', () => {
    loadStatus();
  });
};

onMounted(() => {
  loadStatus();
  initTrackSocket();
  pollTimer = setInterval(loadStatus, 60000);
});

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer);
  if (trackSocket) trackSocket.disconnect();
});
</script>

<template>
  <div class="min-h-screen bg-surface-app flex flex-col font-sans text-right select-none" dir="rtl">
    <!-- Header -->
    <header class="bg-surface-card border-b border-line-subtle px-6 py-4 flex items-center justify-between sticky top-0 z-30 shadow-xs">
      <div class="flex items-center gap-2">
        <nuxt-link to="/" class="p-2 text-content-muted hover:text-content-primary hover:bg-surface-elevated rounded-xl transition">
          <ArrowRight class="w-5 h-5" />
        </nuxt-link>
        <span class="text-sm font-black text-content-primary">متابعة الطابور المباشر</span>
      </div>

      <span class="text-xs font-mono font-bold text-brand bg-brand-subtle px-3 py-1 rounded-lg border border-brand-border">
        كود الحجز: #{{ referenceCode }}
      </span>
    </header>

    <!-- Main -->
    <main class="flex-1 p-6 max-w-xl mx-auto w-full space-y-6">
      <div v-if="loading" class="text-center py-16 text-content-muted text-xs">
        جارٍ جلب حالة الطابور المباشرة...
      </div>

      <div v-else-if="error" class="bg-surface-card rounded-3xl p-8 border border-line-subtle text-center space-y-3 shadow-xs">
        <AlertCircle class="w-8 h-8 text-status-danger mx-auto" />
        <h3 class="text-sm font-bold text-content-primary">{{ error }}</h3>
        <nuxt-link to="/" class="inline-block px-4 py-2 bg-brand text-content-inverse rounded-xl text-xs font-bold">
          العودة للرئيسية
        </nuxt-link>
      </div>

      <div v-else-if="statusData" class="space-y-6">
        <!-- Patient Turn Card -->
        <div class="bg-surface-card rounded-3xl p-6 border border-line-subtle shadow-xs space-y-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-content-muted">اسم المريض</p>
              <h2 class="text-lg font-bold text-content-primary">{{ statusData.appointment?.patient?.name }}</h2>
            </div>
            <div class="text-left">
              <p class="text-xs text-content-muted">رقم دورك</p>
              <span class="font-mono text-3xl font-black text-brand">#{{ statusData.appointment?.queue_number }}</span>
            </div>
          </div>

          <!-- Queue Status Indicator -->
          <div class="grid grid-cols-2 gap-3 pt-2">
            <div class="p-4 bg-status-success-bg border border-status-success-border rounded-2xl text-center">
              <span class="text-[11px] font-bold text-status-success block mb-1">الدور الحالي بالغرفة</span>
              <span class="font-mono font-black text-2xl text-status-success">
                {{ statusData.current_in_room_queue ? '#' + statusData.current_in_room_queue : 'بانتظار البدء' }}
              </span>
            </div>

            <div class="p-4 bg-surface-elevated border border-line-subtle rounded-2xl text-center">
              <span class="text-[11px] font-bold text-content-secondary block mb-1">المتبقي أمامك</span>
              <span class="font-mono font-black text-2xl text-content-primary">
                {{ statusData.remaining_ahead || 0 }} مرضى
              </span>
            </div>
          </div>

          <!-- Status Alert -->
          <div v-if="statusData.appointment?.status === 'completed'" class="p-4 bg-surface-header text-content-inverse rounded-2xl space-y-3">
            <div class="flex items-center gap-2 text-status-success text-xs font-bold">
              <CheckCircle class="w-4 h-4" />
              <span>تم الانتهاء من الكشف الطبي بنجاح!</span>
            </div>
            <p class="text-[11px] text-content-muted">
              يمكنك تحميل الروشتة الإلكترونية المعتمدة الخاصة بك الآن:
            </p>
            <a
              v-if="statusData.prescription_token"
              :href="`${config.public.apiBase || 'http://localhost:8000/api'}/rx/${statusData.prescription_token}`"
              target="_blank"
              class="w-full py-2.5 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition"
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
