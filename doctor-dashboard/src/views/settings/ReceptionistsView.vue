<template>
  <div class="space-y-6 text-right select-none font-sans" dir="rtl">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line-subtle pb-4">
      <div>
        <h2 class="text-base font-black text-content-primary">3. طاقم الاستقبال وسكرتارية العيادة</h2>
        <p class="text-xs text-content-muted mt-0.5">إدارة حسابات الموظفين وصلاحيات الدخول على الفروع</p>
      </div>

      <button
        type="button"
        @click="openAddModal"
        class="px-3.5 py-2 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer"
      >
        <UserPlus class="w-4 h-4" />
        <span>إضافة موظف استقبال جديد</span>
      </button>
    </div>

    <!-- Content -->
    <div v-if="!settingsStore.receptionists || settingsStore.receptionists.length === 0" class="bg-surface-elevated rounded-2xl p-8 text-center border border-line-subtle text-xs text-content-muted">
      لا يوجد موظفو استقبال مسجلون حالياً
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="rec in settingsStore.receptionists"
        :key="rec.id"
        class="bg-surface-elevated rounded-2xl p-4 border border-line-subtle shadow-xs flex items-center justify-between"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-brand-subtle text-brand border border-brand-border rounded-xl flex items-center justify-center font-bold text-xs">
            {{ rec.name?.substring(0, 2) }}
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-xs font-black text-content-primary">{{ rec.name }}</h3>
              <span v-if="rec.phone" class="text-[10px] text-content-muted font-mono" dir="ltr">{{ rec.phone }}</span>
            </div>
            <p class="text-[11px] text-content-muted font-mono">{{ rec.email }}</p>
            <p class="text-[10px] text-brand font-medium mt-0.5">
              فرع: {{ rec.clinic?.name || rec.clinic?.name_ar || 'كافة الفروع' }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <span
            class="text-[10px] px-2 py-0.5 rounded-full font-bold"
            :class="rec.is_active !== false ? 'bg-status-success-bg text-status-success' : 'bg-status-warning-bg text-status-warning'"
          >
            {{ rec.is_active !== false ? 'نشط' : 'معطل' }}
          </span>
          <button
            type="button"
            @click="openEditModal(rec)"
            class="text-content-muted hover:text-brand p-1.5 rounded-lg hover:bg-surface-card transition cursor-pointer"
            title="تعديل بيانات الموظف"
          >
            <Pencil class="w-3.5 h-3.5" />
          </button>
          <button
            type="button"
            @click="handleDelete(rec.id)"
            class="text-content-muted hover:text-status-danger p-1.5 rounded-lg hover:bg-surface-card transition cursor-pointer"
            title="حذف الحساب"
          >
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Add / Edit Receptionist -->
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-surface-overlay/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-surface-card border border-line-subtle rounded-2xl shadow-xl w-full max-w-md overflow-hidden text-right animate-scale-in" dir="rtl">
        <div class="px-5 py-3.5 bg-brand text-content-inverse font-bold text-sm flex items-center justify-between">
          <span>{{ isEditing ? 'تعديل بيانات موظف الاستقبال' : 'إضافة موظف استقبال جديد' }}</span>
          <span class="text-xs opacity-80">{{ settingsStore.branches?.length || 0 }} فرع متاح</span>
        </div>
        <div class="p-5 space-y-3.5">
          <div>
            <label class="block text-xs font-bold text-content-primary mb-1">الاسم بالكامل <span class="text-status-danger">*</span></label>
            <input
              v-model="recForm.name"
              type="text"
              placeholder="مثال: سارة أحمد..."
              class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary focus:border-brand focus:outline-hidden"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-content-primary mb-1">البريد الإلكتروني للدخول <span class="text-status-danger">*</span></label>
            <input
              v-model="recForm.email"
              type="email"
              placeholder="sara@clinic.com"
              class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary font-mono focus:border-brand focus:outline-hidden"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-content-primary mb-1">رقم الهاتف</label>
            <input
              v-model="recForm.phone"
              type="tel"
              placeholder="010XXXXXXXX"
              class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary font-mono focus:border-brand focus:outline-hidden"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-content-primary mb-1">
              {{ isEditing ? 'كلمة المرور الجديدة (اختياري)' : 'كلمة المرور *' }}
            </label>
            <input
              v-model="recForm.password"
              type="password"
              :placeholder="isEditing ? 'اتركها فارغة للإبقاء على كلمة المرور الحالية' : '••••••••'"
              class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary font-mono focus:border-brand focus:outline-hidden"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-content-primary mb-1">
              الفرع المخصص <span class="text-status-danger">*</span>
            </label>
            <select
              v-model="recForm.clinic_id"
              class="w-full px-3 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary focus:border-brand focus:outline-hidden cursor-pointer"
            >
              <option :value="null" disabled>-- اختر الفرع المخصص لهذا الموظف --</option>
              <option
                v-for="b in settingsStore.branches"
                :key="b.id"
                :value="b.id"
              >
                {{ b.name || b.name_ar || ('فرع #' + b.id) }}
              </option>
            </select>
            <p v-if="!settingsStore.branches || settingsStore.branches.length === 0" class="text-[11px] text-status-danger mt-1">
              ⚠️ لا توجد فروع مسجلة حالياً. يرجى التوجه لتبويب "الفروع والورديات" وإضافة فرع أولاً.
            </p>
          </div>
          <div v-if="isEditing" class="flex items-center gap-2 pt-1">
            <input
              id="rec-is-active"
              v-model="recForm.is_active"
              type="checkbox"
              class="rounded border-line-subtle text-brand focus:ring-brand cursor-pointer"
            />
            <label for="rec-is-active" class="text-xs font-bold text-content-primary cursor-pointer">
              الحساب نشط ويستطيع تسجيل الدخول
            </label>
          </div>
        </div>
        <div class="px-5 py-3 bg-surface-elevated border-t border-line-subtle flex justify-between items-center">
          <button
            type="button"
            @click="showModal = false"
            class="px-4 py-2 text-xs font-bold text-content-secondary hover:text-content-primary cursor-pointer"
          >
            إلغاء
          </button>
          <button
            type="button"
            @click="handleSave"
            :disabled="saving || !recForm.clinic_id"
            class="px-5 py-2 bg-brand hover:bg-brand-hover disabled:opacity-50 text-content-inverse rounded-xl text-xs font-bold transition cursor-pointer shadow-xs"
          >
            <span v-if="saving">جاري الحفظ...</span>
            <span v-else>{{ isEditing ? 'حفظ التعديلات' : 'حفظ الموظف' }}</span>
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
import { UserPlus, Trash2, Pencil } from 'lucide-vue-next';

const settingsStore = useSettingsStore();

const showModal = ref(false);
const isEditing = ref(false);
const saving = ref(false);

const recForm = ref({
  id: null,
  name: '',
  email: '',
  phone: '',
  password: '',
  clinic_id: null,
  is_active: true,
});

onMounted(async () => {
  await settingsStore.fetchAll();
});

const openAddModal = async () => {
  if (!settingsStore.branches || settingsStore.branches.length === 0) {
    await settingsStore.fetchBranches();
  }
  isEditing.value = false;
  recForm.value = {
    id: null,
    name: '',
    email: '',
    phone: '',
    password: '',
    clinic_id: settingsStore.branches?.[0]?.id || null,
    is_active: true,
  };
  showModal.value = true;
};

const openEditModal = async (rec) => {
  if (!settingsStore.branches || settingsStore.branches.length === 0) {
    await settingsStore.fetchBranches();
  }
  isEditing.value = true;
  recForm.value = {
    id: rec.id,
    name: rec.name || '',
    email: rec.email || '',
    phone: rec.phone || '',
    password: '',
    clinic_id: rec.clinic_id || rec.clinic?.id || settingsStore.branches?.[0]?.id || null,
    is_active: rec.is_active !== false,
  };
  showModal.value = true;
};

const handleSave = async () => {
  if (!recForm.value.name.trim() || !recForm.value.email.trim()) {
    alerts.warning('يرجى إدخال اسم الموظف والبريد الإلكتروني');
    return;
  }
  if (!isEditing.value && !recForm.value.password) {
    alerts.warning('يرجى إدخال كلمة المرور لموظف الاستقبال الجديد');
    return;
  }
  if (!recForm.value.clinic_id) {
    alerts.warning('يرجى اختيار الفرع المخصص لموظف الاستقبال');
    return;
  }
  saving.value = true;
  try {
    await settingsStore.saveReceptionist(recForm.value);
    showModal.value = false;
    alerts.success(isEditing.value ? 'تم تحديث بيانات موظف الاستقبال بنجاح' : 'تمت إضافة موظف الاستقبال بنجاح');
  } catch (e) {
    alerts.error('فشل الحفظ', e.response?.data?.message || 'حدث خطأ أثناء حفظ البيانات');
  } finally {
    saving.value = false;
  }
};

const handleDelete = async (id) => {
  const confirmed = await alerts.confirm('تأكيد الحذف', 'هل أنت متأكد من حذف حساب موظف الاستقبال؟', 'نعم، احذف');
  if (!confirmed) return;
  try {
    await settingsStore.deleteReceptionist(id);
    alerts.toast('تم حذف حساب الموظف بنجاح');
  } catch (e) {
    alerts.error('فشل الحذف', e.response?.data?.message || 'حدث خطأ أثناء الحذف');
  }
};
</script>
