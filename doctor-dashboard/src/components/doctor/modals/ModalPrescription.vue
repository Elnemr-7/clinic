<script setup>
import { ref, watch, onMounted } from 'vue';
import { useDoctorWorkspaceStore } from '../../../stores/doctorWorkspace';
import client from '../../../api/client';
import alerts from '../../../utils/alerts';
import { Plus, Trash2, Printer, Send, Save, X, Sparkles } from 'lucide-vue-next';

const props = defineProps({
  isOpen: Boolean,
  appointment: Object,
});

const emit = defineEmits(['close', 'saved']);
const workspaceStore = useDoctorWorkspaceStore();

const symptoms = ref('');
const diagnosis = ref('');
const privateNotes = ref('');
const drugs = ref([]);
const templates = ref([]);
const selectedTemplateId = ref('');
const saving = ref(false);
const drugSuggestions = ref([]);
const activeDrugIndex = ref(null);

const emptyDrugRow = () => ({
  drug_name: '',
  dosage: '',
  frequency: 'كل 8 ساعات',
  duration: '5 أيام',
  instructions: 'بعد الأكل',
});

const loadTemplates = async () => {
  try {
    const res = await client.get('/doctor/prescription-templates');
    templates.value = res.data.data || [];
  } catch (e) {
    console.error('Failed loading templates', e);
  }
};

const applyTemplate = () => {
  if (!selectedTemplateId.value) return;
  const tpl = templates.value.find(t => t.id === Number(selectedTemplateId.value));
  if (!tpl) return;

  const tplDiagnosis = tpl.diagnosis_default || tpl.diagnosis;
  if (tplDiagnosis && !diagnosis.value) {
    diagnosis.value = tplDiagnosis;
  }

  const tplDrugs = tpl.drugs_json || tpl.drugs_payload;
  if (Array.isArray(tplDrugs) && tplDrugs.length > 0) {
    // If current list only has one empty drug row, replace it
    if (drugs.value.length === 1 && !drugs.value[0].drug_name) {
      drugs.value = [];
    }
    tplDrugs.forEach(item => {
      drugs.value.push({
        drug_name: item.drug_name || '',
        dosage: item.dosage || '',
        frequency: item.frequency || 'كل 8 ساعات',
        duration: item.duration || '5 أيام',
        instructions: item.instructions || 'بعد الأكل',
      });
    });
  }
  selectedTemplateId.value = '';
};

const populateForm = () => {
  const appt = props.appointment;
  if (appt && appt.consultation) {
    const c = appt.consultation;
    symptoms.value = c.complaints_symptoms || c.symptoms || '';
    diagnosis.value = c.diagnosis || '';
    privateNotes.value = c.private_doctor_notes || c.private_notes || '';
    const rawDrugs = c.prescribed_drugs || c.prescription_drugs;
    if (Array.isArray(rawDrugs) && rawDrugs.length > 0) {
      drugs.value = rawDrugs.map(d => ({
        drug_name: d.drug_name || '',
        dosage: d.dosage || '',
        frequency: d.frequency || '',
        duration: d.duration || '',
        instructions: d.instructions || '',
      }));
    } else {
      drugs.value = [emptyDrugRow()];
    }
  } else {
    symptoms.value = '';
    diagnosis.value = '';
    privateNotes.value = '';
    drugs.value = [emptyDrugRow()];
  }
};

watch(() => [props.appointment, props.isOpen], () => {
  if (props.isOpen) {
    populateForm();
    loadTemplates();
  }
}, { immediate: true });

onMounted(() => {
  loadTemplates();
});

const addDrug = () => {
  drugs.value.push(emptyDrugRow());
};

const removeDrug = (index) => {
  drugs.value.splice(index, 1);
  if (drugs.value.length === 0) {
    drugs.value.push(emptyDrugRow());
  }
};

const onDrugInput = async (index, query) => {
  activeDrugIndex.value = index;
  if (!query || query.length < 2) {
    drugSuggestions.value = [];
    return;
  }
  try {
    drugSuggestions.value = await workspaceStore.searchDrugs(query);
  } catch (e) {
    drugSuggestions.value = [];
  }
};

const selectSuggestion = (index, item) => {
  drugs.value[index].drug_name = item.name;
  if (item.default_dosage) drugs.value[index].dosage = item.default_dosage;
  if (item.default_frequency) drugs.value[index].frequency = item.default_frequency;
  if (item.default_duration) drugs.value[index].duration = item.default_duration;
  if (item.default_instructions) drugs.value[index].instructions = item.default_instructions;
  drugSuggestions.value = [];
  activeDrugIndex.value = null;
};

const isDirty = () => {
  if (diagnosis.value && diagnosis.value.trim().length > 0) return true;
  if (symptoms.value && symptoms.value.trim().length > 0) return true;
  if (drugs.value.some(d => d.drug_name && d.drug_name.trim().length > 0)) return true;
  return false;
};

const handleSafeClose = () => {
  if (isDirty()) {
    if (!confirm('لديك تعديلات غير محفوظة في الروشتة. هل أنت متأكد من الإغلاق وفقدان البيانات؟')) {
      return;
    }
  }
  emit('close');
};

const handleSave = async (actionType = 'save') => {
  if (!props.appointment) return;

  const validDrugs = drugs.value.filter(d => d.drug_name && d.drug_name.trim() !== '');

  // Friendly Arabic validation
  if (!diagnosis.value || !diagnosis.value.trim()) {
    alerts.warning('يرجى إدخال التشخيص الطبي للمريض قبل الحفظ');
    return;
  }

  saving.value = true;
  try {
    const payload = {
      symptoms: symptoms.value,
      complaints_symptoms: symptoms.value,
      diagnosis: diagnosis.value,
      private_notes: privateNotes.value,
      private_doctor_notes: privateNotes.value,
      drugs: validDrugs,
      prescribed_drugs: validDrugs,
      send_whatsapp: actionType === 'whatsapp',
      send_sms: actionType === 'sms',
    };

    const res = await workspaceStore.savePrescription(props.appointment.id, payload);

    if (actionType === 'print') {
      const pdfUrl = res.data?.pdf_url || `/rx/${res.data?.consultation?.pdf_short_token}`;
      window.open(pdfUrl, '_blank');
    }

    if (actionType === 'whatsapp') {
      alerts.toast('تم حفظ الروشتة وإرسالها عبر الواتساب للمريض بنجاح');
    } else {
      alerts.toast('تم حفظ وتحديث الروشتة الطبية بنجاح');
    }

    emit('saved', res);
    emit('close');
  } catch (e) {
    const backendMsg = e.response?.data?.message;
    if (backendMsg && backendMsg.includes('diagnosis')) {
      alerts.warning('التشخيص الطبي مطلوب لإتمام كتابة الروشتة');
    } else {
      alerts.error('حدث خطأ أثناء حفظ الروشتة', backendMsg || 'يرجى مراجعة البيانات والمحاولة مجدداً');
    }
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" @click.self="handleSafeClose" class="fixed inset-0 z-50 overflow-y-auto bg-surface-overlay/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-surface-card border border-line-subtle rounded-2xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden text-right" dir="rtl">
      <!-- Modal Header -->
      <div class="px-6 py-4 bg-brand text-content-inverse flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-white/10 rounded-xl">
            <span class="text-2xl">💊</span>
          </div>
          <div>
            <h3 class="text-xl font-bold">الروشتة والعلاج الدوائي</h3>
            <p class="text-xs text-brand-subtle/80 font-medium">المريض: {{ appointment?.patient?.name }} (كود: #{{ appointment?.reference_code }})</p>
          </div>
        </div>
        <button @click="handleSafeClose" class="p-2 text-content-inverse/80 hover:text-content-inverse hover:bg-white/10 rounded-xl transition">
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 overflow-y-auto space-y-6 flex-1 bg-surface-app">
        <!-- Diagnosis and Symptoms -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-content-primary mb-1">الشكوى / الأعراض (Symptoms)</label>
            <input
              v-model="symptoms"
              type="text"
              placeholder="مثال: صداع نصفي، ارتفاع درجة الحرارة..."
              class="w-full px-4 py-2.5 rounded-xl border border-line-strong bg-surface-input text-content-primary focus:ring-2 focus:ring-brand text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-semibold text-content-primary mb-1">التشخيص النهائي (Diagnosis)</label>
            <input
              v-model="diagnosis"
              type="text"
              placeholder="مثال: التهاب الشعب الهوائية الحاد..."
              class="w-full px-4 py-2.5 rounded-xl border border-line-strong bg-surface-input text-content-primary focus:ring-2 focus:ring-brand text-sm"
            />
          </div>
        </div>

        <!-- Template Selector Bar -->
        <div class="flex items-center gap-3 bg-surface-card p-3 rounded-xl border border-line-subtle">
          <Sparkles class="w-5 h-5 text-status-warning" />
          <span class="text-xs font-semibold text-content-secondary">تطبيق قالب روشتة جاهز:</span>
          <select
            v-model="selectedTemplateId"
            @change="applyTemplate"
            class="flex-1 px-3 py-1.5 text-xs bg-surface-input text-content-primary rounded-lg border border-line-subtle focus:ring-2 focus:ring-brand"
          >
            <option value="">-- اختر قالباً لإضافته سريعاً --</option>
            <option v-for="tpl in templates" :key="tpl.id" :value="tpl.id">
              {{ tpl.template_name || tpl.name }} ({{ (tpl.drugs_json || tpl.drugs_payload)?.length || 0 }} أدوية)
            </option>
          </select>
        </div>

        <!-- Smart Drug Rows -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-bold text-content-primary flex items-center gap-2">
              <span>قائمة الأدوية الموصوفة</span>
              <span class="px-2 py-0.5 text-xs bg-brand-subtle text-brand font-bold rounded-full font-mono">{{ drugs.length }}</span>
            </h4>
            <button
              @click="addDrug"
              type="button"
              class="px-3 py-1.5 text-xs font-semibold bg-brand-subtle text-brand hover:opacity-90 rounded-lg flex items-center gap-1 transition"
            >
              <Plus class="w-4 h-4" />
              إضافة دواء جديد
            </button>
          </div>

          <div class="space-y-3">
            <div
              v-for="(drug, idx) in drugs"
              :key="idx"
              class="relative bg-surface-card border border-line-subtle p-3 rounded-xl grid grid-cols-12 gap-2 items-center"
            >
              <!-- Drug Name with Autocomplete -->
              <div class="col-span-12 md:col-span-4 relative">
                <label class="block text-[11px] font-semibold text-content-muted mb-1">اسم الدواء</label>
                <input
                  v-model="drug.drug_name"
                  @input="onDrugInput(idx, drug.drug_name)"
                  type="text"
                  placeholder="ابحث أو اكتب دواء جديداً..."
                  class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-lg border border-line-subtle focus:ring-2 focus:ring-brand"
                />
                <!-- Autocomplete Dropdown -->
                <div
                  v-if="activeDrugIndex === idx && drugSuggestions.length > 0"
                  class="absolute top-full right-0 left-0 mt-1 bg-surface-card border border-line-subtle rounded-xl shadow-xl z-20 max-h-48 overflow-y-auto"
                >
                  <div
                    v-for="sug in drugSuggestions"
                    :key="sug.id"
                    @click="selectSuggestion(idx, sug)"
                    class="p-2 hover:bg-surface-elevated cursor-pointer border-b border-line-subtle last:border-none text-right"
                  >
                    <p class="text-xs font-bold text-content-primary">{{ sug.name }}</p>
                    <p class="text-[10px] text-content-muted">{{ sug.default_dosage || '' }} - {{ sug.default_frequency || '' }}</p>
                  </div>
                </div>
              </div>

              <!-- Dosage -->
              <div class="col-span-6 md:col-span-2">
                <label class="block text-[11px] font-semibold text-content-muted mb-1">الجرعة</label>
                <input
                  v-model="drug.dosage"
                  type="text"
                  placeholder="500 mg"
                  class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-lg border border-line-subtle focus:ring-2 focus:ring-brand"
                />
              </div>

              <!-- Frequency -->
              <div class="col-span-6 md:col-span-2">
                <label class="block text-[11px] font-semibold text-content-muted mb-1">التكرار</label>
                <input
                  v-model="drug.frequency"
                  type="text"
                  placeholder="كل 8 ساعات"
                  class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-lg border border-line-subtle focus:ring-2 focus:ring-brand"
                />
              </div>

              <!-- Duration -->
              <div class="col-span-6 md:col-span-2">
                <label class="block text-[11px] font-semibold text-content-muted mb-1">المدة</label>
                <input
                  v-model="drug.duration"
                  type="text"
                  placeholder="5 أيام"
                  class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-lg border border-line-subtle focus:ring-2 focus:ring-brand"
                />
              </div>

              <!-- Instructions & Delete -->
              <div class="col-span-6 md:col-span-2 flex items-end gap-1">
                <div class="flex-1">
                  <label class="block text-[11px] font-semibold text-content-muted mb-1">تعليمات</label>
                  <input
                    v-model="drug.instructions"
                    type="text"
                    placeholder="بعد الأكل"
                    class="w-full px-2 py-2 text-xs bg-surface-input text-content-primary rounded-lg border border-line-subtle focus:ring-2 focus:ring-brand"
                  />
                </div>
                <button
                  @click="removeDrug(idx)"
                  type="button"
                  class="p-2 text-status-danger hover:bg-status-danger-bg rounded-lg transition mb-0.5"
                  title="حذف السطر"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Doctor Private Notes -->
        <div>
          <label class="block text-sm font-semibold text-content-primary mb-1">ملاحظات سرية للطبيب (لا تظهر في الروشتة المطبوعة للمريض)</label>
          <textarea
            v-model="privateNotes"
            rows="2"
            placeholder="ملاحظات متابعة سرية، شكوك سريرية، أو توجيهات داخلية خاصة..."
            class="w-full px-4 py-2 rounded-xl border border-line-subtle bg-surface-input text-content-primary focus:ring-2 focus:ring-brand text-xs"
          ></textarea>
        </div>
      </div>

      <!-- Modal Footer with Action Buttons -->
      <div class="px-6 py-4 bg-surface-card border-t border-line-subtle flex flex-wrap items-center justify-between gap-3">
        <button
          @click="handleSafeClose"
          type="button"
          class="px-4 py-2 text-xs font-semibold text-content-secondary hover:bg-surface-elevated rounded-xl transition"
        >
          إلغاء وإغلاق
        </button>

        <div class="flex items-center gap-2">
          <button
            @click="handleSave('save')"
            :disabled="saving"
            type="button"
            class="px-4 py-2.5 text-xs font-bold bg-surface-elevated text-content-primary hover:bg-line-strong rounded-xl flex items-center gap-2 transition disabled:opacity-50"
          >
            <Save class="w-4 h-4" />
            <span>حفظ فقط</span>
          </button>

          <button
            @click="handleSave('print')"
            :disabled="saving"
            type="button"
            class="px-4 py-2.5 text-xs font-bold bg-status-info text-white hover:opacity-90 rounded-xl flex items-center gap-2 transition disabled:opacity-50"
          >
            <Printer class="w-4 h-4" />
            <span>حفظ وطباعة (PDF)</span>
          </button>

          <button
            @click="handleSave('whatsapp')"
            :disabled="saving"
            type="button"
            class="px-4 py-2.5 text-xs font-bold bg-brand text-content-inverse hover:bg-brand-hover rounded-xl flex items-center gap-2 transition disabled:opacity-50 shadow-md"
          >
            <Send class="w-4 h-4" />
            <span>حفظ وإرسال واتساب</span>
          </button>
        </div>
      </div>
    </div>
  </div>
  </Teleport>
</template>
