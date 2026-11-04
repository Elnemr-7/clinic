<template>
  <div class="space-y-6 text-right select-none font-sans" dir="rtl">
    <div class="flex justify-between items-center border-b border-line-subtle pb-4">
      <div>
        <h3 class="text-base font-black text-content-primary">الأطباء والعيادات المشتركة</h3>
        <p class="text-xs text-content-muted mt-0.5">عرض وتحكم مطلق في كل عيادة، حدود الفروع، ومحافظ الرسائل</p>
      </div>

      <button
        @click="isCreateModalOpen = true"
        type="button"
        class="px-4 py-2 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl font-bold text-xs flex items-center gap-2 shadow-sm transition cursor-pointer"
      >
        <span class="text-sm font-black">+</span>
        <span>إضافة طبيب جديد</span>
      </button>
    </div>

    <!-- Doctors Table -->
    <div class="bg-surface-card border border-line-subtle rounded-2xl overflow-hidden shadow-xs">
      <table class="w-full text-right text-xs">
        <thead class="bg-surface-elevated border-b border-line-subtle font-bold text-content-secondary">
          <tr>
            <th class="p-3.5">الطبيب</th>
            <th class="p-3.5">الباقة والاشتراك</th>
            <th class="p-3.5">العيادات والفروع</th>
            <th class="p-3.5">محفظة الواتساب</th>
            <th class="p-3.5">محفظة الـ SMS</th>
            <th class="p-3.5">الحالة</th>
            <th class="p-3.5 text-center">الإجراءات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-line-subtle">
          <!-- Empty State -->
          <tr v-if="doctors.length === 0">
            <td colspan="7" class="p-12 text-center text-content-muted">
              <div class="text-4xl mb-2">🩺</div>
              <p class="text-sm font-bold text-content-primary mb-1">لا يوجد أطباء مسجلون في المنصة حالياً</p>
              <p class="text-xs text-content-muted mb-4">يمكنك إنشاء حساب أول طبيب وتعيين باقته وبيانات دخوله فوراً</p>
              <button
                @click="isCreateModalOpen = true"
                type="button"
                class="px-5 py-2.5 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl font-bold text-xs inline-flex items-center gap-2 shadow-sm transition cursor-pointer"
              >
                <span>+</span>
                <span>إضافة أول طبيب للمنصة</span>
              </button>
            </td>
          </tr>

          <tr v-for="doc in doctors" :key="doc.id" class="hover:bg-surface-elevated/70 transition">
            <td class="p-3.5">
              <div class="font-bold text-content-primary">{{ doc.name }}</div>
              <div class="text-[11px] text-content-muted">{{ doc.title_ar || 'طبيب' }}</div>
              <div class="text-[10px] text-brand font-mono mt-0.5">{{ doc.subdomain }}.platform.com</div>
              <div class="mt-1 flex items-center gap-1">
                <span
                  class="text-[9px] font-bold px-1.5 py-0.5 rounded border"
                  :class="getDoctorWebsiteBadge(doc).classes"
                >
                  {{ getDoctorWebsiteBadge(doc).label }}
                </span>
              </div>
            </td>
            <td class="p-3.5">
              <span class="inline-block px-2 py-0.5 rounded-lg text-[10px] font-bold bg-surface-elevated text-content-primary border border-line-subtle">
                {{ doc.plan?.name_ar || 'الخطة القياسية' }}
              </span>
              <div class="text-[10px] text-content-muted mt-1">
                ينتهي: {{ doc.subscription_ends_at ? doc.subscription_ends_at.substring(0, 10) : 'مفتوح' }}
              </div>
            </td>
            <td class="p-3.5">
              <span class="text-content-primary font-bold font-mono">{{ doc.clinics_count ?? doc.clinics?.length ?? 0 }}</span>
              <span class="text-[10px] text-content-muted"> / {{ doc.max_clinics_override === -1 ? 'غير محدود' : (doc.max_clinics_override ?? doc.plan?.max_clinics ?? 1) }}</span>
            </td>
            <td class="p-3.5">
              <div class="flex items-center gap-1.5 font-mono">
                <span :class="doc.whatsapp_enabled ? 'text-status-success' : 'text-content-muted'">●</span>
                <span class="font-bold text-content-primary">{{ doc.whatsapp_balance }}</span>
                <span class="text-[10px] text-content-muted">رسالة</span>
              </div>
              <span class="text-[9px] text-content-muted">استهلك {{ doc.whatsapp_used_this_month }} هذا الشهر</span>
            </td>
            <td class="p-3.5">
              <div class="flex items-center gap-1.5 font-mono">
                <span :class="doc.sms_enabled ? 'text-status-info' : 'text-content-muted'">●</span>
                <span class="font-bold text-content-primary">{{ doc.sms_balance }}</span>
                <span class="text-[10px] text-content-muted">رسالة</span>
              </div>
              <span class="text-[9px] text-content-muted">استهلك {{ doc.sms_used_this_month }} هذا الشهر</span>
            </td>
            <td class="p-3.5">
              <span
                class="px-2.5 py-1 rounded-full text-[10px] font-bold"
                :class="doc.status === 'active' ? 'bg-status-success-bg text-status-success' : 'bg-status-danger-bg text-status-danger'"
              >
                {{ doc.status === 'active' ? 'نشط' : 'معطل' }}
              </span>
            </td>
            <td class="p-3.5">
              <div class="flex items-center justify-center gap-1.5">
                <button
                  @click="openGodMode(doc)"
                  class="px-2.5 py-1 rounded-xl bg-status-warning text-white font-bold text-xs shadow-xs hover:opacity-90 transition flex items-center gap-1 cursor-pointer"
                  title="التحكم الكامل بالحساب والحدود"
                >
                  <span>⚡</span>
                  <span>تحكم مطلق</span>
                </button>
                <button
                  @click="impersonate(doc)"
                  class="px-2.5 py-1 rounded-xl bg-surface-elevated hover:bg-surface-card text-content-primary border border-line-subtle text-xs font-medium transition flex items-center gap-1 cursor-pointer"
                  title="دخول لحساب الطبيب للدعم الفني"
                >
                  <span>🔑</span>
                  <span>دخول</span>
                </button>
                <button
                  @click="confirmDelete(doc)"
                  class="px-2.5 py-1 rounded-xl bg-status-danger/10 text-status-danger hover:bg-status-danger hover:text-white border border-status-danger/30 text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                  title="حذف حساب الطبيب نهائياً"
                >
                  <span>🗑️</span>
                  <span>حذف</span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modals -->
    <CreateDoctorModal
      :is-open="isCreateModalOpen"
      @close="isCreateModalOpen = false"
      @created="handleDoctorCreated"
    />

    <EditDoctorGodModeModal
      :is-open="isGodModeOpen"
      :doctor="selectedDoctor"
      @close="isGodModeOpen = false"
      @saved="handleDoctorSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import EditDoctorGodModeModal from '../components/modals/EditDoctorGodModeModal.vue';
import CreateDoctorModal from '../components/modals/CreateDoctorModal.vue';
import { useDoctorsStore } from '../stores/doctors';
import { usePlansStore } from '../stores/plans';
import { showConfirm, showSuccess, showError } from '../utils/alerts';

const doctorsStore = useDoctorsStore();
const plansStore = usePlansStore();

const doctors = computed(() => doctorsStore.doctors);
const isGodModeOpen = ref(false);
const isCreateModalOpen = ref(false);
const selectedDoctor = ref(null);

onMounted(async () => {
  await Promise.all([
    doctorsStore.fetchDoctors(),
    plansStore.fetchPlans(),
  ]);
});

const openGodMode = (doc) => {
  selectedDoctor.value = doc;
  isGodModeOpen.value = true;
};

const impersonate = async (doc) => {
  const result = await doctorsStore.impersonateDoctor(doc.id);
  const token = result.token;
  window.open(`http://localhost:5174/workspace?impersonate_token=${token}`, '_blank');
};

const confirmDelete = async (doc) => {
  const confirmed = await showConfirm(
    `حذف حساب ${doc.name}؟`,
    'تحذير: سيتم حذف الطبيب وجميع عياداته ومواعيده وبياناته نهائياً ولا يمكن التراجع عن هذا الإجراء.',
    'نعم، احذف الطبيب',
    'إلغاء'
  );

  if (!confirmed) return;

  try {
    await doctorsStore.deleteDoctor(doc.id);
    showSuccess('تم الحذف', `تم حذف حساب الطبيب (${doc.name}) بنجاح.`);
  } catch (err) {
    showError('فشل الحذف', err.response?.data?.message || 'حدث خطأ أثناء محاولة حذف الطبيب');
  }
};

const getDoctorWebsiteBadge = (doc) => {
  let mode = doc.website_settings?.website_mode;
  if (!mode) {
    if (doc.is_custom_standalone_website && doc.custom_domain) mode = 4;
    else if (doc.is_custom_standalone_website) mode = 3;
    else if (doc.custom_domain) mode = 2;
    else mode = 1;
  }
  mode = Number(mode);

  if (mode === 4) {
    return {
      label: `VIP 4: موقع مخصص + ${doc.custom_domain || 'دومين خاص'}`,
      classes: 'bg-purple-500/20 text-purple-300 border-purple-500/30',
    };
  }
  if (mode === 3) {
    return {
      label: 'VIP 3: موقع مخصص (كود خاص)',
      classes: 'bg-purple-500/20 text-purple-300 border-purple-500/30',
    };
  }
  if (mode === 2) {
    return {
      label: `نموذج 2: قالب المنصة + ${doc.custom_domain || 'دومين خاص'}`,
      classes: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
    };
  }
  return {
    label: 'نموذج 1: قالب المنصة القياسي',
    classes: 'bg-slate-800 text-slate-300 border-slate-700',
  };
};

const handleDoctorCreated = () => {
  doctorsStore.fetchDoctors();
};

const handleDoctorSaved = () => {
  doctorsStore.fetchDoctors();
};
</script>
