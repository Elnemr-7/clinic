<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-lg p-6 shadow-2xl">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-base font-bold text-white">
          {{ form.id ? 'تعديل الباقة' : 'إنشاء باقة اشتراك جديدة' }}
        </h3>
        <button @click="$emit('close')" class="text-slate-400 hover:text-white text-xl">&times;</button>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">اسم الباقة (عربي)</label>
          <input v-model="form.name_ar" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white" />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">السعر الشهري (ج.م)</label>
            <input v-model.number="form.price_monthly" type="number" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white" />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">السعر السنوي (ج.م)</label>
            <input v-model.number="form.price_yearly" type="number" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">الحد الأقصى للعيادات (-1 يعني غير محدود)</label>
          <input v-model.number="form.max_clinics" type="number" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white" />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">كوتة الواتساب المجانية شهرياً</label>
            <input v-model.number="form.free_whatsapp_quota_monthly" type="number" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white" />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">كوتة الـ SMS المجانية شهرياً</label>
            <input v-model.number="form.free_sms_quota_monthly" type="number" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white" />
          </div>
        </div>

        <div class="space-y-2 pt-2">
          <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
            <input type="checkbox" v-model="form.allow_custom_domain" class="rounded border-slate-800 text-emerald-500" />
            <span>السماح بربط دومين مخصص (Custom Domain)</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
            <input type="checkbox" v-model="form.allow_custom_website" class="rounded border-slate-800 text-emerald-500" />
            <span>السماح بموقع خارجي مخصص (API Booking)</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
            <input type="checkbox" v-model="form.is_active" class="rounded border-slate-800 text-emerald-500" />
            <span>الباقة مفعلة ومتاحة للطلب</span>
          </label>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
          <button type="button" @click="$emit('close')" class="px-4 py-2 rounded-xl text-slate-400 hover:text-white text-xs">إلغاء</button>
          <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-6 py-2 rounded-xl text-xs shadow-lg shadow-emerald-900/30">
            حفظ
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { usePlansStore } from '../../stores/plans';

const props = defineProps({
  isOpen: Boolean,
  plan: Object,
});

const emit = defineEmits(['close']);
const plansStore = usePlansStore();

const form = ref({
  id: null,
  name_ar: '',
  price_monthly: 0,
  price_yearly: 0,
  max_clinics: 1,
  free_whatsapp_quota_monthly: 0,
  free_sms_quota_monthly: 0,
  allow_custom_domain: false,
  allow_custom_website: false,
  is_active: true,
});

watch(
  () => props.plan,
  (p) => {
    if (p) {
      form.value = { ...p };
    } else {
      form.value = {
        id: null,
        name_ar: '',
        price_monthly: 300,
        price_yearly: 3000,
        max_clinics: 1,
        free_whatsapp_quota_monthly: 50,
        free_sms_quota_monthly: 50,
        allow_custom_domain: false,
        allow_custom_website: false,
        is_active: true,
      };
    }
  },
  { immediate: true }
);

const submit = async () => {
  await plansStore.savePlan(form.value);
  emit('close');
};
</script>
