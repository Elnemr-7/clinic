<template>
  <div v-if="hasAlerts" class="bg-rose-950/60 border-2 border-rose-500/80 rounded-2xl p-4 flex items-center justify-between text-rose-200 shadow-lg shadow-rose-950/40">
    <div class="flex items-center gap-3">
      <span class="text-2xl animate-bounce">⚠️</span>
      <div>
        <h4 class="font-black text-sm text-rose-300">تنبيه طبي حرج للمريض</h4>
        <div class="text-xs space-x-3 space-x-reverse font-bold mt-1">
          <span v-if="patient?.chronic_diseases">الأمراض المزمنة: {{ patient.chronic_diseases }}</span>
          <span v-if="patient?.chronic_diseases && patient?.drug_allergies"> | </span>
          <span v-if="patient?.drug_allergies" class="text-rose-400 underline decoration-2">حساسية الأدوية: {{ patient.drug_allergies }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  patient: Object,
});

const hasAlerts = computed(() => {
  return Boolean(props.patient?.chronic_diseases || props.patient?.drug_allergies);
});
</script>
