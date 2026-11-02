<script setup>
import { ref, onMounted } from 'vue';
import { useSettingsStore } from '../../stores/settings';
import { useAuthStore } from '../../stores/auth';
import DoctorTopNav from '../../components/common/DoctorTopNav.vue';
import alerts from '../../utils/alerts';
import { Activity, Printer, Pill, Sparkles, FlaskConical, Plus, Save, Trash2, Check, ArrowRight } from 'lucide-vue-next';

const settingsStore = useSettingsStore();
const authStore = useAuthStore();

const activeTab = ref('vitals'); // 'vitals' | 'print' | 'drugs' | 'templates' | 'labs'

const defaultVitalsList = [
  { key: 'bp', label: 'ضغط الدم (BP)', unit: 'mmHg', enabled: true },
  { key: 'pulse', label: 'معدل النبض (Pulse)', unit: 'BPM', enabled: true },
  { key: 'temp', label: 'درجة الحرارة (Temp)', unit: '°C', enabled: true },
  { key: 'weight', label: 'الوزن (Weight)', unit: 'kg', enabled: true },
  { key: 'height', label: 'الطول (Height)', unit: 'cm', enabled: false },
  { key: 'sugar', label: 'السكر العشوائي (Glucose)', unit: 'mg/dL', enabled: true },
  { key: 'oxygen', label: 'نسبة الأكسجين (SpO2)', unit: '%', enabled: true },
];

const vitalsList = ref([...defaultVitalsList]);
const newVital = ref({ label: '', unit: '', enabled: true });

const printMargins = ref({
  margin_top: 25,
  margin_bottom: 20,
  margin_left: 15,
  margin_right: 15,
  header_height: 35,
  footer_height: 25,
});

const newDrug = ref({ name: '', default_dosage: '', default_frequency: 'كل 8 ساعات', default_duration: '5 أيام', default_instructions: 'بعد الأكل' });
const newLab = ref({ name: '', category: 'lab', default_instructions: '' });

const newTemplate = ref({
  template_name: '',
  diagnosis_default: '',
  drugs_json: [{ drug_name: '', dosage: '', duration: '', instructions: '' }],
});

const addTemplateDrugRow = () => {
  newTemplate.value.drugs_json.push({ drug_name: '', dosage: '', duration: '', instructions: '' });
};

const removeTemplateDrugRow = (idx) => {
  newTemplate.value.drugs_json.splice(idx, 1);
  if (newTemplate.value.drugs_json.length === 0) {
    addTemplateDrugRow();
  }
};

const handleSaveTemplate = async () => {
  if (!newTemplate.value.template_name.trim()) {
    alerts.warning('يرجى كتابة اسم نموذج الروشتة (مثال: بروتوكول قرحة المعدة والارتجاع)');
    return;
  }
  const validDrugs = newTemplate.value.drugs_json.filter(d => d.drug_name && d.drug_name.trim() !== '');
  if (validDrugs.length === 0) {
    alerts.warning('يرجى إضافة دواء واحد على الأقل للنموذج');
    return;
  }
  saving.value = true;
  try {
    await settingsStore.saveTemplate({
      template_name: newTemplate.value.template_name.trim(),
      diagnosis_default: newTemplate.value.diagnosis_default.trim(),
      drugs_json: validDrugs,
    });
    newTemplate.value = {
      template_name: '',
      diagnosis_default: '',
      drugs_json: [{ drug_name: '', dosage: '', duration: '', instructions: '' }],
    };
    alerts.toast('تمت إضافة قالب الروشتة بنجاح');
  } catch (e) {
    alerts.error('فشل حفظ القالب', e.response?.data?.message || 'حدث خطأ');
  } finally {
    saving.value = false;
  }
};

const handleDeleteTemplate = async (templateId) => {
  const confirmed = await alerts.confirm('حذف القالب', 'هل أنت متأكد من حذف هذا القالب؟', 'نعم، احذف');
  if (!confirmed) return;
  try {
    await settingsStore.deleteTemplate(templateId);
    alerts.toast('تم حذف القالب بنجاح');
  } catch (e) {
    alerts.error('فشل حذف القالب', e.response?.data?.message || 'حدث خطأ');
  }
};

const saving = ref(false);

const parseVitalsConfig = (config) => {
  if (!config) return [...defaultVitalsList];
  if (Array.isArray(config) && config.length > 0) {
    return config.map(item => ({
      key: item.key || 'v_' + Math.random().toString(36).substring(2, 7),
      label: item.label || 'قياس جديد',
      unit: item.unit || '',
      enabled: item.enabled !== false,
    }));
  }
  if (typeof config === 'object') {
    return defaultVitalsList.map(item => ({
      ...item,
      enabled: config[item.key] !== undefined ? !!config[item.key] : item.enabled,
    }));
  }
  return [...defaultVitalsList];
};

onMounted(async () => {
  await settingsStore.fetchAll();
  if (settingsStore.settings?.vitals_config) {
    vitalsList.value = parseVitalsConfig(settingsStore.settings.vitals_config);
  }
  if (settingsStore.settings?.print_settings) {
    printMargins.value = { ...printMargins.value, ...settingsStore.settings.print_settings };
  }
});

const addCustomVital = () => {
  if (!newVital.value.label.trim()) return;
  const key = 'custom_' + Date.now().toString(36);
  vitalsList.value.push({
    key,
    label: newVital.value.label.trim(),
    unit: newVital.value.unit.trim(),
    enabled: true,
  });
  newVital.value = { label: '', unit: '', enabled: true };
};

const removeVital = (index) => {
  vitalsList.value.splice(index, 1);
};

const saveVitals = async () => {
  saving.value = true;
  try {
    await settingsStore.updateVitals(vitalsList.value);
    await authStore.fetchProfile();
    alerts.success('تم الحفظ بنجاح', 'تم تحديث قائمة القياسات الحيوية وسيرى موظف الاستقبال القياسات الجديدة فوراً');
  } catch (e) {
    alerts.error('فشل حفظ الإعدادات', e.response?.data?.message || 'حدث خطأ');
  } finally {
    saving.value = false;
  }
};

const saveMargins = async () => {
  saving.value = true;
  try {
    await settingsStore.updatePrintMargins(printMargins.value);
    alerts.success('تم الحفظ', 'تم تحديث هوامش طباعة الروشتة (PDF) بنجاح');
  } catch (e) {
    alerts.error('فشل حفظ الإعدادات', e.response?.data?.message || 'حدث خطأ');
  } finally {
    saving.value = false;
  }
};

const handleAddDrug = async () => {
  if (!newDrug.value.name.trim()) return;
  saving.value = true;
  try {
    await settingsStore.saveDrug(newDrug.value);
    newDrug.value = { name: '', default_dosage: '', default_frequency: 'كل 8 ساعات', default_duration: '5 أيام', default_instructions: 'بعد الأكل' };
    alerts.toast('تمت إضافة الدواء بنجاح');
  } catch (e) {
    alerts.error('فشل إضافة الدواء', e.response?.data?.message || 'حدث خطأ');
  } finally {
    saving.value = false;
  }
};

const handleAddLab = async () => {
  if (!newLab.value.name.trim()) return;
  saving.value = true;
  try {
    await settingsStore.saveLab(newLab.value);
    newLab.value = { name: '', category: 'lab', default_instructions: '' };
    alerts.toast('تمت إضافة الفحص بنجاح');
  } catch (e) {
    alerts.error('فشل إضافة الفحص', e.response?.data?.message || 'حدث خطأ');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-slate-100 flex flex-col font-sans text-right select-none" dir="rtl">
    <DoctorTopNav
      title="الإعدادات السريرية والتخصيص الطبي"
      subtitle="تخصيص الروشتة وهوامش الورق والعلامات الحيوية وقوائم الأدوية"
    />

    <!-- Main Body with Tabs -->
    <main class="flex-1 p-6 max-w-6xl mx-auto w-full space-y-6">
      <!-- Tabs -->
      <div class="flex gap-2 border-b border-slate-200 pb-2">
        <button
          @click="activeTab = 'vitals'"
          :class="['px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2', activeTab === 'vitals' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50']"
        >
          <Activity class="w-4 h-4" />
          العلامات الحيوية
        </button>

        <button
          @click="activeTab = 'print'"
          :class="['px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2', activeTab === 'print' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50']"
        >
          <Printer class="w-4 h-4" />
          هوامش طباعة الروشتة (PDF)
        </button>

        <button
          @click="activeTab = 'drugs'"
          :class="['px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2', activeTab === 'drugs' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50']"
        >
          <Pill class="w-4 h-4" />
          قائمة الأدوية المفضلة ({{ settingsStore.drugs?.length || 0 }})
        </button>

        <button
          @click="activeTab = 'labs'"
          :class="['px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2', activeTab === 'labs' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50']"
        >
          <FlaskConical class="w-4 h-4" />
          كتالوج الفحوصات والأشعة ({{ settingsStore.labs?.length || 0 }})
        </button>

        <button
          @click="activeTab = 'templates'"
          :class="['px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2', activeTab === 'templates' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50']"
        >
          <Sparkles class="w-4 h-4" />
          قوالب الروشتات الجاهزة ({{ settingsStore.templates?.length || 0 }})
        </button>
      </div>

      <!-- Tab 1: Vitals Config -->
      <div v-if="activeTab === 'vitals'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-slate-800">تخصيص وإضافة العلامات الحيوية (Biological Measurements)</h3>
            <p class="text-xs text-slate-400">يمكنك تفعيل، تعديل، حذف، أو إضافة أي قياس حيوي إضافي ليقيسه موظف الاستقبال ويظهر في غرفة الكشف</p>
          </div>
        </div>

        <!-- Add Custom Measurement Bar -->
        <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl space-y-3">
          <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
            <Plus class="w-4 h-4 text-teal-600" />
            <span>إضافة قياس حيوي جديد</span>
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input
              v-model="newVital.label"
              type="text"
              placeholder="اسم القياس (مثال: محيط الخصر، معدل التنفس...)"
              class="px-3 py-2 text-xs bg-white rounded-xl border border-slate-300"
            />
            <input
              v-model="newVital.unit"
              type="text"
              placeholder="وحدة القياس (مثال: سم، bpm، ٪...)"
              class="px-3 py-2 text-xs bg-white rounded-xl border border-slate-300"
            />
            <button
              @click="addCustomVital"
              type="button"
              class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm"
            >
              <Plus class="w-4 h-4" />
              <span>إضافة للقائمة</span>
            </button>
          </div>
        </div>

        <!-- List of active & custom vitals -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
          <div
            v-for="(vital, index) in vitalsList"
            :key="vital.key"
            class="p-4 rounded-2xl border flex items-center justify-between transition"
            :class="vital.enabled ? 'border-teal-500 bg-teal-50/30' : 'border-slate-200 bg-slate-50/50'"
          >
            <div class="flex items-center gap-2.5">
              <input
                v-model="vital.enabled"
                type="checkbox"
                class="w-4 h-4 text-teal-600 rounded cursor-pointer"
              />
              <div>
                <span class="text-xs font-bold text-slate-800 block">{{ vital.label }}</span>
                <span v-if="vital.unit" class="text-[10px] text-slate-400 font-mono">الوحدة: {{ vital.unit }}</span>
              </div>
            </div>

            <button
              @click="removeVital(index)"
              type="button"
              class="p-1.5 text-slate-300 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
              title="حذف هذا القياس"
            >
              <Trash2 class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end">
          <button @click="saveVitals" :disabled="saving" class="px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-2">
            <Save class="w-4 h-4" />
            <span>{{ saving ? 'جارٍ الحفظ...' : 'حفظ إعدادات القياسات' }}</span>
          </button>
        </div>
      </div>

      <!-- Tab 2: Print Margins -->
      <div v-if="activeTab === 'print'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-6">
        <div>
          <h3 class="text-sm font-bold text-slate-800">ضبط هوامش ورق الروشتة المطبوع مسبقاً (Letterhead Margins)</h3>
          <p class="text-xs text-slate-400">إذا كانت عيادتك تستخدم أوراقاً مطبوعة مسبقاً بشعار الطبيب وبياناته، قم بضبط الهوامش بالمليمتر (mm) لطباعة محتوى الدواء داخل المساحة المخصصة بدقة.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">الهامش العلوي (Top Margin mm)</label>
              <input v-model="printMargins.margin_top" type="number" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300 font-mono" />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">الهامش السفلي (Bottom Margin mm)</label>
              <input v-model="printMargins.margin_bottom" type="number" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300 font-mono" />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">الهامش الأيمن (Right Margin mm)</label>
              <input v-model="printMargins.margin_right" type="number" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300 font-mono" />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">الهامش الأيسر (Left Margin mm)</label>
              <input v-model="printMargins.margin_left" type="number" class="w-full px-3 py-2 text-xs bg-slate-50 rounded-xl border border-slate-300 font-mono" />
            </div>
          </div>

          <!-- Interactive Visual Preview Box -->
          <div class="p-6 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col items-center justify-center">
            <div class="w-48 h-64 bg-white border-2 border-slate-300 shadow-md rounded relative overflow-hidden text-[10px] text-slate-400 p-2 flex flex-col justify-between">
              <div class="border-b border-dashed border-teal-300 text-center pb-1 text-teal-700 font-bold" :style="{ height: (printMargins.margin_top / 3) + 'px' }">
                رأس الورقة المطبوعة ({{ printMargins.margin_top }}mm)
              </div>
              <div class="flex-1 flex items-center justify-center text-center text-slate-600 font-bold">
                منطقة طباعة الأدوية والتشخيص
              </div>
              <div class="border-t border-dashed border-teal-300 text-center pt-1 text-teal-700 font-bold" :style="{ height: (printMargins.margin_bottom / 3) + 'px' }">
                تذييل الورقة ({{ printMargins.margin_bottom }}mm)
              </div>
            </div>
            <p class="text-[11px] text-slate-400 mt-3 text-center">محاكاة قياسات صفحة A5 / A4</p>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end">
          <button @click="saveMargins" :disabled="saving" class="px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-2">
            <Save class="w-4 h-4" />
            <span>حفظ إعدادات الهوامش</span>
          </button>
        </div>
      </div>

      <!-- Tab 3: Drugs List -->
      <div v-if="activeTab === 'drugs'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-6">
        <div>
          <h3 class="text-sm font-bold text-slate-800">قائمة الأدوية المفضلة</h3>
          <p class="text-xs text-slate-400">أي دواء تقوم بكتابته في شاشة الروشتة يتم حفظه هنا تلقائياً، ويمكنك إضافة أدوية مسبقة هنا.</p>
        </div>

        <!-- Add Drug Form -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl grid grid-cols-12 gap-3 items-end">
          <div class="col-span-12 md:col-span-3">
            <label class="block text-[11px] font-bold text-slate-700 mb-1">اسم الدواء</label>
            <input v-model="newDrug.name" type="text" placeholder="مثال: Augmentin 1g" class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300" />
          </div>
          <div class="col-span-6 md:col-span-2">
            <label class="block text-[11px] font-bold text-slate-700 mb-1">الجرعة</label>
            <input v-model="newDrug.default_dosage" type="text" placeholder="1 قرص" class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300" />
          </div>
          <div class="col-span-6 md:col-span-2">
            <label class="block text-[11px] font-bold text-slate-700 mb-1">التكرار</label>
            <input v-model="newDrug.default_frequency" type="text" class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300" />
          </div>
          <div class="col-span-6 md:col-span-2">
            <label class="block text-[11px] font-bold text-slate-700 mb-1">المدة</label>
            <input v-model="newDrug.default_duration" type="text" class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300" />
          </div>
          <div class="col-span-6 md:col-span-3">
            <button @click="handleAddDrug" :disabled="saving" class="w-full py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1">
              <Plus class="w-4 h-4" />
              <span>إضافة للقائمة المفضلة</span>
            </button>
          </div>
        </div>

        <!-- List -->
        <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden text-xs">
          <div v-for="d in settingsStore.drugs" :key="d.id" class="p-3 bg-white flex items-center justify-between hover:bg-slate-50 transition">
            <div>
              <p class="font-bold text-slate-800">{{ d.name }}</p>
              <p class="text-[11px] text-slate-400">{{ d.default_dosage }} • {{ d.default_frequency }} • {{ d.default_duration }}</p>
            </div>
            <span class="text-[10px] text-slate-400 font-mono">مرات الاستخدام: {{ d.usage_count || 1 }}</span>
          </div>
        </div>
      </div>

      <!-- Tab 4: Labs Catalog -->
      <div v-if="activeTab === 'labs'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-6">
        <div>
          <h3 class="text-sm font-bold text-slate-800">كتالوج الفحوصات والتحاليل والأشعة</h3>
          <p class="text-xs text-slate-400">الفحوصات المجهزة للاختيار السريع أثناء الكشف</p>
        </div>

        <!-- Add Lab Form -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl grid grid-cols-12 gap-3 items-end">
          <div class="col-span-12 md:col-span-4">
            <label class="block text-[11px] font-bold text-slate-700 mb-1">اسم الفحص أو التحليل</label>
            <input v-model="newLab.name" type="text" placeholder="مثال: صورة دم كاملة CBC" class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300" />
          </div>
          <div class="col-span-6 md:col-span-3">
            <label class="block text-[11px] font-bold text-slate-700 mb-1">النوع</label>
            <select v-model="newLab.category" class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300">
              <option value="lab">تحاليل مخبرية</option>
              <option value="radiology">أشعة وفحوصات تصويرية</option>
            </select>
          </div>
          <div class="col-span-6 md:col-span-3">
            <label class="block text-[11px] font-bold text-slate-700 mb-1">تعليمات المريض الافتراضية</label>
            <input v-model="newLab.default_instructions" type="text" placeholder="صيام 8 ساعات" class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300" />
          </div>
          <div class="col-span-12 md:col-span-2">
            <button @click="handleAddLab" :disabled="saving" class="w-full py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1">
              <Plus class="w-4 h-4" />
              <span>إضافة</span>
            </button>
          </div>
        </div>

        <!-- List -->
        <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden text-xs">
          <div v-for="l in settingsStore.labs" :key="l.id" class="p-3 bg-white flex items-center justify-between hover:bg-slate-50 transition">
            <div>
              <p class="font-bold text-slate-800">{{ l.name }}</p>
              <p class="text-[11px] text-slate-400">{{ l.default_instructions || 'بدون تعليمات مسبقة' }}</p>
            </div>
            <span class="text-[11px] px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 font-bold">
              {{ l.category === 'radiology' ? 'أشعة' : 'تحليل' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Tab 5: Prescription Templates -->
      <div v-if="activeTab === 'templates'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-6">
        <div>
          <h3 class="text-sm font-bold text-slate-800">قوالب الروشتات الجاهزة (Prescription Protocols)</h3>
          <p class="text-xs text-slate-400">بروتوكولات علاجية مسبقة تتضمن التشخيص ومجموعة أدوية لتطبيقها بنقرة واحدة داخل غرفة الكشف</p>
        </div>

        <!-- Add Template Form -->
        <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">اسم قالب الروشتة *</label>
              <input
                v-model="newTemplate.template_name"
                type="text"
                placeholder="مثال: بروتوكول قرحة المعدة والارتجاع (GERD)"
                class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">التشخيص التلقائي المقترن بالقالب</label>
              <input
                v-model="newTemplate.diagnosis_default"
                type="text"
                placeholder="مثال: ارتجاع مريئي حاد وقرحة بالمعدة"
                class="w-full px-3 py-2 text-xs bg-white rounded-xl border border-slate-300"
              />
            </div>
          </div>

          <!-- Drugs Rows for Template -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-700">الأدوية المشمولة في هذا القالب:</span>
              <button
                @click="addTemplateDrugRow"
                type="button"
                class="text-xs text-teal-600 font-bold hover:underline flex items-center gap-1"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>إضافة دواء للنموذج</span>
              </button>
            </div>

            <div
              v-for="(row, idx) in newTemplate.drugs_json"
              :key="idx"
              class="grid grid-cols-12 gap-2 items-center bg-white p-2 rounded-xl border border-slate-200"
            >
              <div class="col-span-12 md:col-span-4">
                <input
                  v-model="row.drug_name"
                  type="text"
                  placeholder="اسم الدواء (مثال: كونتارلوك 40 مجم)"
                  class="w-full px-2.5 py-1.5 text-xs bg-slate-50 rounded-lg border border-slate-200"
                />
              </div>
              <div class="col-span-4 md:col-span-2">
                <input
                  v-model="row.dosage"
                  type="text"
                  placeholder="الجرعة (قرص)"
                  class="w-full px-2.5 py-1.5 text-xs bg-slate-50 rounded-lg border border-slate-200"
                />
              </div>
              <div class="col-span-4 md:col-span-2">
                <input
                  v-model="row.duration"
                  type="text"
                  placeholder="المدة (شهر)"
                  class="w-full px-2.5 py-1.5 text-xs bg-slate-50 rounded-lg border border-slate-200"
                />
              </div>
              <div class="col-span-3 md:col-span-3">
                <input
                  v-model="row.instructions"
                  type="text"
                  placeholder="تعليمات (قبل الإفطار)"
                  class="w-full px-2.5 py-1.5 text-xs bg-slate-50 rounded-lg border border-slate-200"
                />
              </div>
              <div class="col-span-1 flex justify-center">
                <button
                  @click="removeTemplateDrugRow(idx)"
                  type="button"
                  class="text-rose-400 hover:text-rose-600 p-1"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <button
              @click="handleSaveTemplate"
              :disabled="saving"
              class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
            >
              <Plus class="w-4 h-4" />
              <span>حفظ وإضافة القالب</span>
            </button>
          </div>
        </div>

        <!-- List of Existing Templates -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="tpl in settingsStore.templates"
            :key="tpl.id"
            class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm hover:border-teal-400 transition space-y-3"
          >
            <div class="flex items-start justify-between">
              <div>
                <h4 class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                  <Sparkles class="w-4 h-4 text-amber-500" />
                  <span>{{ tpl.template_name || tpl.name }}</span>
                </h4>
                <p v-if="tpl.diagnosis_default || tpl.diagnosis" class="text-xs text-slate-500 mt-1">
                  <strong>التشخيص:</strong> {{ tpl.diagnosis_default || tpl.diagnosis }}
                </p>
              </div>
              <button
                @click="handleDeleteTemplate(tpl.id)"
                class="text-slate-400 hover:text-rose-500 p-1.5 rounded-lg hover:bg-rose-50 transition"
                title="حذف القالب"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>

            <div class="border-t border-slate-100 pt-2 space-y-1">
              <span class="text-[11px] font-bold text-slate-400 block mb-1">
                الأدوية ({{ (tpl.drugs_json || tpl.drugs_payload)?.length || 0 }}):
              </span>
              <div
                v-for="(drug, dIdx) in (tpl.drugs_json || tpl.drugs_payload || [])"
                :key="dIdx"
                class="text-xs text-slate-700 flex items-center justify-between bg-slate-50 px-2.5 py-1.5 rounded-lg"
              >
                <span class="font-bold">{{ drug.drug_name }}</span>
                <span class="text-[11px] text-slate-400">{{ drug.dosage }} • {{ drug.duration }} • {{ drug.instructions }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
