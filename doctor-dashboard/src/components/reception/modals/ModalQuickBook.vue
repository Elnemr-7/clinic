<script setup>
import { ref, watch, computed } from 'vue';
import { useReceptionQueueStore } from '../../../stores/receptionQueue';
import alerts from '../../../utils/alerts';
import { UserPlus, Sparkles, Check, X, Phone, User, Calendar, Clock, DollarSign } from 'lucide-vue-next';

const props = defineProps({
  isOpen: Boolean,
  clinicId: [Number, String],
  shifts: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close', 'booked']);
const queueStore = useReceptionQueueStore();

const phone = ref('');
const name = ref('');
const age = ref('');
const gender = ref('male');
const appointmentType = ref('examination'); // examination | followup | urgent
const shiftId = ref('');
const appointmentDate = ref(new Date().toISOString().substring(0, 10));
const customFee = ref(null);
const markPaidNow = ref(true);

const checkingFollowup = ref(false);
const followupEligibility = ref(null);
const booking = ref(false);

watch(() => props.shifts, (s) => {
  if (s && s.length > 0 && !shiftId.value) {
    shiftId.value = s[0].id;
  }
}, { immediate: true });

let checkTimer = null;
const onPhoneChange = () => {
  clearTimeout(checkTimer);
  followupEligibility.value = null;
  if (!phone.value || phone.value.length < 10) return;

  checkTimer = setTimeout(async () => {
    checkingFollowup.value = true;
    try {
      const res = await queueStore.checkFollowup(phone.value, props.clinicId);
      followupEligibility.value = res;
      if (res && res.is_followup) {
        appointmentType.value = 'followup';
      }
      if (res && res.patient_name && !name.value) {
        name.value = res.patient_name;
      }
    } catch (e) {
      console.error(e);
    } finally {
      checkingFollowup.value = false;
    }
  }, 500);
};

const handleBook = async () => {
  if (!name.value || !phone.value) {
    alerts.warning('يرجى كتابة اسم المريض ورقم الهاتف');
    return;
  }

  booking.value = true;
  try {
    const payload = {
      clinic_id: props.clinicId,
      shift_id: shiftId.value || null,
      appointment_date: appointmentDate.value,
      name: name.value,
      phone: phone.value,
      age: age.value ? Number(age.value) : null,
      gender: gender.value,
      appointment_type: appointmentType.value,
      custom_fee: customFee.value ? Number(customFee.value) : null,
      mark_paid: markPaidNow.value,
    };

    const res = await queueStore.quickBook(payload);
    alerts.success('تم الحجز بنجاح', `رقم الدور: ${res.data?.queue_number || ''} | كود: ${res.data?.reference_code || ''}`);
    emit('booked', res);
    emit('close');
  } catch (e) {
    alerts.error('فشل حجز الموعد', e.response?.data?.message || 'حدث خطأ أثناء تسجيل الحجز');
  } finally {
    booking.value = false;
  }
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden text-right" dir="rtl">
      <!-- Header -->
      <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-700 text-white flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-white/10 rounded-xl">
            <UserPlus class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-xl font-bold">حجز وإدخال سريع للمريض</h3>
            <p class="text-xs text-blue-100 font-medium">تسجيل فوري في طابور الانتظار لليوم</p>
          </div>
        </div>
        <button @click="$emit('close')" class="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition">
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Form Body -->
      <div class="p-6 space-y-4">
        <!-- Phone Input with Followup Auto-detector -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">رقم الهاتف المحمول</label>
          <div class="relative">
            <input
              v-model="phone"
              @input="onPhoneChange"
              type="tel"
              placeholder="01XXXXXXXXX"
              dir="ltr"
              class="w-full pl-3 pr-10 py-2.5 text-sm font-mono bg-white rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 text-right"
            />
            <Phone class="w-4 h-4 text-slate-400 absolute right-3 top-3.5" />
          </div>
        </div>

        <!-- Follow-up Auto Detection Alert Banner -->
        <div
          v-if="followupEligibility && followupEligibility.is_followup"
          class="p-3 bg-emerald-50 border border-emerald-300 rounded-xl flex items-center gap-3 text-emerald-900"
        >
          <Sparkles class="w-5 h-5 text-emerald-600 shrink-0" />
          <div class="text-xs">
            <p class="font-bold">مريض مستحق للاستشارة / إعادة الكشف مجاناً!</p>
            <p class="text-[11px] text-emerald-700">
              آخر كشف: {{ followupEligibility.last_visit_date }} (متبقي {{ followupEligibility.days_remaining }} أيام من فترة السماح)
            </p>
          </div>
        </div>

        <!-- Patient Name & Age -->
        <div class="grid grid-cols-12 gap-3">
          <div class="col-span-8">
            <label class="block text-xs font-bold text-slate-700 mb-1">اسم المريض بالكامل</label>
            <div class="relative">
              <input
                v-model="name"
                type="text"
                placeholder="محمد أحمد..."
                class="w-full pl-3 pr-9 py-2 text-xs bg-white rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500"
              />
              <User class="w-4 h-4 text-slate-400 absolute right-3 top-2.5" />
            </div>
          </div>

          <div class="col-span-4">
            <label class="block text-xs font-bold text-slate-700 mb-1">السن</label>
            <input
              v-model="age"
              type="number"
              placeholder="35"
              class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <!-- Gender & Booking Type -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">النوع</label>
            <select
              v-model="gender"
              class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500"
            >
              <option value="male">ذكر</option>
              <option value="female">أنثى</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">نوع الكشف</label>
            <select
              v-model="appointmentType"
              class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500"
            >
              <option value="examination">كشف جديد</option>
              <option value="followup">إعادة كشف / استشارة</option>
              <option value="urgent">حالة طارئة مستعجلة</option>
            </select>
          </div>
        </div>

        <!-- Shift & Date -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">تاريخ الحجز</label>
            <input
              v-model="appointmentDate"
              type="date"
              class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300"
            />
          </div>

          <div v-if="shifts.length > 0">
            <label class="block text-xs font-bold text-slate-700 mb-1">الوردية / الفترة</label>
            <select
              v-model="shiftId"
              class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300"
            >
              <option v-for="sh in shifts" :key="sh.id" :value="sh.id">
                {{ sh.name || 'فترة العمل' }} ({{ sh.start_time?.substring(0, 5) }} - {{ sh.end_time?.substring(0, 5) }})
              </option>
            </select>
          </div>
        </div>

        <!-- Payment Checkbox -->
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              v-model="markPaidNow"
              type="checkbox"
              class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500"
            />
            <span class="text-xs font-bold text-slate-800">تحصيل قيمة الكشف فوراً وإيداعها في الخزينة</span>
          </label>
          <span class="text-[11px] text-slate-400">سجل محاسبي</span>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
        <button
          @click="$emit('close')"
          type="button"
          class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition"
        >
          إلغاء
        </button>

        <button
          @click="handleBook"
          :disabled="booking"
          type="button"
          class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition disabled:opacity-50 shadow-md shadow-blue-500/20 flex items-center gap-2"
        >
          <Check class="w-4 h-4" />
          <span>{{ booking ? 'جارٍ التسجيل...' : 'تأكيد الحجز والإضافة للطابور' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>
