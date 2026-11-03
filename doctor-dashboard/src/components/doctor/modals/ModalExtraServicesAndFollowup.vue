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
    const rawServices = appt.consultation?.selected_services ?? appt.consultation?.extra_services_billed ?? [];
    if (Array.isArray(rawServices)) {
      selectedServiceIds.value = rawServices.map(s => s.service_id || s.id);
    } else {
      selectedServiceIds.value = [];
    }
    followupDate.value = String(appt.consultation?.followup_date || '').slice(0, 10);
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
    const chosenServices = availableServices.value
      .filter(s => selectedServiceIds.value.includes(s.id))
      .map(s => ({
        id: s.id,
        service_name: s.name_ar || s.name,
        price: Number(s.price || 0),
      }));

    const payload = {
      service_ids: selectedServiceIds.value,
      selected_services: chosenServices,
      extra_services_billed: chosenServices,
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
  <Teleport to="body">
    <div v-if="isOpen" @click.self="handleSafeClose" class="fixed inset-0 z-50 overflow-y-auto bg-surface-overlay/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-surface-card border border-line-subtle rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden text-right text-content-primary" dir="rtl">
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
          <button @click="handleSafeClose" class="p-2 text-content-inverse/80 hover:text-content-inverse hover:bg-white/10 rounded-xl transition cursor-pointer">
            <X class="w-6 h-6" />
          </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto space-y-6 flex-1 bg-surface-app">
          <!-- Section 1: Extra Clinic Services -->
          <div>
            <div class="flex items-center justify-between mb-3">
              <div>
                <h4 class="text-sm font-bold text-content-primary">إجراءات وخدمات إضافية تم تقديمها بالعيادة</h4>
                <p class="text-[11px] text-content-muted">سيتم تنبيه الاستقبال تلقائياً لتحصيلها في الخزينة</p>
              </div>
              <div class="text-left">
                <span class="text-xs font-semibold text-content-secondary">إجمالي الخدمات: </span>
                <span class="text-sm font-extrabold text-status-warning font-mono">{{ selectedTotal() }} ج.م</span>
              </div>
            </div>

            <div v-if="availableServices.length === 0" class="text-center py-6 bg-surface-card border border-dashed border-line-subtle rounded-xl text-xs text-content-muted">
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
                    ? 'bg-brand/10 border-brand shadow-xs'
                    : 'bg-surface-card border-line-subtle hover:border-brand/40'
                ]"
              >
                <div class="flex items-center gap-3">
                  <div
                    :class="[
                      'w-5 h-5 rounded-lg border flex items-center justify-center transition',
                      selectedServiceIds.includes(svc.id)
                        ? 'bg-brand border-brand text-content-inverse'
                        : 'border-line-strong bg-surface-card'
                    ]"
                  >
                    <Check v-if="selectedServiceIds.includes(svc.id)" class="w-3.5 h-3.5" />
                  </div>
                  <div>
                    <p class="text-xs font-bold text-content-primary">{{ svc.name }}</p>
                    <p class="text-[10px] text-content-muted">{{ svc.description || 'خدمة سريرية' }}</p>
                  </div>
                </div>
                <span class="text-xs font-extrabold text-brand font-mono">{{ svc.price }} ج.م</span>
              </div>
            </div>
          </div>

          <hr class="border-line-subtle" />

          <!-- Section 2: Followup Scheduling -->
          <div>
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-bold text-content-primary flex items-center gap-2">
                <Calendar class="w-4 h-4 text-brand" />
                تحديد موعد إعادة الكشف / الاستشارة (Follow-up)
              </h4>
              <button
                v-if="followupDate"
                @click="clearFollowup"
                class="text-[11px] text-status-danger hover:underline cursor-pointer"
              >
                إلغاء موعد الإعادة
              </button>
            </div>

            <!-- Quick Date Buttons -->
            <div class="grid grid-cols-3 gap-2 mb-3">
              <button
                @click="setFollowupDays(7)"
                type="button"
                class="py-2 px-3 text-xs font-semibold rounded-xl border border-line-subtle bg-surface-card hover:border-brand hover:bg-brand/10 text-content-primary transition cursor-pointer"
              >
                + بعد أسبوع (7 أيام)
              </button>
              <button
                @click="setFollowupDays(10)"
                type="button"
                class="py-2 px-3 text-xs font-semibold rounded-xl border border-line-subtle bg-surface-card hover:border-brand hover:bg-brand/10 text-content-primary transition cursor-pointer"
              >
                + بعد 10 أيام
              </button>
              <button
                @click="setFollowupDays(14)"
                type="button"
                class="py-2 px-3 text-xs font-semibold rounded-xl border border-line-subtle bg-surface-card hover:border-brand hover:bg-brand/10 text-content-primary transition cursor-pointer"
              >
                + بعد أسبوعين (14 يوماً)
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-content-secondary mb-1">تاريخ الإعادة المختار</label>
                <input
                  v-model="followupDate"
                  type="date"
                  class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-xl border border-line-subtle focus:ring-2 focus:ring-brand font-mono"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-content-secondary mb-1">تعليمات وتوصيات للمريض عند الحضور</label>
                <input
                  v-model="followupNotes"
                  type="text"
                  placeholder="مثال: إحضار نتيجة تحليل السكر التراكمي..."
                  class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-xl border border-line-subtle focus:ring-2 focus:ring-brand"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-surface-card border-t border-line-subtle flex items-center justify-between">
          <button
            @click="$emit('close')"
            type="button"
            class="px-4 py-2 text-xs font-semibold text-content-secondary hover:bg-surface-elevated rounded-xl transition cursor-pointer"
          >
            إلغاء
          </button>

          <button
            @click="handleSave"
            :disabled="saving"
            type="button"
            class="px-5 py-2.5 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold transition disabled:opacity-50 shadow-md cursor-pointer"
          >
            {{ saving ? 'جارٍ الحفظ...' : 'حفظ الخدمات وتاريخ الاستشارة' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
