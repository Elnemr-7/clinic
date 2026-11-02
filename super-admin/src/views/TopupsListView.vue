<template>
  <div class="flex min-h-screen bg-slate-900">
    <AdminSidebar />
    <div class="flex-1 flex flex-col">
      <AdminHeader title="سجل طلبات شحن الرصيد وفوري" />

      <main class="flex-1 p-8 space-y-6">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-xl font-bold text-white">شحنات محافظ الرسائل والاشتراكات</h3>
            <p class="text-xs text-slate-400 mt-1">مراجعة واعتماد طلبات التحويل اليدوي وسجل عمليات الدفع الإلكتروني (فوري)</p>
          </div>
        </div>

        <div class="bg-slate-950 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
          <table class="w-full text-right text-sm">
            <thead class="bg-slate-900/60 border-b border-slate-800 text-xs text-slate-400 font-bold">
              <tr>
                <th class="p-4">رقم الطلب</th>
                <th class="p-4">الطبيب</th>
                <th class="p-4">نوع الباقة</th>
                <th class="p-4">الكمية</th>
                <th class="p-4">المبلغ</th>
                <th class="p-4">طريقة الدفع</th>
                <th class="p-4">الحالة</th>
                <th class="p-4 text-center">الإجراء</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="topup in topups" :key="topup.id" class="hover:bg-slate-900/40 transition">
                <td class="p-4 font-mono text-slate-400">#{{ topup.id }}</td>
                <td class="p-4">
                  <div class="font-bold text-white">{{ topup.tenant?.name }}</div>
                  <div class="text-[11px] text-slate-400">{{ topup.tenant?.subdomain }}</div>
                </td>
                <td class="p-4">
                  <span
                    class="px-2.5 py-1 rounded-lg text-xs font-bold"
                    :class="topup.package_type === 'whatsapp' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-cyan-500/10 text-cyan-400'"
                  >
                    {{ topup.package_type === 'whatsapp' ? 'واتساب' : (topup.package_type === 'sms' ? 'SMS' : 'اشتراك') }}
                  </span>
                </td>
                <td class="p-4 font-bold text-white">{{ topup.quantity_added }} رسالة</td>
                <td class="p-4 font-bold text-emerald-400">{{ topup.amount_egp }} ج.م</td>
                <td class="p-4 text-xs text-slate-300">
                  {{ topup.payment_method === 'fawry' ? 'فوري آلي' : 'تحويل بنكي / يدوي' }}
                </td>
                <td class="p-4">
                  <span
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold"
                    :class="{
                      'bg-amber-500/10 text-amber-400 border border-amber-500/30': topup.status === 'pending',
                      'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30': topup.status === 'paid',
                      'bg-rose-500/10 text-rose-400 border border-rose-500/30': topup.status === 'rejected',
                    }"
                  >
                    {{ topup.status === 'paid' ? 'تم الشحن' : (topup.status === 'pending' ? 'بانتظار الاعتماد' : 'مرفوض') }}
                  </span>
                </td>
                <td class="p-4 text-center">
                  <div v-if="topup.status === 'pending'" class="flex items-center justify-center gap-2">
                    <button
                      @click="approve(topup.id)"
                      class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition"
                    >
                      اعتماد وشحن
                    </button>
                    <button
                      @click="reject(topup.id)"
                      class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 px-3 py-1.5 rounded-lg text-xs transition"
                    >
                      رفض
                    </button>
                  </div>
                  <span v-else class="text-xs text-slate-500">مكتمل</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue';
import AdminSidebar from '../components/layout/AdminSidebar.vue';
import AdminHeader from '../components/layout/AdminHeader.vue';
import { useTopupsStore } from '../stores/topups';
import alerts from '../utils/alerts';

const topupsStore = useTopupsStore();
const topups = computed(() => topupsStore.topups);

onMounted(() => {
  topupsStore.fetchTopups();
});

const approve = async (id) => {
  const confirmed = await alerts.confirm('اعتماد وشحن الرصيد', 'هل ترغب في اعتماد هذا الطلب وشحن الرصيد للطبيب فوراً؟', 'نعم، اعتمد الطلب');
  if (confirmed) {
    await topupsStore.approveTopup(id);
    alerts.toast('تم شحن رصيد الطبيب بنجاح');
  }
};

const reject = async (id) => {
  const confirmed = await alerts.confirm('رفض الطلب', 'هل ترغب في رفض هذا الطلب؟', 'نعم، ارفض');
  if (confirmed) {
    await topupsStore.rejectTopup(id);
    alerts.toast('تم رفض الطلب');
  }
};
</script>
