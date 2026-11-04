<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 font-sans select-none" dir="rtl">
    <!-- Custom Doctor Banner -->
    <header class="border-b border-amber-500/20 bg-slate-900/80 backdrop-blur-md sticky top-0 z-30 py-4 px-6 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-amber-500 to-amber-300 text-slate-950 flex items-center justify-center font-bold text-lg shadow-md shadow-amber-500/20">
          ⭐
        </div>
        <div>
          <h1 class="text-base font-extrabold text-amber-400 leading-tight">{{ tenant?.name }}</h1>
          <p class="text-[11px] text-slate-400 font-medium">موقع مخصص مستقل • عيادة استشارية خاصة (VIP Dedicated)</p>
        </div>
      </div>

      <a
        href="#booking-section"
        class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition shadow-lg shadow-amber-500/20 cursor-pointer"
      >
        حجز موعد فوري
      </a>
    </header>

    <!-- Custom Hero -->
    <section class="max-w-6xl mx-auto px-6 py-16 text-center space-y-6">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold">
        <span>✨</span>
        <span>موقع خاص تم تصميمه وبرمجته خصيصاً لعيادة {{ tenant?.name }}</span>
      </div>

      <h2 class="text-3xl md:text-5xl font-black text-white leading-tight">
        رعاية طبية تخصصية راقية<br />
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-200">بمعايير عالمية وتقنيات متقدمة</span>
      </h2>

      <p class="text-sm md:text-base text-slate-400 max-w-2xl mx-auto leading-relaxed">
        {{ tenant?.website_settings?.about_text || 'نرحب بكم في عيادتنا الخاصة، حيث نجمع بين الخبرة الأكاديمية والسريرية وأحدث التجهيزات الرقمية لتوفير رعاية دقيقة ومخصصة لكل مريض.' }}
      </p>
    </section>

    <!-- Integrated Booking Form Section -->
    <section id="booking-section" class="max-w-4xl mx-auto px-4 pb-20">
      <div class="bg-slate-900 border border-amber-500/20 rounded-3xl p-6 md:p-8 shadow-2xl">
        <div class="text-center mb-6">
          <h3 class="text-xl font-bold text-white flex items-center justify-center gap-2">
            <span>📅</span>
            <span>حجز موعد كشف مباشر</span>
          </h3>
          <p class="text-xs text-slate-400 mt-1">اختر الفرع والموعد وسيتولى النظام حجز دورك وإشعارك لحظياً</p>
        </div>

        <!-- Integrated Platform Booking Form -->
        <DoctorBookingForm :tenant="tenant" />
      </div>
    </section>
  </div>
</template>

<script setup>
import DoctorBookingForm from '../../components/templates/DoctorBookingForm.vue';

defineProps({
  tenant: {
    type: Object,
    required: true,
  },
});
</script>
