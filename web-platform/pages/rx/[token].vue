<script setup>
const route = useRoute();
const token = route.params.token;
const config = useRuntimeConfig();

const apiBase = config.public.apiBase || 'http://localhost:8000/api';
await navigateTo(`${apiBase}/rx/${token}`, { external: true, redirectCode: 302 });
</script>

<template>
  <div class="min-h-screen bg-surface-card flex items-center justify-center text-xs font-bold text-content-muted" dir="rtl">
    جارٍ فتح الروشتة الطبية بصيغة PDF...
  </div>
</template>
