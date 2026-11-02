<script setup>
import { ref, watch, computed } from 'vue';
import { useClinicBooking } from '../../composables/useClinicBooking';
import { 
  Calendar, Clock, Phone, User, CheckCircle2, MessageSquare, 
  AlertCircle, Sparkles, MapPin, Check, ChevronDown 
} from 'lucide-vue-next';

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
  accentColor: {
    type: String,
    default: 'cyan', // 'cyan', 'teal', 'rose', 'purple', 'blue', 'amber'
  },
});

const { checkFollowupEligibility, submitBooking } = useClinicBooking();

const selectedClinicId = ref(null);
const selectedShiftId = ref(null);
const appointmentDate = ref(new Date().toISOString().substring(0, 10));

const patientName = ref('');
const patientPhone = ref('');
const patientAge = ref('');
const patientGender = ref('male');
const complaint = ref('');

const checkingFollowup = ref(false);
const followupEligibility = ref(null);
const submitting = ref(false);
const bookingResult = ref(null);
const errorMessage = ref('');

const clinics = computed(() => props.tenant?.clinics || []);

const currentClinic = computed(() => {
  return clinics.value.find(c => c.id === selectedClinicId.value) || clinics.value[0];
});

const shifts = computed(() => {
  return currentClinic.value?.shifts || [];
});

watch(clinics, (cList) => {
  if (cList.length > 0 && !selectedClinicId.value) {
    selectedClinicId.value = cList[0].id;
  }
}, { immediate: true });

watch(shifts, (sList) => {
  if (sList.length > 0) {
    selectedShiftId.value = sList[0].id;
  } else {
    selectedShiftId.value = null;
  }
}, { immediate: true });

let checkTimer = null;
const onPhoneChange = () => {
  clearTimeout(checkTimer);
  followupEligibility.value = null;
  if (!patientPhone.value || patientPhone.value.length < 10) return;

  checkTimer = setTimeout(async () => {
    checkingFollowup.value = true;
    try {
      const res = await checkFollowupEligibility(patientPhone.value, selectedClinicId.value);
      followupEligibility.value = res;
      if (res && res.patient_name && !patientName.value) {
        patientName.value = res.patient_name;
      }
    } catch (e) {
      console.error(e);
    } finally {
      checkingFollowup.value = false;
    }
  }, 500);
};

const handleBook = async () => {
  errorMessage.value = '';
  if (!patientName.value || !patientPhone.value || !selectedClinicId.value) {
    errorMessage.value = 'يرجى إدخال اسم المريض ورقم الهاتف واختيار الفرع';
    return;
  }

  submitting.value = true;
  try {
    const payload = {
      clinic_id: selectedClinicId.value,
      shift_id: selectedShiftId.value,
      appointment_date: appointmentDate.value,
      name: patientName.value,
      phone: patientPhone.value,
      age: patientAge.value ? Number(patientAge.value) : null,
      gender: patientGender.value,
      complaint: complaint.value,
      appointment_type: followupEligibility.value?.is_followup ? 'followup' : 'examination',
    };

    const res = await submitBooking(payload);
    bookingResult.value = res.data;
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'فشل تسجيل الحجز. يرجى مراجعة البيانات المدخلة.';
  } finally {
    submitting.value = false;
  }
};

const accentStyles = computed(() => {
  switch (props.accentColor) {
    case 'teal':
      return {
        button: 'bg-teal-600 hover:bg-teal-700 shadow-teal-500/25',
        ring: 'focus:ring-teal-500',
        badge: 'bg-teal-50 text-teal-800 border-teal-200',
        activeBranch: 'border-teal-500 bg-teal-50/50 text-teal-900',
      };
    case 'rose':
      return {
        button: 'bg-rose-600 hover:bg-rose-700 shadow-rose-500/25',
        ring: 'focus:ring-rose-500',
        badge: 'bg-rose-50 text-rose-800 border-rose-200',
        activeBranch: 'border-rose-500 bg-rose-50/50 text-rose-900',
      };
    case 'purple':
      return {
        button: 'bg-purple-600 hover:bg-purple-700 shadow-purple-500/25',
        ring: 'focus:ring-purple-500',
        badge: 'bg-purple-50 text-purple-800 border-purple-200',
        activeBranch: 'border-purple-500 bg-purple-50/50 text-purple-900',
      };
    case 'blue':
      return {
        button: 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/25',
        ring: 'focus:ring-blue-500',
        badge: 'bg-blue-50 text-blue-800 border-blue-200',
        activeBranch: 'border-blue-500 bg-blue-50/50 text-blue-900',
      };
    case 'amber':
      return {
        button: 'bg-amber-600 hover:bg-amber-700 shadow-amber-500/25',
        ring: 'focus:ring-amber-500',
        badge: 'bg-amber-50 text-amber-800 border-amber-200',
        activeBranch: 'border-amber-500 bg-amber-50/50 text-amber-900',
      };
    case 'cyan':
    default:
      return {
        button: 'bg-cyan-600 hover:bg-cyan-700 shadow-cyan-500/25',
        ring: 'focus:ring-cyan-500',
        badge: 'bg-cyan-50 text-cyan-800 border-cyan-200',
        activeBranch: 'border-cyan-500 bg-cyan-50/50 text-cyan-900',
      };
  }
});
</script>

<template>
  <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-xl text-right" dir="rtl">
    <!-- Success State with Activation Deep-link -->
    <div v-if="bookingResult" class="text-center py-6 space-y-5">
      <div class="inline-flex p-4 bg-emerald-50 text-emerald-600 rounded-3xl border border-emerald-100">
        <CheckCircle2 class="w-12 h-12" />
      </div>

      <div class="space-y-1">
        <span class="text-xs font-bold text-emerald-600">تم تسجيل حجزك بنجاح!</span>
        <h3 class="text-2xl font-black text-slate-900">رقم دورك في الطابور: #{{ bookingResult.queue_number }}</h3>
        <p class="text-sm text-slate-500 font-mono">كود الحجز المرجعي: #{{ bookingResult.reference_code }}</p>
      </div>

      <!-- Time Window -->
      <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl max-w-sm mx-auto space-y-1">
        <p class="text-xs text-slate-500">النافذة الزمنية التقديرية لوصولك:</p>
        <p class="text-base font-extrabold text-slate-800 font-mono" dir="ltr">
          {{ bookingResult.estimated_window_start }} - {{ bookingResult.estimated_window_end }}
        </p>
        <p class="text-xs text-slate-600 font-medium">التكلفة المستحقة: {{ bookingResult.amount_due }} ج.م</p>
      </div>

      <!-- WhatsApp Activation Button -->
      <div v-if="bookingResult.whatsapp_activation_url" class="pt-2">
        <a
          :href="bookingResult.whatsapp_activation_url"
          target="_blank"
          class="inline-flex items-center gap-2 px-6 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-bold text-xs sm:text-sm shadow-lg shadow-emerald-500/25 transition active:scale-95"
        >
          <MessageSquare class="w-4 h-4" />
          <span>تأكيد الحجز وتفعيل إشعارات الواتساب</span>
        </a>
      </div>

      <div class="pt-2">
        <nuxt-link
          :to="`/track/${bookingResult.reference_code}`"
          class="text-xs text-cyan-600 hover:underline font-bold"
        >
          متابعة حالة دورك في الطابور المباشر لحظة بلحظة ←
        </nuxt-link>
      </div>
    </div>

    <!-- Booking Form -->
    <div v-else class="space-y-5">
      <div>
        <h3 class="text-xl font-black text-slate-900">حجز موعد كشف أو استشارة</h3>
        <p class="text-xs text-slate-500 mt-1">اختر الفرع والوردية وسجل بياناتك للحصول على رقم دور فوري</p>
      </div>

      <div v-if="errorMessage" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl flex items-center gap-2">
        <AlertCircle class="w-4 h-4 shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Followup Banner -->
      <div
        v-if="followupEligibility && followupEligibility.is_followup"
        class="p-3 bg-emerald-50 border border-emerald-300 rounded-2xl text-emerald-900 text-xs flex items-center gap-3"
      >
        <Sparkles class="w-5 h-5 text-emerald-600 shrink-0" />
        <div>
          <p class="font-bold">أنت مستحق للاستشارة / إعادة الكشف مجاناً!</p>
          <p class="text-xs text-emerald-700">آخر كشف كان بتاريخ: {{ followupEligibility.last_visit_date }} (متبقي {{ followupEligibility.days_remaining }} أيام)</p>
        </div>
      </div>

      <form @submit.prevent="handleBook" class="space-y-4">
        <!-- Branch Selector -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">الفرع والعيادة</label>
          <select
            v-model="selectedClinicId"
            :class="['w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 font-medium transition', accentStyles.ring]"
          >
            <option v-for="c in clinics" :key="c.id" :value="c.id">
              {{ c.name_ar || c.name || 'الفرع الرئيسي' }} (كشف: {{ c.consultation_price ?? c.examination_fee ?? 300 }} ج.م)
            </option>
          </select>
        </div>

        <!-- Shift & Date -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">تاريخ الكشف</label>
            <div class="relative">
              <input
                v-model="appointmentDate"
                type="date"
                required
                :class="['w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 font-medium transition', accentStyles.ring]"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">الوردية / الفترة</label>
            <select
              v-model="selectedShiftId"
              :class="['w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 font-medium transition', accentStyles.ring]"
            >
              <option v-for="sh in shifts" :key="sh.id" :value="sh.id">
                {{ sh.shift_name_ar || sh.name || 'الفترة' }} ({{ sh.start_time?.substring(0, 5) }} - {{ sh.end_time?.substring(0, 5) }})
              </option>
              <option v-if="shifts.length === 0" :value="null">
                لا توجد ورديات متاحة اليوم
              </option>
            </select>
          </div>
        </div>

        <!-- Phone & Auto-detection -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">رقم الهاتف المحمول (واتساب)</label>
          <div class="relative">
            <input
              v-model="patientPhone"
              @input="onPhoneChange"
              type="tel"
              required
              dir="ltr"
              placeholder="010XXXXXXXX"
              :class="['w-full pl-3.5 pr-10 py-2.5 text-xs sm:text-sm bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 font-mono text-right transition', accentStyles.ring]"
            />
            <Phone class="w-4 h-4 text-slate-400 absolute right-3.5 top-3.5" />
          </div>
          <p v-if="checkingFollowup" class="text-xs text-slate-400 mt-1">جارٍ فحص استحقاق الاستشارة...</p>
        </div>

        <!-- Patient Name & Age -->
        <div class="grid grid-cols-12 gap-3">
          <div class="col-span-8">
            <label class="block text-xs font-bold text-slate-700 mb-1.5">اسم المريض</label>
            <input
              v-model="patientName"
              type="text"
              required
              placeholder="الاسم ثلاثي"
              :class="['w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 font-medium transition', accentStyles.ring]"
            />
          </div>
          <div class="col-span-4">
            <label class="block text-xs font-bold text-slate-700 mb-1.5">السن</label>
            <input
              v-model="patientAge"
              type="number"
              placeholder="العمر"
              :class="['w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 font-medium transition', accentStyles.ring]"
            />
          </div>
        </div>

        <!-- Complaint / Symptoms -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">الشكوى أو سبب الزيارة (اختياري)</label>
          <input
            v-model="complaint"
            type="text"
            placeholder="مثال: فحص دوري، كشف تجميلي، ألم حاد..."
            :class="['w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-50 rounded-xl border border-slate-200 focus:bg-white focus:ring-2 font-medium transition', accentStyles.ring]"
          />
        </div>

        <button
          type="submit"
          :disabled="submitting"
          :class="['w-full py-3.5 text-white rounded-2xl text-xs sm:text-sm font-bold shadow-lg transition disabled:opacity-50 active:scale-[0.99] flex items-center justify-center gap-2', accentStyles.button]"
        >
          <Calendar class="w-4 h-4" />
          <span>{{ submitting ? 'جارٍ حجز الموعد...' : 'تأكيد الحجز والحصول على رقم الدور فوراً' }}</span>
        </button>
      </form>
    </div>
  </div>
</template>
