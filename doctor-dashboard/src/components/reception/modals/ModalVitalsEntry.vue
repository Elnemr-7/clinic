<script setup>
import { ref, watch, computed } from 'vue';
import { useReceptionQueueStore } from '../../../stores/receptionQueue';
import alerts from '../../../utils/alerts';
import { Activity, X, Check, Heart } from 'lucide-vue-next';

const props = defineProps({
  isOpen: Boolean,
  appointment: Object,
  vitalsSchema: {
    type: [Object, Array],
    default: () => [],
  },
});

const emit = defineEmits(['close', 'saved']);
const queueStore = useReceptionQueueStore();

const bpSystolic = ref('');
const bpDiastolic = ref('');
const dynamicValues = ref({});
const saving = ref(false);

const defaultVitalsList = [
  { key: 'bp', label: 'ضغط الدم (BP)', unit: 'mmHg', enabled: true },
  { key: 'pulse', label: 'معدل النبض (Pulse)', unit: 'bpm', enabled: true },
  { key: 'temp', label: 'درجة الحرارة (Temp)', unit: '°C', enabled: true },
  { key: 'weight', label: 'الوزن (Weight)', unit: 'kg', enabled: true },
  { key: 'height', label: 'الطول (Height)', unit: 'cm', enabled: false },
  { key: 'sugar', label: 'السكر العشوائي (Glucose)', unit: 'mg/dL', enabled: true },
  { key: 'oxygen', label: 'نسبة الأكسجين (SpO2)', unit: '%', enabled: true },
];

const resolvedVitalsList = computed(() => {
  if (Array.isArray(props.vitalsSchema) && props.vitalsSchema.length > 0) {
    return props.vitalsSchema.filter(v => v.enabled !== false);
  }
  if (props.vitalsSchema && typeof props.vitalsSchema === 'object' && Object.keys(props.vitalsSchema).length > 0) {
    return defaultVitalsList.filter(v => props.vitalsSchema[v.key] !== false);
  }
  return defaultVitalsList.filter(v => v.enabled);
});

// Common alias map to resolve existing values
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
  if (!dataObj || typeof dataObj !== 'object') return '';
  if (dataObj[key] !== undefined && dataObj[key] !== null && dataObj[key] !== '') {
    return dataObj[key];
  }
  const aliases = aliasMap[key] || [];
  for (const alias of aliases) {
    if (dataObj[alias] !== undefined && dataObj[alias] !== null && dataObj[alias] !== '') {
      return dataObj[alias];
    }
  }
  return '';
};

const populateForm = () => {
  dynamicValues.value = {};
  bpSystolic.value = '';
  bpDiastolic.value = '';

  const appt = props.appointment;
  if (!appt || !appt.vitals_data) return;

  let v = appt.vitals_data;
  if (typeof v === 'string') {
    try {
      v = JSON.parse(v);
    } catch (e) {
      return;
    }
  }
  if (!v || typeof v !== 'object') return;

  // Extract BP
  const bp = v.bp || v.blood_pressure;
  if (bp) {
    const parts = String(bp).split('/');
    bpSystolic.value = parts[0] || '';
    bpDiastolic.value = parts[1] || '';
  }

  // Prepopulate all active dynamic items (standard & custom)
  resolvedVitalsList.value.forEach(item => {
    if (item.key === 'bp') return;
    const val = extractVitalValue(v, item.key);
    dynamicValues.value[item.key] = val !== undefined && val !== null ? val : '';
  });

  // Preserve any custom keys stored in appointment that might not be in the current schema
  Object.keys(v).forEach(k => {
    if (!['blood_pressure', 'bp', 'pulse', 'temperature', 'temp', 'weight', 'height', 'blood_sugar', 'sugar', 'oxygen_saturation', 'oxygen'].includes(k)) {
      if (dynamicValues.value[k] === undefined) {
        dynamicValues.value[k] = v[k];
      }
    }
  });
};

watch(() => [props.appointment, props.isOpen], populateForm, { immediate: true });

const handleSave = async () => {
  if (!props.appointment) return;
  saving.value = true;
  try {
    const vitalsData = {};

    // 1. Blood pressure
    if (resolvedVitalsList.value.some(v => v.key === 'bp')) {
      if (bpSystolic.value && bpDiastolic.value) {
        const bpStr = `${bpSystolic.value}/${bpDiastolic.value}`;
        vitalsData.bp = bpStr;
        vitalsData.blood_pressure = bpStr;
      } else if (bpSystolic.value) {
        const bpStr = String(bpSystolic.value);
        vitalsData.bp = bpStr;
        vitalsData.blood_pressure = bpStr;
      }
    }

    const numOrStr = (v) => {
      if (v === '' || v === null || v === undefined) return null;
      const n = Number(v);
      return isNaN(n) ? String(v) : n;
    };

    // 2. All dynamic fields in schema
    resolvedVitalsList.value.forEach(item => {
      if (item.key === 'bp') return;
      const rawVal = dynamicValues.value[item.key];
      if (rawVal !== '' && rawVal !== null && rawVal !== undefined) {
        const parsed = numOrStr(rawVal);
        vitalsData[item.key] = parsed;

        // Provide aliases for standard keys to ensure complete backward compatibility
        if (item.key === 'temp') vitalsData.temperature = parsed;
        if (item.key === 'sugar') vitalsData.blood_sugar = parsed;
        if (item.key === 'oxygen') vitalsData.oxygen_saturation = parsed;
      }
    });

    // 3. Save any extra custom keys
    Object.keys(dynamicValues.value).forEach(k => {
      if (vitalsData[k] === undefined && dynamicValues.value[k] !== '' && dynamicValues.value[k] !== null && dynamicValues.value[k] !== undefined) {
        vitalsData[k] = numOrStr(dynamicValues.value[k]);
      }
    });

    await queueStore.saveVitals(props.appointment.id, vitalsData);
    alerts.toast('تم حفظ القياسات الحيوية للمريض بنجاح');
    emit('saved');
    emit('close');
  } catch (e) {
    alerts.error('فشل حفظ العلامات الحيوية', e.response?.data?.message || 'حدث خطأ أثناء الحفظ');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" @click.self="$emit('close')" class="fixed inset-0 z-50 overflow-y-auto bg-surface-overlay/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-surface-card border border-line-subtle rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden text-right text-content-primary" dir="rtl">
        <!-- Header -->
        <div class="px-6 py-4 bg-brand text-content-inverse flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-white/10 rounded-xl">
              <Activity class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-xl font-bold">تسجيل العلامات الحيوية</h3>
              <p class="text-xs text-brand-subtle/80 font-medium">المريض: {{ appointment?.patient?.name }}</p>
            </div>
          </div>
          <button @click="$emit('close')" class="p-2 text-content-inverse/80 hover:text-content-inverse hover:bg-white/10 rounded-xl transition cursor-pointer">
            <X class="w-6 h-6" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-4 bg-surface-app max-h-[70vh] overflow-y-auto">
          <!-- Blood Pressure (if enabled in schema) -->
          <div v-if="resolvedVitalsList.some(v => v.key === 'bp')" class="p-3 bg-surface-card border border-line-subtle rounded-xl">
            <label class="block text-xs font-bold text-content-secondary mb-1.5 flex items-center gap-1.5">
              <Heart class="w-3.5 h-3.5 text-status-danger" />
              <span>ضغط الدم (Blood Pressure mmHg)</span>
            </label>
            <div class="flex items-center gap-2">
              <input
                v-model="bpSystolic"
                type="number"
                placeholder="الانقباضي (120)"
                class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-lg border border-line-subtle text-center font-mono font-bold focus:ring-2 focus:ring-brand"
              />
              <span class="text-content-muted font-bold text-sm">/</span>
              <input
                v-model="bpDiastolic"
                type="number"
                placeholder="الانبساطي (80)"
                class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-lg border border-line-subtle text-center font-mono font-bold focus:ring-2 focus:ring-brand"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <!-- Render all non-bp enabled fields dynamically (standard and custom vitals created by doctor) -->
            <div
              v-for="item in resolvedVitalsList.filter(v => v.key !== 'bp')"
              :key="item.key"
              class="p-2.5 bg-surface-card border border-line-subtle rounded-xl flex flex-col justify-between"
            >
              <label class="block text-xs font-bold text-content-secondary mb-1">
                {{ item.label }} <span v-if="item.unit" class="text-content-muted text-[10px] font-mono">({{ item.unit }})</span>
              </label>
              <input
                v-model="dynamicValues[item.key]"
                type="text"
                :placeholder="item.unit ? `القيمة بـ (${item.unit})` : 'أدخل القيمة...'"
                class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-lg border border-line-subtle text-center font-mono focus:ring-2 focus:ring-brand font-bold"
              />
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
            إلغاء
          </button>

          <button
            @click="handleSave"
            :disabled="saving"
            type="button"
            class="px-5 py-2.5 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold transition disabled:opacity-50 shadow-md flex items-center gap-2 cursor-pointer"
          >
            <Check class="w-4 h-4" />
            <span>{{ saving ? 'جارٍ الحفظ...' : 'حفظ القياسات' }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
