<script setup>
import { onMounted } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const token = route.params.token;
const config = useRuntimeConfig();

onMounted(() => {
  const apiBase = config.public.apiBase || 'http://localhost:8000/api';
  window.location.href = `${apiBase}/rx/${token}`;
});
</script>

<template>
  <div class="min-h-screen bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-500" dir="rtl">
    جارٍ فتح الروشتة الطبية بصيغة PDF...
  </div>
</template>
