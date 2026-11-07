<template>
  <div class="space-y-6 text-right select-none font-sans" dir="rtl">
    <!-- Header with Action Buttons -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line-subtle pb-4">
      <div>
        <h2 class="text-base font-black text-content-primary">2. إدارة الفروع والورديات والعطلات الطارئة</h2>
        <p class="text-xs text-content-muted mt-0.5">فروع العيادة، مواعيد العمل، وجدول الإجازات والاعتذارات الطارئة</p>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="showEmergencyModal = true"
          class="px-3.5 py-2 bg-status-danger hover:opacity-90 text-content-inverse rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer"
        >
          <CalendarX class="w-4 h-4" />
          <span>اعتذار / عطلة طارئة</span>
        </button>

        <button
          type="button"
          @click="showAddBranch = true"
          class="px-3.5 py-2 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>إضافة فرع جديد</span>
        </button>
      </div>
    </div>

    <!-- Branches List -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="branch in settingsStore.branches"
        :key="branch.id"
        class="bg-surface-elevated border border-line-subtle rounded-2xl p-5 shadow-xs flex flex-col justify-between"
      >
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="p-2.5 bg-brand-subtle text-brand border border-brand-border rounded-xl">
                <Building2 class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-sm font-black text-content-primary">{{ branch.name }}</h3>
                <p class="text-[11px] text-content-muted flex items-center gap-1 mt-0.5">
                  <MapPin class="w-3 h-3" />
                  <span>{{ branch.address || 'العنوان غير محدد' }}</span>
                </p>
              </div>
            </div>
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-status-success-bg text-status-success font-bold font-mono">
              كشف: {{ branch.examination_fee }} ج.م
            </span>
          </div>

          <!-- Shifts Section -->
          <div class="pt-2 border-t border-line-subtle">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-content-secondary flex items-center gap-1">
                <Clock class="w-3.5 h-3.5 text-brand" />
                <span>الورديات ومواعيد العمل</span>
              </span>
              <button
                type="button"
                @click="openAddShift(branch.id)"
                class="text-[11px] text-brand hover:underline font-bold flex items-center gap-0.5"
              >
                <Plus class="w-3 h-3" />
                <span>إضافة وردية</span>
              </button>
            </div>

            <div v-if="!branch.shifts || branch.shifts.length === 0" class="text-[11px] text-content-muted py-2 text-center bg-surface-card rounded-xl">
              لا توجد مواعيد عمل مسجلة لهذا الفرع
            </div>

            <div v-else class="space-y-1.5">
              <div
                v-for="shift in branch.shifts"
                :key="shift.id"
                class="p-2.5 rounded-xl bg-surface-card border border-line-subtle flex items-center justify-between text-xs hover:border-brand/30 transition-all"
              >
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded-md bg-brand-subtle text-brand text-[10px] font-bold">
                    {{ shift.day_name || getDayName(shift.day_of_week) }}
                  </span>
                  <span class="font-bold text-content-primary">{{ shift.shift_name_ar || shift.name }}</span>
                </div>
                <div class="flex items-center gap-3">
                  <span class="text-[10px] text-content-muted font-mono" dir="ltr">
                    {{ shift.start_time?.substring(0, 5) }} - {{ shift.end_time?.substring(0, 5) }}
                  </span>
                  <span class="text-[10px] text-brand font-bold bg-surface-elevated px-2 py-0.5 rounded-full border border-line-subtle">
                    أقصى: {{ shift.max_patients }}
                  </span>
                  <button
                    type="button"
                    @click="handleDeleteShift(shift.id)"
                    class="text-content-muted hover:text-status-danger p-1 rounded-lg transition"
                    title="حذف الوردية"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Add Branch -->
    <div v-if="showAddBranch" class="fixed inset-0 z-50 overflow-y-auto bg-surface-overlay/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-surface-card border border-line-subtle rounded-2xl shadow-xl w-full max-w-md overflow-hidden text-right" dir="rtl">
        <div class="px-5 py-3.5 bg-brand text-content-inverse font-bold text-sm">إضافة فرع جديد</div>
        <div class="p-5 space-y-3">
          <div>
            <label class="block text-xs font-bold text-content-primary mb-1">اسم الفرع</label>
            <input v-model="newBranch.name" type="text" placeholder="عيادة المعادي..." class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary" />
          </div>
          <div>
            <label class="block text-xs font-bold text-content-primary mb-1">العنوان بالتفصيل</label>
            <input v-model="newBranch.address" type="text" placeholder="شارع النصر..." class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-content-primary mb-1">سعر الكشف (ج.م)</label>
              <input v-model.number="newBranch.examination_fee" type="number" class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary font-mono" />
            </div>
            <div>
              <label class="block text-xs font-bold text-content-primary mb-1">سعر الاستشارة (ج.م)</label>
              <input v-model.number="newBranch.followup_fee" type="number" class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary font-mono" />
            </div>
          </div>
        </div>
        <div class="px-5 py-3 bg-surface-elevated border-t border-line-subtle flex justify-between">
          <button @click="showAddBranch = false" class="px-4 py-2 text-xs font-bold text-content-secondary">إلغاء</button>
          <button @click="handleSaveBranch" :disabled="saving" class="px-5 py-2 bg-brand text-content-inverse rounded-xl text-xs font-bold">حفظ الفرع</button>
        </div>
      </div>
    </div>

    <!-- Modal: Add Shift -->
    <div v-if="showAddShift" class="fixed inset-0 z-50 overflow-y-auto bg-surface-overlay/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-surface-card border border-line-subtle rounded-2xl shadow-xl w-full max-w-lg overflow-hidden text-right" dir="rtl">
        <div class="px-5 py-3.5 bg-brand text-content-inverse font-bold text-sm flex items-center justify-between">
          <span>إضافة وردية عمل</span>
          <span class="text-xs font-normal opacity-90">{{ getBranchName(newShift.clinic_id) }}</span>
        </div>
        <div class="p-5 space-y-4">
          <!-- Shift Name -->
          <div>
            <label class="block text-xs font-bold text-content-primary mb-1">اسم الوردية</label>
            <input
              v-model="newShift.name"
              type="text"
              placeholder="مثال: الفترة المسائية، الفترة الصباحية..."
              class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary focus:border-brand focus:outline-hidden"
            />
          </div>

          <!-- Days Selection -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-bold text-content-primary">
                أيام العمل <span class="text-status-danger">*</span>
                <span class="text-[11px] font-normal text-content-muted mr-1.5">
                  ({{ newShift.days.length }} أيام محددة)
                </span>
              </label>
              <div class="flex items-center gap-2 text-[10px]">
                <button
                  type="button"
                  @click="selectAllDaysExceptFriday"
                  class="text-brand hover:underline font-bold cursor-pointer"
                >
                  السبت - الخميس
                </button>
                <span class="text-content-muted">•</span>
                <button
                  type="button"
                  @click="selectAllDays"
                  class="text-content-secondary hover:underline cursor-pointer"
                >
                  الكل
                </button>
                <span class="text-content-muted">•</span>
                <button
                  type="button"
                  @click="clearDays"
                  class="text-content-muted hover:underline cursor-pointer"
                >
                  مسح
                </button>
              </div>
            </div>

            <!-- Days chips -->
            <div class="grid grid-cols-4 sm:grid-cols-7 gap-1.5">
              <button
                v-for="d in DAYS_OF_WEEK"
                :key="d.id"
                type="button"
                @click="toggleDay(d.id)"
                class="py-2 px-1 text-center rounded-xl text-xs font-bold transition border cursor-pointer select-none"
                :class="newShift.days.includes(d.id)
                  ? 'bg-brand text-content-inverse border-brand shadow-xs ring-2 ring-brand/30'
                  : 'bg-surface-elevated text-content-secondary border-line-subtle hover:border-brand/40 hover:text-content-primary'"
              >
                {{ d.label }}
              </button>
            </div>
            <p v-if="newShift.days.length === 0" class="text-[11px] text-status-danger mt-1">
              * يرجى اختيار يوم عمل واحد على الأقل للوردية
            </p>
          </div>

          <!-- Start & End Time -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-content-primary mb-1">وقت البدء</label>
              <input
                v-model="newShift.start_time"
                type="time"
                class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary font-mono focus:border-brand focus:outline-hidden"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-content-primary mb-1">وقت الانتهاء</label>
              <input
                v-model="newShift.end_time"
                type="time"
                class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary font-mono focus:border-brand focus:outline-hidden"
              />
            </div>
          </div>

          <!-- Max Patients -->
          <div>
            <label class="block text-xs font-bold text-content-primary mb-1">الحد الأقصى لمرضى الوردية</label>
            <input
              v-model.number="newShift.max_patients"
              type="number"
              min="1"
              max="500"
              class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary font-mono focus:border-brand focus:outline-hidden"
            />
          </div>
        </div>
        <div class="px-5 py-3 bg-surface-elevated border-t border-line-subtle flex justify-between items-center">
          <button
            type="button"
            @click="showAddShift = false"
            class="px-4 py-2 text-xs font-bold text-content-secondary hover:text-content-primary cursor-pointer"
          >
            إلغاء
          </button>
          <button
            type="button"
            @click="handleSaveShift"
            :disabled="saving || newShift.days.length === 0 || !newShift.name.trim()"
            class="px-5 py-2 bg-brand hover:bg-brand-hover disabled:opacity-50 text-content-inverse rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <span v-if="saving">جاري الحفظ...</span>
            <span v-else>حفظ الوردية</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Emergency Day Off & Bulk Reschedule -->
    <div v-if="showEmergencyModal" class="fixed inset-0 z-50 overflow-y-auto bg-surface-overlay/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-surface-card border border-line-subtle rounded-2xl shadow-xl w-full max-w-lg overflow-hidden text-right" dir="rtl">
        <div class="px-5 py-3.5 bg-status-danger text-content-inverse font-bold text-sm flex items-center gap-2">
          <AlertTriangle class="w-4 h-4" />
          <span>عطلة طارئة واعتذار جماعي للمرضى</span>
        </div>
        <div class="p-5 space-y-4">
          <div class="p-3 bg-status-danger-bg border border-status-danger-border rounded-xl text-xs text-status-danger leading-relaxed">
            ⚠️ <strong>تنبيه:</strong> سيقوم النظام تلقائياً بتعليق الحجوزات وإرسال إشعار اعتذار فوري عبر الواتساب والرسائل القصيرة لكل المرضى المسجلين في هذا اليوم وترحيل مواعيدهم إلى اليوم التالي.
          </div>
          <div>
            <label class="block text-xs font-bold text-content-primary mb-1">الفرع المعتذر عنه</label>
            <select v-model="emergencyDayOff.clinic_id" class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary">
              <option v-for="b in settingsStore.branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-content-primary mb-1">تاريخ العطلة</label>
            <input v-model="emergencyDayOff.off_date" type="date" class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary font-mono" />
          </div>
          <div>
            <label class="block text-xs font-bold text-content-primary mb-1">سبب الاعتذار (يظهر في الرسالة للمرضى)</label>
            <input v-model="emergencyDayOff.reason" type="text" placeholder="عذر طبي طارئ للطبيب..." class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary" />
          </div>
        </div>
        <div class="px-5 py-3 bg-surface-elevated border-t border-line-subtle flex justify-between">
          <button @click="showEmergencyModal = false" class="px-4 py-2 text-xs font-bold text-content-secondary">إلغاء</button>
          <button @click="handleEmergencyDayOff" :disabled="saving" class="px-5 py-2 bg-status-danger text-content-inverse rounded-xl text-xs font-bold">
            تأكيد الاعتذار والترحيل
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useSettingsStore } from '../../stores/settings';
import alerts from '../../utils/alerts';
import {
  Building2,
  Plus,
  Clock,
  CalendarX,
  AlertTriangle,
  MapPin,
  Trash2,
} from 'lucide-vue-next';

const settingsStore = useSettingsStore();

const showAddBranch = ref(false);
const showAddShift = ref(false);
const showEmergencyModal = ref(false);

const DAYS_OF_WEEK = [
  { id: 6, label: 'السبت' },
  { id: 0, label: 'الأحد' },
  { id: 1, label: 'الإثنين' },
  { id: 2, label: 'الثلاثاء' },
  { id: 3, label: 'الأربعاء' },
  { id: 4, label: 'الخميس' },
  { id: 5, label: 'الجمعة' },
];

const getDayName = (dayOfWeek) => {
  const match = DAYS_OF_WEEK.find(d => d.id === Number(dayOfWeek));
  return match ? match.label : '';
};

const getBranchName = (branchId) => {
  const branch = settingsStore.branches.find(b => b.id === branchId);
  return branch ? branch.name : '';
};

const newBranch = ref({
  name: '',
  address: '',
  phone: '',
  examination_fee: 300,
  followup_fee: 100,
  followup_validity_days: 14,
});

const newShift = ref({
  clinic_id: null,
  name: 'الفترة المسائية',
  days: [6, 0, 1, 2, 3, 4],
  start_time: '17:00',
  end_time: '21:00',
  max_patients: 20,
});

const toggleDay = (dayId) => {
  const idx = newShift.value.days.indexOf(dayId);
  if (idx > -1) {
    newShift.value.days.splice(idx, 1);
  } else {
    newShift.value.days.push(dayId);
  }
};

const selectAllDaysExceptFriday = () => {
  newShift.value.days = [6, 0, 1, 2, 3, 4];
};

const selectAllDays = () => {
  newShift.value.days = [6, 0, 1, 2, 3, 4, 5];
};

const clearDays = () => {
  newShift.value.days = [];
};

const emergencyDayOff = ref({
  clinic_id: null,
  off_date: new Date().toISOString().substring(0, 10),
  reason: 'عذر طارئ للطبيب',
  action_type: 'auto_reschedule',
});

const saving = ref(false);

onMounted(async () => {
  await settingsStore.fetchAll();
  if (settingsStore.branches.length > 0) {
    newShift.value.clinic_id = settingsStore.branches[0].id;
    emergencyDayOff.value.clinic_id = settingsStore.branches[0].id;
  }
});

const openAddShift = (branchId) => {
  newShift.value.clinic_id = branchId;
  newShift.value.name = 'الفترة المسائية';
  newShift.value.days = [6, 0, 1, 2, 3, 4];
  newShift.value.start_time = '17:00';
  newShift.value.end_time = '21:00';
  newShift.value.max_patients = 20;
  showAddShift.value = true;
};

const handleSaveBranch = async () => {
  if (!newBranch.value.name.trim()) {
    alerts.warning('يرجى إدخال اسم الفرع / العيادة');
    return;
  }
  saving.value = true;
  try {
    await settingsStore.saveBranch(newBranch.value);
    showAddBranch.value = false;
    newBranch.value = { name: '', address: '', phone: '', examination_fee: 300, followup_fee: 100, followup_validity_days: 14 };
    alerts.success('تم حفظ الفرع بنجاح');
  } catch (e) {
    alerts.error('فشل حفظ الفرع', e.response?.data?.message || 'حدث خطأ');
  } finally {
    saving.value = false;
  }
};

const handleSaveShift = async () => {
  if (!newShift.value.name.trim()) {
    alerts.toast('يرجى كتابة اسم الوردية', 'warning');
    return;
  }
  if (!newShift.value.days || newShift.value.days.length === 0) {
    alerts.toast('يرجى اختيار يوم عمل واحد على الأقل', 'warning');
    return;
  }

  saving.value = true;
  try {
    const payload = {
      clinic_id: newShift.value.clinic_id,
      shift_name_ar: newShift.value.name,
      name: newShift.value.name,
      days: newShift.value.days,
      start_time: newShift.value.start_time,
      end_time: newShift.value.end_time,
      max_patients: newShift.value.max_patients,
    };
    await settingsStore.saveShift(payload);
    showAddShift.value = false;
    alerts.success('تمت إضافة وردية العمل بنجاح');
  } catch (e) {
    alerts.error('فشل حفظ الوردية', e.response?.data?.message || 'حدث خطأ');
  } finally {
    saving.value = false;
  }
};

const handleDeleteShift = async (shiftId) => {
  const confirmed = await alerts.confirm('تأكيد الحذف', 'هل تريد حذف هذه الوردية من جدول العيادة؟', 'نعم، احذف');
  if (!confirmed) return;
  try {
    await settingsStore.deleteShift(shiftId);
    alerts.toast('تم حذف الوردية بنجاح');
  } catch (e) {
    alerts.error('فشل الحذف', e.response?.data?.message || 'حدث خطأ');
  }
};

const handleEmergencyDayOff = async () => {
  const confirmed = await alerts.confirm('تنبيه هام جداً', 'سيتم تطبيق الإجراء على جميع حجوزات هذا اليوم وترحيلها وإرسال إشعارات فورية. هل تريد المتابعة؟', 'تأكيد الاعتذار والترحيل');
  if (!confirmed) return;
  saving.value = true;
  try {
    const res = await settingsStore.saveDayOff(emergencyDayOff.value);
    showEmergencyModal.value = false;
    alerts.success('تم بنجاح', res.message || 'تم تسجيل العطلة وترحيل الحجوزات');
  } catch (e) {
    alerts.error('فشل تنفيذ الإجراء', e.response?.data?.message || 'حدث خطأ');
  } finally {
    saving.value = false;
  }
};
</script>
