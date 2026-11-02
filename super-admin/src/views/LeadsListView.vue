<template>
  <div class="space-y-6 text-right select-none font-sans" dir="rtl">
    <div class="flex justify-between items-center border-b border-line-subtle pb-4">
      <div>
        <h3 class="text-base font-black text-content-primary">طلبات الاشتراك من الموقع التسويقي</h3>
        <p class="text-xs text-content-muted mt-0.5">تواصل مع الأطباء الجدد وحوّلهم إلى حسابات مفعلة بنقرة واحدة</p>
      </div>
    </div>

    <div class="bg-surface-card border border-line-subtle rounded-2xl overflow-hidden shadow-xs">
      <table class="w-full text-right text-xs">
        <thead class="bg-surface-elevated border-b border-line-subtle font-bold text-content-secondary">
          <tr>
            <th class="p-3.5">اسم الطبيب</th>
            <th class="p-3.5">رقم الهاتف</th>
            <th class="p-3.5">التخصص</th>
            <th class="p-3.5">الباقة المطلوبة</th>
            <th class="p-3.5">ملاحظات</th>
            <th class="p-3.5">الحالة</th>
            <th class="p-3.5 text-center">الإجراء</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-line-subtle">
          <tr v-for="lead in leads" :key="lead.id" class="hover:bg-surface-elevated/70 transition">
            <td class="p-3.5 font-bold text-content-primary">{{ lead.doctor_name }}</td>
            <td class="p-3.5 text-brand font-mono" dir="ltr">{{ lead.phone }}</td>
            <td class="p-3.5 text-content-secondary">{{ lead.specialty }}</td>
            <td class="p-3.5">
              <span class="px-2 py-0.5 rounded-lg text-[10px] bg-surface-elevated text-content-primary border border-line-subtle font-medium">
                {{ lead.plan?.name_ar || 'غير محدد' }}
              </span>
            </td>
            <td class="p-3.5 text-content-muted max-w-xs truncate">{{ lead.notes || '-' }}</td>
            <td class="p-3.5">
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                :class="{
                  'bg-status-warning-bg text-status-warning border-status-warning-border': lead.status === 'new',
                  'bg-status-info-bg text-status-info border-status-info-border': lead.status === 'contacted',
                  'bg-status-success-bg text-status-success border-status-success-border': lead.status === 'converted',
                  'bg-status-danger-bg text-status-danger border-status-danger-border': lead.status === 'rejected',
                }"
              >
                {{
                  lead.status === 'new' ? 'جديد' :
                  lead.status === 'contacted' ? 'تم التواصل' :
                  lead.status === 'converted' ? 'تم التحويل' : 'مرفوض'
                }}
              </span>
            </td>
            <td class="p-3.5 text-center">
              <button
                v-if="lead.status !== 'converted'"
                type="button"
                @click="openConvert(lead)"
                class="bg-brand hover:bg-brand-hover text-content-inverse font-bold px-3 py-1 rounded-xl text-xs shadow-xs transition flex items-center gap-1 mx-auto cursor-pointer"
              >
                <span>⚡</span>
                <span>تحويل لحساب طبيب</span>
              </button>
              <span v-else class="text-xs text-status-success font-medium">مكتمل ✅</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <ConvertLeadModal
      :is-open="isConvertOpen"
      :lead="selectedLead"
      @close="isConvertOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
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
