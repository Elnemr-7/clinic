<template>
  <div class="flex min-h-screen bg-slate-900">
    <AdminSidebar />
    <div class="flex-1 flex flex-col">
      <AdminHeader title="لوحة الإحصائيات والمؤشرات العامة" />

      <main class="flex-1 p-8 space-y-8">
        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="bg-slate-950 border border-slate-800 p-6 rounded-2xl relative overflow-hidden">
            <div class="text-3xl mb-2">👨‍⚕️</div>
            <p class="text-xs text-slate-400 font-medium">إجمالي الأطباء المشتركين</p>
            <h3 class="text-3xl font-bold text-white mt-1">{{ stats?.total_doctors ?? 0 }}</h3>
            <span class="text-[10px] text-emerald-400 mt-2 block">حسابات نشطة على المنصة</span>
          </div>

          <div class="bg-slate-950 border border-slate-800 p-6 rounded-2xl relative overflow-hidden">
            <div class="text-3xl mb-2">🏥</div>
            <p class="text-xs text-slate-400 font-medium">العيادات والفروع العاملة</p>
            <h3 class="text-3xl font-bold text-white mt-1">{{ stats?.active_clinics ?? 0 }}</h3>
            <span class="text-[10px] text-cyan-400 mt-2 block">فروع تستقبل المرضى اليوم</span>
          </div>

          <div class="bg-slate-950 border border-slate-800 p-6 rounded-2xl relative overflow-hidden">
            <div class="text-3xl mb-2">📅</div>
            <p class="text-xs text-slate-400 font-medium">حجوزات الكشف اليوم</p>
            <h3 class="text-3xl font-bold text-white mt-1">{{ stats?.bookings_today ?? 0 }}</h3>
            <span class="text-[10px] text-amber-400 mt-2 block">عبر الموقع وعيادات الاستقبال</span>
          </div>

          <div class="bg-slate-950 border border-slate-800 p-6 rounded-2xl relative overflow-hidden">
            <div class="text-3xl mb-2">💵</div>
            <p class="text-xs text-slate-400 font-medium">إيرادات الاشتراكات هذا الشهر</p>
            <h3 class="text-3xl font-bold text-emerald-400 mt-1">{{ stats?.monthly_revenue_egp ?? 0 }} ج.م</h3>
            <span class="text-[10px] text-emerald-400 mt-2 block">مدفوعات وفوري والتحويلات</span>
          </div>
        </div>

        <!-- Messaging Usage & Leads Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-slate-950 border border-slate-800 p-6 rounded-2xl">
            <h3 class="text-base font-bold text-white mb-4 flex items-center gap-2">
              <span>💬</span>
              <span>استهلاك رسائل الواتساب والـ SMS هذا الشهر</span>
            </h3>
            <div class="space-y-4">
              <div class="bg-slate-900 p-4 rounded-xl border border-slate-800 flex justify-between items-center">
                <div>
                  <p class="text-xs text-slate-400 font-medium">رسائل الواتساب المستهلكة (Baileys)</p>
                  <p class="text-xl font-bold text-emerald-400 mt-1">{{ stats?.whatsapp_used_this_month ?? 0 }} رسالة</p>
                </div>
                <div class="text-3xl">🟢</div>
              </div>
              <div class="bg-slate-900 p-4 rounded-xl border border-slate-800 flex justify-between items-center">
                <div>
                  <p class="text-xs text-slate-400 font-medium">رسائل الـ SMS الرسمية المرسلة</p>
                  <p class="text-xl font-bold text-cyan-400 mt-1">{{ stats?.sms_used_this_month ?? 0 }} رسالة</p>
                </div>
                <div class="text-3xl">📱</div>
              </div>
            </div>
          </div>

          <div class="bg-slate-950 border border-slate-800 p-6 rounded-2xl flex flex-col justify-between">
            <div>
              <h3 class="text-base font-bold text-white mb-2 flex items-center gap-2">
                <span>🔔</span>
                <span>طلبات انضمام الأطباء المعلقة (Leads)</span>
              </h3>
              <p class="text-xs text-slate-400 mb-6">أطباء جدد سجلوا عبر الصفحة التسويقية بانتظار التواصل والتفعيل</p>
              <div class="text-4xl font-black text-amber-400 mb-2">
                {{ stats?.pending_leads ?? 0 }} طلب جديد
              </div>
            </div>
            <router-link
              to="/leads"
              class="w-full bg-slate-900 hover:bg-slate-800 text-amber-300 border border-amber-500/30 text-center font-bold py-3 rounded-xl text-sm transition"
            >
              عرض وتحويل طلبات الأطباء إلى حسابات نشطة ←
            </router-link>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue';
import AdminSidebar from '../components/layout/AdminSidebar.vue';
import AdminHeader from '../components/layout/AdminHeader.vue';
import { useDashboardStore } from '../stores/dashboard';

const dashboardStore = useDashboardStore();
const stats = computed(() => dashboardStore.stats);

onMounted(() => {
  dashboardStore.fetchStats();
});
</script>
