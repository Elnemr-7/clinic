<template>
  <div class="bg-surface-card border border-line-subtle rounded-2xl p-3 shadow-xs w-full">
    <div class="flex items-center justify-between mb-2 px-1">
      <span class="text-xs font-bold text-content-primary flex items-center gap-1.5">
        <span>🩺</span>
        <span>القياسات الحيوية (مقارنة بالزيارة السابقة)</span>
      </span>
      <span class="text-[10px] text-content-muted">مسجلة بواسطة الاستقبال</span>
    </div>

    <div v-if="enabledVitals.length === 0" class="text-xs text-content-muted py-2 text-center">
      لم يتم تفعيل قياسات حيوية
    </div>

    <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2 w-full">
      <div
        v-for="item in enabledVitals"
        :key="item.key"
        class="bg-surface-elevated border border-line-subtle py-2 px-2.5 rounded-xl flex flex-col justify-between min-w-0"
      >
        <span class="text-[10px] font-semibold text-content-secondary truncate" :title="item.label">{{ item.label }}</span>
        <div class="mt-1 flex items-baseline gap-1 truncate">
          <span class="text-xs lg:text-sm font-black text-content-primary font-mono tabular-nums truncate">
            {{ getVitalDisplay(currentData, item.key, item.unit) }}
          </span>
          <span v-if="hasPrevious(item.key)" class="text-[9px] text-content-muted line-through font-mono shrink-0">
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
  { key: 'bp', label: 'ضغط الدم (BP)', unit: 'mmHg', enabled: true },
  { key: 'pulse', label: 'معدل النبض (Pulse)', unit: 'bpm', enabled: true },
  { key: 'temp', label: 'درجة الحرارة (Temp)', unit: '°C', enabled: true },
  { key: 'weight', label: 'الوزن (Weight)', unit: 'kg', enabled: true },
  { key: 'sugar', label: 'السكر العشوائي (Glucose)', unit: 'mg/dL', enabled: true },
  { key: 'oxygen', label: 'نسبة الأكسجين (SpO2)', unit: '%', enabled: true },
];

const enabledVitals = computed(() => {
  const conf = props.vitalsConfig;
  if (Array.isArray(conf) && conf.length > 0) {
    return conf.filter(v => v.enabled !== false);
  }
  return defaultVitals;
});

// Alias mapping for full backwards and cross-naming compatibility
const aliasMap = {
  bp: ['blood_pressure', 'bloodPressure'],
  pulse: ['heart_rate', 'pulseRate'],
  temp: ['temperature'],
  weight: ['wt'],
  height: ['ht'],
  sugar: ['blood_sugar', 'glucose', 'bloodSugar'],
  oxygen: ['oxygen_saturation', 'spO2', 'spo2'],
};

const extractVitalValue = (dataObj, key) => {
  if (!dataObj || typeof dataObj !== 'object') return undefined;

  // 1. Direct match (works for standard keys and any doctor custom_xxx key)
  if (dataObj[key] !== undefined && dataObj[key] !== null && dataObj[key] !== '') {
    return dataObj[key];
  }

  // 2. Fallback to known aliases for standard keys
  const aliases = aliasMap[key] || [];
  for (const alias of aliases) {
    if (dataObj[alias] !== undefined && dataObj[alias] !== null && dataObj[alias] !== '') {
      return dataObj[alias];
    }
  }

  return undefined;
};

const getVitalDisplay = (dataObj, key, unit) => {
  const val = extractVitalValue(dataObj, key);
  if (val === undefined || val === null || val === '') {
    return '-';
  }
  return `${val} ${unit || ''}`.trim();
};

const hasPrevious = (key) => {
  if (!prevData.value) return false;
  const val = extractVitalValue(prevData.value, key);
  return val !== undefined && val !== null && val !== '';
};
</script>
