<template>
  <div v-if="isOffline" class="fixed top-4 left-4 z-50 flex items-center gap-3 px-4 py-2.5 bg-rose-950/90 border border-rose-500/50 rounded-2xl shadow-xl backdrop-blur-md text-white text-xs">
    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
    <span class="font-bold">انقطع الاتصال بالسيرفر!</span>
    <span class="text-rose-200 text-[11px]">جاري إعادة المحاولة تلقائياً...</span>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const isOffline = ref(false);
let heartbeatTimer = null;
let consecutiveFailures = 0;

const checkServer = async () => {
  if (!navigator.onLine) {
    isOffline.value = true;
    return;
  }
  try {
    await axios.get('/api/clinic/heartbeat', { timeout: 4000 });
    consecutiveFailures = 0;
    isOffline.value = false;
  } catch (e) {
    consecutiveFailures++;
    // Only alert after 2 consecutive failures to avoid flickering on transient delays
    if (consecutiveFailures >= 2) {
      isOffline.value = true;
    }
  }
};

onMounted(() => {
  window.addEventListener('offline', () => { isOffline.value = true; });
  window.addEventListener('online', checkServer);

  heartbeatTimer = setInterval(checkServer, 15000);
});

onUnmounted(() => {
  if (heartbeatTimer) clearInterval(heartbeatTimer);
});
</script>
