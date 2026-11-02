<template>
  <div class="space-y-6 text-right select-none font-sans" dir="rtl">
    <div class="flex justify-between items-center border-b border-line-subtle pb-4">
      <div>
        <h3 class="text-base font-black text-content-primary">الأطباء والعيادات المشتركة</h3>
        <p class="text-xs text-content-muted mt-0.5">عرض وتحكم مطلق في كل عيادة، حدود الفروع، ومحافظ الرسائل</p>
      </div>
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
          <tr v-for="doc in doctors" :key="doc.id" class="hover:bg-surface-elevated/70 transition">
            <td class="p-3.5">
              <div class="font-bold text-content-primary">{{ doc.name }}</div>
              <div class="text-[11px] text-content-muted">{{ doc.title_ar || 'طبيب' }}</div>
              <div class="text-[10px] text-brand font-mono mt-0.5">{{ doc.subdomain }}.platform.com</div>
            </td>
            <td class="p-3.5">
              <span class="inline-block px-2 py-0.5 rounded-lg text-[10px] font-bold bg-surface-elevated text-content-primary border border-line-subtle">
                {{ doc.plan?.name_ar }}
              </span>
              <div class="text-[10px] text-content-muted mt-1">
                ينتهي: {{ doc.subscription_ends_at ? doc.subscription_ends_at.substring(0, 10) : 'مفتوح' }}
              </div>
            </td>
            <td class="p-3.5">
              <span class="text-content-primary font-bold font-mono">{{ doc.clinics_count ?? doc.clinics?.length ?? 0 }}</span>
              <span class="text-[10px] text-content-muted"> / {{ doc.max_clinics_override === -1 ? 'غير محدود' : (doc.max_clinics_override ?? doc.plan?.max_clinics) }}</span>
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
                class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                :class="doc.status === 'active' ? 'bg-status-success-bg text-status-success border-status-success-border' : 'bg-status-danger-bg text-status-danger border-status-danger-border'"
              >
                {{ doc.status === 'active' ? 'نشط' : 'معلق' }}
              </span>
            </td>
            <td class="p-3.5">
              <div class="flex items-center justify-center gap-1.5">
                <button
                  type="button"
                  @click="openGodMode(doc)"
                  class="px-2.5 py-1 rounded-xl bg-status-warning-bg hover:opacity-90 text-status-warning border border-status-warning-border text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                >
                  <span>⚡</span>
                  <span>تحكم مطلق</span>
                </button>
                <button
                  type="button"
                  @click="impersonate(doc)"
                  class="px-2.5 py-1 rounded-xl bg-surface-elevated hover:bg-surface-card text-content-primary border border-line-subtle text-xs font-medium transition flex items-center gap-1 cursor-pointer"
                  title="دخول لحساب الطبيب للدعم الفني"
                >
                  <span>🔑</span>
                  <span>دخول</span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <EditDoctorGodModeModal
      :is-open="isGodModeOpen"
      :doctor="selectedDoctor"
      @close="isGodModeOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import EditDoctorGodModeModal from '../components/modals/EditDoctorGodModeModal.vue';
import { useDoctorsStore } from '../stores/doctors';
import { usePlansStore } from '../stores/plans';

const doctorsStore = useDoctorsStore();
const plansStore = usePlansStore();

const doctors = computed(() => doctorsStore.doctors);
const isGodModeOpen = ref(false);
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
  window.open(`http://localhost:5174/#/workspace?impersonate_token=${token}`, '_blank');
};
</script>
