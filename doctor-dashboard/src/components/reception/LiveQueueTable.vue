<script setup>
import { ref } from 'vue';
import { useReceptionQueueStore } from '../../stores/receptionQueue';
import alerts from '../../utils/alerts';
import { Activity, DollarSign, CheckCircle2, Clock, Phone, AlertCircle, ArrowRightLeft, Sparkles } from 'lucide-vue-next';

const props = defineProps({
  appointments: {
    type: Array,
    default: () => [],
  },
  loading: Boolean,
});

const emit = defineEmits(['openVitals', 'refresh']);
const queueStore = useReceptionQueueStore();
const processingId = ref(null);

const statusLabels = {
  arrived_waiting: { text: 'في الانتظار (حاضر)', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
  waiting: { text: 'في الانتظار', class: 'bg-amber-100 text-amber-800 border-amber-200' },
  in_consultation: { text: 'في غرفة الكشف', class: 'bg-emerald-100 text-emerald-800 border-emerald-200 animate-pulse' },
  completed: { text: 'تم الكشف', class: 'bg-slate-100 text-slate-700 border-slate-200' },
  postponed: { text: 'مؤجل', class: 'bg-orange-100 text-orange-800 border-orange-200' },
  postponed_waiting_arrival: { text: 'مؤجل بانتظار الحضور', class: 'bg-orange-100 text-orange-800 border-orange-200' },
  booked: { text: 'محجوز مسبقاً', class: 'bg-sky-100 text-sky-800 border-sky-200' },
  cancelled: { text: 'ملغي', class: 'bg-rose-100 text-rose-800 border-rose-200' },
};

const handleStatusChange = async (appointmentId, newStatus) => {
  processingId.value = appointmentId;
  try {
    await queueStore.updateStatus(appointmentId, newStatus);
    alerts.toast('تم تحديث حالة المريض بنجاح');
    emit('refresh');
  } catch (e) {
    alerts.error('فشل تحديث الحالة', e.response?.data?.message || 'حدث خطأ أثناء الاتصال بالسيرفر');
  } finally {
    processingId.value = null;
  }
};

const handleCollectExtra = async (appointmentId) => {
  const confirmed = await alerts.confirm('تأكيد التحصيل', 'هل تريد تأكيد تحصيل قيمة الخدمات الإضافية وإيداعها في الخزينة؟', 'نعم، حصّل الآن');
  if (!confirmed) return;
  processingId.value = appointmentId;
  try {
    await queueStore.collectExtraServices(appointmentId);
    alerts.success('تم التحصيل بنجاح', 'تم تسجيل المبلغ في خزينة العيادة فوراً');
    emit('refresh');
  } catch (e) {
    alerts.error('فشل التحصيل', e.response?.data?.message || 'حدث خطأ أثناء تسجيل التحصيل');
  } finally {
    processingId.value = null;
  }
};

const handleMarkPaid = async (appointmentId) => {
  processingId.value = appointmentId;
  try {
    await queueStore.updateStatus(appointmentId, null, true);
    alerts.toast('تم تأكيد السداد وإيداعه بالخزينة');
    emit('refresh');
  } catch (e) {
    alerts.error('فشل تسجيل السداد', e.response?.data?.message || 'حدث خطأ');
  } finally {
    processingId.value = null;
  }
};
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden text-right" dir="rtl">
    <div class="overflow-x-auto">
      <table class="w-full text-right text-xs">
        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 select-none">
          <tr>
            <th class="py-3.5 px-4 w-16 text-center">الدور</th>
            <th class="py-3.5 px-4">كود الحجز</th>
            <th class="py-3.5 px-4">اسم المريض</th>
            <th class="py-3.5 px-4">رقم الهاتف</th>
            <th class="py-3.5 px-4">النوع / التكلفة</th>
            <th class="py-3.5 px-4">الحالة الحالية</th>
            <th class="py-3.5 px-4">العلامات الحيوية</th>
            <th class="py-3.5 px-4 text-center">خدمات إضافية</th>
            <th class="py-3.5 px-4 text-center">إجراءات سريعة</th>
          </tr>
        </thead>

        <tbody v-if="loading" class="divide-y divide-slate-100">
          <tr>
            <td colspan="9" class="py-12 text-center text-slate-400">
              <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-blue-600 border-t-transparent mb-2"></div>
              <p>جارٍ تحميل الطابور المباشر...</p>
            </td>
          </tr>
        </tbody>

        <tbody v-else-if="appointments.length === 0" class="divide-y divide-slate-100">
          <tr>
            <td colspan="9" class="py-12 text-center text-slate-400">
              لا توجد حجوزات في طابور اليوم حتى الآن
            </td>
          </tr>
        </tbody>

        <tbody v-else class="divide-y divide-slate-100">
          <tr
            v-for="appt in appointments"
            :key="appt.id"
            :class="[
              'transition hover:bg-slate-50/80',
              appt.status === 'in_consultation' ? 'bg-emerald-50/40 font-semibold' : ''
            ]"
          >
            <!-- Turn # -->
            <td class="py-3 px-4 text-center">
              <span
                :class="[
                  'inline-flex items-center justify-center w-8 h-8 rounded-xl font-bold font-mono text-sm',
                  appt.status === 'in_consultation'
                    ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/30'
                    : 'bg-slate-100 text-slate-800'
                ]"
              >
                {{ appt.queue_number || '-' }}
              </span>
            </td>

            <!-- Reference Code -->
            <td class="py-3 px-4 font-mono font-bold text-slate-700">
              #{{ appt.reference_code }}
            </td>

            <!-- Patient Name -->
            <td class="py-3 px-4">
              <div class="font-bold text-slate-900 text-sm">{{ appt.patient?.name }}</div>
              <div class="text-[11px] text-slate-400">
                {{ appt.patient?.gender === 'female' ? 'أنثى' : 'ذكر' }}
                <span v-if="appt.patient?.age">، {{ appt.patient.age }} سنة</span>
              </div>
            </td>

            <!-- Phone -->
            <td class="py-3 px-4 font-mono text-slate-600" dir="ltr">
              {{ appt.patient?.phone }}
            </td>

            <!-- Type / Fee -->
            <td class="py-3 px-4">
              <div class="flex items-center gap-1.5">
                <span
                  :class="[
                    'px-2 py-0.5 rounded text-[10px] font-bold',
                    appt.appointment_type === 'followup' ? 'bg-blue-100 text-blue-800' :
                    appt.appointment_type === 'urgent' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700'
                  ]"
                >
                  {{ appt.appointment_type === 'followup' ? 'استشارة' : appt.appointment_type === 'urgent' ? 'طارئ' : 'كشف' }}
                </span>
                <span class="font-mono text-slate-700 font-bold">{{ appt.consultation_fee ?? appt.fee ?? 0 }} ج.م</span>
              </div>
              <div class="mt-1">
                <span
                  v-if="appt.is_paid"
                  class="inline-flex items-center gap-1 text-[10px] text-emerald-600 font-bold"
                >
                  <CheckCircle2 class="w-3 h-3" /> تم السداد
                </span>
                <button
                  v-else
                  @click="handleMarkPaid(appt.id)"
                  :disabled="processingId === appt.id"
                  class="text-[10px] text-amber-600 hover:text-amber-700 font-bold hover:underline"
                >
                  تحصيل الآن
                </button>
              </div>
            </td>

            <!-- Status Badge -->
            <td class="py-3 px-4">
              <span
                :class="[
                  'inline-block px-2.5 py-1 rounded-full text-[11px] font-bold border',
                  statusLabels[appt.status]?.class || 'bg-slate-100 text-slate-600'
                ]"
              >
                {{ statusLabels[appt.status]?.text || appt.status }}
              </span>
            </td>

            <!-- Vitals -->
            <td class="py-3 px-4">
              <button
                @click="$emit('openVitals', appt)"
                class="px-2.5 py-1.5 rounded-lg border border-teal-200 bg-teal-50 hover:bg-teal-100 text-teal-800 text-[11px] font-bold flex items-center gap-1 transition"
              >
                <Activity class="w-3.5 h-3.5" />
                <span v-if="appt.vitals_data">مسجلة (تعديل)</span>
                <span v-else>قياس العلامات</span>
              </button>
            </td>

            <!-- Correction 4: Extra Services Collection Badge -->
            <td class="py-3 px-4 text-center">
              <div v-if="appt.consultation?.extra_services_billed && appt.consultation.extra_services_billed.length > 0">
                <div v-if="!appt.extra_services_paid">
                  <button
                    @click="handleCollectExtra(appt.id)"
                    :disabled="processingId === appt.id"
                    class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] shadow-sm flex items-center gap-1 mx-auto transition animate-bounce"
                    title="تحصيل خدمات إضافية قررها الطبيب أثناء الكشف"
                  >
                    <span>💰</span>
                    <span>تحصيل خدمات ({{ appt.extra_services_total || 0 }} ج.م)</span>
                  </button>
                </div>
                <div v-else class="text-[11px] text-emerald-600 font-bold flex items-center justify-center gap-1">
                  <CheckCircle2 class="w-3.5 h-3.5" />
                  <span>تم تحصيل الإضافي</span>
                </div>
              </div>
              <span v-else class="text-slate-300 text-xs">-</span>
            </td>

            <!-- Quick Action Dropdown -->
            <td class="py-3 px-4 text-center">
              <select
                :value="appt.status"
                @change="handleStatusChange(appt.id, $event.target.value)"
                :disabled="processingId === appt.id"
                class="px-2.5 py-1 text-[11px] font-semibold bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              >
                <option value="booked">محجوز</option>
                <option value="arrived_waiting">حضر (في الانتظار)</option>
                <option value="in_consultation">إدخال للطبيب</option>
                <option value="completed">إنهاء الكشف</option>
                <option value="postponed_waiting_arrival">تأجيل</option>
                <option value="cancelled">إلغاء</option>
              </select>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
