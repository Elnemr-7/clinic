<template>
  <div class="flex min-h-screen bg-slate-900">
    <AdminSidebar />
    <div class="flex-1 flex flex-col">
      <AdminHeader title="إدارة حسابات الأطباء والعيادات" />

      <main class="flex-1 p-8 space-y-6">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-xl font-bold text-white">الأطباء والعيادات المشتركة</h3>
            <p class="text-xs text-slate-400 mt-1">عرض وتحكم مطلق في كل عيادة، حدود الفروع، ومحافظ الرسائل</p>
          </div>
        </div>

        <!-- Doctors Table -->
        <div class="bg-slate-950 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
          <table class="w-full text-right text-sm">
            <thead class="bg-slate-900/60 border-b border-slate-800 text-xs text-slate-400 font-bold">
              <tr>
                <th class="p-4">الطبيب</th>
                <th class="p-4">الباقة والاشتراك</th>
                <th class="p-4">العيادات والفروع</th>
                <th class="p-4">محفظة الواتساب</th>
                <th class="p-4">محفظة الـ SMS</th>
                <th class="p-4">الحالة</th>
                <th class="p-4 text-center">الإجراءات</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="doc in doctors" :key="doc.id" class="hover:bg-slate-900/40 transition">
                <td class="p-4">
                  <div class="font-bold text-white">{{ doc.name }}</div>
                  <div class="text-xs text-slate-400">{{ doc.title_ar || 'طبيب' }}</div>
                  <div class="text-[11px] text-amber-400/80 font-mono mt-0.5">{{ doc.subdomain }}.platform.com</div>
                </td>
                <td class="p-4">
                  <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-800 text-slate-200">
                    {{ doc.plan?.name_ar }}
                  </span>
                  <div class="text-[11px] text-slate-400 mt-1">
                    ينتهي: {{ doc.subscription_ends_at ? doc.subscription_ends_at.substring(0, 10) : 'مفتوح' }}
                  </div>
                </td>
                <td class="p-4">
                  <span class="text-white font-bold">{{ doc.clinics_count ?? doc.clinics?.length ?? 0 }}</span>
                  <span class="text-xs text-slate-400"> / {{ doc.max_clinics_override === -1 ? 'غير محدود' : (doc.max_clinics_override ?? doc.plan?.max_clinics) }}</span>
                </td>
                <td class="p-4">
                  <div class="flex items-center gap-2">
                    <span :class="doc.whatsapp_enabled ? 'text-emerald-400' : 'text-slate-500'">●</span>
                    <span class="font-bold text-white">{{ doc.whatsapp_balance }}</span>
                    <span class="text-xs text-slate-400">رسالة</span>
                  </div>
                  <span class="text-[10px] text-slate-500">استهلك {{ doc.whatsapp_used_this_month }} هذا الشهر</span>
                </td>
                <td class="p-4">
                  <div class="flex items-center gap-2">
                    <span :class="doc.sms_enabled ? 'text-cyan-400' : 'text-slate-500'">●</span>
                    <span class="font-bold text-white">{{ doc.sms_balance }}</span>
                    <span class="text-xs text-slate-400">رسالة</span>
                  </div>
                  <span class="text-[10px] text-slate-500">استهلك {{ doc.sms_used_this_month }} هذا الشهر</span>
                </td>
                <td class="p-4">
                  <span
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold"
                    :class="doc.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border border-rose-500/30'"
                  >
                    {{ doc.status === 'active' ? 'نشط' : 'معلق' }}
                  </span>
                </td>
                <td class="p-4">
                  <div class="flex items-center justify-center gap-2">
                    <button
                      @click="openGodMode(doc)"
                      class="px-3 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold transition flex items-center gap-1"
                    >
                      <span>⚡</span>
                      <span>تحكم مطلق</span>
                    </button>
                    <button
                      @click="impersonate(doc)"
                      class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium transition flex items-center gap-1"
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
      </main>

      <EditDoctorGodModeModal
        :is-open="isGodModeOpen"
        :doctor="selectedDoctor"
        @close="isGodModeOpen = false"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import AdminSidebar from '../components/layout/AdminSidebar.vue';
import AdminHeader from '../components/layout/AdminHeader.vue';
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
