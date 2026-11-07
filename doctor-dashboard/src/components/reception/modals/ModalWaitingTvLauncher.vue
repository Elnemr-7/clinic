<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '../../../stores/auth';
import { Tv, Monitor, Copy, ExternalLink, X, Check, Cast } from 'lucide-vue-next';

const props = defineProps({
  isOpen: { type: Boolean, default: true },
  clinicId: [String, Number],
  tvCode: String,
  clinicName: String,
});

const emit = defineEmits(['close']);
const copied = ref(false);
const launchingTauri = ref(false);
const authStore = useAuthStore();

const effectiveTvCode = computed(() => {
  if (props.tvCode) return props.tvCode;
  const clinic = authStore.user?.tenant?.clinics?.find(c => c.id === Number(props.clinicId)) || authStore.clinic;
  return clinic?.tv_screen_code || 'TV1001';
});

const tvUrl = computed(() => {
  const origin = window.location.origin;
  return `${origin}/tv/${effectiveTvCode.value}`;
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
        tvCode: effectiveTvCode.value,
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
  <Teleport to="body">
    <div v-if="isOpen" @click.self="$emit('close')" class="fixed inset-0 z-50 overflow-y-auto bg-surface-overlay/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-surface-card border border-line-subtle rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden text-right text-content-primary" dir="rtl">
        <!-- Header -->
        <div class="px-6 py-4 bg-brand text-content-inverse flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-white/10 rounded-xl">
              <Tv class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-xl font-bold">شاشة صالة الانتظار الذكية (TV)</h3>
              <p class="text-xs text-brand-subtle/80 font-medium">{{ clinicName || 'شاشة العيادة' }}</p>
            </div>
          </div>
          <button @click="$emit('close')" class="p-2 text-content-inverse/80 hover:text-content-inverse hover:bg-white/10 rounded-xl transition cursor-pointer">
            <X class="w-6 h-6" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-6 bg-surface-app">
          <!-- 6-digit PIN Box -->
          <div class="p-5 bg-surface-card border border-brand-border rounded-2xl text-center">
            <p class="text-xs font-semibold text-content-secondary mb-2">كود ربط شاشة الانتظار (TV PIN Code):</p>
            <div class="inline-block px-6 py-2 bg-surface-elevated text-brand font-mono text-3xl font-black rounded-xl tracking-widest shadow-inner border border-line-subtle">
              {{ effectiveTvCode || '------' }}
            </div>
            <p class="text-[11px] text-content-muted mt-2">
              يمكنك إدخال هذا الكود على متصفح شاشة التلفاز الذكية مباشرة
            </p>
          </div>

          <!-- Direct URL Box -->
          <div>
            <label class="block text-xs font-bold text-content-secondary mb-1">رابط الشاشة المباشر</label>
            <div class="flex items-center gap-2">
              <input
                :value="tvUrl"
                readonly
                dir="ltr"
                class="w-full px-3 py-2 text-xs bg-surface-input rounded-xl border border-line-subtle font-mono text-content-primary focus:outline-none"
              />
              <button
                @click="copyLink"
                type="button"
                class="px-3 py-2 bg-surface-elevated hover:bg-surface-card border border-line-subtle rounded-xl text-content-primary transition flex items-center gap-1 shrink-0 text-xs font-bold cursor-pointer"
              >
                <Check v-if="copied" class="w-4 h-4 text-status-success" />
                <Copy v-else class="w-4 h-4" />
                <span>{{ copied ? 'تم النسخ' : 'نسخ' }}</span>
              </button>
            </div>
          </div>

          <!-- Dual Monitor HDMI Button -->
          <div class="p-4 bg-surface-card border border-line-subtle rounded-2xl space-y-3">
            <div class="flex items-center gap-2 text-brand font-bold text-sm">
              <Monitor class="w-5 h-5" />
              <span>العرض على الشاشة الإضافية (HDMI / كابل الشاشة الثاني)</span>
            </div>
            <p class="text-xs text-content-secondary">
              إذا كان جهاز الكمبيوتر موصولاً بشاشة تلفزيون عبر HDMI في صالة الانتظار، انقر أدناه لنقل الشاشة تلقائياً بالوضع الكامل ملء الشاشة.
            </p>
            <button
              @click="launchOnSecondaryMonitor"
              :disabled="launchingTauri"
              type="button"
              class="w-full py-3 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 shadow-md cursor-pointer disabled:opacity-50"
            >
              <Cast class="w-4 h-4" />
              <span>فتح الشاشة على الشاشة الخارجية (ملء الشاشة)</span>
            </button>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-surface-card border-t border-line-subtle flex items-center justify-between">
          <button
            @click="$emit('close')"
            type="button"
            class="px-4 py-2 text-xs font-semibold text-content-secondary hover:bg-surface-elevated rounded-xl transition cursor-pointer"
          >
            إغلاق
          </button>

          <button
            @click="openInBrowser"
            type="button"
            class="px-4 py-2 text-xs font-bold text-brand hover:bg-surface-elevated rounded-xl transition flex items-center gap-1 cursor-pointer"
          >
            <ExternalLink class="w-4 h-4" />
            <span>فتح في نافذة متصفح جديدة</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
