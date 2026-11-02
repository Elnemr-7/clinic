<script setup>
import { ref } from 'vue';
import { useReceptionQueueStore } from '../../stores/receptionQueue';
import alerts from '../../utils/alerts';
import { Activity, CheckCircle2, Clock, DollarSign, Sparkles } from 'lucide-vue-next';

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
  arrived_waiting: { text: 'حاضر (بالانتظار)', class: 'bg-status-info-bg text-status-info border-status-info-border' },
  waiting: { text: 'في الانتظار', class: 'bg-status-warning-bg text-status-warning border-status-warning-border' },
  in_consultation: { text: 'داخل غرفة الكشف', class: 'bg-status-success-bg text-status-success border-status-success-border animate-pulse' },
  completed: { text: 'تم الكشف', class: 'bg-surface-elevated text-content-secondary border-line-subtle' },
  postponed: { text: 'مؤجل', class: 'bg-status-warning-bg text-status-warning border-status-warning-border' },
  postponed_waiting_arrival: { text: 'مؤجل لحين الحضور', class: 'bg-status-warning-bg text-status-warning border-status-warning-border' },
  booked: { text: 'محجوز مسبقاً', class: 'bg-surface-elevated text-content-muted border-line-subtle' },
  cancelled: { text: 'ملغي', class: 'bg-status-danger-bg text-status-danger border-status-danger-border' },
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
  <div class="bg-surface-card rounded-2xl shadow-xs border border-line-subtle overflow-hidden text-right select-none" dir="rtl">
    <div class="overflow-x-auto">
      <table class="w-full text-right text-xs">
        <thead class="bg-surface-elevated text-content-secondary font-bold border-b border-line-subtle select-none">
          <tr>
            <th class="py-3 px-3.5 w-14 text-center">#</th>
            <th class="py-3 px-3.5">كود الحجز</th>
            <th class="py-3 px-3.5">اسم المريض والهاتف</th>
            <th class="py-3 px-3.5">نوع الزيارة والحساب</th>
            <th class="py-3 px-3.5">الحالة الحالية</th>
            <th class="py-3 px-3.5">العلامات الحيوية</th>
            <th class="py-3 px-3.5 text-center">خدمات إضافية</th>
            <th class="py-3 px-3.5 text-center">الإجراء السريع</th>
          </tr>
        </thead>

        <tbody v-if="loading && appointments.length === 0" class="divide-y divide-line-subtle">
          <tr>
            <td colspan="8" class="py-12 text-center text-content-muted">
              <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-brand border-t-transparent mb-2"></div>
              <p>جارٍ تحميل الطابور المباشر...</p>
            </td>
          </tr>
        </tbody>

        <tbody v-else-if="appointments.length === 0" class="divide-y divide-line-subtle">
          <tr>
            <td colspan="8" class="py-12 text-center text-content-muted">
              لا توجد حجوزات مطابقة للفلاتر المحددة اليوم
            </td>
          </tr>
        </tbody>

        <tbody v-else class="divide-y divide-line-subtle">
          <tr
            v-for="appt in appointments"
            :key="appt.id"
            :class="[
              'transition hover:bg-surface-elevated/70',
              appt.status === 'in_consultation' ? 'bg-status-success-bg/30 font-semibold border-r-4 border-r-status-success' : ''
            ]"
          >
            <!-- Turn # -->
            <td class="py-3 px-3.5 text-center">
              <span
                :class="[
                  'inline-flex items-center justify-center w-8 h-8 rounded-xl font-bold font-mono text-xs tabular-nums',
                  appt.status === 'in_consultation'
                    ? 'bg-status-success text-content-inverse shadow-xs'
                    : 'bg-surface-elevated text-content-primary border border-line-subtle'
                ]"
              >
                {{ appt.queue_number || '-' }}
              </span>
            </td>

            <!-- Reference Code -->
            <td class="py-3 px-3.5 font-mono font-bold text-content-primary">
              #{{ appt.reference_code }}
            </td>

            <!-- Patient Name & Phone -->
            <td class="py-3 px-3.5">
              <div class="font-bold text-content-primary text-xs">{{ appt.patient?.name }}</div>
              <div class="text-[11px] text-content-muted flex items-center gap-2 mt-0.5">
                <span dir="ltr" class="font-mono">{{ appt.patient?.phone }}</span>
                <span>•</span>
                <span>{{ appt.patient?.gender === 'female' ? 'أنثى' : 'ذكر' }}</span>
                <span v-if="appt.patient?.age">، {{ appt.patient.age }} سنة</span>
              </div>
            </td>

            <!-- Type / Fee -->
            <td class="py-3 px-3.5">
              <div class="flex items-center gap-1.5">
                <span
                  :class="[
                    'px-2 py-0.5 rounded text-[10px] font-bold border',
                    appt.appointment_type === 'followup' ? 'bg-status-info-bg text-status-info border-status-info-border' :
                    appt.appointment_type === 'urgent' ? 'bg-status-danger-bg text-status-danger border-status-danger-border' : 'bg-surface-elevated text-content-secondary border-line-subtle'
                  ]"
                >
                  {{ appt.appointment_type === 'followup' ? 'استشارة' : appt.appointment_type === 'urgent' ? 'طارئ' : 'كشف' }}
                </span>
                <span class="font-mono text-content-primary font-bold tabular-nums">{{ appt.consultation_fee ?? appt.fee ?? 0 }} ج.م</span>
              </div>
              <div class="mt-1">
                <span
                  v-if="appt.is_paid"
                  class="inline-flex items-center gap-1 text-[10px] text-status-success font-bold"
                >
                  <CheckCircle2 class="w-3 h-3" /> تم السداد
                </span>
                <button
                  v-else
                  type="button"
                  @click="handleMarkPaid(appt.id)"
                  :disabled="processingId === appt.id"
                  class="text-[10px] text-status-warning hover:underline font-bold cursor-pointer"
                >
                  تحصيل الكشف الآن
                </button>
              </div>
            </td>

            <!-- Status Badge -->
            <td class="py-3 px-3.5">
              <span
                :class="[
                  'inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold border',
                  statusLabels[appt.status]?.class || 'bg-surface-elevated text-content-secondary border-line-subtle'
                ]"
              >
                {{ statusLabels[appt.status]?.text || appt.status }}
              </span>
            </td>

            <!-- Vitals -->
            <td class="py-3 px-3.5">
              <button
                type="button"
                @click="$emit('openVitals', appt)"
                class="px-2.5 py-1 rounded-lg border border-line-subtle bg-surface-elevated hover:bg-surface-card text-brand text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
              >
                <Activity class="w-3.5 h-3.5 text-brand" />
                <span v-if="appt.vitals_data">مسجلة (تعديل)</span>
                <span v-else>تسجيل قياسات</span>
              </button>
            </td>

            <!-- Extra Services Collection Button -->
            <td class="py-3 px-3.5 text-center">
              <div v-if="appt.extra_services_total && appt.extra_services_total > 0">
                <div v-if="!appt.extra_services_paid">
                  <button
                    type="button"
                    @click="handleCollectExtra(appt.id)"
                    :disabled="processingId === appt.id"
                    class="px-2.5 py-1 rounded-xl bg-status-warning hover:opacity-90 text-content-inverse font-bold text-[10px] shadow-xs flex items-center gap-1 mx-auto transition cursor-pointer"
                    title="تحصيل خدمات إضافية طلبها الطبيب"
                  >
                    <span>💰</span>
                    <span>تحصيل (+{{ appt.extra_services_total }} ج.م)</span>
                  </button>
                </div>
                <div v-else class="text-[10px] text-status-success font-bold flex items-center justify-center gap-1">
                  <CheckCircle2 class="w-3 h-3" />
                  <span>تم سداد الإضافي</span>
                </div>
              </div>
              <span v-else class="text-content-muted text-xs">-</span>
            </td>

            <!-- Quick Action Dropdown -->
            <td class="py-3 px-3.5 text-center">
              <select
                :value="appt.status"
                @change="handleStatusChange(appt.id, $event.target.value)"
                :disabled="processingId === appt.id"
                class="px-2 py-1 text-[11px] font-bold bg-surface-elevated text-content-primary border border-line-subtle rounded-lg focus:outline-none focus:border-brand cursor-pointer"
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
