<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { io } from 'socket.io-client';
import axios from 'axios';
import client from '../api/client';
import { Tv, Volume2, VolumeX, Stethoscope, Clock, Users, CheckCircle, ArrowRight } from 'lucide-vue-next';

const route = useRoute();
const tvCode = computed(() => route.params.tv_screen_code || 'TV1001');

const data = ref({
  clinic: null,
  current_patient: null,
  next_patient: null,
  upcoming: [],
});

const currentTime = ref('');
const currentDate = ref('');
const soundEnabled = ref(true);
let pollTimer = null;
let clockTimer = null;
let previousCurrentTurn = null;

// Synthesize pleasant two-tone medical chime using Web Audio API
const playChime = () => {
  if (!soundEnabled.value) return;
  try {
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return;
    const ctx = new AudioCtx();

    // First tone (D5 - 587.33 Hz)
    const osc1 = ctx.createOscillator();
    const gain1 = ctx.createGain();
    osc1.type = 'sine';
    osc1.frequency.setValueAtTime(587.33, ctx.currentTime);
    gain1.gain.setValueAtTime(0.3, ctx.currentTime);
    gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
    osc1.connect(gain1);
    gain1.connect(ctx.destination);
    osc1.start(ctx.currentTime);
    osc1.stop(ctx.currentTime + 0.6);

    // Second tone (A5 - 880 Hz) after 0.25s
    const osc2 = ctx.createOscillator();
    const gain2 = ctx.createGain();
    osc2.type = 'sine';
    osc2.frequency.setValueAtTime(880, ctx.currentTime + 0.25);
    gain2.gain.setValueAtTime(0.35, ctx.currentTime + 0.25);
    gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.9);
    osc2.connect(gain2);
    gain2.connect(ctx.destination);
    osc2.start(ctx.currentTime + 0.25);
    osc2.stop(ctx.currentTime + 0.9);
  } catch (e) {
    console.warn('Audio chime error:', e);
  }
};

const updateClock = () => {
  const now = new Date();
  currentTime.value = now.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  currentDate.value = now.toLocaleDateString('ar-EG', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
};

const fetchQueue = async () => {
  try {
    const res = await axios.get(`/api/public/tv/${tvCode.value || 'TV1001'}`);
    const nextData = res.data.data;

    // Check if current patient changed -> play chime
    if (nextData.current_patient?.queue_number && nextData.current_patient.queue_number !== previousCurrentTurn) {
      if (previousCurrentTurn !== null) {
        playChime();
      }
      previousCurrentTurn = nextData.current_patient.queue_number;
    }

    data.value = nextData;
  } catch (e) {
    console.error('TV Queue sync error:', e);
  }
};

let tvSocket = null;
const initTvSocket = () => {
  const socketUrl = import.meta.env.VITE_SOCKET_URL || 'http://localhost:3000';
  tvSocket = io(socketUrl, {
    transports: ['websocket', 'polling'],
  });

  tvSocket.on('connect', () => {
    tvSocket.emit('join_tv', { tvCode: tvCode.value || 'TV1001' });
  });

  tvSocket.on('queue:updated', () => {
    fetchQueue();
  });
};

onMounted(() => {
  updateClock();
  clockTimer = setInterval(updateClock, 1000);
  fetchQueue();
  initTvSocket();
  // Fallback heartbeat poll every 60s
  pollTimer = setInterval(fetchQueue, 60000);
});

onUnmounted(() => {
  if (clockTimer) clearInterval(clockTimer);
  if (pollTimer) clearInterval(pollTimer);
  if (tvSocket) tvSocket.disconnect();
});
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-white flex flex-col font-sans select-none overflow-hidden" dir="rtl">
    <!-- TV Header -->
    <header class="bg-slate-900/90 border-b border-slate-800 px-8 py-5 flex items-center justify-between backdrop-blur-md">
      <div class="flex items-center gap-4">
        <div class="p-3 bg-teal-500/10 text-teal-400 rounded-2xl border border-teal-500/20">
          <Stethoscope class="w-8 h-8" />
        </div>
        <div>
          <h1 class="text-2xl font-black text-white tracking-tight">
            {{ data.clinic?.doctor_name || 'عيادة النخبة التخصصية' }}
          </h1>
          <p class="text-sm font-semibold text-teal-400">
            {{ data.clinic?.specialty || 'استشاري' }} • {{ data.clinic?.name || '' }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-8">
        <!-- Live Clock -->
        <div class="text-left font-mono">
          <p class="text-2xl font-black text-emerald-400 tracking-wider">{{ currentTime }}</p>
          <p class="text-xs text-slate-400 font-medium">{{ currentDate }}</p>
        </div>

        <!-- Audio Toggle Button -->
        <button
          @click="soundEnabled = !soundEnabled"
          :class="[
            'p-3 rounded-2xl border transition',
            soundEnabled
              ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20'
              : 'bg-slate-800 border-slate-700 text-slate-500 hover:bg-slate-700'
          ]"
          :title="soundEnabled ? 'التنبيه الصوتي مفعّل' : 'التنبيه الصوتي مكتوم'"
        >
          <Volume2 v-if="soundEnabled" class="w-6 h-6" />
          <VolumeX v-else class="w-6 h-6" />
        </button>
      </div>
    </header>

    <!-- TV Main Display Grid -->
    <main class="flex-1 p-8 grid grid-cols-12 gap-8 items-stretch">
      <!-- Left Column: Upcoming Waiting Queue (Col 4) -->
      <div class="col-span-12 lg:col-span-4 bg-slate-900/60 border border-slate-800 rounded-3xl p-6 flex flex-col justify-between backdrop-blur-sm">
        <div>
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-800">
            <h3 class="text-lg font-bold text-slate-300 flex items-center gap-2">
              <Users class="w-5 h-5 text-indigo-400" />
              <span>قائمة الانتظار القادمة</span>
            </h3>
            <span class="text-xs px-2.5 py-1 rounded-full bg-slate-800 text-slate-400 font-mono">
              {{ data.upcoming?.length || 0 }} في الانتظار
            </span>
          </div>

          <!-- List of Next Turn Cards -->
          <div v-if="!data.upcoming || data.upcoming.length === 0" class="py-12 text-center text-slate-500 text-sm">
            لا توجد حالات إضافية في صالة الانتظار
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="item in data.upcoming"
              :key="item.queue_number"
              class="p-4 bg-slate-800/60 border border-slate-700/60 rounded-2xl flex items-center justify-between transition hover:border-slate-600"
            >
              <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-slate-700 text-slate-200 font-mono font-black text-lg flex items-center justify-center">
                  {{ item.queue_number }}
                </span>
                <div>
                  <p class="font-bold text-white text-base">{{ item.patient_name }}</p>
                  <p class="text-xs text-slate-400 font-mono">#{{ item.reference_code }}</p>
                </div>
              </div>
              <span class="text-xs px-2.5 py-1 rounded-lg bg-indigo-500/10 text-indigo-400 font-bold">
                {{ item.appointment_type === 'followup' ? 'استشارة' : 'كشف' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Clinic TV Notice -->
        <div class="pt-4 border-t border-slate-800/80 text-center text-xs text-slate-500">
          يرجى التواجد بالقرب من صالة الانتظار عند اقتراب رقم دورك
        </div>
      </div>

      <!-- Right Column: Hero Focus (Col 8) -->
      <div class="col-span-12 lg:col-span-8 flex flex-col gap-6">
        <!-- Big Active Turn Card (#N) -->
        <div class="flex-1 bg-gradient-to-br from-emerald-950/80 via-slate-900 to-teal-950/80 border-2 border-emerald-500/50 rounded-3xl p-8 flex flex-col justify-between shadow-2xl shadow-emerald-900/30 relative overflow-hidden">
          <!-- Ambient Glow Effect -->
          <div class="absolute -top-24 -right-24 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="relative flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500"></span>
              </span>
              <span class="text-base font-bold text-emerald-400 tracking-wide uppercase">
                الدور الحالي داخل غرفة الكشف
              </span>
            </div>

            <span class="text-sm font-bold text-slate-400">تفضل بالدخول</span>
          </div>

          <!-- Giant Turn Number -->
          <div class="text-center py-6">
            <div v-if="data.current_patient" class="space-y-4">
              <div class="inline-flex items-center justify-center min-w-[160px] h-36 px-10 bg-emerald-500 text-slate-950 font-mono font-black text-8xl rounded-3xl shadow-2xl shadow-emerald-500/40">
                #{{ data.current_patient.queue_number }}
              </div>
              <h2 class="text-4xl font-black text-white tracking-tight">
                {{ data.current_patient.patient_name }}
              </h2>
              <p class="text-lg text-emerald-300 font-mono font-semibold">
                كود المريض: #{{ data.current_patient.reference_code }}
              </p>
            </div>

            <div v-else class="space-y-3 py-8">
              <p class="text-3xl font-bold text-slate-500">لا يوجد مريض حالياً داخل الغرفة</p>
              <p class="text-sm text-slate-600">سيتم استدعاء المريض التالي قريباً</p>
            </div>
          </div>

          <div class="flex items-center justify-between text-xs text-slate-400 border-t border-emerald-500/20 pt-4">
            <span>نظام الانتظار الآلي السحابي</span>
            <span>يرجى الدخول بمجرد سماع نغمة التنبيه</span>
          </div>
        </div>

        <!-- Next Turn Card (#N+1) -->
        <div class="bg-slate-900/80 border border-amber-500/40 rounded-3xl p-6 flex items-center justify-between backdrop-blur-md">
          <div class="flex items-center gap-4">
            <span class="w-14 h-14 rounded-2xl bg-amber-500/20 border border-amber-500/30 text-amber-400 font-mono font-black text-2xl flex items-center justify-center">
              {{ data.next_patient?.queue_number ? '#' + data.next_patient.queue_number : '--' }}
            </span>
            <div>
              <p class="text-xs font-bold text-amber-400 mb-0.5">المريض التالي (يرجى الاستعداد):</p>
              <p class="text-2xl font-black text-white">
                {{ data.next_patient?.patient_name || 'لا يوجد' }}
              </p>
            </div>
          </div>

          <div v-if="data.next_patient" class="text-left font-mono">
            <span class="text-xs text-slate-400">الكود: </span>
            <span class="text-sm font-bold text-slate-200">#{{ data.next_patient.reference_code }}</span>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
