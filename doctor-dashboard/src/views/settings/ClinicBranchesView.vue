<script setup>
import { ref, onMounted } from 'vue';
import { useSettingsStore } from '../../stores/settings';
import DoctorTopNav from '../../components/common/DoctorTopNav.vue';
import alerts from '../../utils/alerts';
import { Building2, Plus, Clock, CalendarX, AlertTriangle, Check, ArrowRight, MapPin, Phone, Trash2 } from 'lucide-vue-next';

const settingsStore = useSettingsStore();

const showAddBranch = ref(false);
const showAddShift = ref(false);
const showEmergencyModal = ref(false);

const selectedBranchId = ref(null);

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
  day_of_week: 'saturday',
  start_time: '17:00',
  end_time: '21:00',
  slot_duration_minutes: 15,
  max_patients: 20,
});

const emergencyDayOff = ref({
  clinic_id: null,
  off_date: new Date().toISOString().substring(0, 10),
  reason: 'عذر طارئ للطبيب',
  action_type: 'auto_reschedule', // auto_reschedule | cancel_notify_refund
});

const saving = ref(false);

onMounted(async () => {
  await settingsStore.fetchAll();
  if (settingsStore.branches.length > 0) {
    selectedBranchId.value = settingsStore.branches[0].id;
    newShift.value.clinic_id = selectedBranchId.value;
    emergencyDayOff.value.clinic_id = selectedBranchId.value;
  }
});

const handleSaveBranch = async () => {
  if (!newBranch.value.name.trim()) return;
  saving.value = true;
  try {
    await settingsStore.saveBranch(newBranch.value);
    showAddBranch.value = false;
    newBranch.value = { name: '', address: '', phone: '', examination_fee: 300, followup_fee: 100, followup_validity_days: 14 };
    alerts.success('تم حفظ الفرع بنجاح');
  } catch (e) {
    alerts.error('فشل حفظ الفرع', e.response?.data?.message || 'قد تكون تجاوزت الحد الأقصى للباقة');
  } finally {
    saving.value = false;
  }
};

const handleSaveShift = async () => {
  saving.value = true;
  try {
    await settingsStore.saveShift(newShift.value);
    showAddShift.value = false;
    alerts.success('تمت إضافة الوردية بنجاح');
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
  const confirmed = await alerts.confirm('تنبيه هام جداً', 'سيتم تطبيق الإجراء على جميع حجوزات هذا اليوم وإرسال إشعارات فورية عبر الواتساب والرسائل للمرضى. هل تريد المتابعة؟', 'تأكيد الاعتذار والترحيل');
  if (!confirmed) return;
  saving.value = true;
  try {
    const res = await settingsStore.saveDayOff(emergencyDayOff.value);
    showEmergencyModal.value = false;
    alerts.success('تم بنجاح', res.message || 'تم تسجيل العطلة الطارئة ومعالجة الحجوزات بنجاح');
  } catch (e) {
    alerts.error('فشل تنفيذ الإجراء', e.response?.data?.message || 'حدث خطأ');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-slate-100 flex flex-col font-sans text-right select-none" dir="rtl">
    <DoctorTopNav
      title="إدارة الفروع والورديات والعطلات الطارئة"
      subtitle="فروع العيادة ومواعيد العمل وجدول الإجازات والاعتذارات"
    >
      <template #actions>
        <button
          @click="showEmergencyModal = true"
          class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-rose-500/20"
        >
          <CalendarX class="w-4 h-4" />
          <span>اعتذار / عطلة طارئة</span>
        </button>

        <button
          @click="showAddBranch = true"
          class="px-3.5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-teal-500/20"
        >
          <Plus class="w-4 h-4" />
          <span>إضافة فرع</span>
        </button>
      </template>
    </DoctorTopNav>

    <!-- Main Content -->
    <main class="flex-1 p-6 max-w-6xl mx-auto w-full space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div
          v-for="branch in settingsStore.branches"
          :key="branch.id"
          class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-5 flex flex-col justify-between"
        >
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="p-2.5 bg-teal-50 text-teal-700 rounded-2xl">
                  <Building2 class="w-6 h-6" />
                </div>
                <div>
                  <h3 class="text-base font-bold text-slate-900">{{ branch.name }}</h3>
                  <p class="text-xs text-slate-400 flex items-center gap-1">
                    <MapPin class="w-3.5 h-3.5" />
                    <span>{{ branch.address || 'العنوان غير محدد' }}</span>
                  </p>
                </div>
              </div>

              <div class="text-left font-mono">
                <p class="text-[11px] text-slate-400">كود شاشة TV</p>
                <span class="text-xs font-black text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg">
                  {{ branch.tv_screen_code || '------' }}
                </span>
              </div>
            </div>

            <!-- Fees & Terms -->
            <div class="grid grid-cols-3 gap-2 bg-slate-50 p-3 rounded-2xl text-center text-xs">
              <div>
                <p class="text-slate-400 text-[10px]">سعر الكشف</p>
                <p class="font-bold text-slate-800 font-mono">{{ branch.examination_fee }} ج.م</p>
              </div>
              <div>
                <p class="text-slate-400 text-[10px]">سعر الاستشارة</p>
                <p class="font-bold text-slate-800 font-mono">{{ branch.followup_fee }} ج.م</p>
              </div>
              <div>
                <p class="text-slate-400 text-[10px]">فترة سماح الإعادة</p>
                <p class="font-bold text-slate-800 font-mono">{{ branch.followup_validity_days }} يوماً</p>
              </div>
            </div>

            <!-- Shifts for this Branch -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                  <Clock class="w-4 h-4 text-slate-400" />
                  <span>الورديات ومواعيد العمل ({{ branch.shifts?.length || 0 }})</span>
                </h4>
                <button
                  @click="newShift.clinic_id = branch.id; showAddShift = true"
                  class="text-[11px] font-bold text-teal-600 hover:underline"
                >
                  + إضافة وردية
                </button>
              </div>

              <div v-if="!branch.shifts || branch.shifts.length === 0" class="text-center py-4 bg-slate-50 border border-dashed rounded-xl text-xs text-slate-400">
                لا توجد مواعيد عمل مسجلة لهذا الفرع
              </div>

              <div v-else class="space-y-1.5">
                <div
                  v-for="sh in branch.shifts"
                  :key="sh.id"
                  class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs"
                >
                  <div>
                    <span class="font-bold text-slate-800">{{ sh.name || 'وردية' }}</span>
                    <span class="text-slate-400 text-[11px] mr-2">({{ sh.day_of_week }})</span>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="font-mono text-slate-600 font-semibold">
                      {{ sh.start_time?.substring(0, 5) }} - {{ sh.end_time?.substring(0, 5) }}
                    </span>
                    <button @click="handleDeleteShift(sh.id)" class="text-rose-500 hover:text-rose-700">
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal: Add Branch -->
    <div v-if="showAddBranch" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden text-right" dir="rtl">
        <div class="px-6 py-4 bg-teal-600 text-white font-bold text-base">إضافة فرع عيادة جديد</div>
        <div class="p-6 space-y-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">اسم الفرع</label>
            <input v-model="newBranch.name" type="text" placeholder="مثال: فرع المهندسين" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">العنوان التفصيلي</label>
            <input v-model="newBranch.address" type="text" placeholder="مثال: 15 شارع جامعة الدول العربية" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">هاتف العيادة</label>
            <input v-model="newBranch.phone" type="text" placeholder="010XXXXXXXX" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">سعر الكشف (ج.م)</label>
              <input v-model="newBranch.examination_fee" type="number" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">سعر الاستشارة (ج.م)</label>
              <input v-model="newBranch.followup_fee" type="number" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
            </div>
          </div>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between">
          <button @click="showAddBranch = false" class="px-4 py-2 text-xs font-semibold text-slate-600">إلغاء</button>
          <button @click="handleSaveBranch" :disabled="saving" class="px-5 py-2 bg-teal-600 text-white rounded-xl text-xs font-bold">حفظ الفرع</button>
        </div>
      </div>
    </div>

    <!-- Modal: Add Shift -->
    <div v-if="showAddShift" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden text-right" dir="rtl">
        <div class="px-6 py-4 bg-indigo-600 text-white font-bold text-base">إضافة وردية عمل</div>
        <div class="p-6 space-y-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">مسمى الوردية</label>
            <input v-model="newShift.name" type="text" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">اليوم</label>
            <select v-model="newShift.day_of_week" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300">
              <option value="saturday">السبت</option>
              <option value="sunday">الأحد</option>
              <option value="monday">الاثنين</option>
              <option value="tuesday">الثلاثاء</option>
              <option value="wednesday">الأربعاء</option>
              <option value="thursday">الخميس</option>
              <option value="friday">الجمعة</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">بداية الوردية</label>
              <input v-model="newShift.start_time" type="time" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">نهاية الوردية</label>
              <input v-model="newShift.end_time" type="time" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">وقت الكشف (دقيقة)</label>
              <input v-model="newShift.slot_duration_minutes" type="number" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">أقصى عدد مرضى</label>
              <input v-model="newShift.max_patients" type="number" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
            </div>
          </div>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between">
          <button @click="showAddShift = false" class="px-4 py-2 text-xs font-semibold text-slate-600">إلغاء</button>
          <button @click="handleSaveShift" :disabled="saving" class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold">حفظ الوردية</button>
        </div>
      </div>
    </div>

    <!-- Modal: Emergency Day Off (Correction 10) -->
    <div v-if="showEmergencyModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden text-right" dir="rtl">
        <div class="px-6 py-4 bg-rose-600 text-white flex items-center justify-between">
          <div class="flex items-center gap-2">
            <AlertTriangle class="w-5 h-5 text-amber-300" />
            <h3 class="font-bold text-base">تسجيل عطلة طارئة / اعتذار الطبيب</h3>
          </div>
        </div>
        <div class="p-6 space-y-4">
          <p class="text-xs text-slate-600">
            يتيح لك هذا الخيار إغلاق الحجوزات في يوم محدد لمعالجة ظرف طارئ، مع إرسال إشعارات جماعية للمرضى المحجوزين عبر الواتساب والـ SMS.
          </p>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">الفرع المتأثر</label>
            <select v-model="emergencyDayOff.clinic_id" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300">
              <option v-for="b in settingsStore.branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">تاريخ العطلة / الاعتذار</label>
            <input v-model="emergencyDayOff.off_date" type="date" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">سبب الاعتذار (يُرسل للمرضى)</label>
            <input v-model="emergencyDayOff.reason" type="text" placeholder="عذر طبي طارئ..." class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">الإجراء المطلوب تنفيذه على حجوزات المرضى:</label>
            <div class="space-y-2">
              <label class="p-3 rounded-xl border flex items-center gap-3 cursor-pointer" :class="emergencyDayOff.action_type === 'auto_reschedule' ? 'border-teal-500 bg-teal-50/50' : 'border-slate-200'">
                <input v-model="emergencyDayOff.action_type" value="auto_reschedule" type="radio" class="text-teal-600" />
                <div>
                  <p class="text-xs font-bold text-slate-800">ترحيل تلقائي لأقرب يوم عمل تالٍ متاح</p>
                  <p class="text-[11px] text-slate-400">نقل الحجوزات بنفس الترتيب وإرسال الموعد الجديد لكل مريض</p>
                </div>
              </label>

              <label class="p-3 rounded-xl border flex items-center gap-3 cursor-pointer" :class="emergencyDayOff.action_type === 'cancel_notify_refund' ? 'border-rose-500 bg-rose-50/50' : 'border-slate-200'">
                <input v-model="emergencyDayOff.action_type" value="cancel_notify_refund" type="radio" class="text-rose-600" />
                <div>
                  <p class="text-xs font-bold text-slate-800">إلغاء المواعيد وتنبيه المريض بإعادة الحجز</p>
                  <p class="text-[11px] text-slate-400">إلغاء الحجوزات وإرسال رسالة اعتذار رسمية مع رابط إعادة الحجز</p>
                </div>
              </label>
            </div>
          </div>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between">
          <button @click="showEmergencyModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600">إلغاء</button>
          <button @click="handleEmergencyDayOff" :disabled="saving" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-500/20">
            {{ saving ? 'جارٍ التنفيذ والإرسال...' : 'تأكيد الاعتذار وإرسال الإشعارات' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
