<script setup>
import { ref, onMounted } from 'vue';
import { useCashboxStore } from '../../../stores/cashbox';
import alerts from '../../../utils/alerts';
import { DollarSign, ArrowDownRight, ArrowUpRight, Plus, X, Wallet } from 'lucide-vue-next';

const props = defineProps({
  isOpen: Boolean,
  clinicId: [Number, String],
});

const emit = defineEmits(['close']);
const cashboxStore = useCashboxStore();

const expenseAmount = ref('');
const expenseCategory = ref('supplies'); // supplies | utilities | maintenance | other
const expenseDescription = ref('');
const recordingExpense = ref(false);

const loadSummary = async () => {
  if (props.clinicId) {
    await cashboxStore.fetchSummary(props.clinicId);
  }
};

onMounted(() => {
  loadSummary();
});

const handleAddExpense = async () => {
  if (!expenseAmount.value || Number(expenseAmount.value) <= 0) {
    alerts.warning('يرجى تحديد مبلغ المصروف بشكل صحيح');
    return;
  }
  if (!expenseDescription.value) {
    alerts.warning('يرجى كتابة بيان / سبب المصروف');
    return;
  }

  recordingExpense.value = true;
  try {
    const mappedCategory = expenseCategory.value === 'supplies' ? 'clinic_supplies' : (expenseCategory.value === 'utilities' ? 'utilities' : 'other_expense');
    await cashboxStore.recordExpense({
      clinic_id: props.clinicId,
      amount: Number(expenseAmount.value),
      category: mappedCategory,
      description: expenseDescription.value,
      description_ar: expenseDescription.value,
    });
    expenseAmount.value = '';
    expenseDescription.value = '';
    alerts.toast('تم تسجيل المصروف في الخزينة بنجاح');
  } catch (e) {
    alerts.error('فشل تسجيل المصروف', e.response?.data?.message || 'حدث خطأ');
  } finally {
    recordingExpense.value = false;
  }
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" @click.self="$emit('close')" class="fixed inset-0 z-50 overflow-y-auto bg-surface-overlay/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-surface-card border border-line-subtle rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden text-right text-content-primary" dir="rtl">
      <!-- Header -->
      <div class="px-6 py-4 bg-brand text-content-inverse flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-white/10 rounded-xl">
            <Wallet class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-xl font-bold">خزينة العيادة والدرج النقدي</h3>
            <p class="text-xs text-brand-subtle/80 font-medium">حركة الإيرادات اليومية والمصروفات النثرية</p>
          </div>
        </div>
        <button @click="$emit('close')" class="p-2 text-content-inverse/80 hover:text-content-inverse hover:bg-white/10 rounded-xl transition cursor-pointer">
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 overflow-y-auto space-y-6 flex-1 bg-surface-app">
        <!-- 3 Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="p-4 bg-status-success-bg border border-status-success-border rounded-2xl">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-status-success">إجمالي المقبوضات اليوم</span>
              <ArrowDownRight class="w-4 h-4 text-status-success" />
            </div>
            <p class="text-2xl font-black text-status-success font-mono">
              {{ cashboxStore.summary.total_income || 0 }} <span class="text-xs font-normal">ج.م</span>
            </p>
          </div>

          <div class="p-4 bg-status-danger-bg border border-status-danger-border rounded-2xl">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-status-danger">إجمالي المصروفات اليوم</span>
              <ArrowUpRight class="w-4 h-4 text-status-danger" />
            </div>
            <p class="text-2xl font-black text-status-danger font-mono">
              {{ cashboxStore.summary.total_expense || 0 }} <span class="text-xs font-normal">ج.م</span>
            </p>
          </div>

          <div class="p-4 bg-surface-card border border-brand-border rounded-2xl shadow-sm">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-content-secondary">صافي النقدية بالدرج</span>
              <Wallet class="w-4 h-4 text-brand" />
            </div>
            <p class="text-2xl font-black text-brand font-mono">
              {{ cashboxStore.summary.net_in_drawer || 0 }} <span class="text-xs font-normal text-content-muted">ج.م</span>
            </p>
          </div>
        </div>

        <!-- Add Expense Section -->
        <div class="p-4 bg-surface-card border border-line-subtle rounded-2xl space-y-3">
          <h4 class="text-xs font-bold text-content-primary">تسجيل صرف نثريات أو مصروفات من الدرج</h4>
          <div class="grid grid-cols-12 gap-3 items-center">
            <div class="col-span-12 md:col-span-3">
              <input
                v-model="expenseAmount"
                type="number"
                placeholder="المبلغ (ج.م)"
                class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-xl border border-line-subtle focus:ring-2 focus:ring-brand font-mono"
              />
            </div>
            <div class="col-span-12 md:col-span-3">
              <select
                v-model="expenseCategory"
                class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-xl border border-line-subtle focus:ring-2 focus:ring-brand"
              >
                <option value="supplies">مستلزمات وضيافة</option>
                <option value="utilities">فواتير ونثريات</option>
                <option value="maintenance">صيانة دورية</option>
                <option value="other">أخرى</option>
              </select>
            </div>
            <div class="col-span-12 md:col-span-4">
              <input
                v-model="expenseDescription"
                type="text"
                placeholder="البيان (شراء أدوات نظافة، ضيافة، ...)"
                class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-xl border border-line-subtle focus:ring-2 focus:ring-brand"
              />
            </div>
            <div class="col-span-12 md:col-span-2">
              <button
                @click="handleAddExpense"
                :disabled="recordingExpense"
                type="button"
                class="w-full py-2 bg-status-danger hover:opacity-90 text-white rounded-xl text-xs font-bold transition disabled:opacity-50 flex items-center justify-center gap-1 cursor-pointer"
              >
                <Plus class="w-4 h-4" />
                <span>خصم</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Today Transactions Table -->
        <div>
          <h4 class="text-xs font-bold text-content-secondary mb-3">حركات الدرج اليومية المسجلة:</h4>
          <div v-if="!cashboxStore.summary.transactions || cashboxStore.summary.transactions.length === 0" class="text-center py-6 border border-dashed border-line-subtle rounded-xl text-xs text-content-muted">
            لا توجد حركات مسجلة اليوم حتى الآن
          </div>

          <div v-else class="divide-y divide-line-subtle border border-line-subtle rounded-xl overflow-hidden text-xs">
            <div
              v-for="tx in cashboxStore.summary.transactions"
              :key="tx.id"
              class="p-3 bg-surface-card flex items-center justify-between hover:bg-surface-elevated transition"
            >
              <div class="flex items-center gap-3">
                <span
                  :class="[
                    'p-1.5 rounded-lg',
                    tx.type === 'income' ? 'bg-status-success-bg text-status-success' : 'bg-status-danger-bg text-status-danger'
                  ]"
                >
                  <ArrowDownRight v-if="tx.type === 'income'" class="w-4 h-4" />
                  <ArrowUpRight v-else class="w-4 h-4" />
                </span>
                <div>
                  <p class="font-bold text-content-primary">{{ tx.description_ar || tx.description || (tx.type === 'income' ? 'تحصيل كشف' : 'مصروف نثري') }}</p>
                  <p class="text-[10px] text-content-muted">{{ tx.created_at ? new Date(tx.created_at).toLocaleTimeString('ar-EG') : '' }}</p>
                </div>
              </div>

              <span
                :class="[
                  'font-mono font-bold text-sm',
                  tx.type === 'income' ? 'text-status-success' : 'text-status-danger'
                ]"
              >
                {{ tx.type === 'income' ? '+' : '-' }}{{ tx.amount }} ج.م
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 bg-surface-card border-t border-line-subtle flex items-center justify-between">
        <button
          @click="$emit('close')"
          type="button"
          class="px-4 py-2 text-xs font-semibold text-content-secondary hover:bg-surface-elevated rounded-xl transition cursor-pointer"
        >
          إغلاق
        </button>
      </div>
    </div>
  </div>
  </Teleport>
</template>
