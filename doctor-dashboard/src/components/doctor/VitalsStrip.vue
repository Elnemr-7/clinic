<template>
  <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4">
    <div class="flex items-center justify-between mb-3">
      <span class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
        <span>🩺</span>
        <span>القياسات الحيوية للمريض (مقارنة بالزيارة السابقة)</span>
      </span>
      <span class="text-[11px] text-slate-500">تم القياس بواسطة الاستقبال</span>
    </div>

    <div v-if="enabledVitals.length === 0" class="text-xs text-slate-500 py-2 text-center">
      لم يتم تفعيل قياسات حيوية في الإعدادات
    </div>

    <div v-else class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div
        v-for="item in enabledVitals"
        :key="item.key"
        class="bg-slate-900 border border-slate-800/80 p-3 rounded-xl flex flex-col justify-between"
      >
        <span class="text-[11px] font-medium text-slate-400">{{ item.label }}</span>
        <div class="mt-1 flex items-baseline gap-2">
          <span class="text-lg font-bold text-white">
            {{ getVitalDisplay(currentData, item.key, item.unit) }}
          </span>
          <span v-if="hasPrevious(item.key)" class="text-[10px] text-slate-500 line-through">
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
  { key: 'pulse', label: 'النبض', unit: 'BPM', enabled: true },
  { key: 'temp', label: 'الحرارة', unit: '°C', enabled: true },
  { key: 'weight', label: 'الوزن', unit: 'كجم', enabled: true },
  { key: 'sugar', label: 'السكر العشوائي', unit: 'mg/dL', enabled: true },
  { key: 'oxygen', label: 'نسبة الأكسجين', unit: '%', enabled: true },
];

const enabledVitals = computed(() => {
  if (Array.isArray(props.vitalsConfig) && props.vitalsConfig.length > 0) {
    return props.vitalsConfig.filter((v) => v.enabled !== false);
  }
  if (props.vitalsConfig && typeof props.vitalsConfig === 'object' && Object.keys(props.vitalsConfig).length > 0) {
    return defaultVitals.filter(v => props.vitalsConfig[v.key] !== false);
  }
  return defaultVitals;
});

const getVitalDisplay = (source, key, unit) => {
  const data = source || currentData.value || {};
  if (!data || typeof data !== 'object') return 'لم يقاس';

  let val = null;

  if (key === 'bp') val = data.blood_pressure || data.bp;
  else if (key === 'pulse') val = data.pulse;
  else if (key === 'temp') val = data.temperature || data.temp;
  else if (key === 'weight') val = data.weight;
  else if (key === 'height') val = data.height;
  else if (key === 'sugar') val = data.blood_sugar || data.sugar;
  else if (key === 'oxygen') val = data.oxygen_saturation || data.oxygen;
  else val = data[key];

  if (val !== undefined && val !== null && val !== '') {
    return unit ? `${val} ${unit}` : `${val}`;
  }
  return 'لم يقاس';
};

const hasPrevious = (key) => {
  if (!prevData.value) return false;
  return getVitalDisplay(prevData.value, key, '') !== 'لم يقاس';
};
</script>
