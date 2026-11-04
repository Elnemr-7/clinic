<template>
  <Teleport to="body">
    <div v-if="isOpen" @click.self="$emit('close')" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-overlay/80 backdrop-blur-sm">
      <div class="bg-surface-card border border-line-subtle rounded-3xl w-full max-w-3xl max-h-[85vh] flex flex-col shadow-2xl overflow-hidden text-right text-content-primary" dir="rtl">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-line-subtle flex justify-between items-center bg-brand text-content-inverse">
          <div class="flex items-center gap-3">
            <span class="text-2xl">📋</span>
            <div>
              <h3 class="text-base font-bold">التاريخ المرضي والزيارات السابقة</h3>
              <p class="text-xs text-brand-subtle/80 font-medium">سجل الفحوصات والروشتات السابقة للمريض عبر جميع الفروع</p>
            </div>
          </div>
          <button @click="$emit('close')" class="p-2 text-content-inverse/80 hover:text-content-inverse hover:bg-white/10 rounded-xl transition cursor-pointer text-2xl font-bold leading-none">&times;</button>
        </div>

        <!-- Body -->
        <div class="flex-1 p-6 overflow-y-auto space-y-4 bg-surface-app">
          <div v-if="loading" class="text-center py-12 text-content-muted text-sm">جاري تحميل السجل الطبي...</div>

          <div v-else-if="visits.length === 0" class="text-center py-12 text-content-muted text-sm border border-dashed border-line-subtle rounded-2xl">
            لا توجد زيارات سابقة مسجلة لهذا المريض
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="visit in visits"
              :key="visit.id"
              class="bg-surface-card border border-line-subtle p-5 rounded-2xl space-y-3 shadow-xs"
            >
              <div class="flex justify-between items-center border-b border-line-subtle pb-3">
                <div>
                  <span class="text-xs font-bold text-status-success font-mono">بتاريخ: {{ visit.appointment_date }}</span>
                  <span class="text-xs text-content-muted mx-2">|</span>
                  <span class="text-xs text-content-secondary font-medium">{{ visit.clinic?.name_ar || visit.clinic?.name }}</span>
                </div>
                <span class="text-[11px] px-2.5 py-0.5 rounded-full bg-surface-elevated text-content-primary border border-line-subtle font-bold">
                  {{ visit.visit_type === 'followup' ? 'استشارة' : 'كشف جديد' }}
                </span>
              </div>

              <div v-if="visit.consultation?.diagnosis" class="text-xs">
                <strong class="text-content-secondary">التشخيص:</strong>
                <span class="text-brand font-bold mr-1">{{ visit.consultation.diagnosis }}</span>
              </div>

              <div v-if="visit.consultation?.prescribed_drugs?.length" class="text-xs">
                <strong class="text-content-secondary block mb-1">الأدوية الموصوفة:</strong>
                <div class="flex flex-wrap gap-1.5">
                  <span
                    v-for="(drug, i) in visit.consultation.prescribed_drugs"
                    :key="i"
                    class="bg-surface-elevated border border-line-subtle px-2.5 py-1 rounded-lg text-content-primary text-[11px] font-medium"
                  >
                    {{ drug.drug_name }} - {{ drug.dosage }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-line-subtle bg-surface-card flex justify-end">
          <button @click="$emit('close')" class="bg-surface-elevated hover:bg-surface-card border border-line-subtle text-content-primary px-6 py-2 rounded-xl text-xs font-bold transition cursor-pointer">
            إغلاق
          </button>
        </div>
      </div>
    </div>
  </Teleport>
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
