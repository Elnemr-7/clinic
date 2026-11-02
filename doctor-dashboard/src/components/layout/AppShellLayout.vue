<template>
  <div class="min-h-screen bg-surface-app text-content-primary flex flex-col font-sans select-none" dir="rtl">
    <!-- PERSISTENT UNIFIED TOP HEADER (Fixed Height: h-16 / 64px) -->
    <header class="h-16 bg-surface-header border-b border-line-subtle px-4 lg:px-6 flex items-center justify-between sticky top-0 z-30 shadow-xs">
      
      <!-- ZONE 1: Identity & Global Branch Switcher (Right in RTL) -->
      <div class="flex items-center gap-3">
        <!-- Clinic & Doctor Badge -->
        <div class="flex items-center gap-2.5">
          <div class="w-10 h-10 rounded-xl bg-brand-subtle text-brand border border-brand-border flex items-center justify-center shrink-0 shadow-xs">
            <Stethoscope class="w-5 h-5" />
          </div>
          <div class="hidden sm:block">
            <h1 class="text-xs lg:text-sm font-extrabold text-content-primary leading-tight flex items-center gap-1.5">
              <span>{{ activeClinicName || 'عيادتي' }}</span>
              <span v-if="authStore.user?.role === 'doctor'" class="text-[10px] px-1.5 py-0.5 rounded bg-brand/10 text-brand font-bold">طبيب</span>
              <span v-else class="text-[10px] px-1.5 py-0.5 rounded bg-status-info/10 text-status-info font-bold">استقبال</span>
            </h1>
            <p class="text-[11px] text-content-muted font-medium truncate max-w-[150px]">
              {{ authStore.user?.name || 'د. استشاري' }}
            </p>
          </div>
        </div>

        <!-- Global Branch & Shift Switcher -->
        <div v-if="branches.length > 0" class="flex items-center gap-1.5 bg-surface-elevated px-2.5 py-1.5 rounded-xl border border-line-subtle text-xs">
          <Building2 class="w-3.5 h-3.5 text-content-muted" />
          <select
            v-if="canSwitchBranch"
            :value="selectedClinicId"
            @change="handleBranchChange($event.target.value)"
            class="bg-transparent font-bold text-content-primary focus:outline-none cursor-pointer text-xs"
          >
            <option v-for="b in branches" :key="b.id" :value="b.id">
              {{ b.name }}
            </option>
          </select>
          <span v-else class="font-bold text-content-primary text-xs">
            {{ activeClinicName }}
          </span>
          <span v-if="activeShiftLabel" class="hidden md:inline text-[10px] px-1.5 py-0.5 rounded bg-surface-card border border-line-subtle text-content-secondary font-mono">
            {{ activeShiftLabel }}
          </span>
        </div>
      </div>

      <!-- ZONE 2: Unified Navigation Tabs (Center in RTL) -->
      <nav class="flex items-center gap-1 bg-surface-elevated p-1 rounded-xl border border-line-subtle text-xs">
        <router-link
          v-if="authStore.user?.role === 'doctor'"
          to="/workspace"
          class="px-3 py-1.5 font-bold rounded-lg transition-all flex items-center gap-1.5"
          :class="isRouteActive('/workspace') ? 'bg-brand text-content-inverse shadow-xs' : 'text-content-secondary hover:text-content-primary hover:bg-surface-card'"
        >
          <span>🩺 غرفة الكشف</span>
        </router-link>

        <router-link
          to="/reception"
          class="px-3 py-1.5 font-bold rounded-lg transition-all flex items-center gap-1.5"
          :class="isRouteActive('/reception') ? 'bg-brand text-content-inverse shadow-xs' : 'text-content-secondary hover:text-content-primary hover:bg-surface-card'"
        >
          <span>📋 الاستقبال</span>
        </router-link>

        <button
          type="button"
          @click="showCashboxModal = true"
          class="px-3 py-1.5 font-bold rounded-lg transition-all flex items-center gap-1.5 text-content-secondary hover:text-content-primary hover:bg-surface-card cursor-pointer"
        >
          <span>💰 الخزنة</span>
        </button>

        <router-link
          v-if="authStore.user?.role === 'doctor'"
          to="/settings/clinical"
          class="px-3 py-1.5 font-bold rounded-lg transition-all flex items-center gap-1.5"
          :class="isRouteActive('/settings') ? 'bg-brand text-content-inverse shadow-xs' : 'text-content-secondary hover:text-content-primary hover:bg-surface-card'"
        >
          <span>⚙️ الإعدادات</span>
        </router-link>
      </nav>

      <!-- ZONE 3: Persistent Live Counters & Global Utilities (Left in RTL) -->
      <div class="flex items-center gap-2">
        <!-- Live Queue Counters -->
        <div class="hidden xl:flex items-center gap-1.5 text-xs">
          <div class="flex items-center gap-1 bg-status-info-bg text-status-info border border-status-info-border px-2.5 py-1 rounded-lg font-bold">
            <Clock class="w-3.5 h-3.5" />
            <span>انتظار:</span>
            <span class="font-mono tabular-nums">{{ queueCounts.waiting }}</span>
          </div>

          <div class="flex items-center gap-1 bg-status-warning-bg text-status-warning border border-status-warning-border px-2.5 py-1 rounded-lg font-bold">
            <span>مؤجل:</span>
            <span class="font-mono tabular-nums">{{ queueCounts.postponed }}</span>
          </div>

          <div class="flex items-center gap-1 bg-status-success-bg text-status-success border border-status-success-border px-2.5 py-1 rounded-lg font-bold">
            <CheckCircle class="w-3.5 h-3.5" />
            <span>تم:</span>
            <span class="font-mono tabular-nums">{{ queueCounts.completed }}</span>
          </div>
        </div>

        <!-- TV Launcher Button -->
        <button
          type="button"
          @click="showWaitingTvLauncher = true"
          class="h-9 px-2.5 bg-surface-elevated hover:bg-surface-card text-content-secondary hover:text-brand border border-line-subtle rounded-xl text-xs font-bold transition-all flex items-center gap-1 shadow-xs cursor-pointer"
          title="تشغيل شاشة الانتظار للعيادة (TV)"
        >
          <Tv class="w-3.5 h-3.5 text-brand" />
          <span class="hidden md:inline">شاشة TV</span>
        </button>

        <!-- Doctor Website Button -->
        <a
          v-if="doctorWebsiteUrl"
          :href="doctorWebsiteUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="h-9 px-2.5 bg-surface-elevated hover:bg-surface-card text-content-secondary hover:text-brand border border-line-subtle rounded-xl text-xs font-bold transition-all flex items-center gap-1 shadow-xs"
          title="معاينة موقع العيادة والحجز الإلكتروني"
        >
          <Globe class="w-3.5 h-3.5 text-brand" />
          <span class="hidden md:inline">موقع العيادة</span>
        </a>

        <!-- Dark/Light Mode Switcher -->
        <button
          type="button"
          @click="theme.toggleTheme()"
          class="h-9 w-9 flex items-center justify-center text-content-secondary hover:text-content-primary hover:bg-surface-elevated border border-line-subtle rounded-xl transition-all shadow-xs cursor-pointer"
          :title="theme.isDark.value ? 'التبديل إلى الوضع النهاري' : 'التبديل إلى الوضع الليلي'"
        >
          <Sun v-if="theme.isDark.value" class="w-4 h-4 text-amber-400" />
          <Moon v-else class="w-4 h-4 text-content-secondary" />
        </button>

        <!-- Logout Button -->
        <button
          type="button"
          @click="handleLogout"
          class="h-9 w-9 flex items-center justify-center text-status-danger hover:bg-status-danger-bg border border-status-danger-border rounded-xl transition-all shadow-xs cursor-pointer"
          title="تسجيل الخروج"
        >
          <LogOut class="w-4 h-4" />
        </button>
      </div>
    </header>

    <!-- DYNAMIC PAGE CANVAS (<RouterView /> inside h-[calc(100vh-4rem)]) -->
    <main class="h-[calc(100vh-4rem)] bg-surface-app overflow-auto">
      <router-view />
    </main>

    <!-- Global Modals mounted once in shell -->
    <ModalCashbox
      v-if="showCashboxModal"
      @close="showCashboxModal = false"
    />

    <ModalWaitingTvLauncher
      v-if="showWaitingTvLauncher"
      :clinic-id="selectedClinicId"
      :clinic-name="activeClinicName"
      @close="showWaitingTvLauncher = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useSettingsStore } from '../../stores/settings';
import { useDoctorWorkspaceStore } from '../../stores/doctorWorkspace';
import { useReceptionQueueStore } from '../../stores/receptionQueue';
import { useTheme } from '../../composables/useTheme';
import ModalCashbox from '../reception/modals/ModalCashbox.vue';
import ModalWaitingTvLauncher from '../reception/modals/ModalWaitingTvLauncher.vue';
import {
  Stethoscope,
  Building2,
  Clock,
  CheckCircle,
  Tv,
  Globe,
  Sun,
  Moon,
  LogOut,
} from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const settingsStore = useSettingsStore();
const workspaceStore = useDoctorWorkspaceStore();
const queueStore = useReceptionQueueStore();
const theme = useTheme();

const showCashboxModal = ref(false);
const showWaitingTvLauncher = ref(false);
let liveTimer = null;

const branches = computed(() => {
  if (authStore.user?.tenant?.clinics?.length) return authStore.user.tenant.clinics;
  if (authStore.clinic) return [authStore.clinic];
  return settingsStore.branches || [];
});

const canSwitchBranch = computed(() => {
  return authStore.user?.role === 'doctor' && branches.value.length > 1;
});

const selectedClinicId = computed({
  get() {
    return workspaceStore.selectedClinicId || queueStore.selectedClinicId || branches.value[0]?.id || null;
  },
  set(val) {
    workspaceStore.selectedClinicId = Number(val);
    queueStore.selectedClinicId = Number(val);
  },
});

const activeClinicName = computed(() => {
  const match = branches.value.find(b => b.id === selectedClinicId.value);
  return match?.name || authStore.clinic?.name || authStore.tenant?.name || 'عيادتي';
});

const activeShiftLabel = computed(() => {
  const hour = new Date().getHours();
  return hour >= 14 ? 'الفترة المسائية' : 'الفترة الصباحية';
});

const queueCounts = computed(() => {
  if (route.path.includes('/workspace')) {
    return {
      waiting: workspaceStore.counts?.waiting || 0,
      postponed: workspaceStore.counts?.postponed || 0,
      completed: workspaceStore.counts?.completed || 0,
    };
  }
  const appts = queueStore.appointments || [];
  return {
    waiting: appts.filter(a => a.status === 'arrived_waiting' || a.status === 'waiting').length,
    postponed: appts.filter(a => a.status === 'postponed' || a.status === 'postponed_waiting_arrival').length,
    completed: appts.filter(a => a.status === 'completed').length,
  };
});

const doctorWebsiteUrl = computed(() => {
  const tenant = authStore.user?.tenant || authStore.tenant;
  if (!tenant?.subdomain) return null;
  return `http://${tenant.subdomain}.localhost:3000`;
});

const isRouteActive = (prefix) => {
  return route.path.startsWith(prefix);
};

const handleBranchChange = async (newId) => {
  const idNum = Number(newId);
  selectedClinicId.value = idNum;
  if (route.path.includes('/workspace')) {
    await workspaceStore.fetchCurrentPatient(idNum);
  } else if (route.path.includes('/reception')) {
    await queueStore.fetchQueue(idNum);
  }
};

const handleLogout = () => {
  authStore.logout();
  router.push('/login');
};

onMounted(async () => {
  theme.initTheme();
  if (branches.value.length > 0 && !selectedClinicId.value) {
    selectedClinicId.value = branches.value[0].id;
  }
  // Periodically silent-fetch queue counts
  liveTimer = setInterval(() => {
    if (selectedClinicId.value) {
      if (route.path.includes('/workspace')) {
        workspaceStore.fetchCurrentPatient(selectedClinicId.value, true);
      } else {
        queueStore.fetchQueue(selectedClinicId.value, null, null, true);
      }
    }
  }, 12000);
});

onUnmounted(() => {
  if (liveTimer) clearInterval(liveTimer);
});
</script>
