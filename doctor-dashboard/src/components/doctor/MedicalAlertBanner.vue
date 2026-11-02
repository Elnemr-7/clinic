<template>
  <div v-if="hasAlerts" class="bg-status-danger-bg border-2 border-status-danger-border rounded-2xl p-3.5 flex items-center justify-between text-status-danger shadow-xs">
    <div class="flex items-center gap-3">
      <span class="text-xl shrink-0">⚠️</span>
      <div>
        <h4 class="font-black text-xs lg:text-sm text-status-danger">تنبيه طبي حرج للمريض</h4>
        <div class="text-xs space-x-2 space-x-reverse font-bold mt-0.5">
          <span v-if="patient?.chronic_diseases">الأمراض المزمنة: {{ patient.chronic_diseases }}</span>
          <span v-if="patient?.chronic_diseases && patient?.drug_allergies"> | </span>
          <span v-if="patient?.drug_allergies" class="underline decoration-2">حساسية الأدوية: {{ patient.drug_allergies }}</span>
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
