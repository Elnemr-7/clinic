<script setup>
import { ref, watch, onMounted } from 'vue';
import { useDoctorWorkspaceStore } from '../../../stores/doctorWorkspace';
import client from '../../../api/client';
import alerts from '../../../utils/alerts';
import { Calendar, DollarSign, Plus, Check, X, Clock } from 'lucide-vue-next';

const props = defineProps({
  isOpen: Boolean,
  appointment: Object,
});

const emit = defineEmits(['close', 'saved']);
const workspaceStore = useDoctorWorkspaceStore();

const availableServices = ref([]);
const selectedServiceIds = ref([]);
const followupDate = ref('');
const followupNotes = ref('');
const saving = ref(false);

const loadClinicServices = async () => {
  try {
    const res = await client.get('/settings/services');
    availableServices.value = res.data.data || [];
  } catch (e) {
    console.error('Failed loading clinic services', e);
  }
};

watch(() => props.appointment, (appt) => {
  if (appt) {
    if (Array.isArray(appt.consultation?.extra_services_billed)) {
      selectedServiceIds.value = appt.consultation.extra_services_billed.map(s => s.service_id || s.id);
    } else {
      selectedServiceIds.value = [];
    }
    followupDate.value = appt.consultation?.followup_date || '';
    followupNotes.value = appt.consultation?.followup_notes || '';
  }
}, { immediate: true });

onMounted(() => {
  loadClinicServices();
});

const toggleService = (id) => {
  const index = selectedServiceIds.value.indexOf(id);
  if (index > -1) {
    selectedServiceIds.value.splice(index, 1);
  } else {
    selectedServiceIds.value.push(id);
  }
};

const setFollowupDays = (days) => {
  const d = new Date();
  d.setDate(d.getDate() + days);
  followupDate.value = d.toISOString().split('T')[0];
};

const clearFollowup = () => {
  followupDate.value = '';
};

const selectedTotal = () => {
  return availableServices.value
    .filter(s => selectedServiceIds.value.includes(s.id))
    .reduce((sum, s) => sum + Number(s.price || 0), 0);
};

const handleSafeClose = () => {
  if (selectedServiceIds.value.length > 0 || followupDate.value || followupNotes.value) {
    if (!confirm('لديك تعديلات غير محفوظة في الخدمات أو موعد الإعادة. هل تريد الإغلاق بدون حفظ؟')) {
      return;
    }
  }
  emit('close');
};

const handleSave = async () => {
  if (!props.appointment) return;
  saving.value = true;
  try {
    const payload = {
      service_ids: selectedServiceIds.value,
      followup_date: followupDate.value || null,
      followup_notes: followupNotes.value || null,
    };
    const res = await workspaceStore.saveExtraServices(props.appointment.id, payload);
    alerts.toast('تم حفظ الخدمات الإضافية وتاريخ الإعادة بنجاح');
    emit('saved', res);
    emit('close');
  } catch (e) {
    alerts.error('فشل حفظ الخدمات والمتابعة', e.response?.data?.message || 'يرجى مراجعة البيانات والمحاولة مجدداً');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <div v-if="isOpen" @click.self="handleSafeClose" class="fixed inset-0 z-50 overflow-y-auto bg-surface-overlay/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-surface-card border border-line-subtle rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden text-right" dir="rtl">
      <!-- Modal Header -->
      <div class="px-6 py-4 bg-brand text-content-inverse flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-white/10 rounded-xl">
            <DollarSign class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-xl font-bold">الخدمات الإضافية وموعد الإعادة / الاستشارة</h3>
            <p class="text-xs text-brand-subtle/80 font-medium">المريض: {{ appointment?.patient?.name }}</p>
          </div>
        </div>
        <button @click="handleSafeClose" class="p-2 text-content-inverse/80 hover:text-content-inverse hover:bg-white/10 rounded-xl transition">
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 overflow-y-auto space-y-6 flex-1">
        <!-- Section 1: Extra Clinic Services -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <div>
              <h4 class="text-sm font-bold text-slate-800">إجراءات وخدمات إضافية تم تقديمها بالعيادة</h4>
              <p class="text-[11px] text-slate-500">سيتم تنبيه الاستقبال تلقائياً لتحصيلها في الخزينة</p>
            </div>
            <div class="text-left">
              <span class="text-xs font-semibold text-slate-500">إجمالي الخدمات: </span>
              <span class="text-sm font-extrabold text-amber-600 font-mono">{{ selectedTotal() }} ج.م</span>
            </div>
          </div>

          <div v-if="availableServices.length === 0" class="text-center py-6 bg-slate-50 border border-dashed rounded-xl text-xs text-slate-400">
            لا توجد خدمات إضافية معرفة في إعدادات العيادة حالياً
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div
              v-for="svc in availableServices"
              :key="svc.id"
              @click="toggleService(svc.id)"
              :class="[
                'p-3 rounded-xl border cursor-pointer transition flex items-center justify-between',
                selectedServiceIds.includes(svc.id)
                  ? 'bg-amber-50/80 border-amber-500 shadow-sm'
                  : 'bg-white border-slate-200 hover:border-slate-300'
              ]"
            >
              <div class="flex items-center gap-3">
                <div
                  :class="[
                    'w-5 h-5 rounded-lg border flex items-center justify-center transition',
                    selectedServiceIds.includes(svc.id)
                      ? 'bg-amber-600 border-amber-600 text-white'
                      : 'border-slate-300 bg-white'
                  ]"
                >
                  <Check v-if="selectedServiceIds.includes(svc.id)" class="w-3.5 h-3.5" />
                </div>
                <div>
                  <p class="text-xs font-bold text-slate-800">{{ svc.name }}</p>
                  <p class="text-[10px] text-slate-400">{{ svc.description || 'خدمة سريرية' }}</p>
                </div>
              </div>
              <span class="text-xs font-extrabold text-slate-700 font-mono">{{ svc.price }} ج.م</span>
            </div>
          </div>
        </div>

        <hr class="border-slate-100" />

        <!-- Section 2: Followup Scheduling -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
              <Calendar class="w-4 h-4 text-amber-600" />
              تحديد موعد إعادة الكشف / الاستشارة (Follow-up)
            </h4>
            <button
              v-if="followupDate"
              @click="clearFollowup"
              class="text-[11px] text-rose-600 hover:underline"
            >
              إلغاء موعد الإعادة
            </button>
          </div>

          <!-- Quick Date Buttons -->
          <div class="grid grid-cols-3 gap-2 mb-3">
            <button
              @click="setFollowupDays(7)"
              type="button"
              class="py-2 px-3 text-xs font-semibold rounded-xl border border-slate-200 hover:border-amber-500 hover:bg-amber-50 transition"
            >
              + بعد أسبوع (7 أيام)
            </button>
            <button
              @click="setFollowupDays(10)"
              type="button"
              class="py-2 px-3 text-xs font-semibold rounded-xl border border-slate-200 hover:border-amber-500 hover:bg-amber-50 transition"
            >
              + بعد 10 أيام
            </button>
            <button
              @click="setFollowupDays(14)"
              type="button"
              class="py-2 px-3 text-xs font-semibold rounded-xl border border-slate-200 hover:border-amber-500 hover:bg-amber-50 transition"
            >
              + بعد أسبوعين (14 يوماً)
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">تاريخ الإعادة المختار</label>
              <input
                v-model="followupDate"
                type="date"
                class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">تعليمات وتوصيات للمريض عند الحضور</label>
              <input
                v-model="followupNotes"
                type="text"
                placeholder="مثال: إحضار نتيجة تحليل السكر التراكمي..."
                class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
        <button
          @click="$emit('close')"
          type="button"
          class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition"
        >
          إلغاء
        </button>

        <button
          @click="handleSave"
          :disabled="saving"
          type="button"
          class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition disabled:opacity-50 shadow-md shadow-amber-500/20"
        >
          {{ saving ? 'جارٍ الحفظ...' : 'حفظ الخدمات وتاريخ الاستشارة' }}
        </button>
      </div>
    </div>
  </div>
</template>
