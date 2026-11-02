<script setup>
import { ref, watch, computed } from 'vue';
import { useReceptionQueueStore } from '../../../stores/receptionQueue';
import alerts from '../../../utils/alerts';
import { Activity, X, Check, Heart, Thermometer, Weight, Ruler } from 'lucide-vue-next';

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
  { key: 'bp', label: 'ضغط الدم', unit: 'mmHg', enabled: true },
  { key: 'pulse', label: 'النبض', unit: 'BPM', enabled: true },
  { key: 'temp', label: 'الحرارة', unit: '°C', enabled: true },
  { key: 'weight', label: 'الوزن', unit: 'kg', enabled: true },
  { key: 'height', label: 'الطول', unit: 'cm', enabled: false },
  { key: 'sugar', label: 'السكر العشوائي', unit: 'mg/dL', enabled: true },
  { key: 'oxygen', label: 'نسبة الأكسجين', unit: '%', enabled: true },
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

  const bp = v.blood_pressure || v.bp;
  if (bp) {
    const parts = String(bp).split('/');
    bpSystolic.value = parts[0] || '';
    bpDiastolic.value = parts[1] || '';
  }

  dynamicValues.value.pulse = v.pulse !== undefined && v.pulse !== null ? v.pulse : '';
  dynamicValues.value.temp = (v.temperature !== undefined && v.temperature !== null ? v.temperature : (v.temp !== undefined ? v.temp : ''));
  dynamicValues.value.weight = v.weight !== undefined && v.weight !== null ? v.weight : '';
  dynamicValues.value.height = v.height !== undefined && v.height !== null ? v.height : '';
  dynamicValues.value.sugar = (v.blood_sugar !== undefined && v.blood_sugar !== null ? v.blood_sugar : (v.sugar !== undefined ? v.sugar : ''));
  dynamicValues.value.oxygen = (v.oxygen_saturation !== undefined && v.oxygen_saturation !== null ? v.oxygen_saturation : (v.oxygen !== undefined ? v.oxygen : ''));

  // Copy any custom dynamic keys
  Object.keys(v).forEach(k => {
    if (!['blood_pressure', 'bp', 'pulse', 'temperature', 'temp', 'weight', 'height', 'blood_sugar', 'sugar', 'oxygen_saturation', 'oxygen'].includes(k)) {
      dynamicValues.value[k] = v[k];
    }
  });
};

watch(() => [props.appointment, props.isOpen], populateForm, { immediate: true });

const handleSave = async () => {
  if (!props.appointment) return;
  saving.value = true;
  try {
    const vitalsData = {};
    if (bpSystolic.value && bpDiastolic.value) {
      vitalsData.blood_pressure = `${bpSystolic.value}/${bpDiastolic.value}`;
    } else if (bpSystolic.value) {
      vitalsData.blood_pressure = String(bpSystolic.value);
    }

    const numOrStr = (v) => {
      if (v === '' || v === null || v === undefined) return null;
      const n = Number(v);
      return isNaN(n) ? v : n;
    };

    if (dynamicValues.value.pulse) vitalsData.pulse = numOrStr(dynamicValues.value.pulse);
    if (dynamicValues.value.temp) vitalsData.temperature = numOrStr(dynamicValues.value.temp);
    if (dynamicValues.value.weight) vitalsData.weight = numOrStr(dynamicValues.value.weight);
    if (dynamicValues.value.height) vitalsData.height = numOrStr(dynamicValues.value.height);
    if (dynamicValues.value.sugar) vitalsData.blood_sugar = numOrStr(dynamicValues.value.sugar);
    if (dynamicValues.value.oxygen) vitalsData.oxygen_saturation = numOrStr(dynamicValues.value.oxygen);

    // Save custom keys
    Object.keys(dynamicValues.value).forEach(k => {
      if (!['pulse', 'temp', 'weight', 'height', 'sugar', 'oxygen'].includes(k)) {
        if (dynamicValues.value[k] !== undefined && dynamicValues.value[k] !== '') {
          vitalsData[k] = numOrStr(dynamicValues.value[k]);
        }
      }
    });

    await queueStore.saveVitals(props.appointment.id, vitalsData);
    alerts.toast('تم حفظ القياسات الحيوية للمريض بنجاح');
    emit('saved');
    emit('close');
  } catch (e) {
    alerts.error('فشل حفظ العلامات الحيوية', e.response?.data?.message || 'حدث خطأ');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden text-right" dir="rtl">
      <!-- Header -->
      <div class="px-6 py-4 bg-gradient-to-r from-teal-600 to-emerald-700 text-white flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-white/10 rounded-xl">
            <Activity class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-xl font-bold">تسجيل العلامات الحيوية</h3>
            <p class="text-xs text-teal-100 font-medium">المريض: {{ appointment?.patient?.name }}</p>
          </div>
        </div>
        <button @click="$emit('close')" class="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition">
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 space-y-4">
        <!-- Blood Pressure (if enabled in schema) -->
        <div v-if="resolvedVitalsList.some(v => v.key === 'bp')" class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
          <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1.5">
            <Heart class="w-3.5 h-3.5 text-rose-500" />
            <span>ضغط الدم (Blood Pressure mmHg)</span>
          </label>
          <div class="flex items-center gap-2">
            <input
              v-model="bpSystolic"
              type="number"
              placeholder="120"
              class="w-full px-3 py-2 text-xs bg-white rounded-lg border border-slate-300 text-center font-mono font-bold"
            />
            <span class="text-slate-400 font-bold">/</span>
            <input
              v-model="bpDiastolic"
              type="number"
              placeholder="80"
              class="w-full px-3 py-2 text-xs bg-white rounded-lg border border-slate-300 text-center font-mono font-bold"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <!-- Render all non-bp enabled fields dynamically -->
          <div
            v-for="item in resolvedVitalsList.filter(v => v.key !== 'bp')"
            :key="item.key"
          >
            <label class="block text-xs font-bold text-slate-700 mb-1">
              {{ item.label }} <span v-if="item.unit" class="text-slate-400 text-[10px]">({{ item.unit }})</span>
            </label>
            <input
              v-model="dynamicValues[item.key]"
              type="text"
              :placeholder="item.unit || ''"
              class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300 text-center font-mono"
            />
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
        <button
          @click="$emit('close')"
          type="button"
          class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition"
        >
          إلغاء
        </button>

        <button
          @click="handleSave"
          :disabled="saving"
          type="button"
          class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition disabled:opacity-50 shadow-md shadow-teal-500/20 flex items-center gap-2"
        >
          <Check class="w-4 h-4" />
          <span>{{ saving ? 'جارٍ الحفظ...' : 'حفظ القياسات' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>
