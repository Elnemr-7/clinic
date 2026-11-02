<template>
  <div class="space-y-6 text-right select-none font-sans" dir="rtl">
    <div class="flex justify-between items-center border-b border-line-subtle pb-4">
      <div>
        <h3 class="text-base font-black text-content-primary">باقات المنصة والأسعار</h3>
        <p class="text-xs text-content-muted mt-0.5">تحديد المزايا، الأسعار الشهرية والسنوية، والحدود لكل اشتراك</p>
      </div>
      <button
        type="button"
        @click="openCreate"
        class="bg-brand hover:bg-brand-hover text-content-inverse font-bold px-4 py-2 rounded-xl text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer"
      >
        <span>➕</span>
        <span>إضافة باقة جديدة</span>
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div
        v-for="plan in plans"
        :key="plan.id"
        class="bg-surface-card border border-line-subtle rounded-2xl p-5 flex flex-col justify-between relative overflow-hidden shadow-xs"
      >
        <div>
          <div class="flex justify-between items-start mb-3">
            <h4 class="text-sm font-black text-content-primary">{{ plan.name_ar }}</h4>
            <span
              class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
              :class="plan.is_active ? 'bg-status-success-bg text-status-success border-status-success-border' : 'bg-status-danger-bg text-status-danger border-status-danger-border'"
            >
              {{ plan.is_active ? 'مفعلة' : 'معطلة' }}
            </span>
          </div>

          <div class="mb-5">
            <span class="text-2xl font-black text-brand font-mono tabular-nums">{{ plan.price_monthly }} ج.م</span>
            <span class="text-xs text-content-muted"> / شهرياً</span>
            <p class="text-[11px] text-content-muted mt-0.5 font-mono">أو {{ plan.price_yearly }} ج.م سنوياً</p>
          </div>

          <ul class="space-y-2.5 text-xs text-content-secondary border-t border-line-subtle pt-3.5">
            <li class="flex items-center gap-2">
              <span class="text-status-success font-bold">✓</span>
              <span>العيادات المسموحة: <strong class="text-content-primary font-mono">{{ plan.max_clinics === -1 ? 'غير محدود' : plan.max_clinics }}</strong></span>
            </li>
            <li class="flex items-center gap-2">
              <span class="text-status-success font-bold">✓</span>
              <span>رصيد واتساب شهري: <strong class="text-content-primary font-mono">{{ plan.free_whatsapp_quota_monthly }}</strong></span>
            </li>
            <li class="flex items-center gap-2">
              <span class="text-status-success font-bold">✓</span>
              <span>رصيد SMS شهري: <strong class="text-content-primary font-mono">{{ plan.free_sms_quota_monthly }}</strong></span>
            </li>
            <li class="flex items-center gap-2">
              <span :class="plan.allow_custom_domain ? 'text-status-success' : 'text-content-muted'">●</span>
              <span>دومين مخصص (Custom Domain): <strong class="text-content-primary">{{ plan.allow_custom_domain ? 'متاح' : 'غير متاح' }}</strong></span>
            </li>
            <li class="flex items-center gap-2">
              <span :class="plan.allow_custom_website ? 'text-status-success' : 'text-content-muted'">●</span>
              <span>موقع خارجي VIP: <strong class="text-content-primary">{{ plan.allow_custom_website ? 'متاح' : 'غير متاح' }}</strong></span>
            </li>
          </ul>
        </div>

        <div class="mt-6 pt-3 border-t border-line-subtle flex justify-between items-center">
          <span class="text-[11px] text-content-muted">{{ plan.tenants_count ?? 0 }} طبيب مشترك</span>
          <div class="flex gap-1.5">
            <button
              type="button"
              @click="openEdit(plan)"
              class="bg-surface-elevated hover:bg-surface-card text-content-primary border border-line-subtle px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer"
            >
              تعديل
            </button>
            <button
              type="button"
              @click="deletePlan(plan.id)"
              class="bg-status-danger-bg hover:opacity-90 text-status-danger border border-status-danger-border px-2.5 py-1 rounded-lg text-xs transition cursor-pointer"
            >
              حذف
            </button>
          </div>
        </div>
      </div>
    </div>

    <PlanEditModal
      :is-open="isModalOpen"
      :plan="selectedPlan"
      @close="isModalOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import PlanEditModal from '../components/modals/PlanEditModal.vue';
import { usePlansStore } from '../stores/plans';
import alerts from '../utils/alerts';

const plansStore = usePlansStore();
const plans = computed(() => plansStore.plans);

const isModalOpen = ref(false);
const selectedPlan = ref(null);

onMounted(() => {
  plansStore.fetchPlans();
});

const openCreate = () => {
  selectedPlan.value = null;
  isModalOpen.value = true;
};

const openEdit = (plan) => {
  selectedPlan.value = plan;
  isModalOpen.value = true;
};

const deletePlan = async (id) => {
  const confirmed = await alerts.confirm('تأكيد الحذف', 'هل أنت متأكد من حذف هذه الباقة؟', 'نعم، احذف');
  if (confirmed) {
    await plansStore.deletePlan(id);
    alerts.toast('تم حذف الباقة بنجاح');
  }
};
</script>
