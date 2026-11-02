<script setup>
import { ref, watch, onMounted } from 'vue';
import { useDoctorWorkspaceStore } from '../../../stores/doctorWorkspace';
import client from '../../../api/client';
import alerts from '../../../utils/alerts';
import { Plus, Trash2, Upload, FileText, ExternalLink, X, FlaskConical, Image as ImageIcon } from 'lucide-vue-next';

const props = defineProps({
  isOpen: Boolean,
  appointment: Object,
});

const emit = defineEmits(['close', 'saved']);
const workspaceStore = useDoctorWorkspaceStore();

const activeTab = ref('labs'); // 'labs' | 'attachments'
const labRequests = ref([]);
const attachments = ref([]);
const saving = ref(false);
const uploading = ref(false);

const labQuery = ref('');
const labSuggestions = ref([]);
const labInstructions = ref('');

const selectedFile = ref(null);
const fileTitle = ref('');

const emptyLabRow = () => ({
  test_name: '',
  category: 'lab', // lab | radiology
  instructions: '',
});

watch(() => props.appointment, (appt) => {
  if (appt && appt.consultation) {
    const c = appt.consultation;
    if (Array.isArray(c.lab_requests) && c.lab_requests.length > 0) {
      labRequests.value = c.lab_requests.map(l => ({
        test_name: l.test_name || '',
        category: l.category || 'lab',
        instructions: l.instructions || '',
      }));
    } else {
      labRequests.value = [];
    }
  } else {
    labRequests.value = [];
  }

  if (appt && appt.patient) {
    loadAttachments(appt.patient.id);
  }
}, { immediate: true });

async function loadAttachments(patientId) {
  try {
    const history = await workspaceStore.fetchPatientHistory(patientId);
    attachments.value = history.attachments || [];
  } catch (e) {
    console.error('Failed loading attachments', e);
  }
}

const onLabSearch = async () => {
  if (labQuery.value.length < 2) {
    labSuggestions.value = [];
    return;
  }
  try {
    labSuggestions.value = await workspaceStore.searchLabs(labQuery.value);
  } catch (e) {
    labSuggestions.value = [];
  }
};

const addLabItem = (name = null, category = 'lab') => {
  const testName = name || labQuery.value.trim();
  if (!testName) return;

  labRequests.value.push({
    test_name: testName,
    category: category,
    instructions: labInstructions.value.trim(),
  });

  labQuery.value = '';
  labInstructions.value = '';
  labSuggestions.value = [];
};

const removeLabItem = (index) => {
  labRequests.value.splice(index, 1);
};

const handleSaveLabs = async () => {
  if (!props.appointment) return;
  saving.value = true;
  try {
    const payload = {
      lab_requests: labRequests.value,
    };
    const res = await workspaceStore.saveLabs(props.appointment.id, payload);
    alerts.toast('تم حفظ الفحوصات والتحاليل بنجاح');
    emit('saved', res);
    emit('close');
  } catch (e) {
    alerts.error('فشل حفظ الفحوصات', e.response?.data?.message || 'حدث خطأ أثناء حفظ الفحوصات');
  } finally {
    saving.value = false;
  }
};

const onFileSelected = (e) => {
  if (e.target.files && e.target.files[0]) {
    selectedFile.value = e.target.files[0];
    if (!fileTitle.value) {
      fileTitle.value = selectedFile.value.name;
    }
  }
};

const handleUploadAttachment = async () => {
  if (!selectedFile.value || !props.appointment?.patient?.id) return;
  uploading.value = true;
  try {
    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('title', fileTitle.value || 'مستند طبي');
    formData.append('appointment_id', props.appointment.id);

    await workspaceStore.uploadAttachment(props.appointment.patient.id, formData);
    selectedFile.value = null;
    fileTitle.value = '';
    alerts.toast('تم رفع المرفق الطبي بنجاح');
    await loadAttachments(props.appointment.patient.id);
  } catch (e) {
    alerts.error('فشل رفع الملف', e.response?.data?.message || 'تأكد من صيغة الملف وحجمه');
  } finally {
    uploading.value = false;
  }
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden text-right" dir="rtl">
      <!-- Modal Header -->
      <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-violet-700 text-white flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-white/10 rounded-xl">
            <FlaskConical class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-xl font-bold">التحاليل، الأشعة، والمرفقات السريرية</h3>
            <p class="text-xs text-indigo-100 font-medium">المريض: {{ appointment?.patient?.name }}</p>
          </div>
        </div>
        <button @click="$emit('close')" class="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition">
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Tab Buttons -->
      <div class="flex border-b border-slate-200 bg-slate-50 px-6 pt-3 gap-3">
        <button
          @click="activeTab = 'labs'"
          :class="[
            'pb-3 px-4 font-bold text-sm border-b-2 flex items-center gap-2 transition',
            activeTab === 'labs'
              ? 'border-indigo-600 text-indigo-600'
              : 'border-transparent text-slate-500 hover:text-slate-800'
          ]"
        >
          <FlaskConical class="w-4 h-4" />
          طلب فحوصات وأشعة ({{ labRequests.length }})
        </button>

        <button
          @click="activeTab = 'attachments'"
          :class="[
            'pb-3 px-4 font-bold text-sm border-b-2 flex items-center gap-2 transition',
            activeTab === 'attachments'
              ? 'border-indigo-600 text-indigo-600'
              : 'border-transparent text-slate-500 hover:text-slate-800'
          ]"
        >
          <ImageIcon class="w-4 h-4" />
          أرشيف المرفقات والتقارير ({{ attachments.length }})
        </button>
      </div>

      <!-- Tab 1: Labs & Radiology -->
      <div v-if="activeTab === 'labs'" class="p-6 overflow-y-auto space-y-5 flex-1">
        <!-- Add Lab Row Box -->
        <div class="bg-indigo-50/60 border border-indigo-100 p-4 rounded-xl space-y-3">
          <label class="block text-xs font-bold text-indigo-950">إضافة فحص مخبري أو فحص أشعة جديد</label>
          <div class="grid grid-cols-12 gap-2">
            <div class="col-span-12 md:col-span-6 relative">
              <input
                v-model="labQuery"
                @input="onLabSearch"
                type="text"
                placeholder="ابحث أو اكتب اسم الفحص (مثل: CBC, Pelvi-Abdominal US)..."
                class="w-full px-3 py-2 text-xs bg-white rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500"
              />
              <div
                v-if="labSuggestions.length > 0"
                class="absolute top-full right-0 left-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-20 max-h-40 overflow-y-auto"
              >
                <div
                  v-for="sug in labSuggestions"
                  :key="sug.id"
                  @click="addLabItem(sug.name, sug.category)"
                  class="p-2 hover:bg-indigo-50 cursor-pointer border-b border-slate-100 last:border-none flex items-center justify-between"
                >
                  <span class="text-xs font-bold text-slate-800">{{ sug.name }}</span>
                  <span class="text-[10px] px-2 py-0.5 rounded bg-slate-100 text-slate-600">{{ sug.category === 'radiology' ? 'أشعة' : 'تحليل' }}</span>
                </div>
              </div>
            </div>

            <div class="col-span-8 md:col-span-4">
              <input
                v-model="labInstructions"
                type="text"
                placeholder="تعليمات (مثل: صيام 12 ساعة)..."
                class="w-full px-3 py-2 text-xs bg-white rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <div class="col-span-4 md:col-span-2">
              <button
                @click="addLabItem()"
                type="button"
                class="w-full py-2 bg-indigo-600 text-white hover:bg-indigo-700 text-xs font-bold rounded-lg flex items-center justify-center gap-1 transition"
              >
                <Plus class="w-4 h-4" />
                إضافة
              </button>
            </div>
          </div>
        </div>

        <!-- Selected Labs List -->
        <div>
          <h4 class="text-xs font-bold text-slate-500 mb-2">الفحوصات المطلوبة للمريض في هذه الزيارة:</h4>
          <div v-if="labRequests.length === 0" class="text-center py-8 text-slate-400 text-xs border border-dashed rounded-xl">
            لم يتم إضافة أي تحاليل أو فحوصات بعد
          </div>

          <div v-else class="space-y-2">
            <div
              v-for="(lab, idx) in labRequests"
              :key="idx"
              class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between"
            >
              <div class="flex items-center gap-3">
                <span class="p-1.5 rounded-lg bg-indigo-100 text-indigo-700">
                  <FlaskConical class="w-4 h-4" />
                </span>
                <div>
                  <p class="text-xs font-bold text-slate-800">{{ lab.test_name }}</p>
                  <p v-if="lab.instructions" class="text-[11px] text-slate-500">ملاحظات: {{ lab.instructions }}</p>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <select
                  v-model="lab.category"
                  class="text-[11px] px-2 py-1 bg-white border border-slate-300 rounded-lg"
                >
                  <option value="lab">تحاليل طبية</option>
                  <option value="radiology">أشعة وفحوصات تصويرية</option>
                </select>
                <button
                  @click="removeLabItem(idx)"
                  type="button"
                  class="p-1 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 2: Attachments & Scans -->
      <div v-if="activeTab === 'attachments'" class="p-6 overflow-y-auto space-y-6 flex-1">
        <!-- Upload Form -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
          <h4 class="text-xs font-bold text-slate-700">رفع فحص أو تقرير جديد (PDF / صورة)</h4>
          <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <div class="md:col-span-5">
              <input
                v-model="fileTitle"
                type="text"
                placeholder="عنوان المستند (مثال: رنين مغناطيسي 2026)"
                class="w-full px-3 py-2 text-xs bg-white rounded-lg border border-slate-300"
              />
            </div>
            <div class="md:col-span-5">
              <input
                @change="onFileSelected"
                type="file"
                accept="image/*,application/pdf"
                class="w-full text-xs text-slate-500 file:mr-0 file:ml-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
              />
            </div>
            <div class="md:col-span-2">
              <button
                @click="handleUploadAttachment"
                :disabled="!selectedFile || uploading"
                type="button"
                class="w-full py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 disabled:opacity-50 transition flex items-center justify-center gap-1"
              >
                <Upload class="w-4 h-4" />
                <span>{{ uploading ? 'جارٍ الرفع...' : 'رفع' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Past Attachments Grid -->
        <div>
          <h4 class="text-xs font-bold text-slate-700 mb-3">المستندات والفحوصات المحفوظة للمريض:</h4>
          <div v-if="attachments.length === 0" class="text-center py-8 text-slate-400 text-xs border border-dashed rounded-xl">
            لا توجد مرفقات سابقة لهذا المريض
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
            <div
              v-for="att in attachments"
              :key="att.id"
              class="p-3 bg-white border border-slate-200 rounded-xl hover:border-indigo-400 transition flex flex-col justify-between"
            >
              <div class="flex items-start gap-2 mb-3">
                <FileText class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" />
                <div class="overflow-hidden">
                  <p class="text-xs font-bold text-slate-800 truncate" :title="att.title">{{ att.title || 'مستند بدون عنوان' }}</p>
                  <p class="text-[10px] text-slate-400">{{ att.created_at ? new Date(att.created_at).toLocaleDateString('ar-EG') : '' }}</p>
                </div>
              </div>

              <a
                :href="att.file_url"
                target="_blank"
                class="w-full py-1.5 bg-slate-100 hover:bg-indigo-50 text-indigo-700 text-[11px] font-semibold rounded-lg flex items-center justify-center gap-1 transition"
              >
                <ExternalLink class="w-3.5 h-3.5" />
                عرض المستند
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
        <button
          @click="$emit('close')"
          type="button"
          class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition"
        >
          إغلاق
        </button>

        <button
          v-if="activeTab === 'labs'"
          @click="handleSaveLabs"
          :disabled="saving"
          type="button"
          class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition disabled:opacity-50 shadow-md shadow-indigo-500/20"
        >
          {{ saving ? 'جارٍ الحفظ...' : 'حفظ الفحوصات المطلوبة' }}
        </button>
      </div>
    </div>
  </div>
</template>
