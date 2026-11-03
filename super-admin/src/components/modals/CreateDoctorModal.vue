<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-overlay/80 backdrop-blur-sm select-none" dir="rtl">
    <div class="bg-surface-card border border-line-subtle rounded-3xl w-full max-w-2xl max-h-[90vh] flex flex-col shadow-2xl overflow-hidden font-sans text-right">
      <!-- Modal Header -->
      <div class="p-6 border-b border-line-subtle flex justify-between items-center bg-brand text-content-inverse">
        <div class="flex items-center gap-3">
          <span class="text-2xl">👨‍⚕️</span>
          <div>
            <h3 class="text-base font-black">إضافة طبيب جديد للمنصة</h3>
            <p class="text-xs text-brand-subtle/80 font-medium">إنشاء اشتراك وحساب طبيب جديد (بدون عيادات مسبقة)</p>
          </div>
        </div>
        <button @click="$emit('close')" type="button" class="p-2 text-content-inverse/80 hover:text-content-inverse hover:bg-white/10 rounded-xl transition cursor-pointer text-2xl font-bold leading-none">&times;</button>
      </div>

      <!-- Form Body -->
      <form @submit.prevent="handleSubmit" class="p-6 overflow-y-auto space-y-4 flex-1 bg-surface-app text-xs">
        
        <!-- Info Alert -->
        <div class="p-3.5 bg-brand-subtle border border-brand-border rounded-2xl flex items-start gap-2.5 text-content-primary">
          <span class="text-brand text-base mt-0.5">ℹ️</span>
          <p class="text-[11px] leading-relaxed text-content-secondary">
            سيتم تفعيل حساب الطبيب والاشتراك مباشرة. <strong>لن يتم إنشاء أي عيادات الآن</strong>؛ حيث يقوم الطبيب بإنشاء عياداته وفروعه ومواعيد عمله بنفسه من داخل لوحة تحكم الطبيب فور تسجيل الدخول.
          </p>
        </div>

        <!-- Section 1: Doctor Identity -->
        <div class="bg-surface-card border border-line-subtle p-4 rounded-2xl space-y-3 shadow-xs">
          <h4 class="font-bold text-content-primary flex items-center gap-1.5">
            <span>🩺</span>
            <span>البيانات المهنية للطبيب</span>
          </h4>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-content-secondary mb-1">اسم الطبيب بالكامل *</label>
              <input
                v-model="form.name"
                @input="handleNameInput"
                type="text"
                required
                placeholder="د. محمد عبد الله"
                class="w-full bg-surface-input border border-line-subtle rounded-xl px-3.5 py-2.5 text-xs text-content-primary focus:outline-none focus:ring-2 focus:ring-brand font-medium"
              />
            </div>

            <div>
              <label class="block font-bold text-content-secondary mb-1">اللقب المهني / التخصص الدقيق</label>
              <input
                v-model="form.title_ar"
                type="text"
                placeholder="استشاري أمراض الباطنة والكبد"
                class="w-full bg-surface-input border border-line-subtle rounded-xl px-3.5 py-2.5 text-xs text-content-primary focus:outline-none focus:ring-2 focus:ring-brand font-medium"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-content-secondary mb-1">التخصص الرئيسي</label>
              <select
                v-model="form.specialty_key"
                class="w-full bg-surface-input border border-line-subtle rounded-xl px-3.5 py-2.5 text-xs text-content-primary focus:outline-none focus:ring-2 focus:ring-brand font-medium"
              >
                <option value="general">باطنة عامة وأمراض باطنية</option>
                <option value="dental">طب وجراحة الأسنان</option>
                <option value="dermatology">جلدية وتجميل وليزر</option>
                <option value="pediatrics">طب الأطفال وحديثي الولادة</option>
                <option value="orthopedics">طب وجراحة العظام والمفاصل</option>
                <option value="obgyn">نساء وتوليد وعقم</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-content-secondary mb-1">الرابط الفرعي (Subdomain)</label>
              <div class="flex items-center gap-1.5" dir="ltr">
                <span class="text-content-muted font-mono text-[11px]">.platform.com</span>
                <input
                  v-model="form.subdomain"
                  type="text"
                  placeholder="dr-mohamed"
                  class="flex-1 bg-surface-input border border-line-subtle rounded-xl px-3 py-2 text-xs text-content-primary focus:outline-none focus:ring-2 focus:ring-brand font-mono"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Section 2: Login Credentials -->
        <div class="bg-surface-card border border-line-subtle p-4 rounded-2xl space-y-3 shadow-xs">
          <h4 class="font-bold text-content-primary flex items-center gap-1.5">
            <span>🔐</span>
            <span>بيانات تسجيل دخول الطبيب</span>
          </h4>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
              <label class="block font-bold text-content-secondary mb-1">البريد الإلكتروني *</label>
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="doctor@example.com"
                dir="ltr"
                class="w-full bg-surface-input border border-line-subtle rounded-xl px-3.5 py-2.5 text-xs text-content-primary focus:outline-none focus:ring-2 focus:ring-brand font-mono text-right"
              />
            </div>

            <div>
              <label class="block font-bold text-content-secondary mb-1">رقم الهاتف المحمول *</label>
              <input
                v-model="form.phone"
                type="tel"
                required
                placeholder="01012345678"
                dir="ltr"
                class="w-full bg-surface-input border border-line-subtle rounded-xl px-3.5 py-2.5 text-xs text-content-primary focus:outline-none focus:ring-2 focus:ring-brand font-mono text-right"
              />
            </div>

            <div>
              <label class="block font-bold text-content-secondary mb-1">كلمة المرور المؤقتة</label>
              <input
                v-model="form.password"
                type="text"
                placeholder="password123"
                dir="ltr"
                class="w-full bg-surface-input border border-line-subtle rounded-xl px-3.5 py-2.5 text-xs text-content-primary focus:outline-none focus:ring-2 focus:ring-brand font-mono text-right"
              />
            </div>
          </div>
        </div>

        <!-- Section 3: Subscription Plan -->
        <div class="bg-surface-card border border-line-subtle p-4 rounded-2xl space-y-3 shadow-xs">
          <h4 class="font-bold text-content-primary flex items-center gap-1.5">
            <span>📦</span>
            <span>باقة الاشتراك المخصصة</span>
          </h4>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-content-secondary mb-1">اختيار الباقة</label>
              <select
                v-model="form.plan_id"
                class="w-full bg-surface-input border border-line-subtle rounded-xl px-3.5 py-2.5 text-xs text-content-primary focus:outline-none focus:ring-2 focus:ring-brand font-medium"
              >
                <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                  {{ plan.name_ar }} ({{ plan.price_monthly }} ج.م/شهر - {{ plan.max_clinics === -1 ? 'فروع غير محدودة' : `${plan.max_clinics} فروع` }})
                </option>
                <option v-if="plans.length === 0" :value="null">
                  لا توجد باقات معرفة (سيتم تفعيل خطة افتراضية)
                </option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-content-secondary mb-1">الحد الأقصى للفروع (تجاوز اختياري)</label>
              <input
                v-model.number="form.max_clinics_override"
                type="number"
                placeholder="افتراضي حسب الباقة"
                class="w-full bg-surface-input border border-line-subtle rounded-xl px-3.5 py-2.5 text-xs text-content-primary focus:outline-none focus:ring-2 focus:ring-brand font-mono"
              />
            </div>
          </div>
        </div>

        <!-- Form Error Alert -->
        <div v-if="errorMessage" class="p-3 bg-status-danger-bg border border-status-danger-border rounded-xl text-status-danger font-bold text-xs">
          {{ errorMessage }}
        </div>

        <!-- Modal Actions Footer -->
        <div class="pt-3 border-t border-line-subtle flex justify-end gap-2.5">
          <button
            @click="$emit('close')"
            type="button"
            class="px-5 py-2.5 rounded-xl border border-line-subtle bg-surface-card hover:bg-surface-elevated text-content-secondary font-bold transition cursor-pointer"
          >
            إلغاء
          </button>

          <button
            type="submit"
            :disabled="saving"
            class="px-6 py-2.5 rounded-xl bg-brand hover:bg-brand-hover text-content-inverse font-bold transition disabled:opacity-50 flex items-center gap-1.5 shadow-md cursor-pointer"
          >
            <span v-if="saving">جارٍ الحفظ...</span>
            <span v-else>تأكيد إنشاء الطبيب</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useDoctorsStore } from '../../stores/doctors';
import { usePlansStore } from '../../stores/plans';
import Swal from 'sweetalert2';

const props = defineProps({
  isOpen: Boolean,
});

const emit = defineEmits(['close', 'created']);

const doctorsStore = useDoctorsStore();
const plansStore = usePlansStore();
const plans = computed(() => plansStore.plans);

const saving = ref(false);
const errorMessage = ref('');

const form = ref({
  name: '',
  title_ar: 'طبيب استشاري',
  specialty_key: 'general',
  email: '',
  phone: '',
  password: 'password123',
  plan_id: null,
  subdomain: '',
  max_clinics_override: null,
});

// Auto-select first plan when available
watch(plans, (newPlans) => {
  if (newPlans && newPlans.length > 0 && !form.value.plan_id) {
    form.value.plan_id = newPlans[0].id;
  }
}, { immediate: true });

const handleNameInput = () => {
  if (!form.value.subdomain) {
    // Generate clean slug from name
    const cleaned = form.value.name
      .trim()
      .toLowerCase()
      .replace(/[^\w\s-]/g, '')
      .replace(/[\s_-]+/g, '-');
    if (cleaned) {
      form.value.subdomain = 'dr-' + cleaned;
    }
  }
};

const handleSubmit = async () => {
  errorMessage.value = '';
  saving.value = true;
  try {
    const payload = {
      ...form.value,
      subdomain: form.value.subdomain ? form.value.subdomain.trim() : undefined,
    };
    const res = await doctorsStore.createDoctor(payload);
    
    await Swal.fire({
      icon: 'success',
      title: 'تم إنشاء حساب الطبيب بنجاح',
      html: `
        <div style="font-family: Cairo, sans-serif; text-align: right; direction: rtl; font-size: 13px;">
          <p><strong>اسم الطبيب:</strong> ${form.value.name}</p>
          <p><strong>البريد الإلكتروني:</strong> ${form.value.email}</p>
          <p><strong>كلمة المرور:</strong> ${form.value.password || 'password123'}</p>
          <p style="margin-top: 10px; color: #0f766e;">يمكن للطبيب الآن تسجيل الدخول عبر البوابة وإضافة عياداته وفروعه الأولى.</p>
        </div>
      `,
      confirmButtonText: 'حسناً',
      confirmButtonColor: '#0f766e',
    });

    // Reset form
    form.value = {
      name: '',
      title_ar: 'طبيب استشاري',
      specialty_key: 'general',
      email: '',
      phone: '',
      password: 'password123',
      plan_id: plans.value[0]?.id || null,
      subdomain: '',
      max_clinics_override: null,
    };

    emit('created', res);
    emit('close');
  } catch (err) {
    console.error('Failed to create doctor', err);
    errorMessage.value = err.response?.data?.message || 'حدث خطأ أثناء إنشاء الطبيب، يرجى التأكد من عدم تكرار البريد أو الهاتف.';
  } finally {
    saving.value = false;
  }
};
</script>
