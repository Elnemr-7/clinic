<script setup>
import { ref, onMounted } from 'vue';
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

const subdomain = route.params.subdomain;
const doctorTenant = ref(null);
const loading = ref(true);
const error = ref(null);

onMounted(async () => {
  try {
    doctorTenant.value = await resolveTenant(null, subdomain);
  } catch (e) {
    error.value = 'لم يتم العثور على عيادة بهذا الرابط';
  } finally {
    loading.value = false;
  }
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
    <div v-if="loading" class="min-h-screen flex items-center justify-center bg-slate-50 text-slate-400 text-xs font-bold" dir="rtl">
      جارٍ تحميل بيانات العيادة...
    </div>

    <div v-else-if="error" class="min-h-screen flex flex-col items-center justify-center bg-slate-50 text-center p-6" dir="rtl">
      <h2 class="text-xl font-bold text-slate-800 mb-2">{{ error }}</h2>
      <p class="text-xs text-slate-400 mb-4">يرجى التأكد من صحة الرابط</p>
      <nuxt-link to="/" class="px-5 py-2.5 bg-teal-600 text-white rounded-xl text-xs font-bold">
        العودة للصفحة الرئيسية
      </nuxt-link>
    </div>

    <div v-else-if="doctorTenant">
      <component
        :is="getTemplateComponent(doctorTenant.specialty_key || doctorTenant.template_key || doctorTenant.title_ar || doctorTenant.medical_specialty)"
        :tenant="doctorTenant"
      />
    </div>
  </div>
</template>
