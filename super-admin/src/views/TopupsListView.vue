<template>
  <div class="space-y-6 text-right select-none font-sans" dir="rtl">
    <div class="flex justify-between items-center border-b border-line-subtle pb-4">
      <div>
        <h3 class="text-base font-black text-content-primary">شحنات محافظ الرسائل والاشتراكات</h3>
        <p class="text-xs text-content-muted mt-0.5">مراجعة واعتماد طلبات التحويل اليدوي وسجل عمليات الدفع الإلكتروني (فوري)</p>
      </div>
    </div>

    <div class="bg-surface-card border border-line-subtle rounded-2xl overflow-hidden shadow-xs">
      <table class="w-full text-right text-xs">
        <thead class="bg-surface-elevated border-b border-line-subtle font-bold text-content-secondary">
          <tr>
            <th class="p-3.5">رقم الطلب</th>
            <th class="p-3.5">الطبيب</th>
            <th class="p-3.5">نوع الباقة</th>
            <th class="p-3.5">الكمية</th>
            <th class="p-3.5">المبلغ</th>
            <th class="p-3.5">طريقة الدفع</th>
            <th class="p-3.5">الحالة</th>
            <th class="p-3.5 text-center">الإجراء</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-line-subtle">
          <tr v-for="topup in topups" :key="topup.id" class="hover:bg-surface-elevated/70 transition">
            <td class="p-3.5 font-mono text-content-muted">#{{ topup.id }}</td>
            <td class="p-3.5">
              <div class="font-bold text-content-primary">{{ topup.tenant?.name }}</div>
              <div class="text-[10px] text-content-muted font-mono">{{ topup.tenant?.subdomain }}</div>
            </td>
            <td class="p-3.5">
              <span
                class="px-2 py-0.5 rounded-lg text-[10px] font-bold border"
                :class="topup.package_type === 'whatsapp' ? 'bg-status-success-bg text-status-success border-status-success-border' : 'bg-status-info-bg text-status-info border-status-info-border'"
              >
                {{ topup.package_type === 'whatsapp' ? 'واتساب' : (topup.package_type === 'sms' ? 'SMS' : 'اشتراك') }}
              </span>
            </td>
            <td class="p-3.5 font-bold text-content-primary font-mono tabular-nums">{{ topup.quantity_added }} رسالة</td>
            <td class="p-3.5 font-bold text-brand font-mono tabular-nums">{{ topup.amount_egp }} ج.م</td>
            <td class="p-3.5 text-content-secondary">
              {{ topup.payment_method === 'fawry' ? 'فوري آلي' : 'تحويل بنكي / يدوي' }}
            </td>
            <td class="p-3.5">
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                :class="{
                  'bg-status-warning-bg text-status-warning border-status-warning-border': topup.status === 'pending',
                  'bg-status-success-bg text-status-success border-status-success-border': topup.status === 'paid',
                  'bg-status-danger-bg text-status-danger border-status-danger-border': topup.status === 'rejected',
                }"
              >
                {{ topup.status === 'paid' ? 'تم الشحن' : (topup.status === 'pending' ? 'بانتظار الاعتماد' : 'مرفوض') }}
              </span>
            </td>
            <td class="p-3.5 text-center">
              <div v-if="topup.status === 'pending'" class="flex items-center justify-center gap-1.5">
                <button
                  type="button"
                  @click="approve(topup.id)"
                  class="bg-brand hover:bg-brand-hover text-content-inverse font-bold px-2.5 py-1 rounded-lg text-xs transition cursor-pointer"
                >
                  اعتماد وشحن
                </button>
                <button
                  type="button"
                  @click="reject(topup.id)"
                  class="bg-status-danger-bg hover:opacity-90 text-status-danger border border-status-danger-border px-2.5 py-1 rounded-lg text-xs transition cursor-pointer"
                >
                  رفض
                </button>
              </div>
              <span v-else class="text-xs text-content-muted">مكتمل</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue';
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
