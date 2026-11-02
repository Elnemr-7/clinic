<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-3xl max-h-[85vh] flex flex-col shadow-2xl overflow-hidden">
      <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-950">
        <div class="flex items-center gap-3">
          <span class="text-2xl">📋</span>
          <div>
            <h3 class="text-base font-bold text-white">التاريخ المرضي والزيارات السابقة</h3>
            <p class="text-xs text-slate-400">سجل الفحوصات والروشتات السابقة للمريض عبر جميع الفروع</p>
          </div>
        </div>
        <button @click="$emit('close')" class="text-slate-400 hover:text-white text-2xl font-bold">&times;</button>
      </div>

      <div class="flex-1 p-6 overflow-y-auto space-y-4">
        <div v-if="loading" class="text-center py-12 text-slate-400 text-sm">جاري تحميل السجل الطبي...</div>

        <div v-else-if="visits.length === 0" class="text-center py-12 text-slate-500 text-sm">
          لا توجد زيارات سابقة مسجلة لهذا المريض
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="visit in visits"
            :key="visit.id"
            class="bg-slate-950 border border-slate-800 p-5 rounded-2xl space-y-3"
          >
            <div class="flex justify-between items-center border-b border-slate-800/80 pb-3">
              <div>
                <span class="text-xs font-bold text-emerald-400">بتاريخ: {{ visit.appointment_date }}</span>
                <span class="text-xs text-slate-400 mx-2">|</span>
                <span class="text-xs text-slate-300 font-medium">{{ visit.clinic?.name_ar }}</span>
              </div>
              <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-bold">
                {{ visit.visit_type === 'followup' ? 'استشارة' : 'كشف جديد' }}
              </span>
            </div>

            <div v-if="visit.consultation?.diagnosis" class="text-xs">
              <strong class="text-slate-400">التشخيص:</strong>
              <span class="text-white font-bold mr-1">{{ visit.consultation.diagnosis }}</span>
            </div>

            <div v-if="visit.consultation?.prescribed_drugs?.length" class="text-xs">
              <strong class="text-slate-400 block mb-1">الأدوية الموصوفة:</strong>
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="(drug, i) in visit.consultation.prescribed_drugs"
                  :key="i"
                  class="bg-slate-900 border border-slate-800 px-2.5 py-1 rounded-lg text-slate-300 text-[11px]"
                >
                  {{ drug.drug_name }} - {{ drug.dosage }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="p-4 border-t border-slate-800 bg-slate-950 flex justify-end">
        <button @click="$emit('close')" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-6 py-2 rounded-xl text-xs font-bold transition">
          إغلاق
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  isOpen: Boolean,
  visits: {
    type: Array,
    default: () => [],
  },
  loading: Boolean,
});

defineEmits(['close']);
</script>
