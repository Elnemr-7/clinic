<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useDoctorWorkspaceStore } from '../stores/doctorWorkspace';
import ActivePatientCard from '../components/doctor/ActivePatientCard.vue';
import MedicalAlertBanner from '../components/doctor/MedicalAlertBanner.vue';
import VitalsStrip from '../components/doctor/VitalsStrip.vue';
import ModalPastHistory from '../components/doctor/modals/ModalPastHistory.vue';
import ModalPrescription from '../components/doctor/modals/ModalPrescription.vue';
import ModalLabsAndAttachments from '../components/doctor/modals/ModalLabsAndAttachments.vue';
import ModalExtraServicesAndFollowup from '../components/doctor/modals/ModalExtraServicesAndFollowup.vue';
import DoctorTopNav from '../components/common/DoctorTopNav.vue';
import alerts from '../utils/alerts';
import {
  Stethoscope,
  Users,
  CheckCircle,
  Clock,
  Settings,
  LogOut,
  FileText,
  Pill,
  FlaskConical,
  CalendarCheck,
  ChevronRight,
  UserCheck,
  Building2,
  RefreshCw,
} from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();
const workspaceStore = useDoctorWorkspaceStore();

const showHistoryModal = ref(false);
const showPrescriptionModal = ref(false);
const showLabsModal = ref(false);
const showExtraModal = ref(false);

const branches = computed(() => authStore.user?.tenant?.clinics || authStore.tenant?.clinics || []);
const vitalsConfig = computed(() => authStore.tenant?.vitals_config || authStore.user?.tenant?.vitals_config || []);
const selectedClinicId = ref(null);

onMounted(async () => {
  await authStore.fetchProfile();
  if (branches.value.length > 0) {
    selectedClinicId.value = branches.value[0].id;
    workspaceStore.selectedClinicId = selectedClinicId.value;
  }
  await workspaceStore.fetchCurrentPatient();
});

const handleBranchChange = async () => {
  workspaceStore.selectedClinicId = selectedClinicId.value;
  await workspaceStore.fetchCurrentPatient();
};

const handleNextPatient = async () => {
  try {
    const res = await workspaceStore.completeAndNext();
    if (res.data?.next_patient) {
      alerts.toast(`تم استدعاء المريض: ${res.data.next_patient.patient?.name || ''}`);
    } else {
      alerts.toast('تم إنهاء الكشف، لا توجد حالات أخرى في الانتظار');
    }
  } catch (e) {
    alerts.warning('قائمة الانتظار فارغة', e.response?.data?.message || 'لا توجد حالات تالية في قائمة الانتظار اليوم');
  }
};

const handleLogout = () => {
  authStore.logout();
  router.push('/login');
};
</script>

<template>
  <div class="min-h-screen bg-slate-100 flex flex-col font-sans text-right select-none" dir="rtl">
    <!-- Top Bar with Universal Doctor Navigation -->
    <DoctorTopNav title="غرفة الكشف والاستشارة" :subtitle="authStore.user?.name">
      <template #after-title>
        <!-- Branch Selector -->
        <div v-if="branches.length > 1" class="flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200 text-xs">
          <Building2 class="w-4 h-4 text-slate-400" />
          <select
            v-model="selectedClinicId"
            @change="handleBranchChange"
            class="bg-transparent font-bold text-slate-700 focus:outline-none cursor-pointer"
          >
            <option v-for="b in branches" :key="b.id" :value="b.id">
              {{ b.name }}
            </option>
          </select>
        </div>
      </template>

      <template #actions>
        <!-- Real-time Queue Counters -->
        <div class="flex items-center gap-2">
          <div class="flex items-center gap-1.5 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-xl">
            <Users class="w-3.5 h-3.5 text-amber-600" />
            <span class="text-xs text-amber-800 font-bold">انتظار:</span>
            <span class="text-xs font-extrabold text-amber-900 font-mono">{{ workspaceStore.counts.waiting || 0 }}</span>
          </div>

          <div class="flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-xl">
            <CheckCircle class="w-3.5 h-3.5 text-emerald-600" />
            <span class="text-xs text-emerald-800 font-bold">تم:</span>
            <span class="text-xs font-extrabold text-emerald-900 font-mono">{{ workspaceStore.counts.completed || 0 }}</span>
          </div>

          <div class="flex items-center gap-1.5 bg-orange-50 border border-orange-200 px-3 py-1.5 rounded-xl">
            <Clock class="w-3.5 h-3.5 text-orange-600" />
            <span class="text-xs text-orange-800 font-bold">مؤجل:</span>
            <span class="text-xs font-extrabold text-orange-900 font-mono">{{ workspaceStore.counts.postponed || 0 }}</span>
          </div>

          <button
            @click="workspaceStore.fetchCurrentPatient()"
            class="h-9 w-9 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition border border-slate-200"
            title="تحديث البيانات"
          >
            <RefreshCw class="w-4 h-4" />
          </button>
        </div>
      </template>
    </DoctorTopNav>

    <!-- Workspace Main Body -->
    <main class="flex-1 p-6 max-w-7xl mx-auto w-full space-y-5">
      <!-- Active Patient Details -->
      <div v-if="workspaceStore.currentPatient" class="space-y-4">
        <!-- Medical Alert (Allergies / Chronic) -->
        <MedicalAlertBanner :patient="workspaceStore.currentPatient.patient" />

        <!-- Patient Header Card -->
        <ActivePatientCard :appointment="workspaceStore.currentPatient" />

        <!-- Vitals Strip -->
        <VitalsStrip
          :vitals-config="vitalsConfig"
          :current="workspaceStore.currentPatient.vitals_data"
          :today-vitals="workspaceStore.currentPatient.vitals_data"
          :previous="workspaceStore.previousVitals"
          :previous-vitals="workspaceStore.previousVitals"
        />

        <!-- 4 Fast Action Buttons (Decoupled Modals) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
          <!-- Button 1: History -->
          <button
            @click="showHistoryModal = true"
            type="button"
            class="p-4 bg-white hover:bg-slate-50 border border-slate-200 hover:border-blue-400 rounded-2xl shadow-sm transition flex items-center justify-between group"
          >
            <div class="flex items-center gap-3">
              <div class="p-3 bg-blue-50 text-blue-600 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition">
                <FileText class="w-6 h-6" />
              </div>
              <div class="text-right">
                <h4 class="text-sm font-bold text-slate-800">السجل الطبي</h4>
                <p class="text-[11px] text-slate-400">الزيارات السابقة والتشخيصات</p>
              </div>
            </div>
            <ChevronRight class="w-5 h-5 text-slate-300 group-hover:text-blue-500 transition" />
          </button>

          <!-- Button 2: Prescription -->
          <button
            @click="showPrescriptionModal = true"
            type="button"
            class="p-4 bg-white hover:bg-slate-50 border border-slate-200 hover:border-emerald-400 rounded-2xl shadow-sm transition flex items-center justify-between group"
          >
            <div class="flex items-center gap-3">
              <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition">
                <Pill class="w-6 h-6" />
              </div>
              <div class="text-right">
                <h4 class="text-sm font-bold text-slate-800">الروشتة والعلاج</h4>
                <p class="text-[11px] text-slate-400">إضافة الأدوية والطباعة</p>
              </div>
            </div>
            <ChevronRight class="w-5 h-5 text-slate-300 group-hover:text-emerald-500 transition" />
          </button>

          <!-- Button 3: Labs -->
          <button
            @click="showLabsModal = true"
            type="button"
            class="p-4 bg-white hover:bg-slate-50 border border-slate-200 hover:border-indigo-400 rounded-2xl shadow-sm transition flex items-center justify-between group"
          >
            <div class="flex items-center gap-3">
              <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition">
                <FlaskConical class="w-6 h-6" />
              </div>
              <div class="text-right">
                <h4 class="text-sm font-bold text-slate-800">التحاليل والمرفقات</h4>
                <p class="text-[11px] text-slate-400">طلب فحوصات أو صور الأشعة</p>
              </div>
            </div>
            <ChevronRight class="w-5 h-5 text-slate-300 group-hover:text-indigo-500 transition" />
          </button>

          <!-- Button 4: Extra Services & Followup -->
          <button
            @click="showExtraModal = true"
            type="button"
            class="p-4 bg-white hover:bg-slate-50 border border-slate-200 hover:border-amber-400 rounded-2xl shadow-sm transition flex items-center justify-between group"
          >
            <div class="flex items-center gap-3">
              <div class="p-3 bg-amber-50 text-amber-600 rounded-xl group-hover:bg-amber-600 group-hover:text-white transition">
                <CalendarCheck class="w-6 h-6" />
              </div>
              <div class="text-right">
                <h4 class="text-sm font-bold text-slate-800">الخدمات والمتابعة</h4>
                <p class="text-[11px] text-slate-400">إجراءات العيادة وموعد الإعادة</p>
              </div>
            </div>
            <ChevronRight class="w-5 h-5 text-slate-300 group-hover:text-amber-500 transition" />
          </button>
        </div>
      </div>

      <!-- No Patient Currently in Consultation -->
      <div v-else class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm space-y-4">
        <div class="inline-flex p-4 bg-teal-50 text-teal-600 rounded-2xl">
          <UserCheck class="w-10 h-10" />
        </div>
        <h3 class="text-xl font-bold text-slate-800">لا يوجد مريض حالياً في غرفة الكشف</h3>
        <p class="text-xs text-slate-400 max-w-md mx-auto">
          انقر أدناه لإنهاء الانتظار واستدعاء أول مريض في طابور اليوم للدخول إلى غرفة الكشف
        </p>
        <button
          @click="handleNextPatient"
          :disabled="workspaceStore.loading"
          type="button"
          class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-bold text-sm shadow-lg shadow-emerald-600/30 transition disabled:opacity-50"
        >
          {{ workspaceStore.loading ? 'جارٍ التحميل...' : 'استدعاء المريض الأول من الانتظار' }}
        </button>
      </div>
    </main>

    <!-- Giant Bottom Action Bar (Sticky Footer) -->
    <footer v-if="workspaceStore.currentPatient" class="bg-white border-t border-slate-200 px-6 py-4 shadow-lg sticky bottom-0 z-30">
      <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 text-xs text-slate-500">
          <span class="font-bold text-slate-700">المريض الحالي:</span>
          <span>{{ workspaceStore.currentPatient?.patient?.name }}</span>
          <span>•</span>
          <span class="font-mono">#{{ workspaceStore.currentPatient?.reference_code }}</span>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="handleNextPatient"
            :disabled="workspaceStore.loading"
            type="button"
            class="px-10 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-2xl font-black text-sm shadow-xl shadow-emerald-500/25 transition flex items-center gap-3 disabled:opacity-50 active:scale-95"
          >
            <UserCheck class="w-5 h-5" />
            <span>إنهاء الكشف واستدعاء المريض التالي (Next Patient)</span>
          </button>
        </div>
      </div>
    </footer>

    <!-- Modals -->
    <ModalPastHistory
      :is-open="showHistoryModal"
      :patient="workspaceStore.currentPatient?.patient"
      @close="showHistoryModal = false"
    />

    <ModalPrescription
      :is-open="showPrescriptionModal"
      :appointment="workspaceStore.currentPatient"
      @close="showPrescriptionModal = false"
      @saved="workspaceStore.fetchCurrentPatient()"
    />

    <ModalLabsAndAttachments
      :is-open="showLabsModal"
      :appointment="workspaceStore.currentPatient"
      @close="showLabsModal = false"
      @saved="workspaceStore.fetchCurrentPatient()"
    />

    <ModalExtraServicesAndFollowup
      :is-open="showExtraModal"
      :appointment="workspaceStore.currentPatient"
      @close="showExtraModal = false"
      @saved="workspaceStore.fetchCurrentPatient()"
    />
  </div>
</template>
