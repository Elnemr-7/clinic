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

// Dynamically auto-discover all custom sites created in custom-sites/*/index.vue
const customSiteModules = import.meta.glob('../../custom-sites/*/index.vue', { eager: true });

const route = useRoute();
const { resolveTenant } = useClinicBooking();

const subdomain = computed(() => String(route.params.subdomain || ''));

// SSR Tenant resolution
const { data: doctorTenant, error } = await useAsyncData(`tenant-subdomain-${subdomain.value}`, async () => {
  if (!subdomain.value) return null;
  return await resolveTenant(null, subdomain.value);
});

const seoSettings = computed(() => doctorTenant.value?.website_settings?.seo || {});
const ogSettings = computed(() => doctorTenant.value?.website_settings?.og || {});

// Dynamic SEO & OpenGraph tags
useSeoMeta({
  title: () => seoSettings.value.meta_title || (doctorTenant.value ? `${doctorTenant.value.name} | ${doctorTenant.value.title_ar || 'حجز موعد إلكتروني'}` : 'العيادة غير موجودة | عيادتي'),
  description: () => seoSettings.value.meta_description || doctorTenant.value?.website_settings?.about_text || doctorTenant.value?.bio || 'احجز موعدك وتتبع دورك في صالة الانتظار مباشرة عبر الهاتف.',
  keywords: () => seoSettings.value.meta_keywords || '',
  ogTitle: () => ogSettings.value.og_title || seoSettings.value.meta_title || doctorTenant.value?.name || 'عيادتي الذكية',
  ogDescription: () => ogSettings.value.og_description || seoSettings.value.meta_description || doctorTenant.value?.website_settings?.about_text || 'حجز موعد إلكتروني وتتبع الدور المباشر',
  ogImage: () => ogSettings.value.og_image || '/images/og-clinic-default.jpg',
  twitterCard: () => 'summary_large_image',
  twitterTitle: () => ogSettings.value.og_title || seoSettings.value.meta_title || doctorTenant.value?.name,
  twitterDescription: () => ogSettings.value.og_description || seoSettings.value.meta_description,
  twitterImage: () => ogSettings.value.og_image || '/images/og-clinic-default.jpg',
});

// Check if doctor has a custom dedicated site component in custom-sites/[subdomain]/index.vue
const customSiteComponent = computed(() => {
  if (!doctorTenant.value?.is_custom_standalone_website) return null;
  const slug = doctorTenant.value.subdomain || subdomain.value;
  const path = `../../custom-sites/${slug}/index.vue`;
  return customSiteModules[path]?.default || null;
});

const getTemplateComponent = (specialty) => {
  // If doctor has custom standalone website enabled AND custom component exists in custom-sites/
  if (customSiteComponent.value) {
    return customSiteComponent.value;
  }

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
