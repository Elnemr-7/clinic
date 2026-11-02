<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useClinicBooking } from '../../composables/useClinicBooking';

import TemplateGeneral from '../../components/templates/TemplateGeneral.vue';
import TemplateDental from '../../components/templates/TemplateDental.vue';
import TemplatePediatrics from '../../components/templates/TemplatePediatrics.vue';
import TemplateDermatology from '../../components/templates/TemplateDermatology.vue';
import TemplateObgyn from '../../components/templates/TemplateObgyn.vue';
import TemplateOrthopedics from '../../components/templates/TemplateOrthopedics.vue';

const route = useRoute();
const { resolveTenant } = useClinicBooking();

const subdomain = computed(() => String(route.params.subdomain || ''));

// SSR Tenant resolution
const { data: doctorTenant, error } = await useAsyncData(`tenant-subdomain-${subdomain.value}`, async () => {
  if (!subdomain.value) return null;
  return await resolveTenant(null, subdomain.value);
});

// Dynamic SEO & OpenGraph tags
useSeoMeta({
  title: () => doctorTenant.value ? `${doctorTenant.value.clinic_name || doctorTenant.value.name || 'عيادة تخصصية'} | حجز موعد إلكتروني` : 'العيادة غير موجودة | عيادتي',
  description: () => doctorTenant.value?.bio || doctorTenant.value?.specialty_title || 'احجز موعدك وتتبع دورك في صالة الانتظار مباشرة عبر الهاتف.',
  ogTitle: () => doctorTenant.value ? `${doctorTenant.value.clinic_name || doctorTenant.value.name}` : 'عيادتي الذكية',
  ogDescription: () => doctorTenant.value?.bio || 'حجز موعد إلكتروني وتتبع الدور المباشر',
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
  <div class="min-h-screen bg-surface-app text-content-primary">
    <!-- Clean Arabic 404 Fallback -->
    <div v-if="error || !doctorTenant" class="min-h-screen flex flex-col items-center justify-center p-6 text-center" dir="rtl">
      <div class="w-16 h-16 bg-status-danger-bg text-status-danger border border-status-danger-border rounded-2xl flex items-center justify-center text-2xl font-bold mb-4 shadow-sm">
        ⚠️
      </div>
      <h2 class="text-2xl font-bold text-content-primary mb-2">لم يتم العثور على العيادة المطلوبة</h2>
      <p class="text-sm text-content-muted mb-6 max-w-md">الرابط الذي قمت بفتحه غير صحيح أو قد تم نقل العيادة إلى نطاق آخر. يرجى التأكد من كتابة الرابط بشكل سليم.</p>
      <nuxt-link to="/" class="px-6 py-3 bg-brand text-content-inverse hover:bg-brand-hover rounded-xl text-xs font-bold transition shadow-sm">
        العودة لبوابة المنصة الرئيسية
      </nuxt-link>
    </div>

    <!-- Active Doctor Specialty Template -->
    <div v-else>
      <component
        :is="getTemplateComponent(doctorTenant.specialty_key || doctorTenant.template_key || doctorTenant.title_ar || doctorTenant.medical_specialty)"
        :tenant="doctorTenant"
      />
    </div>
  </div>
</template>
