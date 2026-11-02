<template>
  <div class="flex min-h-screen bg-slate-900">
    <AdminSidebar />
    <div class="flex-1 flex flex-col">
      <AdminHeader title="طلبات انضمام الأطباء (Doctor Leads)" />

      <main class="flex-1 p-8 space-y-6">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-xl font-bold text-white">طلبات الاشتراك من الموقع التسويقي</h3>
            <p class="text-xs text-slate-400 mt-1">تواصل مع الأطباء الجدد وحوّلهم إلى حسابات مفعلة بنقرة واحدة</p>
          </div>
        </div>

        <div class="bg-slate-950 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
          <table class="w-full text-right text-sm">
            <thead class="bg-slate-900/60 border-b border-slate-800 text-xs text-slate-400 font-bold">
              <tr>
                <th class="p-4">اسم الطبيب</th>
                <th class="p-4">رقم الهاتف</th>
                <th class="p-4">التخصص</th>
                <th class="p-4">الباقة المطلوبة</th>
                <th class="p-4">ملاحظات</th>
                <th class="p-4">الحالة</th>
                <th class="p-4 text-center">الإجراء</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="lead in leads" :key="lead.id" class="hover:bg-slate-900/40 transition">
                <td class="p-4 font-bold text-white">{{ lead.doctor_name }}</td>
                <td class="p-4 text-amber-400 font-mono">{{ lead.phone }}</td>
                <td class="p-4 text-slate-300">{{ lead.specialty }}</td>
                <td class="p-4">
                  <span class="px-2.5 py-1 rounded-lg text-xs bg-slate-800 text-slate-300 font-medium">
                    {{ lead.plan?.name_ar || 'غير محدد' }}
                  </span>
                </td>
                <td class="p-4 text-xs text-slate-400 max-w-xs truncate">{{ lead.notes || '-' }}</td>
                <td class="p-4">
                  <span
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold"
                    :class="{
                      'bg-amber-500/10 text-amber-400 border border-amber-500/30': lead.status === 'new',
                      'bg-cyan-500/10 text-cyan-400 border border-cyan-500/30': lead.status === 'contacted',
                      'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30': lead.status === 'converted',
                      'bg-rose-500/10 text-rose-400 border border-rose-500/30': lead.status === 'rejected',
                    }"
                  >
                    {{
                      lead.status === 'new' ? 'جديد' :
                      lead.status === 'contacted' ? 'تم التواصل' :
                      lead.status === 'converted' ? 'تم التحويل' : 'مرفوض'
                    }}
                  </span>
                </td>
                <td class="p-4 text-center">
                  <button
                    v-if="lead.status !== 'converted'"
                    @click="openConvert(lead)"
                    class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-4 py-1.5 rounded-xl text-xs shadow-lg shadow-emerald-900/30 transition flex items-center gap-1 mx-auto"
                  >
                    <span>⚡</span>
                    <span>تحويل لحساب طبيب</span>
                  </button>
                  <span v-else class="text-xs text-slate-500 font-medium">مكتمل ✅</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </main>

      <ConvertLeadModal
        :is-open="isConvertOpen"
        :lead="selectedLead"
        @close="isConvertOpen = false"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import AdminSidebar from '../components/layout/AdminSidebar.vue';
import AdminHeader from '../components/layout/AdminHeader.vue';
import ConvertLeadModal from '../components/modals/ConvertLeadModal.vue';
import { useLeadsStore } from '../stores/leads';
import { usePlansStore } from '../stores/plans';

const leadsStore = useLeadsStore();
const plansStore = usePlansStore();

const leads = computed(() => leadsStore.leads);
const isConvertOpen = ref(false);
const selectedLead = ref(null);

onMounted(async () => {
  await Promise.all([
    leadsStore.fetchLeads(),
    plansStore.fetchPlans(),
  ]);
});

const openConvert = (lead) => {
  selectedLead.value = lead;
  isConvertOpen.value = true;
};
</script>
