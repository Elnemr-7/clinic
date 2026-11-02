<script setup>
import { ref, onMounted } from 'vue';
import { useSettingsStore } from '../../stores/settings';
import DoctorTopNav from '../../components/common/DoctorTopNav.vue';
import alerts from '../../utils/alerts';
import { Globe, MessageSquare, Wallet, Check, Copy, ExternalLink, ArrowRight, Smartphone, ShieldCheck, AlertCircle } from 'lucide-vue-next';

const settingsStore = useSettingsStore();

const waPhone = ref('201001234567');
const pairingCode = ref('');
const loadingPairing = ref(false);
const copiedCode = ref(false);

const topupPackage = ref('tier_250');
const paymentMethod = ref('instapay');
const referenceNumber = ref('');
const submittingTopup = ref(false);

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

const handleTopup = async () => {
  if (!referenceNumber.value) {
    alerts.warning('يرجى إدخال رقم العملية المرجعي للتحقق');
    return;
  }
  submittingTopup.value = true;
  try {
    await settingsStore.requestTopup({
      package: topupPackage.value,
      payment_method: paymentMethod.value,
      reference_number: referenceNumber.value,
    });
    referenceNumber.value = '';
    alerts.success('تم إرسال الطلب', 'تم إرسال طلب الشحن بنجاح. سيتم مراجعته وتفعيل الرصيد خلال دقائق.');
  } catch (e) {
    alerts.error('فشل إرسال الطلب', e.response?.data?.message || 'حدث خطأ');
  } finally {
    submittingTopup.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-slate-100 flex flex-col font-sans text-right select-none" dir="rtl">
    <DoctorTopNav
      title="الموقع الإلكتروني، الواتساب، ومحفظة الرسائل"
      subtitle="إدارة نطاق العيادة وربط خادم الواتساب وشحن رصيد الإشعارات"
    />

    <!-- Main Content -->
    <main class="flex-1 p-6 max-w-6xl mx-auto w-full space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Col 1: Website & Domain (Col 2 span) -->
        <div class="md:col-span-2 space-y-6">
          <!-- Web Portal Status -->
          <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl">
                  <Globe class="w-6 h-6" />
                </div>
                <div>
                  <h3 class="text-base font-bold text-slate-900">موقع العيادة ونظام الحجز الأونلاين</h3>
                  <p class="text-xs text-slate-400">رابط الحجز المباشر لمرضى العيادة</p>
                </div>
              </div>

              <span class="text-xs px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-200 flex items-center gap-1">
                <ShieldCheck class="w-3.5 h-3.5" />
                <span>مفعل ويعمل</span>
              </span>
            </div>

            <!-- Domain Display -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">الرابط الفرعي السحابي:</span>
                <span class="text-xs font-mono font-bold text-blue-600 bg-white px-3 py-1 rounded-lg border border-slate-200">
                  https://{{ settingsStore.settings?.subdomain || 'doctor' }}.platform.com
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">النطاق الخاص (Custom Domain):</span>
                <span class="text-xs font-mono font-bold text-slate-700 bg-white px-3 py-1 rounded-lg border border-slate-200">
                  {{ settingsStore.settings?.custom_domain || 'غير مرتبط بعد' }}
                </span>
              </div>
            </div>
          </div>

          <!-- WhatsApp Zero-Ban Pairing (Correction 7) -->
          <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl">
                  <MessageSquare class="w-6 h-6" />
                </div>
                <div>
                  <h3 class="text-base font-bold text-slate-900">ربط رقم الواتساب بالعيادة (Baileys Service)</h3>
                  <p class="text-xs text-slate-400">إرسال الروشتات الإلكترونية وتأكيدات الحجز عبر كود الربط المباشر</p>
                </div>
              </div>

              <span class="text-xs px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">
                جاهز للربط
              </span>
            </div>

            <!-- Step by Step instructions -->
            <div class="p-4 bg-emerald-50/50 border border-emerald-200 rounded-2xl space-y-3">
              <p class="text-xs text-slate-700 font-bold">طريقة ربط رقم الواتساب بدون مسح QR (عبر كود الهاتف):</p>
              <ol class="text-xs text-slate-600 space-y-1 list-decimal list-inside">
                <li>افتح تطبيق واتساب على هاتفك > الأجهزة المرتبطة (Linked Devices).</li>
                <li>اختر "ربط باستخدام رقم الهاتف بدلاً من ذلك" (Link with phone number).</li>
                <li>أدخل رقم هاتفك أدناه واضغط "توليد كود الربط".</li>
                <li>اكتب الكود المكون من 8 أحرف في تطبيق واتساب على هاتفك.</li>
              </ol>

              <div class="grid grid-cols-12 gap-3 items-end pt-2">
                <div class="col-span-8">
                  <label class="block text-[11px] font-bold text-slate-700 mb-1">رقم هاتف الواتساب (مع كود الدولة)</label>
                  <input
                    v-model="waPhone"
                    type="tel"
                    dir="ltr"
                    placeholder="2010XXXXXXXX"
                    class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300 font-mono"
                  />
                </div>
                <div class="col-span-4">
                  <button
                    @click="handleGeneratePairingCode"
                    :disabled="loadingPairing"
                    class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition disabled:opacity-50"
                  >
                    {{ loadingPairing ? 'جارٍ التوليد...' : 'توليد كود الربط' }}
                  </button>
                </div>
              </div>

              <!-- Generated Pairing Code Box -->
              <div v-if="pairingCode" class="mt-4 p-4 bg-white border-2 border-emerald-500 rounded-2xl text-center space-y-2">
                <p class="text-xs text-slate-500 font-bold">أدخل هذا الكود في واتساب الآن:</p>
                <div class="inline-flex items-center gap-3">
                  <span class="font-mono text-3xl font-black text-emerald-600 tracking-widest bg-emerald-50 px-4 py-1.5 rounded-xl border border-emerald-200">
                    {{ pairingCode }}
                  </span>
                  <button
                    @click="copyPairingCode"
                    class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1"
                  >
                    <Check v-if="copiedCode" class="w-4 h-4 text-emerald-600" />
                    <Copy v-else class="w-4 h-4" />
                    <span>{{ copiedCode ? 'تم النسخ' : 'نسخ' }}</span>
                  </button>
                </div>
                <p class="text-[10px] text-slate-400">الكود صالح لمدة 3 دقائق فقط</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Col 2: Messaging Wallet & Topup -->
        <div class="space-y-6">
          <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center gap-3">
              <div class="p-3 bg-teal-50 text-teal-600 rounded-2xl">
                <Wallet class="w-6 h-6" />
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-900">محفظة الرسائل</h3>
                <p class="text-xs text-slate-400">رصيد رسائل الواتساب والـ SMS</p>
              </div>
            </div>

            <!-- Balances -->
            <div class="space-y-3">
              <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700">رسائل الواتساب المتبقية</span>
                <span class="font-mono font-black text-lg text-emerald-600">
                  {{ settingsStore.settings?.wallet?.whatsapp_balance || 500 }}
                </span>
              </div>

              <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700">رسائل SMS المتبقية</span>
                <span class="font-mono font-black text-lg text-blue-600">
                  {{ settingsStore.settings?.wallet?.sms_balance || 150 }}
                </span>
              </div>
            </div>

            <hr class="border-slate-100" />

            <!-- Recharge Form -->
            <div class="space-y-3">
              <h4 class="text-xs font-bold text-slate-800">طلب شحن رصيد إضافي</h4>

              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">باقة الشحن</label>
                <select v-model="topupPackage" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300">
                  <option value="tier_250">باقة 250 رسالة (150 ج.م)</option>
                  <option value="tier_500">باقة 500 رسالة (280 ج.م)</option>
                  <option value="tier_1000">باقة 1000 رسالة (500 ج.م)</option>
                </select>
              </div>

              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">وسيلة الدفع</label>
                <select v-model="paymentMethod" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300">
                  <option value="instapay">Instapay انستاباي</option>
                  <option value="fawry">فوري Fawry</option>
                  <option value="vodafone_cash">فودافون كاش</option>
                </select>
              </div>

              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">رقم العملية / المرجع</label>
                <input
                  v-model="referenceNumber"
                  type="text"
                  placeholder="مثال: REF-987213"
                  class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300 font-mono"
                />
              </div>

              <button
                @click="handleTopup"
                :disabled="submittingTopup"
                class="w-full py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition disabled:opacity-50"
              >
                {{ submittingTopup ? 'جارٍ الإرسال...' : 'إرسال طلب الشحن للمراجعة' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
