<script setup>
import { useAuthStore } from './stores/auth';
import ImpersonationBanner from './components/common/ImpersonationBanner.vue';
import OfflineBlockerModal from './components/common/OfflineBlockerModal.vue';

const authStore = useAuthStore();
</script>

<template>
  <div class="min-h-screen bg-slate-100 text-slate-900 font-sans antialiased" dir="rtl">
    <!-- Super Admin Impersonation Top Banner (Correction 9) -->
    <ImpersonationBanner v-if="authStore.isImpersonating" />

    <!-- Main View Routing -->
    <router-view />

    <!-- Online-only Offline Blocker Modal -->
    <OfflineBlockerModal />
  </div>
</template>
