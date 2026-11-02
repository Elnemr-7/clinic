<script setup>
import { Stethoscope, Sparkles, ShieldCheck, Zap, ArrowLeft, CheckCircle2 } from 'lucide-vue-next';
</script>

<template>
  <section class="relative pt-12 pb-20 overflow-hidden text-right" dir="rtl">
    <!-- Ambient Backdrop Glow -->
    <div class="absolute top-0 right-1/4 w-96 h-96 bg-teal-400/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute top-20 left-1/4 w-96 h-96 bg-blue-400/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-6 relative z-10">
      <div class="text-center max-w-3xl mx-auto space-y-6">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-teal-50 border border-teal-200 text-teal-800 text-xs font-bold shadow-sm">
          <Sparkles class="w-4 h-4 text-teal-600" />
          <span>الجيل الأحدث لمنظومة إدارة العيادات والحجز الذكي في الشرق الأوسط</span>
        </div>

        <!-- Main Title -->
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 tracking-tight leading-[1.2]">
          المنصة الأكثر تكاملاً لـ
          <span class="bg-gradient-to-l from-teal-600 to-emerald-600 bg-clip-text text-transparent">
            إدارة عيادتك الطبية
          </span>
          وحجز المرضى
        </h1>

        <!-- Subtitle -->
        <p class="text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto">
          تطبيق كمبيوتر متطور وسحابي، موقع حجز خاص باسمك، إدارة طابور العيادة في الوقت الحقيقي عبر شاشات صالة الانتظار، وإرسال الروشتات وتنبيهات الأدوار عبر الواتساب بدون حظر.
        </p>

        <!-- Bullet Highlights -->
        <div class="flex flex-wrap items-center justify-center gap-6 text-xs font-bold text-slate-700 pt-2">
          <div class="flex items-center gap-2">
            <CheckCircle2 class="w-4 h-4 text-teal-600" />
            <span>واتساب رسمي وتنبيه #REF فوري</span>
          </div>
          <div class="flex items-center gap-2">
            <CheckCircle2 class="w-4 h-4 text-teal-600" />
            <span>شاشة انتظار TV ذكية عبر HDMI</span>
          </div>
          <div class="flex items-center gap-2">
            <CheckCircle2 class="w-4 h-4 text-teal-600" />
            <span>حساب تلقائي لمدد الاستشارة والإعادة</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
