<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-lg p-6 shadow-2xl">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-base font-bold text-white flex items-center gap-2">
          <span>✨</span>
          <span>تحويل طلب الاشتراك إلى حساب طبيب نشط</span>
        </h3>
        <button @click="$emit('close')" class="text-slate-400 hover:text-white text-xl">&times;</button>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">اسم الطبيب</label>
          <input v-model="form.doctor_name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white" />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">رقم الهاتف</label>
            <input v-model="form.phone" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white" />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">التخصص</label>
            <input v-model="form.specialty" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">البريد الإلكتروني لدخول الطبيب</label>
          <input v-model="form.email" type="email" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white" />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">كلمة المرور المبدئية</label>
          <input v-model="form.password" type="text" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white font-mono" />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">الباقة الممنوحة</label>
          <select v-model="form.plan_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white">
            <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name_ar }}</option>
          </select>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
          <button type="button" @click="$emit('close')" class="px-4 py-2 rounded-xl text-slate-400 hover:text-white text-xs">إلغاء</button>
          <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-6 py-2 rounded-xl text-xs shadow-lg shadow-emerald-900/30">
            تأكيد وإنشاء الحساب فوراً
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useLeadsStore } from '../../stores/leads';
import { usePlansStore } from '../../stores/plans';

const props = defineProps({
  isOpen: Boolean,
  lead: Object,
});

const emit = defineEmits(['close']);
const leadsStore = useLeadsStore();
const plansStore = usePlansStore();
const plans = computed(() => plansStore.plans);

const form = ref({
  doctor_name: '',
  phone: '',
  specialty: '',
  email: '',
  password: 'password123',
  plan_id: null,
});

watch(
  () => props.lead,
  (l) => {
    if (l) {
      form.value = {
        doctor_name: l.doctor_name,
        phone: l.phone,
        specialty: l.specialty,
        email: `dr.${Date.now()}@clinic.com`,
        password: 'password123',
        plan_id: l.requested_plan_id || plans.value[0]?.id,
      };
    }
  },
  { immediate: true }
);

const submit = async () => {
  await leadsStore.convertLead(props.lead.id, form.value);
  emit('close');
};
</script>
