<script setup>
import { ref, computed } from 'vue';
import { Tv, Monitor, Copy, ExternalLink, X, Check, Cast } from 'lucide-vue-next';

const props = defineProps({
  isOpen: Boolean,
  tvCode: String,
  clinicName: String,
});

const emit = defineEmits(['close']);
const copied = ref(false);
const launchingTauri = ref(false);

const tvUrl = computed(() => {
  const origin = window.location.origin;
  return `${origin}/#/tv/${props.tvCode || '123456'}`;
});

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(tvUrl.value);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
  } catch (e) {
    console.error(e);
  }
};

const openInBrowser = () => {
  window.open(tvUrl.value, '_blank');
};

const launchOnSecondaryMonitor = async () => {
  launchingTauri.value = true;
  try {
    if (window.__TAURI__ && window.__TAURI__.core) {
      await window.__TAURI__.core.invoke('open_tv_on_secondary_monitor', {
        tvCode: props.tvCode || '123456',
      });
    } else {
      // In web browser fallback: open popup window
      window.open(tvUrl.value, 'WaitingTv', 'width=1280,height=720,menubar=no,toolbar=no');
    }
  } catch (e) {
    console.error('Tauri launch error:', e);
    // Fallback
    window.open(tvUrl.value, 'WaitingTv', 'width=1280,height=720');
  } finally {
    launchingTauri.value = false;
  }
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden text-right" dir="rtl">
      <!-- Header -->
      <div class="px-6 py-4 bg-gradient-to-r from-purple-700 to-indigo-800 text-white flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-white/10 rounded-xl">
            <Tv class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-xl font-bold">شاشة صالة الانتظار الذكية (TV)</h3>
            <p class="text-xs text-purple-200 font-medium">{{ clinicName || 'شاشة العيادة' }}</p>
          </div>
        </div>
        <button @click="$emit('close')" class="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition">
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 space-y-6">
        <!-- 6-digit PIN Box -->
        <div class="p-5 bg-gradient-to-br from-purple-50 to-indigo-50 border border-purple-200 rounded-2xl text-center">
          <p class="text-xs font-semibold text-slate-500 mb-2">كود ربط شاشة الانتظار (TV PIN Code):</p>
          <div class="inline-block px-6 py-2 bg-slate-900 text-emerald-400 font-mono text-3xl font-black rounded-xl tracking-widest shadow-inner">
            {{ tvCode || '------' }}
          </div>
          <p class="text-[11px] text-slate-500 mt-2">
            يمكنك إدخال هذا الكود على متصفح شاشة التلفاز الذكية مباشرة
          </p>
        </div>

        <!-- Direct URL Box -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">رابط الشاشة المباشر</label>
          <div class="flex items-center gap-2">
            <input
              :value="tvUrl"
              readonly
              dir="ltr"
              class="w-full px-3 py-2 text-xs bg-slate-100 rounded-xl border border-slate-300 font-mono text-slate-700"
            />
            <button
              @click="copyLink"
              type="button"
              class="px-3 py-2 bg-slate-200 hover:bg-slate-300 rounded-xl text-slate-700 transition flex items-center gap-1 shrink-0 text-xs font-bold"
            >
              <Check v-if="copied" class="w-4 h-4 text-emerald-600" />
              <Copy v-else class="w-4 h-4" />
              <span>{{ copied ? 'تم النسخ' : 'نسخ' }}</span>
            </button>
          </div>
        </div>

        <!-- Dual Monitor HDMI Button -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
          <div class="flex items-center gap-2 text-indigo-700 font-bold text-sm">
            <Monitor class="w-5 h-5" />
            <span>العرض على الشاشة الإضافية (HDMI / كابل الشاشة الثاني)</span>
          </div>
          <p class="text-xs text-slate-600">
            إذا كان جهاز الكمبيوتر موصولاً بشاشة تلفزيون عبر HDMI في صالة الانتظار، انقر أدناه لنقل الشاشة تلقائياً بالوضع الكامل ملء الشاشة.
          </p>
          <button
            @click="launchOnSecondaryMonitor"
            :disabled="launchingTauri"
            type="button"
            class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 shadow-md shadow-indigo-500/20"
          >
            <Cast class="w-4 h-4" />
            <span>فتح الشاشة على الشاشة الخارجية (ملء الشاشة)</span>
          </button>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
        <button
          @click="$emit('close')"
          type="button"
          class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition"
        >
          إغلاق
        </button>

        <button
          @click="openInBrowser"
          type="button"
          class="px-4 py-2 text-xs font-bold text-indigo-600 hover:bg-indigo-50 rounded-xl transition flex items-center gap-1"
        >
          <ExternalLink class="w-4 h-4" />
          <span>فتح في نافذة متصفح جديدة</span>
        </button>
      </div>
    </div>
  </div>
</template>
