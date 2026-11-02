<script setup>
import {
  MessageSquare,
  Tv,
  Stethoscope,
  Activity,
  Layers,
  CalendarCheck,
  ShieldCheck,
  Zap,
} from 'lucide-vue-next';

const features = [
  {
    icon: MessageSquare,
    title: 'نظام واتساب فوري وآمن (Zero-Ban)',
    desc: 'إرسال الروشتة PDF وتنبيه اقتراب الدور برابط تتبع حي، مع محاكاة الكتابة البشرية لمنع الحظر وتقنية التنشيط الذاتي بالرمز #REF.',
    color: 'from-emerald-500 to-teal-600',
  },
  {
    icon: Tv,
    title: 'شاشة صالة الانتظار الذكية (TV Display)',
    desc: 'عرض رقم الدور الحالي والمريض التالي مع نغمة تنبيه صوتية طبية تفاعلية، ودعم كامل للشاشات الخارجية عبر منفذ HDMI.',
    color: 'from-purple-500 to-indigo-600',
  },
  {
    icon: Stethoscope,
    title: 'شاشة كشف واحدة وسريعة للطبيب',
    desc: 'سجل طبي فوري، روشتة ذكية، طلب فحوصات، وتحصيل خدمات إضافية بنوافذ مستقلة تحفظ بياناتها لحظياً دون مقاطعة الكشف.',
    color: 'from-blue-500 to-cyan-600',
  },
  {
    icon: CalendarCheck,
    title: 'كشف الاستشارة والإعادة التلقائي',
    desc: 'النظام يفحص رقم هاتف المريض تلقائياً عند الحجز لمعرفة أحقية الاستشارة المجانية أو المخفضة بناءً على سياسة كل فرع.',
    color: 'from-amber-500 to-orange-600',
  },
  {
    icon: Layers,
    title: 'تعدد الفروع والورديات وصلاحيات الموظفين',
    desc: 'إدارة عدة فروع وعيادات تحت حساب طبيب واحد، مع تنظيم ورديات الصباح والمساء والتحكم بدخول موظفي الاستقبال.',
    color: 'from-rose-500 to-pink-600',
  },
  {
    icon: Zap,
    title: 'معالجة العطلات والاعتذارات الطارئة',
    desc: 'إغلاق الحجوزات بضغطة زر وترحيل مواعيد المرضى تلقائياً إلى أقرب موعد متاح مع إرسال إشعارات جماعية فورية للمرضى.',
    color: 'from-teal-500 to-emerald-600',
  },
];
</script>

<template>
  <section class="py-16 bg-white border-y border-slate-200/80 text-right" dir="rtl">
    <div class="max-w-7xl mx-auto px-6 space-y-12">
      <div class="text-center max-w-2xl mx-auto space-y-3">
        <h2 class="text-3xl font-black text-slate-900 tracking-tight">مصمم خصيصاً ليناسب الواقع اليومي للعيادة العربية</h2>
        <p class="text-xs text-slate-500 leading-relaxed">
          جميع الأدوات والخصائص تم بناؤها بالتعاون مع أطباء ومسؤولي عيادات لإلغاء التعقيد وتوفير أقصى سرعة ممكنة.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="(f, i) in features"
          :key="i"
          class="p-6 bg-slate-50/70 hover:bg-white rounded-3xl border border-slate-200/70 hover:border-teal-500/40 hover:shadow-xl transition-all duration-300 space-y-4 group"
        >
          <div :class="['w-12 h-12 rounded-2xl bg-gradient-to-br text-white flex items-center justify-center shadow-lg transition group-hover:scale-105', f.color]">
            <component :is="f.icon" class="w-6 h-6" />
          </div>

          <div class="space-y-1.5">
            <h3 class="text-base font-bold text-slate-900 group-hover:text-teal-700 transition">{{ f.title }}</h3>
            <p class="text-xs text-slate-500 leading-relaxed">{{ f.desc }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
