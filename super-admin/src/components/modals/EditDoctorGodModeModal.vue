<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-4xl max-h-[90vh] flex flex-col shadow-2xl overflow-hidden">
      <!-- Modal Header -->
      <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-950">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 font-bold flex items-center justify-center border border-amber-500/30">
            ⚡
          </div>
          <div>
            <h3 class="text-lg font-bold text-white">التحكم بالطبيب</h3>
            <p class="text-xs text-slate-400">{{ doctor?.name }} - {{ doctor?.title_ar }}</p>
          </div>
        </div>
        <button @click="close" class="text-slate-400 hover:text-white text-2xl font-bold">&times;</button>
      </div>

      <!-- Tabs Navigation -->
      <div class="flex border-b border-slate-800 bg-slate-950/50 px-6 gap-2">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="currentTab = tab.id"
          class="px-4 py-3 text-xs font-bold border-b-2 transition"
          :class="currentTab === tab.id ? 'border-amber-500 text-amber-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Tab Content Area -->
      <div class="flex-1 p-6 overflow-y-auto space-y-6">
        <!-- TAB 1: Subscription & Clinics -->
        <div v-if="currentTab === 'subscription'" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-300 mb-2">الباقة المشترك بها</label>
              <select v-model="form.plan_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white">
                <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name_ar }} ({{ plan.price_monthly }} ج.م/شهر)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-300 mb-2">تاريخ انتهاء الاشتراك</label>
              <input v-model="form.subscription_ends_at" type="date" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white" />
            </div>
          </div>

          <div class="bg-slate-950 p-4 rounded-xl border border-slate-800">
            <label class="block text-xs font-medium text-slate-300 mb-2">تجاوز الحد الأقصى للعيادات (Max Clinics Override)</label>
            <div class="flex items-center gap-4">
              <input
                v-model="form.max_clinics_override"
                type="number"
                :disabled="isUnlimitedClinics"
                placeholder="عدد الفروع المسموح بها"
                class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 text-sm text-white disabled:opacity-40"
              />
              <label class="flex items-center gap-2 cursor-pointer text-xs text-amber-400 font-bold">
                <input type="checkbox" v-model="isUnlimitedClinics" class="rounded border-slate-800 text-amber-500" />
                <span>عيادات غير محدودة (-1)</span>
              </label>
            </div>
            <p class="text-[11px] text-slate-400 mt-2">إذا تم تفعيل هذا الخيار، سيتم تجاهل حد الباقة والسماح للطبيب بإنشاء فروع حسب رغبتك.</p>
          </div>
        </div>

        <!-- TAB 2: WhatsApp Control -->
        <div v-if="currentTab === 'whatsapp'" class="space-y-4">
          <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 flex justify-between items-center">
            <div>
              <p class="text-sm font-bold text-white">تفعيل خدمة الواتساب (Baileys Gateway)</p>
              <p class="text-xs text-slate-400 mt-0.5">تحكم في إتاحة الإرسال والاستقبال التلقائي للروشتة والتنبيهات</p>
            </div>
            <input type="checkbox" v-model="form.whatsapp_enabled" class="w-6 h-6 rounded accent-emerald-500 cursor-pointer" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800">
              <label class="block text-xs font-medium text-slate-300 mb-2">رصيد رسائل الواتساب الحالي</label>
              <input v-model.number="form.whatsapp_balance" type="number" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 text-lg font-bold text-emerald-400" />
            </div>
            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 flex flex-col justify-between">
              <div>
                <p class="text-xs font-medium text-slate-300">المستهلك هذا الشهر</p>
                <p class="text-lg font-bold text-white mt-1">{{ doctor?.whatsapp_used_this_month ?? 0 }} رسالة</p>
              </div>
              <button @click="resetWhatsappCounter" type="button" class="text-[11px] text-rose-400 hover:underline text-right">
                تصفير عداد الشهر لمحاسبة أول الشهر
              </button>
            </div>
          </div>
        </div>

        <!-- TAB 3: SMS Control -->
        <div v-if="currentTab === 'sms'" class="space-y-4">
          <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 flex justify-between items-center">
            <div>
              <p class="text-sm font-bold text-white">تفعيل خدمة رسائل الـ SMS الرسمية</p>
              <p class="text-xs text-slate-400 mt-0.5">إرسال روابط الروشتة وتأكيد الحجز عبر رسائل SMS الهاتفية</p>
            </div>
            <input type="checkbox" v-model="form.sms_enabled" class="w-6 h-6 rounded accent-cyan-500 cursor-pointer" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800">
              <label class="block text-xs font-medium text-slate-300 mb-2">رصيد رسائل الـ SMS الحالي</label>
              <input v-model.number="form.sms_balance" type="number" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 text-lg font-bold text-cyan-400" />
            </div>
            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 flex flex-col justify-between">
              <div>
                <p class="text-xs font-medium text-slate-300">المستهلك هذا الشهر</p>
                <p class="text-lg font-bold text-white mt-1">{{ doctor?.sms_used_this_month ?? 0 }} رسالة</p>
              </div>
              <button @click="resetSmsCounter" type="button" class="text-[11px] text-rose-400 hover:underline text-right">
                تصفير عداد الشهر لمحاسبة أول الشهر
              </button>
            </div>
          </div>
        </div>

        <!-- TAB 4: Domain & Website -->
        <div v-if="currentTab === 'domain'" class="space-y-4">
          <!-- 4 Website Modes Selector -->
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-2">نموذج موقع الطبيب (Website Hosting Mode)</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                @click="form.website_mode = 1"
                :class="[
                  'p-3 rounded-xl border text-right transition cursor-pointer',
                  form.website_mode === 1
                    ? 'border-amber-500 bg-amber-500/10 text-white shadow-sm'
                    : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'
                ]"
              >
                <div class="font-bold text-xs flex items-center justify-between">
                  <span>1. قالب المنصة + رابط المنصة</span>
                  <span v-if="form.website_mode === 1" class="text-amber-400 font-bold">✓</span>
                </div>
                <div class="text-[10px] text-slate-500 mt-1">الوضع الافتراضي لجميع الأطباء</div>
              </button>

              <button
                type="button"
                @click="form.website_mode = 2"
                :class="[
                  'p-3 rounded-xl border text-right transition cursor-pointer',
                  form.website_mode === 2
                    ? 'border-amber-500 bg-amber-500/10 text-white shadow-sm'
                    : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'
                ]"
              >
                <div class="font-bold text-xs flex items-center justify-between">
                  <span>2. قالب المنصة + دومين خاص</span>
                  <span v-if="form.website_mode === 2" class="text-amber-400 font-bold">✓</span>
                </div>
                <div class="text-[10px] text-slate-500 mt-1">قالب المنصة مع CNAME</div>
              </button>

              <button
                type="button"
                @click="form.website_mode = 3"
                :class="[
                  'p-3 rounded-xl border text-right transition cursor-pointer',
                  form.website_mode === 3
                    ? 'border-purple-500 bg-purple-500/10 text-white shadow-sm'
                    : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'
                ]"
              >
                <div class="font-bold text-xs text-purple-400 flex items-center justify-between">
                  <span>3. موقع مخصص + رابط المنصة (VIP)</span>
                  <span v-if="form.website_mode === 3" class="text-purple-400 font-bold">✓</span>
                </div>
                <div class="text-[10px] text-slate-500 mt-1">كود مخصص في فولدر الطبيب</div>
              </button>

              <button
                type="button"
                @click="form.website_mode = 4"
                :class="[
                  'p-3 rounded-xl border text-right transition cursor-pointer',
                  form.website_mode === 4
                    ? 'border-purple-500 bg-purple-500/10 text-white shadow-sm'
                    : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'
                ]"
              >
                <div class="font-bold text-xs text-purple-400 flex items-center justify-between">
                  <span>4. موقع مخصص + دومين خاص (VIP)</span>
                  <span v-if="form.website_mode === 4" class="text-purple-400 font-bold">✓</span>
                </div>
                <div class="text-[10px] text-slate-500 mt-1">كود مخصص + دومين العيادة المستقل</div>
              </button>
            </div>
          </div>

          <!-- Subdomain Field (Always editable) -->
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-2">الرابط الفرعي (Subdomain)</label>
            <div class="flex items-center gap-2" dir="ltr">
              <span class="text-slate-400 text-xs font-mono">.platform.com</span>
              <input
                v-model="form.subdomain"
                type="text"
                placeholder="dr-name"
                class="flex-1 bg-slate-900 border border-slate-700 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-xl px-4 py-2 text-sm text-white font-mono"
              />
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5 text-right">اسم النطاق الفرعي للطبيب (يجب أن يكون فريداً وباللغة الإنجليزية).</p>
          </div>

          <!-- Custom Site Code Folder Info (If Mode 3 or 4) -->
          <div v-if="[3, 4].includes(form.website_mode)" class="bg-purple-950/40 p-4 rounded-xl border border-purple-500/30 space-y-2">
            <p class="text-xs font-bold text-purple-300 flex items-center gap-1.5">
              <span>📁</span>
              <span>مسار كود الموقع المخصص (VIP Custom Site):</span>
            </p>
            <p class="text-[11px] text-slate-300 leading-relaxed">
              تقوم المنصة بتحميل ملف الموقع الخاص بهذا الطبيب تلقائياً من المجلد:
            </p>
            <div class="bg-slate-950 p-2.5 rounded-lg border border-purple-500/20 font-mono text-xs text-amber-300 select-all" dir="ltr">
              web-platform/custom-sites/{{ form.subdomain || 'slug' }}/index.vue
            </div>
          </div>

          <!-- Custom Domain Field (Visible for Mode 2 & 4) -->
          <div v-if="[2, 4].includes(form.website_mode)">
            <label class="block text-xs font-medium text-slate-300 mb-2">الدومين المخصص (Custom Domain via Cloudflare)</label>
            <input
              v-model="form.custom_domain"
              placeholder="مثال: drahmedclinic.com"
              class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white font-mono"
              dir="ltr"
            />
            <p class="text-[11px] text-slate-400 mt-1">يجب توجيه سجل CNAME إلى cname.platform.com لدى مزود الدومين.</p>
          </div>

          <!-- Template Key (For Modes 1 & 2) -->
          <div v-if="[1, 2].includes(form.website_mode)">
            <label class="block text-xs font-medium text-slate-300 mb-2">قالب التخصص المطبق (Specialty Template)</label>
            <select v-model="form.template_key" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white">
              <option value="general">عام وباطنة (General / Polyclinic)</option>
              <option value="dental">طب وتجميل الأسنان (Dental)</option>
              <option value="pediatrics">طب الأطفال (Pediatrics)</option>
              <option value="dermatology">الجلدية والتجميل (Dermatology)</option>
              <option value="obgyn">النساء والتوليد (OB/GYN)</option>
              <option value="orthopedics">العظام والمفاصل (Orthopedics)</option>
            </select>
          </div>

          <!-- Public Booking API Key (For Custom Sites & Integrations) -->
          <div class="bg-slate-950 p-4 rounded-xl border border-slate-800">
            <p class="text-xs font-medium text-slate-300 mb-1">مفتاح الربط للمواقع المخصصة (Public Booking API Key)</p>
            <div class="flex items-center gap-2">
              <input :value="doctor?.public_booking_api_key" readonly class="flex-1 bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-amber-300 font-mono" />
              <button @click="copyApiKey" type="button" class="bg-slate-800 px-3 py-1.5 rounded-lg text-xs hover:bg-slate-700 cursor-pointer">نسخ</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="p-6 border-t border-slate-800 bg-slate-950 flex justify-between items-center">
        <button
          type="button"
          @click="impersonate"
          class="bg-amber-500/20 text-amber-300 border border-amber-500/40 hover:bg-amber-500/30 px-5 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition"
        >
          <span>🔑</span>
          <span>الدخول كدكتور (Impersonate)</span>
        </button>

        <div class="flex items-center gap-3">
          <button @click="close" type="button" class="px-5 py-2.5 rounded-xl text-slate-400 hover:text-white font-medium text-xs cursor-pointer">إلغاء</button>
          <button @click="save" :disabled="saving" type="button" class="bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white px-6 py-2.5 rounded-xl font-bold text-xs shadow-lg shadow-emerald-900/30 cursor-pointer flex items-center gap-2">
            <span v-if="saving">جارٍ الحفظ...</span>
            <span v-else>حفظ التعديلات فوراً</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useDoctorsStore } from '../../stores/doctors';
import { usePlansStore } from '../../stores/plans';
import alerts from '../../utils/alerts';

const props = defineProps({
  isOpen: Boolean,
  doctor: Object,
});

const emit = defineEmits(['close', 'saved']);

const doctorsStore = useDoctorsStore();
const plansStore = usePlansStore();
const plans = computed(() => plansStore.plans);

const currentTab = ref('subscription');
const tabs = [
  { id: 'subscription', label: 'الاشتراك والعيادات' },
  { id: 'whatsapp', label: 'محفظة الواتساب' },
  { id: 'sms', label: 'محفظة الـ SMS' },
  { id: 'domain', label: 'الموقع والدومين' },
];

const isUnlimitedClinics = ref(false);

const form = ref({
  plan_id: null,
  subscription_ends_at: '',
  max_clinics_override: null,
  whatsapp_enabled: false,
  whatsapp_balance: 0,
  sms_enabled: false,
  sms_balance: 0,
  subdomain: '',
  custom_domain: '',
  website_mode: 1,
  template_key: 'general',
  reset_monthly_whatsapp: false,
  reset_monthly_sms: false,
});

watch(
  () => props.doctor,
  (doc) => {
    if (doc) {
      isUnlimitedClinics.value = doc.max_clinics_override === -1;
      let mode = doc.website_settings?.website_mode;
      if (!mode) {
        if (doc.is_custom_standalone_website && doc.custom_domain) {
          mode = 4;
        } else if (doc.is_custom_standalone_website) {
          mode = 3;
        } else if (doc.custom_domain) {
          mode = 2;
        } else {
          mode = 1;
        }
      }

      form.value = {
        plan_id: doc.plan_id,
        subscription_ends_at: doc.subscription_ends_at ? doc.subscription_ends_at.substring(0, 10) : '',
        max_clinics_override: doc.max_clinics_override,
        whatsapp_enabled: Boolean(doc.whatsapp_enabled),
        whatsapp_balance: doc.whatsapp_balance ?? 0,
        sms_enabled: Boolean(doc.sms_enabled),
        sms_balance: doc.sms_balance ?? 0,
        subdomain: doc.subdomain || '',
        custom_domain: doc.custom_domain || '',
        website_mode: Number(mode),
        template_key: doc.template_key || 'general',
        reset_monthly_whatsapp: false,
        reset_monthly_sms: false,
      };
    }
  },
  { immediate: true }
);

watch(isUnlimitedClinics, (unlimited) => {
  if (unlimited) {
    form.value.max_clinics_override = -1;
  } else if (form.value.max_clinics_override === -1) {
    form.value.max_clinics_override = null;
  }
});

const resetWhatsappCounter = () => {
  form.value.reset_monthly_whatsapp = true;
  alerts.info('إشعار', 'سيتم تصفير عداد الواتساب عند الضغط على حفظ.');
};

const resetSmsCounter = () => {
  form.value.reset_monthly_sms = true;
  alerts.info('إشعار', 'سيتم تصفير عداد الـ SMS عند الضغط على حفظ.');
};

const copyApiKey = () => {
  navigator.clipboard.writeText(props.doctor?.public_booking_api_key || '');
  alerts.toast('تم نسخ مفتاح API للحافظة');
};

const saving = ref(false);

const impersonate = async () => {
  const result = await doctorsStore.impersonateDoctor(props.doctor.id);
  const token = result.token;
  window.open(`http://localhost:5174/workspace?impersonate_token=${token}`, '_blank');
};

const save = async () => {
  saving.value = true;
  try {
    const payload = {
      plan_id: form.value.plan_id,
      subscription_ends_at: form.value.subscription_ends_at || null,
      max_clinics_override: form.value.max_clinics_override,
      whatsapp_enabled: form.value.whatsapp_enabled,
      whatsapp_balance: form.value.whatsapp_balance,
      sms_enabled: form.value.sms_enabled,
      sms_balance: form.value.sms_balance,
      subdomain: form.value.subdomain,
      website_mode: form.value.website_mode,
      is_custom_standalone_website: [3, 4].includes(form.value.website_mode),
      custom_domain: [2, 4].includes(form.value.website_mode) ? (form.value.custom_domain || null) : null,
      template_key: form.value.template_key,
      reset_monthly_whatsapp: form.value.reset_monthly_whatsapp,
      reset_monthly_sms: form.value.reset_monthly_sms,
    };

    const res = await doctorsStore.updateDoctor(props.doctor.id, payload);
    alerts.toast('تم حفظ التعديلات بنجاح');
    emit('saved', res);
    emit('close');
  } catch (err) {
    console.error('Failed to update doctor:', err);
    const msg = err.response?.data?.errors?.subdomain?.[0]
      || err.response?.data?.message
      || 'حدث خطأ أثناء حفظ التعديلات';
    alerts.error('تعذر الحفظ', msg);
  } finally {
    saving.value = false;
  }
};

const close = () => {
  emit('close');
};
</script>
