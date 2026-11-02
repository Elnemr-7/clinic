<script setup>
import { ref, onMounted } from 'vue';
import { useSettingsStore } from '../../stores/settings';
import DoctorTopNav from '../../components/common/DoctorTopNav.vue';
import alerts from '../../utils/alerts';
import { Users, UserPlus, Mail, Phone, Lock, Building2, Check, ArrowRight } from 'lucide-vue-next';

const settingsStore = useSettingsStore();

const showAddModal = ref(false);
const newRec = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  clinic_id: null,
});

const saving = ref(false);

onMounted(async () => {
  await settingsStore.fetchAll();
  if (settingsStore.branches.length > 0) {
    newRec.value.clinic_id = settingsStore.branches[0].id;
  }
});

const handleSave = async () => {
  if (!newRec.value.name || !newRec.value.email || !newRec.value.password) {
    alerts.warning('يرجى ملء كافة الحقول الأساسية المطلوبة');
    return;
  }
  saving.value = true;
  try {
    await settingsStore.saveReceptionist(newRec.value);
    showAddModal.value = false;
    newRec.value = { name: '', email: '', phone: '', password: '', clinic_id: settingsStore.branches[0]?.id || null };
    alerts.success('تمت الإضافة بنجاح', 'تم إنشاء حساب موظف الاستقبال وتعيين صلاحياته بنجاح');
  } catch (e) {
    alerts.error('فشل حفظ موظف الاستقبال', e.response?.data?.message || 'حدث خطأ أثناء إنشاء الحساب');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-slate-100 flex flex-col font-sans text-right select-none" dir="rtl">
    <DoctorTopNav
      title="طاقم الاستقبال وسكرتارية العيادة"
      subtitle="إدارة حسابات الموظفين وصلاحيات الدخول على الفروع"
    >
      <template #actions>
        <button
          @click="showAddModal = true"
          class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-teal-500/20"
        >
          <UserPlus class="w-4 h-4" />
          <span>إضافة موظف استقبال جديد</span>
        </button>
      </template>
    </DoctorTopNav>

    <!-- Main Content -->
    <main class="flex-1 p-6 max-w-5xl mx-auto w-full space-y-4">
      <div v-if="!settingsStore.receptionists || settingsStore.receptionists.length === 0" class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm text-xs text-slate-400">
        لا يوجد موظفو استقبال مسجلون حالياً
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="rec in settingsStore.receptionists"
          :key="rec.id"
          class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between"
        >
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-teal-50 text-teal-700 rounded-xl flex items-center justify-center font-bold text-sm">
              {{ rec.name?.substring(0, 2) }}
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900">{{ rec.name }}</h3>
              <p class="text-xs text-slate-400 font-mono">{{ rec.email }}</p>
              <p class="text-[11px] text-teal-600 font-medium mt-0.5">فرع: {{ rec.clinic?.name || 'كافة الفروع' }}</p>
            </div>
          </div>

          <span class="text-[10px] px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-bold">
            نشط
          </span>
        </div>
      </div>
    </main>

    <!-- Modal: Add Receptionist -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden text-right" dir="rtl">
        <div class="px-6 py-4 bg-teal-600 text-white font-bold text-base">إضافة موظف استقبال</div>
        <div class="p-6 space-y-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">الاسم بالكامل</label>
            <input v-model="newRec.name" type="text" placeholder="سارة أحمد" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">البريد الإلكتروني</label>
            <input v-model="newRec.email" type="email" placeholder="reception@example.com" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">رقم الهاتف</label>
            <input v-model="newRec.phone" type="tel" placeholder="010XXXXXXXX" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">كلمة المرور</label>
            <input v-model="newRec.password" type="password" placeholder="••••••••" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">الفرع المخصص</label>
            <select v-model="newRec.clinic_id" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300">
              <option v-for="b in settingsStore.branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
          </div>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between">
          <button @click="showAddModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600">إلغاء</button>
          <button @click="handleSave" :disabled="saving" class="px-5 py-2 bg-teal-600 text-white rounded-xl text-xs font-bold">حفظ الموظف</button>
        </div>
      </div>
    </div>
  </div>
</template>
