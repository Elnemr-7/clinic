<script setup>
import { computed } from 'vue';
import { useClinicBooking } from '../composables/useClinicBooking';
import PlatformHero from '../components/platform/PlatformHero.vue';
import PlatformFeatures from '../components/platform/PlatformFeatures.vue';
import PlatformPricing from '../components/platform/PlatformPricing.vue';
import LeadForm from '../components/platform/LeadForm.vue';

import TemplateGeneral from '../components/templates/TemplateGeneral.vue';
import TemplateDental from '../components/templates/TemplateDental.vue';
import TemplatePediatrics from '../components/templates/TemplatePediatrics.vue';
import TemplateDermatology from '../components/templates/TemplateDermatology.vue';
import TemplateObgyn from '../components/templates/TemplateObgyn.vue';
import TemplateOrthopedics from '../components/templates/TemplateOrthopedics.vue';

const { resolveTenant } = useClinicBooking();

// SSR Host Resolution
const headers = useRequestHeaders(['host']);
const currentHost = computed(() => {
  if (import.meta.server) {
    return (headers.host || '').split(':')[0].toLowerCase();
  }
  return typeof window !== 'undefined' ? window.location.host.split(':')[0].toLowerCase() : '';
});

const isPlatformDomain = computed(() => {
  const h = currentHost.value;
  return !h || h === 'localhost' || h === '127.0.0.1' || h.startsWith('192.168.') || h.includes('platform.') || h === 'clinic-platform.com';
});

const { data: doctorTenant } = await useAsyncData('tenant-by-host', async () => {
  if (isPlatformDomain.value) {
    return null;
  }
  try {
    return await resolveTenant(currentHost.value);
  } catch (e) {
    return null;
  }
}, {
  watch: [currentHost]
});

// Dynamic SEO & OpenGraph tags
useSeoMeta({
  title: () => doctorTenant.value ? `${doctorTenant.value.clinic_name || doctorTenant.value.name || 'عيادة تخصصية'} | حجز موعد إلكتروني` : 'منصة العيادات الذكية | حجز المواعيد وإدارة العيادات',
  description: () => doctorTenant.value?.bio || doctorTenant.value?.specialty_title || 'احجز موعدك الآن بعيادة الطبيب وتتبع رقمك ودورك بالعيادة مباشرة.',
  ogTitle: () => doctorTenant.value ? `${doctorTenant.value.clinic_name || doctorTenant.value.name}` : 'منصة العيادات الذكية',
  ogDescription: () => doctorTenant.value?.bio || 'نظام إدارة العيادات والحجز المسبق الأكثر سرعة وأماناً',
});

const getTemplateComponent = (specialty) => {
  const sp = (specialty || '').toLowerCase();
  if (sp.includes('أسنان') || sp.includes('dental')) return TemplateDental;
  if (sp.includes('أطفال') || sp.includes('pediatric')) return TemplatePediatrics;
  if (sp.includes('جلدية') || sp.includes('تجميل') || sp.includes('derma')) return TemplateDermatology;
  if (sp.includes('نساء') || sp.includes('توليد') || sp.includes('عقم') || sp.includes('obgyn')) return TemplateObgyn;
  if (sp.includes('عظام') || sp.includes('ortho')) return TemplateOrthopedics;
  return TemplateGeneral;
};
</script>

<template>
  <div>
    <!-- Doctor Custom / Subdomain Website Mode -->
    <div v-if="doctorTenant">
      <component
        :is="getTemplateComponent(doctorTenant.medical_specialty)"
        :tenant="doctorTenant"
      />
    </div>

    <!-- Platform SaaS Marketing Landing Page Mode -->
    <div v-else class="min-h-screen bg-slate-50 font-sans text-right select-none" dir="rtl">
      <!-- Platform Header -->
      <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-40 px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-emerald-600 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-md shadow-teal-500/20">
              ع
            </div>
            <div>
              <span class="text-lg font-black text-slate-900 tracking-tight">عيادتي الذكية</span>
              <span class="text-[11px] text-teal-600 font-bold block -mt-1">منظومة SaaS الطبية</span>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <a
              href="http://localhost:5174"
              target="_blank"
              class="px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 rounded-xl transition"
            >
              دخول الأطباء والاستقبال
            </a>

            <a
              href="#lead-section"
              class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition shadow-sm"
            >
              طلب تجربة مجانية
            </a>
          </div>
        </div>
      </header>

      <!-- Platform Hero -->
      <PlatformHero />

      <!-- Lead Capture Form Section -->
      <section id="lead-section" class="py-12 px-6 max-w-4xl mx-auto">
        <LeadForm />
      </section>

      <!-- Features Section -->
      <PlatformFeatures />

      <!-- Pricing Plans -->
      <PlatformPricing />

      <!-- Footer -->
      <footer class="bg-slate-900 text-slate-400 py-12 px-6 border-t border-slate-800 text-center text-xs space-y-3">
        <p class="font-bold text-white">منصة العيادة الذكية © 2026 - جميع الحقوق محفوظة</p>
        <p>نظام إدارة العيادات والحجز المسبق الأكثر سرعة وأماناً للأطباء والمراكز الطبية</p>
      </footer>
    </div>
  </div>
</template>
