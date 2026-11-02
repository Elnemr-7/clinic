<template>
  <div class="space-y-6 text-right select-none font-sans" dir="rtl">
    <!-- Header -->
    <div class="border-b border-line-subtle pb-4">
      <h2 class="text-base font-black text-content-primary">4. إعدادات الموقع الإلكتروني وقناة الواتساب</h2>
      <p class="text-xs text-content-muted mt-0.5">رابط الحجز المباشر لمرضى العيادة وربط جهاز الواتساب لإرسال الروشتات</p>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Website & Custom Domain Card -->
      <div class="bg-surface-elevated border border-line-subtle rounded-2xl p-5 shadow-xs space-y-4">
        <div class="flex items-center gap-3">
          <div class="p-2.5 bg-brand-subtle text-brand border border-brand-border rounded-xl">
            <Globe class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-sm font-black text-content-primary">موقع العيادة والحجز المباشر</h3>
            <p class="text-[11px] text-content-muted">رابط صفحة الحجز لمرضاك على الإنترنت</p>
          </div>
        </div>

        <div class="p-3 bg-surface-card border border-line-subtle rounded-xl text-xs space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-content-secondary">النطاق الفرعي:</span>
            <span class="font-mono font-bold text-brand" dir="ltr">
              {{ settingsStore.settings?.tenant?.subdomain || 'clinic' }}.clinic.com
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-content-secondary">النطاق المخصص (Custom Domain):</span>
            <span class="font-mono font-bold text-content-primary" dir="ltr">
              {{ settingsStore.settings?.tenant?.custom_domain || 'غير مفعل' }}
            </span>
          </div>
        </div>

        <p class="text-[11px] text-content-muted leading-relaxed">
          يمكنك ربط دومين خاص بعيادتك (مثل: <code class="font-mono bg-surface-card px-1 py-0.5 rounded text-brand">drahmed.com</code>) لتوجيه المرضى مباشرة إلى موقعك.
        </p>
      </div>

      <!-- WhatsApp Pairing Card -->
      <div class="bg-surface-elevated border border-line-subtle rounded-2xl p-5 shadow-xs space-y-4">
        <div class="flex items-center gap-3">
          <div class="p-2.5 bg-status-success-bg text-status-success border border-status-success-border rounded-xl">
            <Smartphone class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-sm font-black text-content-primary">قناة الواتساب المباشرة</h3>
            <p class="text-[11px] text-content-muted">إرسال الروشتات الرقمية وإشعارات الطابور التلقائية</p>
          </div>
        </div>

        <div class="space-y-2.5">
          <label class="block text-xs font-bold text-content-primary">رقم هاتف الواتساب المربوط</label>
          <div class="flex items-center gap-2">
            <input
              v-model="waPhone"
              type="tel"
              placeholder="2010XXXXXXXX"
              class="flex-1 px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-mono"
            />
            <button
              type="button"
              @click="handleGeneratePairingCode"
              :disabled="loadingPairing"
              class="px-4 py-2 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold transition disabled:opacity-50 shadow-xs cursor-pointer"
            >
              {{ loadingPairing ? 'جارٍ التوليد...' : 'توليد كود الربط' }}
            </button>
          </div>
        </div>

        <div v-if="pairingCode" class="p-3 bg-surface-card border-2 border-brand-border rounded-xl text-center space-y-1">
          <p class="text-[11px] text-content-muted">أدخل هذا الكود في جهاز الواتساب الآن:</p>
          <p class="text-xl font-mono font-black text-brand tracking-widest">{{ pairingCode }}</p>
          <button
            type="button"
            @click="copyPairingCode"
            class="text-[11px] text-content-secondary hover:underline font-bold"
          >
            {{ copiedCode ? '✅ تم النسخ' : '📋 نسخ الكود' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useSettingsStore } from '../../stores/settings';
import alerts from '../../utils/alerts';
import { Globe, Smartphone } from 'lucide-vue-next';

const settingsStore = useSettingsStore();

const waPhone = ref('201001234567');
const pairingCode = ref('');
const loadingPairing = ref(false);
const copiedCode = ref(false);

onMounted(async () => {
  await settingsStore.fetchAll();
});

const handleGeneratePairingCode = async () => {
  if (!waPhone.value) return;
  loadingPairing.value = true;
  try {
    const res = await settingsStore.requestWhatsAppPairing(waPhone.value);
    pairingCode.value = res.pairing_code || '1234-5678';
    alerts.success('تم توليد كود الربط', 'أدخل هذا الكود في تطبيق واتساب بهاتفك الآن لربط العيادة');
  } catch (e) {
    alerts.error('فشل توليد كود الربط', e.response?.data?.message || 'تأكد من رقم الهاتف وتشغيل خدمة الواتساب');
  } finally {
    loadingPairing.value = false;
  }
};

const copyPairingCode = async () => {
  if (!pairingCode.value) return;
  await navigator.clipboard.writeText(pairingCode.value);
  copiedCode.value = true;
  alerts.toast('تم نسخ الكود للحافظة');
  setTimeout(() => { copiedCode.value = false; }, 2000);
};
</script>
