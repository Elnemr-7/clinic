<template>
  <div class="max-w-[1600px] mx-auto w-full p-3 lg:p-5 pb-24 lg:pb-32 select-none text-right font-sans min-w-0" dir="rtl">
    
    <!-- Zero Clinics Onboarding Alert -->
    <div v-if="!hasClinics" class="mb-4 p-4 rounded-2xl bg-brand/10 border border-brand/30 flex flex-col md:flex-row items-center justify-between gap-3 text-content-primary shadow-xs">
      <div class="flex items-center gap-3">
        <span class="text-3xl">🏥</span>
        <div>
          <h4 class="font-black text-sm text-content-primary">أهلاً بك يا دكتور! لم تقم بإنشاء أي عيادة أو فرع حتى الآن</h4>
          <p class="text-xs text-content-muted mt-0.5">ابدأ بإضافة بيانات عيادتك الأولى وتحديد فترات العمل ومواعيد الكشف لتتمكن من تشغيل العيادة واستقبال المرضى.</p>
        </div>
      </div>
      <router-link
        to="/settings/branches"
        class="px-5 py-2.5 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl font-bold text-xs inline-flex items-center gap-2 shadow-sm transition shrink-0 cursor-pointer"
      >
        <span>+</span>
        <span>إضافة أول عيادة الآن</span>
      </router-link>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 w-full items-start">
      
      <!-- ======================================================== -->
      <!-- RIGHT SIDEBAR (3-4 Cols): Live Queue & Next Patient Rail -->
      <!-- ======================================================== -->
      <aside class="col-span-12 lg:col-span-4 xl:col-span-3 lg:sticky lg:top-3 flex flex-col bg-surface-card border border-line-subtle rounded-2xl shadow-xs overflow-hidden order-first">
        
        <!-- Rail Header: Current Patient in Room -->
        <div class="p-3.5 border-b border-line-subtle bg-surface-elevated/60">
          <div class="flex items-center justify-between">
            <span class="text-xs font-black text-content-primary flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-status-success animate-pulse"></span>
              <span>بالداخل الآن</span>
            </span>
            <span v-if="workspaceStore.currentPatient" class="text-xs font-mono font-black text-brand bg-brand-subtle border border-brand-border px-2 py-0.5 rounded-md">
              #{{ workspaceStore.currentPatient.queue_number }}
            </span>
          </div>
          
          <div v-if="workspaceStore.currentPatient" class="mt-2 flex items-center justify-between">
            <div class="truncate">
              <p class="text-xs font-bold text-content-primary truncate">
                {{ workspaceStore.currentPatient.patient?.name }}
              </p>
              <p class="text-[10px] text-content-muted">
                {{ workspaceStore.currentPatient.appointment_type === 'followup' ? 'استشارة مجانية' : 'كشف عادي' }}
              </p>
            </div>
            <span class="text-[10px] text-brand font-mono font-bold bg-brand/10 px-1.5 py-0.5 rounded">
              جاري الكشف
            </span>
          </div>
          <div v-else class="mt-1.5 text-[11px] text-content-muted">
            الغرفة شاغرة حالياً
          </div>
        </div>

        <!-- Scrollable Middle Rail: Waiting Room & Postponed Lists -->
        <div class="max-h-[220px] xl:max-h-[340px] overflow-y-auto p-3 space-y-3 divide-y divide-line-subtle">
          <!-- Waiting List Section -->
          <div>
            <div class="flex items-center justify-between mb-2 px-1">
              <span class="text-xs font-bold text-content-secondary flex items-center gap-1.5">
                <Clock class="w-3.5 h-3.5 text-status-info" />
                <span>في صالة الانتظار</span>
              </span>
              <span class="text-xs font-mono font-bold text-status-info bg-status-info-bg px-2 py-0.5 rounded-full">
                {{ waitingList.length }}
              </span>
            </div>

            <div v-if="waitingList.length === 0" class="text-[11px] text-content-muted py-3 text-center bg-surface-elevated/40 rounded-xl">
              لا توجد حالات بانتظار الدخول
            </div>

            <div v-else class="space-y-1.5">
              <div
                v-for="item in waitingList"
                :key="item.id"
                class="p-2.5 rounded-xl border border-line-subtle bg-surface-elevated hover:border-brand transition-all flex items-center justify-between"
              >
                <div class="flex items-center gap-2 truncate">
                  <span class="w-7 h-7 rounded-lg bg-surface-card border border-line-subtle text-content-primary flex items-center justify-center font-mono font-bold text-xs shrink-0">
                    #{{ item.queue_number }}
                  </span>
                  <div class="truncate">
                    <p class="text-xs font-bold text-content-primary truncate">{{ item.patient?.name }}</p>
                    <p class="text-[10px] text-content-muted">
                      {{ item.appointment_type === 'followup' ? 'استشارة' : 'كشف' }}
                    </p>
                  </div>
                </div>
                <span class="text-[10px] font-mono text-content-muted shrink-0" dir="ltr">
                  {{ item.estimated_window_start ? item.estimated_window_start.substring(11, 16) : '' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Postponed Section -->
          <div class="pt-2.5">
            <div class="flex items-center justify-between mb-2 px-1">
              <span class="text-xs font-bold text-content-secondary flex items-center gap-1.5">
                <AlertCircle class="w-3.5 h-3.5 text-status-warning" />
                <span>مؤجل لحين الوصول</span>
              </span>
              <span class="text-xs font-mono font-bold text-status-warning bg-status-warning-bg px-2 py-0.5 rounded-full">
                {{ postponedList.length }}
              </span>
            </div>

            <div v-if="postponedList.length === 0" class="text-[11px] text-content-muted py-2 text-center">
              لا توجد حالات مؤجلة
            </div>

            <div v-else class="space-y-1.5">
              <div
                v-for="item in postponedList"
                :key="item.id"
                class="p-2 rounded-xl border border-status-warning-border/60 bg-status-warning-bg/30 flex items-center justify-between text-xs"
              >
                <div class="flex items-center gap-2 truncate">
                  <span class="font-mono font-bold text-status-warning">#{{ item.queue_number }}</span>
                  <span class="truncate text-content-primary font-medium">{{ item.patient?.name }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Rail Bottom: GIANT STICKY ACTION BUTTON -->
        <div class="p-3 border-t border-line-subtle bg-surface-elevated/60">
          <button
            type="button"
            @click="handleNextPatient"
            :disabled="workspaceStore.loading"
            class="w-full py-3.5 px-4 rounded-xl bg-brand hover:bg-brand-hover text-content-inverse font-black text-xs lg:text-sm shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-98 disabled:opacity-50"
            title="إنهاء الكشف واستدعاء المريض التالي (Alt+N)"
          >
            <UserCheck class="w-4 h-4 shrink-0" />
            <span class="truncate">
              {{ nextPatientButtonLabel }}
            </span>
          </button>
          <p class="text-[10px] text-content-muted text-center mt-1.5">اختصار سريع: Alt + N</p>
        </div>
      </aside>

      <!-- ======================================================== -->
      <!-- MAIN CONSULTATION COCKPIT (8-9 Cols): Smooth Scroll      -->
      <!-- ======================================================== -->
      <div class="col-span-12 lg:col-span-8 xl:col-span-9 flex flex-col gap-3.5 min-w-0">
        
        <template v-if="workspaceStore.currentPatient">
          <!-- 1. Critical Medical Alert Bar -->
          <MedicalAlertBanner :patient="workspaceStore.currentPatient.patient" />

          <!-- 2. Active Patient & Vitals Comparison Strip -->
          <ActivePatientCard :appointment="workspaceStore.currentPatient" />

          <VitalsStrip
            :vitals-config="vitalsConfig"
            :current="workspaceStore.currentPatient.vitals_data"
            :today-vitals="workspaceStore.currentPatient.vitals_data"
            :previous="workspaceStore.previousVitals"
            :previous-vitals="workspaceStore.previousVitals"
          />

          <!-- 3. THE 4 CLINICAL ACTION CARDS (2x2 Bento Grid) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 w-full">
            
            <!-- CARD 1: Medical History (Alt+1) -->
            <div class="bg-surface-card border border-line-subtle hover:border-brand/50 rounded-2xl p-3 shadow-xs flex flex-col justify-between transition-all">
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-xl bg-status-info-bg text-status-info border border-status-info-border">
                      <FileText class="w-4 h-4" />
                    </div>
                    <div>
                      <h3 class="text-xs lg:text-sm font-black text-content-primary">1. التاريخ الطبي والأرشيف</h3>
                      <p class="text-[10px] text-content-muted">الزيارات السابقة والملفات</p>
                    </div>
                  </div>
                  <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-surface-elevated text-content-muted font-bold">
                    Alt+1
                  </span>
                </div>

                <div class="mt-2 p-2 rounded-xl bg-surface-elevated/70 border border-line-subtle text-xs space-y-1">
                  <div class="flex justify-between text-content-secondary">
                    <span>عدد الزيارات السابقة:</span>
                    <span class="font-bold font-mono text-content-primary">{{ pastVisitsCount }} زيارات</span>
                  </div>
                  <div class="flex justify-between text-content-secondary">
                    <span>آخر تشخيص مسجل:</span>
                    <span class="font-bold text-content-primary truncate max-w-[180px]">{{ lastDiagnosis || 'لا يوجد تشخيص سابق' }}</span>
                  </div>
                </div>
              </div>

              <div class="mt-2 pt-2 border-t border-line-subtle">
                <button
                  type="button"
                  @click="showHistoryModal = true"
                  class="w-full py-1.5 px-3 rounded-xl bg-surface-elevated hover:bg-surface-header text-brand border border-brand-border font-bold text-xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                >
                  <FolderOpen class="w-3.5 h-3.5" />
                  <span>فتح السجل الطبي الكامل</span>
                </button>
              </div>
            </div>

            <!-- CARD 2: Diagnosis & Prescription (Alt+2) -->
            <div class="bg-surface-card border border-line-subtle hover:border-brand/50 rounded-2xl p-3 shadow-xs flex flex-col justify-between transition-all">
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-xl bg-status-success-bg text-status-success border border-status-success-border">
                      <Pill class="w-4 h-4" />
                    </div>
                    <div>
                      <h3 class="text-xs lg:text-sm font-black text-content-primary">2. التشخيص والروشتة</h3>
                      <p class="text-[10px] text-content-muted">الأدوية والجرعات والطباعة</p>
                    </div>
                  </div>
                  <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-surface-elevated text-content-muted font-bold">
                    Alt+2
                  </span>
                </div>

                <div class="mt-2 p-2 rounded-xl bg-surface-elevated/70 border border-line-subtle text-xs space-y-1">
                  <div class="flex justify-between text-content-secondary">
                    <span>التشخيص اليوم:</span>
                    <span class="font-bold text-brand truncate max-w-[180px]">
                      {{ currentDiagnosis || 'لم يُكتب بعد' }}
                    </span>
                  </div>
                  <div class="flex justify-between text-content-secondary">
                    <span>الأدوية الموصوفة:</span>
                    <span class="font-bold font-mono text-content-primary">
                      {{ prescribedDrugsCount > 0 ? `${prescribedDrugsCount} أدوية مسجلة` : 'لم تُسجل أدوية' }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="mt-2 pt-2 border-t border-line-subtle flex items-center gap-2">
                <button
                  type="button"
                  @click="showPrescriptionModal = true"
                  class="flex-1 py-1.5 px-3 rounded-xl bg-brand hover:bg-brand-hover text-content-inverse font-bold text-xs transition flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
                >
                  <Pill class="w-3.5 h-3.5" />
                  <span>تعديل الروشتة</span>
                </button>

                <button
                  v-if="prescribedDrugsCount > 0"
                  type="button"
                  @click="handleQuickPrint"
                  class="py-1.5 px-3 rounded-xl bg-surface-elevated hover:bg-surface-header text-content-primary border border-line-subtle font-bold text-xs transition flex items-center gap-1.5 cursor-pointer"
                  title="طباعة سريعة للروشتة الحالية"
                >
                  <Printer class="w-3.5 h-3.5 text-brand" />
                  <span>طباعة سريعة</span>
                </button>
              </div>
            </div>

            <!-- CARD 3: Labs & Attachments (Alt+3) -->
            <div class="bg-surface-card border border-line-subtle hover:border-brand/50 rounded-2xl p-3 shadow-xs flex flex-col justify-between transition-all">
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-xl bg-brand-subtle text-brand border border-brand-border">
                      <FlaskConical class="w-4 h-4" />
                    </div>
                    <div>
                      <h3 class="text-xs lg:text-sm font-black text-content-primary">3. التحاليل والمرفقات</h3>
                      <p class="text-[10px] text-content-muted">طلب الفحوصات ومراجعة الأشعة</p>
                    </div>
                  </div>
                  <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-surface-elevated text-content-muted font-bold">
                    Alt+3
                  </span>
                </div>

                <div class="mt-2 p-2 rounded-xl bg-surface-elevated/70 border border-line-subtle text-xs space-y-1">
                  <div class="flex justify-between text-content-secondary">
                    <span>فحوصات مطلوبة اليوم:</span>
                    <span class="font-bold font-mono text-content-primary">{{ requestedLabsCount }} تحاليل</span>
                  </div>
                  <div class="flex justify-between text-content-secondary">
                    <span>مرفقات المريض المرفوعة:</span>
                    <span class="font-bold font-mono text-content-primary">{{ patientAttachmentsCount }} ملفات</span>
                  </div>
                </div>
              </div>

              <div class="mt-2 pt-2 border-t border-line-subtle">
                <button
                  type="button"
                  @click="showLabsModal = true"
                  class="w-full py-1.5 px-3 rounded-xl bg-surface-elevated hover:bg-surface-header text-brand border border-brand-border font-bold text-xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                >
                  <FlaskConical class="w-3.5 h-3.5" />
                  <span>إدارة التحاليل والمرفقات</span>
                </button>
              </div>
            </div>

            <!-- CARD 4: Extra Services & Followup (Alt+4) -->
            <div class="bg-surface-card border border-line-subtle hover:border-brand/50 rounded-2xl p-3 shadow-xs flex flex-col justify-between transition-all">
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-xl bg-status-warning-bg text-status-warning border border-status-warning-border">
                      <CalendarCheck class="w-4 h-4" />
                    </div>
                    <div>
                      <h3 class="text-xs lg:text-sm font-black text-content-primary">4. خدمات إضافية وإعادة</h3>
                      <p class="text-[10px] text-content-muted">إجراءات العيادة وموعد الاستشارة</p>
                    </div>
                  </div>
                  <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-surface-elevated text-content-muted font-bold">
                    Alt+4
                  </span>
                </div>

                <div class="mt-2 p-2 rounded-xl bg-surface-elevated/70 border border-line-subtle text-xs space-y-1">
                  <div class="flex justify-between text-content-secondary">
                    <span>إجمالي الخدمات الإضافية:</span>
                    <span class="font-bold font-mono text-status-warning">{{ extraServicesTotal }} ج.م</span>
                  </div>
                  <div class="flex justify-between text-content-secondary">
                    <span>موعد الإعادة المحدد:</span>
                    <span class="font-bold text-content-primary">{{ followupDateText || 'غير محدد' }}</span>
                  </div>
                </div>
              </div>

              <div class="mt-2 pt-2 border-t border-line-subtle">
                <button
                  type="button"
                  @click="showExtraModal = true"
                  class="w-full py-1.5 px-3 rounded-xl bg-surface-elevated hover:bg-surface-header text-brand border border-brand-border font-bold text-xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                >
                  <Plus class="w-3.5 h-3.5" />
                  <span>تعديل الخدمات والإعادة</span>
                </button>
              </div>
            </div>

          </div>
        </template>

        <!-- No Patient Currently In Consultation State -->
        <div v-else class="h-full bg-surface-card rounded-2xl p-8 lg:p-12 text-center border border-line-subtle shadow-xs flex flex-col items-center justify-center space-y-4">
          <div class="w-16 h-16 rounded-2xl bg-brand-subtle text-brand border border-brand-border flex items-center justify-center">
            <UserCheck class="w-8 h-8" />
          </div>
          <h3 class="text-lg lg:text-xl font-black text-content-primary">لا يوجد مريض حالياً في غرفة الكشف</h3>
          <p class="text-xs text-content-secondary max-w-md mx-auto">
            انقر أدناه لاستدعاء المريض الأول من صالة الانتظار للدخول فوراً إلى غرفة الكشف، أو اضغط Alt + N
          </p>
          <button
            type="button"
            @click="handleNextPatient"
            :disabled="workspaceStore.loading"
            class="px-8 py-3 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl font-bold text-xs lg:text-sm shadow-md transition disabled:opacity-50 cursor-pointer"
          >
            {{ workspaceStore.loading ? 'جارٍ الاستدعاء...' : 'استدعاء المريض الأول من الانتظار' }}
          </button>
        </div>

      </div>
    </div>

    <!-- Modals with dirty backdrop close guard -->
    <ModalPastHistory
      :is-open="showHistoryModal"
      :patient="workspaceStore.currentPatient?.patient"
      @close="showHistoryModal = false"
    />

    <ModalPrescription
      :is-open="showPrescriptionModal"
      :appointment="workspaceStore.currentPatient"
      @close="showPrescriptionModal = false"
      @saved="refreshWorkspace"
    />

    <ModalLabsAndAttachments
      :is-open="showLabsModal"
      :appointment="workspaceStore.currentPatient"
      @close="showLabsModal = false"
      @saved="refreshWorkspace"
    />

    <ModalExtraServicesAndFollowup
      :is-open="showExtraModal"
      :appointment="workspaceStore.currentPatient"
      @close="showExtraModal = false"
      @saved="refreshWorkspace"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useDoctorWorkspaceStore } from '../stores/doctorWorkspace';
import { useReceptionQueueStore } from '../stores/receptionQueue';
import { useSettingsStore } from '../stores/settings';
import ActivePatientCard from '../components/doctor/ActivePatientCard.vue';
import MedicalAlertBanner from '../components/doctor/MedicalAlertBanner.vue';
import VitalsStrip from '../components/doctor/VitalsStrip.vue';
import ModalPastHistory from '../components/doctor/modals/ModalPastHistory.vue';
import ModalPrescription from '../components/doctor/modals/ModalPrescription.vue';
import ModalLabsAndAttachments from '../components/doctor/modals/ModalLabsAndAttachments.vue';
import ModalExtraServicesAndFollowup from '../components/doctor/modals/ModalExtraServicesAndFollowup.vue';
import alerts from '../utils/alerts';
import {
  Clock,
  AlertCircle,
  UserCheck,
  FileText,
  Pill,
  Printer,
  FlaskConical,
  CalendarCheck,
  FolderOpen,
  Plus,
} from 'lucide-vue-next';

const authStore = useAuthStore();
const workspaceStore = useDoctorWorkspaceStore();
const queueStore = useReceptionQueueStore();
const settingsStore = useSettingsStore();

const hasClinics = computed(() => {
  if (authStore.user?.tenant?.clinics?.length) return true;
  if (authStore.clinic) return true;
  return (settingsStore.branches?.length || 0) > 0;
});

const showHistoryModal = ref(false);
const showPrescriptionModal = ref(false);
const showLabsModal = ref(false);
const showExtraModal = ref(false);

const vitalsConfig = computed(() => {
  return authStore.tenant?.vitals_config || authStore.user?.tenant?.vitals_config || [];
});

const waitingList = computed(() => {
  const all = queueStore.appointments || [];
  return all.filter(a => a.status === 'arrived_waiting' || a.status === 'waiting');
});

const postponedList = computed(() => {
  const all = queueStore.appointments || [];
  return all.filter(a => a.status === 'postponed' || a.status === 'postponed_waiting_arrival');
});

const nextPatientButtonLabel = computed(() => {
  const nextAppt = waitingList.value[0];
  if (nextAppt) {
    return `إنهاء واستدعاء #${nextAppt.queue_number} (${nextAppt.patient?.name || ''})`;
  }
  return 'إنهاء الكشف واستدعاء التالي';
});

const pastVisitsCount = computed(() => {
  return workspaceStore.pastVisits?.length || 0;
});

const lastDiagnosis = computed(() => {
  const last = workspaceStore.pastVisits?.[0];
  return last?.diagnosis || '';
});

const currentDiagnosis = computed(() => {
  return workspaceStore.currentPatient?.consultation?.diagnosis || '';
});

const prescribedDrugsCount = computed(() => {
  const drugs = workspaceStore.currentPatient?.consultation?.prescribed_drugs;
  return Array.isArray(drugs) ? drugs.length : 0;
});

const requestedLabsCount = computed(() => {
  const labs = workspaceStore.currentPatient?.consultation?.requested_labs;
  return Array.isArray(labs) ? labs.length : 0;
});

const patientAttachmentsCount = computed(() => {
  const att = workspaceStore.currentPatient?.patient?.attachments;
  return Array.isArray(att) ? att.length : 0;
});

const extraServicesTotal = computed(() => {
  return workspaceStore.currentPatient?.extra_services_total || 0;
});

const followupDateText = computed(() => {
  const c = workspaceStore.currentPatient?.consultation;
  return c?.followup_date || null;
});

const refreshWorkspace = async () => {
  await workspaceStore.fetchCurrentPatient(null, true);
  await queueStore.fetchQueue(null, null, null, true);
};

const handleNextPatient = async () => {
  try {
    const res = await workspaceStore.completeAndNext();
    await queueStore.fetchQueue(null, null, null, true);
    if (res.data?.next_patient) {
      alerts.toast(`تم استدعاء المريض #${res.data.next_patient.queue_number}: ${res.data.next_patient.patient?.name || ''}`);
    } else {
      alerts.toast('تم إنهاء الكشف، قائمة الانتظار فارغة الآن');
    }
  } catch (e) {
    alerts.warning('قائمة الانتظار فارغة', e.response?.data?.message || 'لا توجد حالات تالية في الانتظار');
  }
};

const handleQuickPrint = () => {
  const appt = workspaceStore.currentPatient;
  if (!appt) return;
  const token = appt.consultation?.pdf_short_token;
  if (token) {
    window.open(`/rx/${token}`, '_blank');
  } else {
    showPrescriptionModal.value = true;
  }
};

// Keyboard Shortcuts (Alt+1, Alt+2, Alt+3, Alt+4, Alt+N)
const handleKeyDown = (e) => {
  if (!e.altKey) return;
  const key = e.key.toLowerCase();
  if (key === '1') {
    e.preventDefault();
    showHistoryModal.value = true;
  } else if (key === '2') {
    e.preventDefault();
    showPrescriptionModal.value = true;
  } else if (key === '3') {
    e.preventDefault();
    showLabsModal.value = true;
  } else if (key === '4') {
    e.preventDefault();
    showExtraModal.value = true;
  } else if (key === 'n') {
    e.preventDefault();
    handleNextPatient();
  }
};

onMounted(async () => {
  window.addEventListener('keydown', handleKeyDown);
  await Promise.all([
    refreshWorkspace(),
    settingsStore.fetchBranches(),
  ]);
  if (workspaceStore.currentPatient?.patient?.id) {
    workspaceStore.fetchPatientHistory(workspaceStore.currentPatient.patient.id);
  }
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});
</script>
