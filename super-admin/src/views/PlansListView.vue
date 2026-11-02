<template>
  <div class="flex min-h-screen bg-slate-900">
    <AdminSidebar />
    <div class="flex-1 flex flex-col">
      <AdminHeader title="إدارة باقات الاشتراك والأسعار" />

      <main class="flex-1 p-8 space-y-6">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-xl font-bold text-white">باقات المنصة</h3>
            <p class="text-xs text-slate-400 mt-1">تحديد المزايا، الأسعار الشهرية والسنوية، والحدود لكل اشتراك</p>
          </div>
          <button
            @click="openCreate"
            class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-5 py-2.5 rounded-xl text-xs shadow-lg shadow-emerald-900/30 transition flex items-center gap-2"
          >
            <span>➕</span>
            <span>إضافة باقة جديدة</span>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div
            v-for="plan in plans"
            :key="plan.id"
            class="bg-slate-950 border border-slate-800 rounded-3xl p-6 flex flex-col justify-between relative overflow-hidden shadow-xl"
          >
            <div>
              <div class="flex justify-between items-start mb-4">
                <h4 class="text-lg font-bold text-white">{{ plan.name_ar }}</h4>
                <span
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                  :class="plan.is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'"
                >
                  {{ plan.is_active ? 'مفعلة' : 'معطلة' }}
                </span>
              </div>

              <div class="mb-6">
                <span class="text-3xl font-black text-emerald-400">{{ plan.price_monthly }} ج.م</span>
                <span class="text-xs text-slate-400"> / شهرياً</span>
                <p class="text-xs text-slate-500 mt-1">أو {{ plan.price_yearly }} ج.م سنوياً</p>
              </div>

              <ul class="space-y-3 text-xs text-slate-300 border-t border-slate-800/80 pt-4">
                <li class="flex items-center gap-2">
                  <span class="text-emerald-400">✓</span>
                  <span>العيادات المسموحة: <strong>{{ plan.max_clinics === -1 ? 'غير محدود' : plan.max_clinics }}</strong></span>
                </li>
                <li class="flex items-center gap-2">
                  <span class="text-emerald-400">✓</span>
                  <span>رصيد واتساب شهري: <strong>{{ plan.free_whatsapp_quota_monthly }}</strong></span>
                </li>
                <li class="flex items-center gap-2">
                  <span class="text-emerald-400">✓</span>
                  <span>رصيد SMS شهري: <strong>{{ plan.free_sms_quota_monthly }}</strong></span>
                </li>
                <li class="flex items-center gap-2">
                  <span :class="plan.allow_custom_domain ? 'text-emerald-400' : 'text-slate-600'">●</span>
                  <span>دومين مخصص (Custom Domain): <strong>{{ plan.allow_custom_domain ? 'متاح' : 'غير متاح' }}</strong></span>
                </li>
                <li class="flex items-center gap-2">
                  <span :class="plan.allow_custom_website ? 'text-emerald-400' : 'text-slate-600'">●</span>
                  <span>موقع خارجي API VIP: <strong>{{ plan.allow_custom_website ? 'متاح' : 'غير متاح' }}</strong></span>
                </li>
              </ul>
            </div>

            <div class="mt-8 pt-4 border-t border-slate-800 flex justify-between items-center">
              <span class="text-xs text-slate-500">{{ plan.tenants_count ?? 0 }} طبيب مشترك</span>
              <div class="flex gap-2">
                <button
                  @click="openEdit(plan)"
                  class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1.5 rounded-lg text-xs font-bold transition"
                >
                  تعديل
                </button>
                <button
                  @click="deletePlan(plan.id)"
                  class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 px-3 py-1.5 rounded-lg text-xs transition"
                >
                  حذف
                </button>
              </div>
            </div>
          </div>
        </div>
      </main>

      <PlanEditModal
        :is-open="isModalOpen"
        :plan="selectedPlan"
        @close="isModalOpen = false"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import AdminSidebar from '../components/layout/AdminSidebar.vue';
import AdminHeader from '../components/layout/AdminHeader.vue';
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
