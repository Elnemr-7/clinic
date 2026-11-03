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

const isDirty = () => {
  if (selectedFile.value) return true;
  if (labQuery.value && labQuery.value.trim().length > 0) return true;
  return false;
};

const handleSafeClose = () => {
  if (isDirty()) {
    if (!confirm('لديك تعديلات غير محفوظة. هل أنت متأكد من الإغلاق؟')) {
      return;
    }
  }
  emit('close');
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
  <Teleport to="body">
    <div v-if="isOpen" @click.self="handleSafeClose" class="fixed inset-0 z-50 overflow-y-auto bg-surface-overlay/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-surface-card border border-line-subtle rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden text-right text-content-primary" dir="rtl">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-brand text-content-inverse flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-white/10 rounded-xl">
              <FlaskConical class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-xl font-bold">التحاليل، الأشعة، والمرفقات السريرية</h3>
              <p class="text-xs text-brand-subtle/80 font-medium">المريض: {{ appointment?.patient?.name }}</p>
            </div>
          </div>
          <button @click="handleSafeClose" class="p-2 text-content-inverse/80 hover:text-content-inverse hover:bg-white/10 rounded-xl transition cursor-pointer">
            <X class="w-6 h-6" />
          </button>
        </div>

        <!-- Tab Buttons -->
        <div class="flex border-b border-line-subtle bg-surface-elevated/40 px-6 pt-3 gap-3">
          <button
            @click="activeTab = 'labs'"
            :class="[
              'pb-3 px-4 font-bold text-sm border-b-2 flex items-center gap-2 transition cursor-pointer',
              activeTab === 'labs'
                ? 'border-brand text-brand'
                : 'border-transparent text-content-muted hover:text-content-primary'
            ]"
          >
            <FlaskConical class="w-4 h-4" />
            طلب فحوصات وأشعة ({{ labRequests.length }})
          </button>

          <button
            @click="activeTab = 'attachments'"
            :class="[
              'pb-3 px-4 font-bold text-sm border-b-2 flex items-center gap-2 transition cursor-pointer',
              activeTab === 'attachments'
                ? 'border-brand text-brand'
                : 'border-transparent text-content-muted hover:text-content-primary'
            ]"
          >
            <ImageIcon class="w-4 h-4" />
            أرشيف المرفقات والتقارير ({{ attachments.length }})
          </button>
        </div>

        <!-- Tab 1: Labs & Radiology -->
        <div v-if="activeTab === 'labs'" class="p-6 overflow-y-auto space-y-5 flex-1 bg-surface-app">
          <!-- Add Lab Row Box -->
          <div class="bg-surface-card border border-line-subtle p-4 rounded-xl space-y-3">
            <label class="block text-xs font-bold text-content-primary">إضافة فحص مخبري أو فحص أشعة جديد</label>
            <div class="grid grid-cols-12 gap-2">
              <div class="col-span-12 md:col-span-6 relative">
                <input
                  v-model="labQuery"
                  @input="onLabSearch"
                  type="text"
                  placeholder="ابحث أو اكتب اسم الفحص (مثل: CBC, Pelvi-Abdominal US)..."
                  class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-lg border border-line-subtle focus:ring-2 focus:ring-brand"
                />
                <div
                  v-if="labSuggestions.length > 0"
                  class="absolute top-full right-0 left-0 mt-1 bg-surface-card border border-line-subtle rounded-xl shadow-xl z-20 max-h-40 overflow-y-auto"
                >
                  <div
                    v-for="sug in labSuggestions"
                    :key="sug.id"
                    @click="addLabItem(sug.name, sug.category)"
                    class="p-2 hover:bg-surface-elevated cursor-pointer border-b border-line-subtle last:border-none flex items-center justify-between"
                  >
                    <span class="text-xs font-bold text-content-primary">{{ sug.name }}</span>
                    <span class="text-[10px] px-2 py-0.5 rounded bg-surface-elevated text-content-secondary border border-line-subtle">{{ sug.category === 'radiology' ? 'أشعة' : 'تحليل' }}</span>
                  </div>
                </div>
              </div>

              <div class="col-span-8 md:col-span-4">
                <input
                  v-model="labInstructions"
                  type="text"
                  placeholder="تعليمات (مثل: صيام 12 ساعة)..."
                  class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-lg border border-line-subtle focus:ring-2 focus:ring-brand"
                />
              </div>

              <div class="col-span-4 md:col-span-2">
                <button
                  @click="addLabItem()"
                  type="button"
                  class="w-full py-2 bg-brand text-content-inverse hover:bg-brand-hover text-xs font-bold rounded-lg flex items-center justify-center gap-1 transition cursor-pointer"
                >
                  <Plus class="w-4 h-4" />
                  إضافة
                </button>
              </div>
            </div>
          </div>

          <!-- Selected Labs List -->
          <div>
            <h4 class="text-xs font-bold text-content-secondary mb-2">الفحوصات المطلوبة للمريض في هذه الزيارة:</h4>
            <div v-if="labRequests.length === 0" class="text-center py-8 text-content-muted text-xs border border-dashed border-line-subtle rounded-xl">
              لم يتم إضافة أي تحاليل أو فحوصات بعد
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="(lab, idx) in labRequests"
                :key="idx"
                class="p-3 bg-surface-card border border-line-subtle rounded-xl flex items-center justify-between"
              >
                <div class="flex items-center gap-3">
                  <span class="p-1.5 rounded-lg bg-brand-subtle text-brand border border-brand-border">
                    <FlaskConical class="w-4 h-4" />
                  </span>
                  <div>
                    <p class="text-xs font-bold text-content-primary">{{ lab.test_name }}</p>
                    <p v-if="lab.instructions" class="text-[11px] text-content-muted">ملاحظات: {{ lab.instructions }}</p>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <select
                    v-model="lab.category"
                    class="text-[11px] px-2 py-1 bg-surface-input text-content-primary border border-line-subtle rounded-lg"
                  >
                    <option value="lab">تحاليل طبية</option>
                    <option value="radiology">أشعة وفحوصات تصويرية</option>
                  </select>
                  <button
                    @click="removeLabItem(idx)"
                    type="button"
                    class="p-1 text-status-danger hover:bg-status-danger-bg rounded transition cursor-pointer"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 2: Attachments & Scans -->
        <div v-if="activeTab === 'attachments'" class="p-6 overflow-y-auto space-y-6 flex-1 bg-surface-app">
          <!-- Upload Form -->
          <div class="p-4 bg-surface-card border border-line-subtle rounded-xl space-y-3">
            <h4 class="text-xs font-bold text-content-primary">رفع فحص أو تقرير جديد (PDF / صورة)</h4>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
              <div class="md:col-span-5">
                <input
                  v-model="fileTitle"
                  type="text"
                  placeholder="عنوان المستند (مثال: رنين مغناطيسي 2026)"
                  class="w-full px-3 py-2 text-xs bg-surface-input text-content-primary rounded-lg border border-line-subtle"
                />
              </div>
              <div class="md:col-span-5">
                <input
                  @change="onFileSelected"
                  type="file"
                  accept="image/*,application/pdf"
                  class="w-full text-xs text-content-secondary file:mr-0 file:ml-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-surface-elevated file:text-brand hover:file:bg-brand/10 cursor-pointer"
                />
              </div>
              <div class="md:col-span-2">
                <button
                  @click="handleUploadAttachment"
                  :disabled="!selectedFile || uploading"
                  type="button"
                  class="w-full py-2 bg-brand text-content-inverse rounded-lg text-xs font-bold hover:bg-brand-hover disabled:opacity-50 transition flex items-center justify-center gap-1 cursor-pointer"
                >
                  <Upload class="w-4 h-4" />
                  <span>{{ uploading ? 'جارٍ الرفع...' : 'رفع' }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Past Attachments Grid -->
          <div>
            <h4 class="text-xs font-bold text-content-primary mb-3">المستندات والفحوصات المحفوظة للمريض:</h4>
            <div v-if="attachments.length === 0" class="text-center py-8 text-content-muted text-xs border border-dashed border-line-subtle rounded-xl">
              لا توجد مرفقات سابقة لهذا المريض
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
              <div
                v-for="att in attachments"
                :key="att.id"
                class="p-3 bg-surface-card border border-line-subtle rounded-xl hover:border-brand/50 transition flex flex-col justify-between"
              >
                <div class="flex items-start gap-2 mb-3">
                  <FileText class="w-5 h-5 text-brand shrink-0 mt-0.5" />
                  <div class="overflow-hidden">
                    <p class="text-xs font-bold text-content-primary truncate" :title="att.title">{{ att.title || 'مستند بدون عنوان' }}</p>
                    <p class="text-[10px] text-content-muted font-mono">{{ att.created_at ? new Date(att.created_at).toLocaleDateString('ar-EG') : '' }}</p>
                  </div>
                </div>

                <a
                  :href="att.file_url"
                  target="_blank"
                  class="w-full py-1.5 bg-surface-elevated hover:bg-brand-subtle text-brand text-[11px] font-semibold rounded-lg flex items-center justify-center gap-1 transition"
                >
                  <ExternalLink class="w-3.5 h-3.5" />
                  عرض المستند
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-surface-card border-t border-line-subtle flex items-center justify-between">
          <button
            @click="$emit('close')"
            type="button"
            class="px-4 py-2 text-xs font-semibold text-content-secondary hover:bg-surface-elevated rounded-xl transition cursor-pointer"
          >
            إغلاق
          </button>

          <button
            v-if="activeTab === 'labs'"
            @click="handleSaveLabs"
            :disabled="saving"
            type="button"
            class="px-5 py-2.5 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold transition disabled:opacity-50 shadow-md cursor-pointer"
          >
            {{ saving ? 'جارٍ الحفظ...' : 'حفظ الفحوصات المطلوبة' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
