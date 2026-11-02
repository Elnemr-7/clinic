<template>
  <div class="p-4 lg:p-6 space-y-4 max-w-[1600px] mx-auto w-full select-none text-right font-sans" dir="rtl">
    
    <!-- ======================================================== -->
    <!-- TIER 1: KPI METRICS (8 Cols) & ACTION BUTTONS (4 Cols)   -->
    <!-- ======================================================== -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-stretch">
      <!-- 4 KPI Counter Cards (8 Cols) -->
      <div class="lg:col-span-8 grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        <!-- Metric 1: Total Today -->
        <div class="bg-surface-card border border-line-subtle rounded-2xl p-3 shadow-xs flex flex-col justify-between">
          <span class="text-[11px] font-bold text-content-secondary flex items-center gap-1">
            <Users class="w-3.5 h-3.5 text-content-muted" />
            <span>إجمالي اليوم</span>
          </span>
          <div class="mt-2 flex items-baseline justify-between">
            <span class="text-xl lg:text-2xl font-black text-content-primary font-mono tabular-nums">
              {{ metrics.total }}
            </span>
            <span class="text-[10px] text-content-muted font-bold">حجز مسجل</span>
          </div>
        </div>

        <!-- Metric 2: Currently Waiting -->
        <div class="bg-surface-card border border-line-subtle rounded-2xl p-3 shadow-xs flex flex-col justify-between">
          <span class="text-[11px] font-bold text-status-info flex items-center gap-1">
            <Clock class="w-3.5 h-3.5" />
            <span>بالانتظار الآن</span>
          </span>
          <div class="mt-2 flex items-baseline justify-between">
            <span class="text-xl lg:text-2xl font-black text-status-info font-mono tabular-nums">
              {{ metrics.waiting }}
            </span>
            <span class="text-[10px] text-status-info font-bold">مريض حاضر</span>
          </div>
        </div>

        <!-- Metric 3: Inside Consultation -->
        <div class="bg-surface-card border border-line-subtle rounded-2xl p-3 shadow-xs flex flex-col justify-between">
          <span class="text-[11px] font-bold text-status-success flex items-center gap-1">
            <CheckCircle2 class="w-3.5 h-3.5" />
            <span>داخل الكشف</span>
          </span>
          <div class="mt-2 flex items-baseline justify-between">
            <span class="text-xl lg:text-2xl font-black text-status-success font-mono tabular-nums">
              {{ metrics.inConsultation ? `#${metrics.inConsultation.queue_number}` : 'شاغرة' }}
            </span>
            <span class="text-[10px] text-content-muted truncate max-w-[70px]">
              {{ metrics.inConsultation?.patient?.name || 'لا يوجد' }}
            </span>
          </div>
        </div>

        <!-- Metric 4: Cashbox Collection Today -->
        <div class="bg-surface-card border border-line-subtle rounded-2xl p-3 shadow-xs flex flex-col justify-between">
          <span class="text-[11px] font-bold text-brand flex items-center gap-1">
            <Wallet class="w-3.5 h-3.5" />
            <span>إيراد اليوم</span>
          </span>
          <div class="mt-2 flex items-baseline justify-between">
            <span class="text-xl lg:text-2xl font-black text-brand font-mono tabular-nums">
              {{ metrics.revenue }}
            </span>
            <span class="text-[10px] text-content-muted font-bold">ج.م نقداً</span>
          </div>
        </div>
      </div>

      <!-- Action Buttons (4 Cols) -->
      <div class="lg:col-span-4 flex items-center gap-2.5">
        <button
          type="button"
          @click="showQuickBook = true"
          class="flex-1 h-full min-h-[52px] px-4 rounded-2xl bg-brand hover:bg-brand-hover text-content-inverse font-black text-xs lg:text-sm shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-98"
          title="حجز مريض سريع (F2)"
        >
          <UserPlus class="w-4 h-4" />
          <span>حجز مريض سريع (F2)</span>
        </button>

        <button
          type="button"
          @click="showCashbox = true"
          class="h-full min-h-[52px] px-4 rounded-2xl bg-surface-card hover:bg-surface-elevated text-content-primary border border-line-subtle font-bold text-xs lg:text-sm shadow-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer"
          title="خزنة ومصروفات اليوم (F4)"
        >
          <Wallet class="w-4 h-4 text-brand" />
          <span class="hidden sm:inline">الخزينة (F4)</span>
        </button>

        <button
          type="button"
          @click="manualRefresh"
          :disabled="queueStore.loading"
          class="h-full min-h-[52px] w-12 flex items-center justify-center bg-surface-card hover:bg-surface-elevated text-content-secondary border border-line-subtle rounded-2xl transition shadow-xs cursor-pointer disabled:opacity-50"
          title="تحديث فوري"
        >
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': queueStore.loading }" />
        </button>
      </div>
    </section>

    <!-- ======================================================== -->
    <!-- TIER 2: UNIFIED FILTER & SEARCH BAR                      -->
    <!-- ======================================================== -->
    <section class="bg-surface-card border border-line-subtle rounded-2xl p-3 shadow-xs flex flex-wrap items-center justify-between gap-3">
      <!-- Search Input -->
      <div class="relative flex-1 min-w-[260px] max-w-md">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="🔍 بحث باسم المريض، رقم الموبايل، أو كود الحجز..."
          class="w-full pl-3 pr-9 py-2 text-xs bg-surface-elevated border border-line-subtle rounded-xl text-content-primary focus:outline-none focus:border-brand font-medium"
        />
        <Search class="w-4 h-4 text-content-muted absolute right-3 top-2.5" />
      </div>

      <!-- Date Filter -->
      <div class="flex items-center gap-1.5 bg-surface-elevated px-2.5 py-1.5 rounded-xl border border-line-subtle text-xs">
        <Calendar class="w-3.5 h-3.5 text-content-muted" />
        <input
          v-model="selectedDate"
          @change="manualRefresh"
          type="date"
          class="bg-transparent font-bold text-content-primary focus:outline-none cursor-pointer text-xs"
        />
      </div>

      <!-- Filter Pills -->
      <div class="flex items-center gap-1 text-xs">
        <button
          type="button"
          @click="statusFilter = 'all'"
          class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer"
          :class="statusFilter === 'all'
            ? 'bg-brand text-content-inverse'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary'"
        >
          الكل ({{ appointmentsList.length }})
        </button>

        <button
          type="button"
          @click="statusFilter = 'waiting'"
          class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer"
          :class="statusFilter === 'waiting'
            ? 'bg-status-info text-content-inverse'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary'"
        >
          بالانتظار ({{ metrics.waiting }})
        </button>

        <button
          type="button"
          @click="statusFilter = 'in_consultation'"
          class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer"
          :class="statusFilter === 'in_consultation'
            ? 'bg-status-success text-content-inverse'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary'"
        >
          داخل الكشف
        </button>

        <button
          type="button"
          @click="statusFilter = 'completed'"
          class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer"
          :class="statusFilter === 'completed'
            ? 'bg-brand text-content-inverse'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary'"
        >
          تم الكشف ({{ metrics.completed }})
        </button>

        <button
          type="button"
          @click="statusFilter = 'postponed'"
          class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer"
          :class="statusFilter === 'postponed'
            ? 'bg-status-warning text-content-inverse'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary'"
        >
          مؤجل ({{ metrics.postponed }})
        </button>
      </div>
    </section>

    <!-- ======================================================== -->
    <!-- TIER 3: HIGH-CLARITY LIVE QUEUE TABLE                    -->
    <!-- ======================================================== -->
    <section>
      <LiveQueueTable
        :appointments="filteredAppointments"
        :loading="queueStore.loading"
        @open-vitals="handleOpenVitals"
        @refresh="manualRefresh"
      />
    </section>

    <!-- Modals -->
    <ModalQuickBook
      :is-open="showQuickBook"
      :clinic-id="selectedClinicId"
      :shifts="currentClinic?.shifts || []"
      @close="showQuickBook = false"
      @booked="manualRefresh"
    />

    <ModalCashbox
      :is-open="showCashbox"
      :clinic-id="selectedClinicId"
      @close="showCashbox = false"
    />

    <ModalVitalsEntry
      :is-open="showVitalsModal"
      :appointment="selectedVitalsAppointment"
      :vitals-schema="vitalsSchema"
      @close="showVitalsModal = false"
      @saved="manualRefresh"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useReceptionQueueStore } from '../stores/receptionQueue';
import LiveQueueTable from '../components/reception/LiveQueueTable.vue';
import ModalQuickBook from '../components/reception/modals/ModalQuickBook.vue';
import ModalCashbox from '../components/reception/modals/ModalCashbox.vue';
import ModalVitalsEntry from '../components/reception/modals/ModalVitalsEntry.vue';
import {
  Users,
  Clock,
  CheckCircle2,
  Wallet,
  UserPlus,
  RefreshCw,
  Search,
  Calendar,
} from 'lucide-vue-next';

const authStore = useAuthStore();
const queueStore = useReceptionQueueStore();

const showQuickBook = ref(false);
const showCashbox = ref(false);
const showVitalsModal = ref(false);
const selectedVitalsAppointment = ref(null);

const searchQuery = ref('');
const statusFilter = ref('all');
const selectedDate = ref(new Date().toISOString().substring(0, 10));
let pollTimer = null;

const branches = computed(() => {
  if (authStore.user?.tenant?.clinics?.length) return authStore.user.tenant.clinics;
  if (authStore.clinic) return [authStore.clinic];
  return [];
});

const selectedClinicId = computed(() => {
  return queueStore.selectedClinicId || branches.value[0]?.id || authStore.clinic?.id || null;
});

const currentClinic = computed(() => {
  return branches.value.find(b => b.id === selectedClinicId.value) || branches.value[0] || authStore.clinic;
});

const vitalsSchema = computed(() => {
  return authStore.tenant?.vitals_config || authStore.user?.tenant?.vitals_config || [];
});

const appointmentsList = computed(() => {
  return queueStore.appointments || [];
});

const metrics = computed(() => {
  const appts = appointmentsList.value;
  const inConsult = appts.find(a => a.status === 'in_consultation');
  const waitingCount = appts.filter(a => a.status === 'arrived_waiting' || a.status === 'waiting').length;
  const completedCount = appts.filter(a => a.status === 'completed').length;
  const postponedCount = appts.filter(a => a.status === 'postponed' || a.status === 'postponed_waiting_arrival').length;

  const totalPaid = appts
    .filter(a => a.is_paid)
    .reduce((sum, a) => sum + Number(a.consultation_fee || a.fee || 0), 0);
  const extraPaid = appts
    .filter(a => a.extra_services_paid)
    .reduce((sum, a) => sum + Number(a.extra_services_total || 0), 0);

  return {
    total: appts.length,
    waiting: waitingCount,
    inConsultation: inConsult || null,
    completed: completedCount,
    postponed: postponedCount,
    revenue: totalPaid + extraPaid,
  };
});

const filteredAppointments = computed(() => {
  let list = appointmentsList.value;

  if (statusFilter.value === 'waiting') {
    list = list.filter(a => a.status === 'arrived_waiting' || a.status === 'waiting');
  } else if (statusFilter.value === 'in_consultation') {
    list = list.filter(a => a.status === 'in_consultation');
  } else if (statusFilter.value === 'completed') {
    list = list.filter(a => a.status === 'completed');
  } else if (statusFilter.value === 'postponed') {
    list = list.filter(a => a.status === 'postponed' || a.status === 'postponed_waiting_arrival');
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase();
    list = list.filter(a => {
      const name = a.patient?.name?.toLowerCase() || '';
      const phone = a.patient?.phone?.toLowerCase() || '';
      const refCode = a.reference_code?.toLowerCase() || '';
      const queueNum = String(a.queue_number || '');
      return name.includes(q) || phone.includes(q) || refCode.includes(q) || queueNum.includes(q);
    });
  }

  return list;
});

const manualRefresh = async () => {
  await queueStore.fetchQueue(selectedClinicId.value, selectedDate.value);
};

const handleOpenVitals = (appt) => {
  selectedVitalsAppointment.value = appt;
  showVitalsModal.value = true;
};

// Keyboard Shortcuts: F2 for Quick Book, F4 for Cashbox
const handleKeyDown = (e) => {
  if (e.key === 'F2') {
    e.preventDefault();
    showQuickBook.value = true;
  } else if (e.key === 'F4') {
    e.preventDefault();
    showCashbox.value = true;
  }
};

onMounted(async () => {
  window.addEventListener('keydown', handleKeyDown);
  await manualRefresh();

  // Background silent polling every 12 seconds (no screen flash or loading spinner)
  pollTimer = setInterval(() => {
    if (selectedClinicId.value) {
      queueStore.fetchQueue(selectedClinicId.value, selectedDate.value, null, true);
    }
  }, 12000);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
  if (pollTimer) clearInterval(pollTimer);
});
</script>
