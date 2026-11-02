<template>
  <div class="space-y-6 text-right select-none font-sans" dir="rtl">
    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-surface-card border border-line-subtle p-5 rounded-2xl shadow-xs">
        <div class="text-2xl mb-1.5">👨‍⚕️</div>
        <p class="text-xs text-content-muted font-bold">إجمالي الأطباء المشتركين</p>
        <h3 class="text-2xl font-black text-content-primary mt-1 font-mono tabular-nums">{{ stats?.total_doctors ?? 0 }}</h3>
        <span class="text-[10px] text-brand font-medium mt-1.5 block">حسابات نشطة على المنصة</span>
      </div>

      <div class="bg-surface-card border border-line-subtle p-5 rounded-2xl shadow-xs">
        <div class="text-2xl mb-1.5">🏥</div>
        <p class="text-xs text-content-muted font-bold">العيادات والفروع العاملة</p>
        <h3 class="text-2xl font-black text-content-primary mt-1 font-mono tabular-nums">{{ stats?.active_clinics ?? 0 }}</h3>
        <span class="text-[10px] text-status-info font-medium mt-1.5 block">فروع تستقبل المرضى اليوم</span>
      </div>

      <div class="bg-surface-card border border-line-subtle p-5 rounded-2xl shadow-xs">
        <div class="text-2xl mb-1.5">📅</div>
        <p class="text-xs text-content-muted font-bold">حجوزات الكشف اليوم</p>
        <h3 class="text-2xl font-black text-content-primary mt-1 font-mono tabular-nums">{{ stats?.bookings_today ?? 0 }}</h3>
        <span class="text-[10px] text-status-warning font-medium mt-1.5 block">عبر الموقع وعيادات الاستقبال</span>
      </div>

      <div class="bg-surface-card border border-line-subtle p-5 rounded-2xl shadow-xs">
        <div class="text-2xl mb-1.5">💵</div>
        <p class="text-xs text-content-muted font-bold">إيرادات الاشتراكات هذا الشهر</p>
        <h3 class="text-2xl font-black text-brand mt-1 font-mono tabular-nums">{{ stats?.monthly_revenue_egp ?? 0 }} ج.م</h3>
        <span class="text-[10px] text-brand font-medium mt-1.5 block">مدفوعات وفوري والتحويلات</span>
      </div>
    </div>

    <!-- Messaging Usage & Leads Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="bg-surface-card border border-line-subtle p-5 rounded-2xl shadow-xs">
        <h3 class="text-sm font-bold text-content-primary mb-3 flex items-center gap-2">
          <span>💬</span>
          <span>استهلاك رسائل الواتساب والـ SMS هذا الشهر</span>
        </h3>
        <div class="space-y-3">
          <div class="bg-surface-elevated p-3.5 rounded-xl border border-line-subtle flex justify-between items-center">
            <div>
              <p class="text-xs text-content-muted font-medium">رسائل الواتساب المستهلكة (Baileys)</p>
              <p class="text-lg font-bold text-status-success font-mono mt-0.5">{{ stats?.whatsapp_used_this_month ?? 0 }} رسالة</p>
            </div>
            <div class="text-2xl">🟢</div>
          </div>
          <div class="bg-surface-elevated p-3.5 rounded-xl border border-line-subtle flex justify-between items-center">
            <div>
              <p class="text-xs text-content-muted font-medium">رسائل الـ SMS الرسمية المرسلة</p>
              <p class="text-lg font-bold text-status-info font-mono mt-0.5">{{ stats?.sms_used_this_month ?? 0 }} رسالة</p>
            </div>
            <div class="text-2xl">📱</div>
          </div>
        </div>
      </div>

      <div class="bg-surface-card border border-line-subtle p-5 rounded-2xl shadow-xs flex flex-col justify-between">
        <div>
          <h3 class="text-sm font-bold text-content-primary mb-1 flex items-center gap-2">
            <span>🔔</span>
            <span>طلبات انضمام الأطباء المعلقة (Leads)</span>
          </h3>
          <p class="text-xs text-content-muted mb-4">أطباء جدد سجلوا عبر الصفحة التسويقية بانتظار التواصل والتفعيل</p>
          <div class="text-3xl font-black text-status-warning font-mono mb-2">
            {{ stats?.pending_leads ?? 0 }} طلب جديد
          </div>
        </div>
        <router-link
          to="/leads"
          class="w-full bg-surface-elevated hover:bg-surface-card text-brand border border-brand-border text-center font-bold py-2.5 rounded-xl text-xs transition"
        >
          عرض وتحويل طلبات الأطباء إلى حسابات نشطة ←
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue';
import { useDashboardStore } from '../stores/dashboard';

const dashboardStore = useDashboardStore();
const stats = computed(() => dashboardStore.stats);

onMounted(() => {
  dashboardStore.fetchStats();
});
</script>
