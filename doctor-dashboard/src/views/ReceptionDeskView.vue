<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useReceptionQueueStore } from '../stores/receptionQueue';
import LiveQueueTable from '../components/reception/LiveQueueTable.vue';
import ModalQuickBook from '../components/reception/modals/ModalQuickBook.vue';
import ModalCashbox from '../components/reception/modals/ModalCashbox.vue';
import ModalWaitingTvLauncher from '../components/reception/modals/ModalWaitingTvLauncher.vue';
import ModalVitalsEntry from '../components/reception/modals/ModalVitalsEntry.vue';
import DoctorTopNav from '../components/common/DoctorTopNav.vue';
import {
  Users,
  UserPlus,
  Wallet,
  Tv,
  Calendar,
  Building2,
  RefreshCw,
  LogOut,
  Stethoscope,
  Globe,
} from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();
const queueStore = useReceptionQueueStore();

const showQuickBook = ref(false);
const showCashbox = ref(false);
const showTvLauncher = ref(false);
const showVitalsModal = ref(false);
const selectedVitalsAppointment = ref(null);

const vitalsSchema = computed(() => {
  return authStore.tenant?.vitals_config || authStore.user?.tenant?.vitals_config || [];
});

const branches = computed(() => {
  if (authStore.user?.tenant?.clinics?.length) return authStore.user.tenant.clinics;
  if (authStore.clinic) return [authStore.clinic];
  return [];
});
const selectedClinicId = ref(authStore.clinic?.id || authStore.user?.clinic_id || null);
const selectedDate = ref(new Date().toISOString().substring(0, 10));

let pollTimer = null;

const currentClinic = computed(() => {
  return branches.value.find(b => b.id === selectedClinicId.value) || branches.value[0] || authStore.clinic;
});

onMounted(async () => {
  await authStore.fetchProfile();
  if (!selectedClinicId.value && branches.value.length > 0) {
    selectedClinicId.value = branches.value[0].id;
  }
  if (!selectedClinicId.value) {
    try {
      const res = await (await import('../api/client')).default.get('/settings/branches');
      if (res.data?.data?.length) {
        selectedClinicId.value = res.data.data[0].id;
      }
    } catch (e) {}
  }
  queueStore.selectedClinicId = selectedClinicId.value;
  await refreshQueue();

  // Background auto-refresh every 12 seconds
  pollTimer = setInterval(() => {
    refreshQueue();
  }, 12000);
});

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer);
});

const refreshQueue = async () => {
  await queueStore.fetchQueue(selectedClinicId.value, selectedDate.value);
};

const handleBranchChange = async () => {
  queueStore.selectedClinicId = selectedClinicId.value;
  await refreshQueue();
};

const handleOpenVitals = (appt) => {
  selectedVitalsAppointment.value = appt;
  showVitalsModal.value = true;
};

const handleLogout = () => {
  authStore.logout();
  router.push('/login');
};
</script>

<template>
  <div class="min-h-screen bg-slate-100 flex flex-col font-sans text-right select-none" dir="rtl">
    <!-- Top Bar using Unified DoctorTopNav component -->
    <DoctorTopNav
      title="مكتب الاستقبال والطابور الذكي"
      :subtitle="authStore.user?.name || 'الاستقبال'"
    >
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

        <!-- Date Selector -->
        <div class="flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200 text-xs">
          <Calendar class="w-4 h-4 text-slate-400" />
          <input
            v-model="selectedDate"
            @change="refreshQueue"
            type="date"
            class="bg-transparent font-bold text-slate-700 focus:outline-none cursor-pointer"
          />
        </div>
      </template>

      <template #actions>
        <button
          @click="showQuickBook = true"
          class="h-9 px-3.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
        >
          <UserPlus class="w-3.5 h-3.5" />
          <span>حجز سريع</span>
        </button>

        <button
          @click="showCashbox = true"
          class="h-9 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
        >
          <Wallet class="w-3.5 h-3.5" />
          <span>الخزينة</span>
        </button>

        <button
          @click="showTvLauncher = true"
          class="h-9 px-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
        >
          <Tv class="w-3.5 h-3.5" />
          <span>الشاشة (TV)</span>
        </button>

        <button
          @click="refreshQueue"
          :disabled="queueStore.loading"
          class="h-9 w-9 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition border border-slate-200"
          title="تحديث فوري"
        >
          <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': queueStore.loading }" />
        </button>
      </template>
    </DoctorTopNav>

    <!-- Main Live Queue Body -->
    <main class="flex-1 p-6 max-w-7xl mx-auto w-full space-y-4">
      <LiveQueueTable
        :appointments="queueStore.appointments"
        :loading="queueStore.loading"
        @open-vitals="handleOpenVitals"
        @refresh="refreshQueue"
      />
    </main>

    <!-- Modals -->
    <ModalQuickBook
      :is-open="showQuickBook"
      :clinic-id="selectedClinicId"
      :shifts="currentClinic?.shifts || []"
      @close="showQuickBook = false"
      @booked="refreshQueue"
    />

    <ModalCashbox
      :is-open="showCashbox"
      :clinic-id="selectedClinicId"
      @close="showCashbox = false"
    />

    <ModalWaitingTvLauncher
      :is-open="showTvLauncher"
      :tv-code="currentClinic?.tv_screen_code || authStore.clinic?.tv_screen_code || 'TV1001'"
      :clinic-name="currentClinic?.name_ar || authStore.clinic?.name_ar || 'عيادة الطبيب'"
      @close="showTvLauncher = false"
    />

    <ModalVitalsEntry
      :is-open="showVitalsModal"
      :appointment="selectedVitalsAppointment"
      :vitals-schema="vitalsSchema"
      @close="showVitalsModal = false"
      @saved="refreshQueue"
    />
  </div>
</template>
