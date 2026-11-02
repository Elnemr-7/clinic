<script setup>
import { ref } from 'vue';
import { useClinicBooking } from '../../composables/useClinicBooking';
import { Stethoscope, CheckCircle, Send, Phone, User, Mail, Building2, MapPin } from 'lucide-vue-next';

const { submitLead } = useClinicBooking();

const form = ref({
  name: '',
  phone: '',
  email: '',
  medical_specialty: 'باطنة عامة',
  clinic_name: '',
  notes: '',
});

const submitting = ref(false);
const submitted = ref(false);
const errorMessage = ref('');

const specialties = [
  'باطنة وجهاز هضمي',
  'طب وجراحة الأسنان',
  'طب الأطفال وحديثي الولادة',
  'جلدية وتجميل وليزر',
  'نساء وتوليد وعلاج العقم',
  'عظام وجراحة المفاصل والعمود الفقري',
  'قلب وأوعية دموية',
  'عيون وجراحة ليزك',
  'أنف وأذن وحنجرة',
  'مخ وأعصاب',
  'أخرى',
];

const handleSubmit = async () => {
  errorMessage.value = '';
  if (!form.value.name || !form.value.phone || !form.value.clinic_name) {
    errorMessage.value = 'يرجى كتابة الاسم ورقم الهاتف واسم العيادة';
    return;
  }

  submitting.value = true;
  try {
    await submitLead(form.value);
    submitted.value = true;
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'فشل إرسال الطلب. يرجى المحاولة مرة أخرى.';
  } finally {
    submitting.value = false;
  }
};
</script>

<template>
  <div class="bg-white rounded-3xl shadow-xl border border-slate-200/80 p-8 text-right" dir="rtl">
    <!-- Success State -->
    <div v-if="submitted" class="text-center py-12 space-y-4">
      <div class="inline-flex p-4 bg-emerald-50 text-emerald-600 rounded-3xl">
        <CheckCircle class="w-12 h-12" />
      </div>
      <h3 class="text-2xl font-black text-slate-900">تم استلام طلبكم بنجاح!</h3>
      <p class="text-sm text-slate-600 max-w-md mx-auto">
        شكراً {{ form.name.startsWith('د') ? form.name : 'د. ' + form.name }}! سيتواصل معكم فريق العمل خلال أقل من ساعة لتفعيل النظام وتجهيز موقع العيادة الخاص بكم مجاناً.
      </p>
    </div>

    <!-- Active Form -->
    <div v-else class="space-y-6">
      <div>
        <span class="text-xs font-bold text-teal-600 uppercase tracking-wider">ابدأ نسختك التجريبية مجاناً</span>
        <h3 class="text-2xl font-black text-slate-900 mt-1">انضم إلى مجتمع الأطباء الأكثر كفاءة</h3>
        <p class="text-xs text-slate-500 mt-1">سجل بياناتك للحصول على استشارة تقنية وتجهيز المنصة لعيادتك فوراً</p>
      </div>

      <div v-if="errorMessage" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Doctor Name -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">اسم الطبيب بالكامل</label>
          <div class="relative">
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="د. أحمد طارق المنشاوي"
              class="w-full pl-3 pr-10 py-2.5 text-xs bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-teal-500"
            />
            <User class="w-4 h-4 text-slate-400 absolute right-3 top-3" />
          </div>
        </div>

        <!-- Phone & Email -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">رقم الهاتف المحمول</label>
            <div class="relative">
              <input
                v-model="form.phone"
                type="tel"
                required
                dir="ltr"
                placeholder="010XXXXXXXX"
                class="w-full pl-3 pr-10 py-2.5 text-xs bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-teal-500 text-right font-mono"
              />
              <Phone class="w-4 h-4 text-slate-400 absolute right-3 top-3" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">البريد الإلكتروني (اختياري)</label>
            <div class="relative">
              <input
                v-model="form.email"
                type="email"
                dir="ltr"
                placeholder="doctor@example.com"
                class="w-full pl-3 pr-10 py-2.5 text-xs bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-teal-500 text-right"
              />
              <Mail class="w-4 h-4 text-slate-400 absolute right-3 top-3" />
            </div>
          </div>
        </div>

        <!-- Specialty & Clinic Name -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">التخصص الطبي</label>
            <select
              v-model="form.medical_specialty"
              class="w-full px-3 py-2.5 text-xs bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-teal-500"
            >
              <option v-for="sp in specialties" :key="sp" :value="sp">{{ sp }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">اسم العيادة أو المركز الطبي</label>
            <div class="relative">
              <input
                v-model="form.clinic_name"
                type="text"
                required
                placeholder="عيادات النخبة التخصصية"
                class="w-full pl-3 pr-10 py-2.5 text-xs bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-teal-500"
              />
              <Building2 class="w-4 h-4 text-slate-400 absolute right-3 top-3" />
            </div>
          </div>
        </div>

        <button
          type="submit"
          :disabled="submitting"
          class="w-full py-3.5 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white rounded-2xl text-xs font-bold shadow-lg shadow-teal-500/25 transition flex items-center justify-center gap-2 disabled:opacity-50"
        >
          <Send class="w-4 h-4" />
          <span>{{ submitting ? 'جارٍ الإرسال والتسجيل...' : 'طلب تجربة مجانية وتفعيل النظام فوراً' }}</span>
        </button>
      </form>
    </div>
  </div>
</template>
