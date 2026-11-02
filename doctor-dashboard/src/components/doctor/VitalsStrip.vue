<template>
  <div class="bg-surface-card border border-line-subtle rounded-2xl p-3.5 shadow-xs">
    <div class="flex items-center justify-between mb-2.5">
      <span class="text-xs font-bold text-content-primary flex items-center gap-1.5">
        <span>🩺</span>
        <span>القياسات الحيوية (مقارنة بالزيارة السابقة)</span>
      </span>
      <span class="text-[10px] text-content-muted">مسجلة بواسطة الاستقبال</span>
    </div>

    <div v-if="enabledVitals.length === 0" class="text-xs text-content-muted py-2 text-center">
      لم يتم تفعيل قياسات حيوية
    </div>

    <div v-else class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
      <div
        v-for="item in enabledVitals"
        :key="item.key"
        class="bg-surface-elevated border border-line-subtle p-2.5 rounded-xl flex flex-col justify-between"
      >
        <span class="text-[10px] font-medium text-content-secondary truncate">{{ item.label }}</span>
        <div class="mt-1 flex items-baseline gap-1.5">
          <span class="text-sm lg:text-base font-extrabold text-content-primary font-mono tabular-nums">
            {{ getVitalDisplay(currentData, item.key, item.unit) }}
          </span>
          <span v-if="hasPrevious(item.key)" class="text-[10px] text-content-muted line-through font-mono">
            ({{ getVitalDisplay(prevData, item.key, item.unit) }})
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  vitalsConfig: {
    type: [Array, Object],
    default: () => [],
  },
  current: {
    type: Object,
    default: null,
  },
  todayVitals: {
    type: Object,
    default: null,
  },
  previous: {
    type: Object,
    default: null,
  },
  previousVitals: {
    type: Object,
    default: null,
  },
});

const currentData = computed(() => {
  return props.current || props.todayVitals || {};
});

const prevData = computed(() => {
  return props.previousVitals || props.previous || null;
});

const defaultVitals = [
  { key: 'bp', label: 'ضغط الدم', unit: 'mmHg', enabled: true },
  { key: 'pulse', label: 'النبض', unit: 'bpm', enabled: true },
  { key: 'temp', label: 'الحرارة', unit: '°C', enabled: true },
  { key: 'weight', label: 'الوزن', unit: 'kg', enabled: true },
  { key: 'sugar', label: 'السكر', unit: 'mg/dL', enabled: true },
  { key: 'oxygen', label: 'الأكسجين', unit: '%', enabled: true },
];

const enabledVitals = computed(() => {
  const conf = props.vitalsConfig;
  if (Array.isArray(conf) && conf.length > 0) {
    return conf.filter(v => v.enabled !== false);
  }
  return defaultVitals;
});

const getVitalDisplay = (dataObj, key, unit) => {
  if (!dataObj || dataObj[key] === undefined || dataObj[key] === null || dataObj[key] === '') {
    return '-';
  }
  return `${dataObj[key]} ${unit || ''}`.trim();
};

const hasPrevious = (key) => {
  if (!prevData.value) return false;
  const val = prevData.value[key];
  return val !== undefined && val !== null && val !== '';
};
</script>
