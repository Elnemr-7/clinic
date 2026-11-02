<script setup>
import { ref, onMounted } from 'vue';
import { useClinicBooking } from '../../composables/useClinicBooking';
import { Check, Sparkles, Building2, ShieldCheck } from 'lucide-vue-next';

const { fetchPlans } = useClinicBooking();
const plans = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    plans.value = await fetchPlans();
  } catch (e) {
    // Fallback plans
    plans.value = [
      { id: 1, name: 'الباقة الفردية (Basic)', price_monthly: 499, max_clinics: 1, max_receptionists: 1, description: 'مناسبة للعيادات الفردية المستقلة' },
      { id: 2, name: 'باقة العيادة المتطورة (Pro)', price_monthly: 999, max_clinics: 2, max_receptionists: 3, is_featured: true, description: 'الأكثر طلباً للعيادات المتنامية' },
      { id: 3, name: 'باقة الفروع والمراكز (VIP)', price_monthly: 1799, max_clinics: 5, max_receptionists: 10, description: 'للمراكز الطبية وسلاسل العيادات المتعددة' },
    ];
  } finally {
    loading.value = false;
  }
});

const scrollToLeadForm = () => {
  const el = document.getElementById('lead-section');
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' });
  }
};
</script>

<template>
  <section class="py-16 text-right" dir="rtl">
    <div class="max-w-7xl mx-auto px-6 space-y-12">
      <div class="text-center max-w-2xl mx-auto space-y-3">
        <span class="text-xs font-bold text-teal-600 uppercase tracking-wider">خطط وباقات مرنة</span>
        <h2 class="text-3xl font-black text-slate-900 tracking-tight">استثمار بسيط يُحدث نقلة نوعية في كفاءة عيادتك</h2>
        <p class="text-xs text-slate-500">اختر الباقة المناسبة لحجم أعمالك، مع تجربة مجانية كاملة لمدة 14 يوماً</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
        <div
          v-for="plan in plans"
          :key="plan.id"
          :class="[
            'rounded-3xl p-8 border flex flex-col justify-between transition-all duration-300 relative',
            plan.is_featured
              ? 'bg-slate-900 text-white border-teal-500 shadow-2xl shadow-teal-950/20 md:-translate-y-2'
              : 'bg-white text-slate-800 border-slate-200 shadow-sm hover:shadow-lg'
          ]"
        >
          <div v-if="plan.is_featured" class="absolute -top-3.5 right-1/2 translate-x-1/2 px-4 py-1 rounded-full bg-gradient-to-r from-teal-500 to-emerald-500 text-slate-950 font-black text-[11px] shadow-md flex items-center gap-1">
            <Sparkles class="w-3.5 h-3.5" />
            <span>الباقة الأكثر اختياراً</span>
          </div>

          <div class="space-y-6">
            <div>
              <h3 class="text-lg font-black" :class="plan.is_featured ? 'text-white' : 'text-slate-900'">{{ plan.name }}</h3>
              <p class="text-xs mt-1" :class="plan.is_featured ? 'text-slate-400' : 'text-slate-500'">{{ plan.description }}</p>
            </div>

            <!-- Price -->
            <div class="flex items-baseline gap-1">
              <span class="text-4xl font-black font-mono" :class="plan.is_featured ? 'text-teal-400' : 'text-slate-900'">
                {{ plan.price_monthly }}
              </span>
              <span class="text-xs font-medium" :class="plan.is_featured ? 'text-slate-400' : 'text-slate-500'">ج.م / شهرياً</span>
            </div>

            <hr :class="plan.is_featured ? 'border-slate-800' : 'border-slate-100'" />

            <!-- Features -->
            <div class="space-y-3 text-xs">
              <div class="flex items-center gap-2.5">
                <Check class="w-4 h-4 text-teal-500 shrink-0" />
                <span v-if="plan.max_clinics === -1 || plan.max_clinics > 50">إدارة <strong>فروع وعيادات غير محدودة</strong></span>
                <span v-else>إدارة حتى <strong>{{ plan.max_clinics }}</strong> فروع وعيادات</span>
              </div>
              <div class="flex items-center gap-2.5">
                <Check class="w-4 h-4 text-teal-500 shrink-0" />
                <span v-if="!plan.max_receptionists || plan.max_receptionists === -1">حسابات <strong>غير محدودة</strong> لموظفي الاستقبال</span>
                <span v-else>حتى <strong>{{ plan.max_receptionists }}</strong> حسابات لموظفي الاستقبال</span>
              </div>
              <div class="flex items-center gap-2.5">
                <Check class="w-4 h-4 text-teal-500 shrink-0" />
                <span>تطبيق كمبيوتر سطح المكتب وتطبيق المتصفح</span>
              </div>
              <div class="flex items-center gap-2.5">
                <Check class="w-4 h-4 text-teal-500 shrink-0" />
                <span>شاشة تلفاز صالة الانتظار الذكية مع نغمة التنبيه</span>
              </div>
              <div class="flex items-center gap-2.5">
                <Check class="w-4 h-4 text-teal-500 shrink-0" />
                <span>ربط رقم الواتساب بالعيادة وإرسال الروشتات PDF</span>
              </div>
            </div>
          </div>

          <div class="pt-8">
            <button
              @click="scrollToLeadForm"
              type="button"
              :class="[
                'w-full py-3 rounded-2xl text-xs font-bold transition',
                plan.is_featured
                  ? 'bg-gradient-to-r from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 text-slate-950 font-black shadow-lg shadow-teal-500/20'
                  : 'bg-slate-100 hover:bg-slate-200 text-slate-800'
              ]"
            >
              طلب تجربة مجانية لهذه الباقة
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
