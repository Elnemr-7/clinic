<script setup>
import { ref } from 'vue';
import { 
  Sparkles, Award, MapPin, ShieldCheck, CheckCircle2, Clock, Calendar, 
  Phone, Star, Heart, Activity, Users, ArrowDown, ChevronRight, Stethoscope, 
  Shield, HeartPulse, ChevronDown, Check, ExternalLink, MessageSquare
} from 'lucide-vue-next';
import DoctorBookingForm from './DoctorBookingForm.vue';

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
});

const obgynServices = [
  { 
    id: 'pregnancy',
    name: 'متابعة الحمل الحرج والولادة الآمنة بدون ألم', 
    duration: 'متابعة شهرية دورية', 
    badge: 'خطة ولادة مخصصة', 
    desc: 'فحص دوري دقيق لضغط الدم وسكر الحمل وتخطيط نبض الجنين، مع رعاية متخصصة لحالات الإجهاض المتكرر وتسمم الحمل وضمان ولادة طبيعية أو قيصرية تجميلية آمنة.',
    points: ['متابعة ضغط الدم ومؤشرات المشيمة', 'تخطيط قلب الجنين (CTG) المتقدم', 'تقنيات الولادة بدون ألم والتخدير النصفي الآمن']
  },
  { 
    id: 'sonar-4d',
    name: 'السونار التفصيلي رباعي وخماسي الأبعاد (4D/5D HD Live)', 
    duration: 'جلسة تصوير تفصيلية', 
    badge: 'سونار GE Voluson الأمريكي', 
    desc: 'رؤية ملامح الجنين وحركته بوضوح فائق ثلاثي ورباعي الأبعاد، والكشف المبكر عن سلامة الأعضاء والشرايين وتشخيص أي عيوب خلقية في مراحل الحمل المبكرة.',
    points: ['أعلى دقة تصوير تفصيلي للجنين', 'فحص دوبلر لتدفق الدم للمشيمة', 'تسجيل فيديو وصور تذكارية للجنين']
  },
  { 
    id: 'fertility',
    name: 'علاج تأخر الإنجاب وعقم الزوجين والحقن المجهري', 
    duration: 'بروتوكول علاجي متكامل', 
    badge: 'أعلى نسب نجاح الحمل', 
    desc: 'تشخيص أسباب تأخر الحمل، تنشيط التبويض، علاج تكيس المبايض (PCOS)، وإجراء تحضيرات الحقن المجهري وتحديد أسباب فشل الانغراس المتكرر.',
    points: ['تحاليل مخزون المبيض والهرمونات الدقيقة', 'علاج تكيسات المبايض وخلل الإباضة', 'متابعة التبويض بالسونار المهبلي الدقيق']
  },
  { 
    id: 'laparoscopy',
    name: 'جراحة المناظير النسائية التشخيصية والمتقدمة', 
    duration: 'إجراء طفيف التوغل', 
    badge: 'تعافي سريع بدون جراحة مفتوحة', 
    desc: 'استئصال الأورام الليفية وأكياس المبايض وعلاج بطانة الرحم المهاجرة وتسليك التصاقات الأنابيب عبر فتحات متناهية الصغر مع خروج في نفس اليوم.',
    points: ['بدون جروح ظاهرة أو ندبات', 'ألم أقل بكثير وسرعة عودة للحياة الطبيعية', 'تقييم شامل لتجويف الرحم بالمنظار الرحمي']
  },
  { 
    id: 'wellness',
    name: 'فحوصات صحة المرأة الدورية والكشف المبكر', 
    duration: 'فحص وقائي شامل', 
    badge: 'اطمئنان ووقاية مستدامة', 
    desc: 'مسحة عنق الرحم (Pap Smear)، الكشف المبكر عن أورام الثدي والرحم، وعلاج اضطرابات الدورة الشهرية وأعراض سن الأمل وهشاشة العظام.',
    points: ['فحص بالموجات فوق الصوتية للرحم والمبيضين', 'مسحة عنق الرحم للكشف المبكر', 'تنظيم الهرمونات وتخفيف الهبات الساخنة']
  },
  { 
    id: 'aesthetic',
    name: 'تجميل وترميم المنطقة الحساسة بعد الولادات المتكررة', 
    duration: 'جلسات ليزر متطورة', 
    badge: 'خصوصية وراحة تامة', 
    desc: 'علاج اتساع وجفاف المهبل بتقنية الليزر التجديدي وتصحيح هبوط جدار المهبل وسلس البول الإجهادي بدون جراحة مؤلمة.',
    points: ['جلسات ليزر غير جراحية في 20 دقيقة', 'علاج فعال لسلس البول الإجهادي', 'استعادة الثقة والراحة الحميمية']
  },
];

const pillars = [
  { title: 'أعلى درجات الخصوصية النسائية', desc: 'طاقم تمريض نسائي متمرس وغرف كشف وسونار مستقلة توفر لكِ أقصى درجات الراحة النفسية.', tag: '100% Privacy' },
  { title: 'أحدث سونار GE Voluson', desc: 'أدق جهاز موجات صوتية عالمياً لتشخيص سلامة الجنين وتفاصيل نمو أعضائه بدقة متناهية.', tag: 'Advanced 4D/5D' },
  { title: 'دعم كامل للولادة الطبيعية', desc: 'خطة ولادة تحترم رغبتكِ مع تطبيق تقنيات الولادة بدون ألم (Epidural) ورعاية الجنين.', tag: 'Safe Delivery' },
  { title: 'متابعة هاتفية للطوارئ', desc: 'تواصل مباشر مع الفريق الطبي للاستفسارات العاجلة ومتابعة أي أعراض طارئة خلال الحمل.', tag: 'Emergency Support' },
];

const reviews = [
  {
    name: 'أ. دينا الشربيني',
    treatment: 'متابعة حمل وولادة طبيعية بدون ألم',
    comment: 'دكتورة رائعة بكل ما تعنيه الكلمة! تابعت معايا حملي الأول بكل هدوء واهتمام، وولدت طبيعي بدون ما أحس بأي ألم بفضل إبرة الظهر ورعايتها واهتمامها بي وبابني.',
    rating: 5,
    date: 'منذ أسبوعين'
  },
  {
    name: 'م. إيمان سامي',
    treatment: 'سونار 4D وعلاج تكيس المبايض',
    comment: 'السونار كان تجربة خيالية، شوفنا ملامح النونو بوضوح فائق والدكتورة شرحت لنا كل تفصيلة في نموه بالتفصيل وأعطتنا تسجيل كامل، والعيادة قمة في النظافة والخصوصية.',
    rating: 5,
    date: 'منذ شهر'
  },
  {
    name: 'أ. مروة حسني',
    treatment: 'علاج تأخر الإنجاب ومنظار رحمي',
    comment: 'بعد سنتين من المعاناة مع تأخر الحمل، ربنا كرمني بالحمل بعد خطة العلاج والمنظار مع الدكتورة. إنسانة خلوقة وبتشرح كل خطوة وتزرع الأمل والراحة.',
    rating: 5,
    date: 'منذ 3 أسابيع'
  },
];

const faqs = [
  { 
    q: 'ما هو الوقت المثالي لإجراء سونار الـ 4D للجنين؟', 
    a: 'أفضل فترة لرؤية ملامح وجه الجنين بوضوح وتصوير تعابيره هي بين الأسبوع 26 والأسبوع 32 من الحمل، حيث يكون حجم الجنين وكمية السائل الأمنيوسي مثاليين للتصوير.',
    open: true 
  },
  { 
    q: 'هل يمكن الولادة الطبيعية بعد ولادة قيصرية سابقة (VBAC)؟', 
    a: 'نعم، في حال توافر الشروط الطبية المناسبة ومضي فترة كافية بين الحملين وسماكة جدار الرحم الآمنة، يمكن خوض تجربة الولادة الطبيعية بأمان تام تحت إشرافنا.',
    open: false 
  },
  { 
    q: 'ما هي أعراض تكيس المبايض وما هي سبل العلاج الفعالة؟', 
    a: 'أبرز الأعراض عدم انتظام الدورة الشهرية، ظهور شعر زائد، وصعوبة نزول الوزن. نعتمد بروتوكولاً حديثاً يجمع بين ضبط الهرمونات والتغذية وتنشيط التبويض الدقيق.',
    open: false 
  },
  { 
    q: 'كيف يمكنني حجز استشارة ومتابعة حمل بالعيادة؟', 
    a: 'يمكنك اختيار الفرع والتاريخ والوردية المناسبة عبر استمارة الحجز في هذه الصفحة لتأكيد دورك فوراً والحصول على إشعار التأكيد عبر واتساب.',
    open: false 
  },
];

const openFaqIndex = ref(0);

const toggleFaq = (index) => {
  openFaqIndex.value = openFaqIndex.value === index ? null : index;
};

const scrollToBooking = (serviceName = '') => {
  const el = document.getElementById('booking-section');
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' });
  }
};
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 selection:bg-purple-100 selection:text-purple-900" dir="rtl">
    <!-- Top Utility Bar: Working Hours & Contact -->
    <div class="bg-gradient-to-r from-slate-900 via-purple-950 to-slate-900 text-white text-xs py-2 px-4 border-b border-purple-900/50">
      <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="flex items-center gap-4 text-slate-300">
          <div class="flex items-center gap-1.5">
            <Clock class="w-3.5 h-3.5 text-purple-400 shrink-0" />
            <span>مواعيد العيادة: السبت إلى الخميس (فترات صباحية ومسائية)</span>
          </div>
          <span class="hidden md:inline text-slate-600">•</span>
          <div class="hidden md:flex items-center gap-1.5">
            <ShieldCheck class="w-3.5 h-3.5 text-emerald-400 shrink-0" />
            <span>خصوصية تامة 100% وأحدث أجهزة سونار 4D/5D</span>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <a 
            v-if="tenant.whatsapp_phone_number" 
            :href="`https://wa.me/2${tenant.whatsapp_phone_number}?text=${encodeURIComponent('مرحباً دكتورة، أود الاستفسار عن كشف النساء والتوليد ومتابعة الحمل وحجز موعد')}`" 
            target="_blank"
            class="inline-flex items-center gap-1.5 text-emerald-400 hover:text-emerald-300 transition font-medium"
          >
            <MessageSquare class="w-3.5 h-3.5" />
            <span>استشارة واتساب سريعة</span>
          </a>
          <span class="text-slate-700">|</span>
          <a 
            v-if="tenant.clinics?.[0]?.phone" 
            :href="`tel:${tenant.clinics[0].phone}`" 
            class="inline-flex items-center gap-1 text-purple-300 hover:text-purple-200 transition font-mono"
            dir="ltr"
          >
            <Phone class="w-3 h-3" />
            <span>{{ tenant.clinics[0].phone }}</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Main Navigation Header -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-40 px-4 sm:px-6 py-3.5 shadow-xs">
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <!-- Logo & Doctor Name -->
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 p-2 bg-gradient-to-br from-purple-600 to-fuchsia-600 text-white rounded-2xl shadow-md shadow-purple-500/20 flex items-center justify-center shrink-0">
            <Heart class="w-6 h-6 text-white fill-white/20" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-bold text-slate-900 leading-snug">{{ tenant.name }}</h1>
              <span class="hidden sm:inline-block px-2.5 py-0.5 bg-purple-50 text-purple-800 text-[11px] font-bold rounded-full border border-purple-200">
                نساء وتوليد وعقم
              </span>
            </div>
            <p class="text-xs text-purple-700 font-semibold mt-0.5">
              {{ tenant.title_ar || 'استشاري أمراض النساء والتوليد وجراحة المناظير والحقن المجهري' }}
            </p>
          </div>
        </div>

        <!-- Navigation Links -->
        <nav class="hidden lg:flex items-center gap-6 text-xs sm:text-sm font-semibold text-slate-600">
          <a href="#about" class="hover:text-purple-700 transition">عن الطبيبة</a>
          <a href="#services" class="hover:text-purple-700 transition">رعاية الحمل والعلاجات</a>
          <a href="#technology" class="hover:text-purple-700 transition">السونار والخصوصية</a>
          <a href="#testimonials" class="hover:text-purple-700 transition">آراء الأمهات</a>
          <a href="#branches" class="hover:text-purple-700 transition">الفروع والمواعيد</a>
          <a href="#faq" class="hover:text-purple-700 transition">الأسئلة الشائعة</a>
        </nav>

        <!-- CTA Header Button -->
        <a 
          href="#booking-section" 
          class="px-4 sm:px-5 py-2.5 bg-gradient-to-r from-purple-600 to-fuchsia-600 hover:from-purple-700 hover:to-fuchsia-700 text-white rounded-xl text-xs sm:text-sm font-bold transition shadow-md shadow-purple-500/25 flex items-center gap-2 active:scale-95"
        >
          <Calendar class="w-4 h-4" />
          <span>حجز استشارة ومتابعة</span>
        </a>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative py-10 lg:py-16 px-4 sm:px-6 bg-gradient-to-b from-purple-50/60 via-fuchsia-50/20 to-slate-50 overflow-hidden">
      <div class="absolute -top-32 -left-32 w-96 h-96 bg-purple-200/40 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute top-1/2 -right-32 w-96 h-96 bg-pink-200/30 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start relative z-10">
        <!-- Hero Left Column: Authority & Copy -->
        <div class="lg:col-span-7 space-y-6">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-purple-100 text-purple-900 text-xs sm:text-sm font-bold border border-purple-200 shadow-xs">
            <Heart class="w-4 h-4 text-purple-700 fill-current shrink-0" />
            <span>رعاية شاملة وداعمة لرحلة الأمومة وصحة المرأة بأحدث البروتوكولات</span>
          </div>

          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 leading-[1.3] tracking-tight">
            رحلة أمومة آمنة وصحة متكاملة للمرأة بين أيدٍ استشارية خبيرة وخصوصية تامة
          </h2>

          <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-xl font-normal">
            نرافقكِ خطوة بخطوة منذ التخطيط للحمل وفحوصات الخصوبة، مروراً بأدق جلسات سونار الجنين رباعي الأبعاد، وحتى لحظة الولادة الآمنة بدون ألم ورعاية ما بعد الإنجاب.
          </p>

          <!-- Authority Metrics (4 Badges) -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
            <div class="p-3 bg-white rounded-2xl border border-purple-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-purple-700">+15</span>
              <span class="text-xs font-semibold text-slate-600">سنة خبرة استشارية</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-purple-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-purple-700">+10,000</span>
              <span class="text-xs font-semibold text-slate-600">ولادة آمنة ناجحة</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-purple-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-purple-700">99.8%</span>
              <span class="text-xs font-semibold text-slate-600">نسبة رضا الأمهات</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-purple-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-purple-700">100%</span>
              <span class="text-xs font-semibold text-slate-600">خصوصية وأمان</span>
            </div>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex flex-wrap items-center gap-3 pt-2">
            <a 
              href="#booking-section" 
              class="px-6 py-3.5 bg-gradient-to-r from-purple-600 to-fuchsia-600 hover:from-purple-700 hover:to-fuchsia-700 text-white rounded-2xl text-xs sm:text-sm font-bold shadow-lg shadow-purple-500/25 transition active:scale-95 flex items-center gap-2"
            >
              <Calendar class="w-4 h-4" />
              <span>احجزي استشارتكِ الآن أونلاين</span>
            </a>

            <a 
              v-if="tenant.whatsapp_phone_number" 
              :href="`https://wa.me/2${tenant.whatsapp_phone_number}?text=${encodeURIComponent('مرحباً دكتورة، أود الاستفسار عن كشف النساء والتوليد وحجز موعد')}`" 
              target="_blank"
              class="px-6 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-xs sm:text-sm font-bold shadow-lg shadow-emerald-500/25 transition active:scale-95 flex items-center gap-2"
            >
              <MessageSquare class="w-4 h-4" />
              <span>محادثة واتساب مباشرة</span>
            </a>
          </div>

          <!-- Highlight Cards -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4">
            <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
              <div class="p-2.5 bg-purple-50 text-purple-600 rounded-xl shrink-0">
                <Heart class="w-5 h-5 fill-purple-600/20" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">سونار 4D/5D HD Live</p>
                <p class="text-[11px] text-slate-500 font-medium">رؤية تفصيلية لملامح الجنين</p>
              </div>
            </div>

            <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
              <div class="p-2.5 bg-purple-50 text-purple-600 rounded-xl shrink-0">
                <ShieldCheck class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">ولادة طبيعية بدون ألم</p>
                <p class="text-[11px] text-slate-500 font-medium">أمان تام للأم والجنين</p>
              </div>
            </div>

            <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
              <div class="p-2.5 bg-purple-50 text-purple-600 rounded-xl shrink-0">
                <Award class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">خبرة علاج العقم</p>
                <p class="text-[11px] text-slate-500 font-medium">والحقن المجهري والمناظير</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Hero Right Column: Booking Container with safe scroll offset -->
        <div id="booking-section" class="lg:col-span-5 scroll-mt-28">
          <div class="relative">
            <div class="absolute -inset-2 bg-gradient-to-r from-purple-500 to-fuchsia-600 rounded-3xl blur-xl opacity-20"></div>
            <div class="relative">
              <DoctorBookingForm :tenant="tenant" accent-color="purple" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Doctor Bio & Clinical Authority -->
    <section id="about" class="py-16 px-4 sm:px-6 bg-white border-y border-slate-200/70">
      <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
          <div class="lg:col-span-5">
            <div class="relative rounded-3xl bg-gradient-to-tr from-slate-900 via-purple-950 to-slate-900 p-8 text-white shadow-xl overflow-hidden">
              <div class="absolute -bottom-10 -right-10 w-48 h-48 bg-purple-500/20 rounded-full blur-2xl"></div>
              <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20">
                  <Heart class="w-10 h-10 text-purple-300 fill-purple-300/30" />
                </div>
                <div>
                  <h3 class="text-xl font-bold">{{ tenant.name }}</h3>
                  <p class="text-xs text-purple-300 font-medium mt-1">{{ tenant.title_ar || 'استشاري أمراض النساء والتوليد وجراحة المناظير' }}</p>
                </div>
              </div>

              <div class="space-y-3 text-xs leading-relaxed text-slate-200">
                <p>
                  {{ tenant.website_settings?.about_text || 'خبرة سريرية وأكاديمية تمتد لأكثر من 15 عاماً في متابعة حالات الحمل الحرج وعلاج تأخر الإنجاب وجراحات المناظير النسائية المتقدمة، مع التزام تام بأعلى معايير الخصوصية والأمان الطبي.' }}
                </p>
                <div class="pt-3 border-t border-white/15 space-y-2">
                  <div class="flex items-center gap-2 text-purple-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-purple-400" />
                    <span>عضو الكلية الملكية لأطباء النساء والتوليد (RCOG)</span>
                  </div>
                  <div class="flex items-center gap-2 text-purple-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-purple-400" />
                    <span>زمالة جراحة المناظير النسائية المتقدمة واستئصال الأورام</span>
                  </div>
                  <div class="flex items-center gap-2 text-purple-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-purple-400" />
                    <span>برامج متخصصة في الحقن المجهري وعلاج تكيس المبايض</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="lg:col-span-7 space-y-5">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-50 text-purple-800 text-xs font-bold border border-purple-200">
              <Award class="w-4 h-4 text-purple-600" />
              <span>فلسفة الرعاية وصحة المرأة</span>
            </div>

            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
              نصنع تجربة أمومة مريحة ومطمئنة قائمة على الثقة والخصوصية التامة
            </h3>

            <p class="text-sm text-slate-600 leading-relaxed">
              ندرك خصوصية مراجعة عيادة النساء والتوليد؛ لذلك حرصنا على توفير بيئة مريحة وداعمة نفسياً لكل مريضة، مع فحص بالموجات الصوتية الدقيقة لشرح كل التفاصيل بوضوح واختيار أسلم الخيارات العلاجية بأحدث الأساليب العالمية.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                  <ShieldCheck class="w-4 h-4 text-purple-600" />
                  <span>خصوصية وسرية مطلقة</span>
                </h4>
                <p class="text-xs text-slate-500 leading-relaxed">غرف كشف معزولة وطاقم تمريض نسائي متفهم ومدرب لتوفير أعلى درجات الراحة.</p>
              </div>

              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                  <Activity class="w-4 h-4 text-purple-600" />
                  <span>أمان الأم والمولود أولاً</span>
                </h4>
                <p class="text-xs text-slate-500 leading-relaxed">تجهيزات كاملة لمتابعة ضربات قلب الجنين ومراقبة سكر وضغط الحمل لحظة بلحظة.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Services & Treatments Grid -->
    <section id="services" class="py-16 px-4 sm:px-6 bg-slate-50">
      <div class="max-w-7xl mx-auto space-y-10">
        <div class="text-center max-w-2xl mx-auto space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-100 text-purple-900 text-xs font-bold">
            <Heart class="w-4 h-4 text-purple-700 fill-current" />
            <span>خدمات النساء والتوليد ورعاية الحمل</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            حلول طبية متكاملة لصحة المرأة ورعاية الجنين
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            اختاري الخدمة أو الفحص المطلوب وسجلي موعد كشفك لتأكيد حضورك فوراً.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="item in obgynServices" 
            :key="item.id" 
            class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs hover:border-purple-400 hover:shadow-lg hover:shadow-purple-500/10 transition flex flex-col justify-between group"
          >
            <div class="space-y-4">
              <div class="flex items-start justify-between gap-3">
                <span class="px-3 py-1 bg-purple-50 text-purple-800 text-[11px] font-bold rounded-xl border border-purple-100">
                  {{ item.badge }}
                </span>
                <span class="text-xs text-slate-500 font-medium flex items-center gap-1">
                  <Clock class="w-3.5 h-3.5 text-slate-400" />
                  {{ item.duration }}
                </span>
              </div>

              <div>
                <h4 class="text-base font-bold text-slate-900 group-hover:text-purple-700 transition leading-snug">
                  {{ item.name }}
                </h4>
                <p class="text-xs text-slate-600 leading-relaxed mt-2">
                  {{ item.desc }}
                </p>
              </div>

              <div class="space-y-1.5 pt-2 border-t border-slate-100">
                <div v-for="(p, pIdx) in item.points" :key="pIdx" class="flex items-center gap-2 text-xs text-slate-700">
                  <Check class="w-3.5 h-3.5 text-purple-600 shrink-0" />
                  <span>{{ p }}</span>
                </div>
              </div>
            </div>

            <div class="pt-5 mt-4 border-t border-slate-100 flex items-center justify-between">
              <button 
                @click="scrollToBooking(item.name)" 
                class="w-full py-2.5 px-4 bg-slate-100 hover:bg-purple-600 hover:text-white text-slate-800 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 group-hover:bg-purple-600 group-hover:text-white shadow-xs"
              >
                <span>احجزي موعد كشف لهذا الإجراء</span>
                <ChevronDown class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Tech & Safety Standards -->
    <section id="technology" class="py-16 px-4 sm:px-6 bg-gradient-to-b from-white to-purple-50/40 border-y border-slate-200/80">
      <div class="max-w-7xl mx-auto space-y-10">
        <div class="text-center max-w-2xl mx-auto space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-100 text-purple-900 text-xs font-bold">
            <ShieldCheck class="w-4 h-4 text-purple-700" />
            <span>الأجهزة ومعايير الخصوصية</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            أحدث أجهزة السونار الأمريكي لراحة واطمئنان تام
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            تجهيزات متطورة تتيح لكِ رؤية طفلكِ بوضوح ومتابعة كل نبضة بأعلى درجات الدقة الطبية.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div 
            v-for="(tech, tIdx) in pillars" 
            :key="tIdx"
            class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs hover:border-purple-300 hover:shadow-md transition space-y-3"
          >
            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold text-sm">
              0{{ tIdx + 1 }}
            </div>
            <span class="inline-block px-2.5 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-mono font-semibold rounded-md">
              {{ tech.tag }}
            </span>
            <h4 class="text-sm font-bold text-slate-900 leading-snug">
              {{ tech.title }}
            </h4>
            <p class="text-xs text-slate-600 leading-relaxed font-normal">
              {{ tech.desc }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Patient Reviews & Social Proof -->
    <section id="testimonials" class="py-16 px-4 sm:px-6 bg-slate-50">
      <div class="max-w-7xl mx-auto space-y-10">
        <div class="text-center max-w-2xl mx-auto space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-100 text-purple-900 text-xs font-bold">
            <Star class="w-4 h-4 text-purple-700 fill-purple-700" />
            <span>آراء وتجارب الأمهات</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            شهادات حقيقية لأمهات عشن أجمل لحظات حياتهن معنا
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            تجارب واقعية لمريضات تلقين الرعاية والمتابعة داخل عياداتنا.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div 
            v-for="(rev, rIdx) in reviews" 
            :key="rIdx"
            class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs flex flex-col justify-between space-y-4"
          >
            <div class="space-y-3">
              <div class="flex items-center gap-1 text-amber-400">
                <Star v-for="s in rev.rating" :key="s" class="w-4 h-4 fill-amber-400" />
              </div>
              <p class="text-xs sm:text-sm text-slate-700 leading-relaxed font-normal italic">
                "{{ rev.comment }}"
              </p>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
              <div>
                <p class="text-xs font-bold text-slate-900">{{ rev.name }}</p>
                <p class="text-[11px] text-purple-700 font-medium">{{ rev.treatment }}</p>
              </div>
              <span class="text-[10px] text-slate-400 font-medium">{{ rev.date }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Branches, Working Hours & Maps Hub -->
    <section id="branches" class="py-16 px-4 sm:px-6 bg-white border-y border-slate-200/80">
      <div class="max-w-7xl mx-auto space-y-10">
        <div class="text-center max-w-2xl mx-auto space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-100 text-purple-900 text-xs font-bold">
            <MapPin class="w-4 h-4 text-purple-700" />
            <span>فروع العيادة ومواعيد العمل</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            مواقعنا وساعات الاستقبال
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            اختاري أقرب فرع إليكِ للاطلاع على مواعيد الاستقبال أو حجز موعدكِ فوراً.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="clinic in tenant.clinics" 
            :key="clinic.id" 
            class="bg-slate-50 rounded-3xl p-6 border border-slate-200/90 shadow-xs hover:border-purple-400 hover:bg-purple-50/20 transition space-y-4"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="p-2.5 bg-purple-100 text-purple-700 rounded-2xl">
                  <MapPin class="w-5 h-5" />
                </div>
                <div>
                  <h4 class="text-sm font-bold text-slate-900">{{ clinic.name_ar || clinic.name }}</h4>
                  <p class="text-[11px] text-purple-700 font-medium">كشف: {{ clinic.consultation_price }} ج.م</p>
                </div>
              </div>
              <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold rounded-md">
                متاح اليوم
              </span>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed font-normal">
              {{ clinic.address_ar || clinic.address || 'العنوان متاح لدى الاستقبال' }}
            </p>

            <div class="space-y-2 pt-2 border-t border-slate-200/70 text-xs">
              <div class="flex items-center gap-2 text-slate-600">
                <Clock class="w-3.5 h-3.5 text-purple-600 shrink-0" />
                <span>السبت إلى الخميس: فترات صباحية ومسائية</span>
              </div>
              <div v-if="clinic.phone" class="flex items-center gap-2 text-slate-600 font-mono" dir="ltr">
                <Phone class="w-3.5 h-3.5 text-purple-600 shrink-0" />
                <a :href="`tel:${clinic.phone}`" class="hover:text-purple-700 transition">{{ clinic.phone }}</a>
              </div>
            </div>

            <div class="pt-3 flex items-center gap-2">
              <a 
                v-if="clinic.google_maps_url" 
                :href="clinic.google_maps_url" 
                target="_blank"
                class="flex-1 py-2 px-3 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition text-center flex items-center justify-center gap-1.5 shadow-xs"
              >
                <ExternalLink class="w-3.5 h-3.5 text-slate-400" />
                <span>الموقع على الخريطة</span>
              </a>

              <button 
                @click="scrollToBooking()" 
                class="flex-1 py-2 px-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition text-center shadow-xs shadow-purple-500/20"
              >
                حجز موعد
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Accordion -->
    <section id="faq" class="py-16 px-4 sm:px-6 bg-slate-50">
      <div class="max-w-4xl mx-auto space-y-8">
        <div class="text-center space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-100 text-purple-900 text-xs font-bold">
            <Sparkles class="w-4 h-4 text-purple-700" />
            <span>الأسئلة الشائعة وإجابات الطبيبة</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            كل ما يهمك معرفته حول الحمل وصحة المرأة
          </h3>
        </div>

        <div class="space-y-3">
          <div 
            v-for="(faq, fIdx) in faqs" 
            :key="fIdx"
            class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden transition"
          >
            <button 
              @click="toggleFaq(fIdx)" 
              class="w-full p-5 text-right font-bold text-xs sm:text-sm text-slate-900 flex items-center justify-between gap-4 hover:bg-slate-50/70 transition cursor-pointer"
            >
              <span>{{ faq.q }}</span>
              <ChevronDown 
                :class="['w-4 h-4 text-purple-700 shrink-0 transition-transform duration-200', openFaqIndex === fIdx ? 'rotate-180' : '']" 
              />
            </button>
            <div 
              v-show="openFaqIndex === fIdx" 
              class="px-5 pb-5 pt-1 text-xs text-slate-600 leading-relaxed border-t border-slate-100 font-normal"
            >
              {{ faq.a }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-white py-12 px-4 sm:px-6 border-t border-slate-800">
      <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
        <div class="md:col-span-5 space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 p-2 bg-purple-600 text-white rounded-xl flex items-center justify-center">
              <Heart class="w-6 h-6 text-white" />
            </div>
            <div>
              <h4 class="text-base font-bold text-white leading-snug">{{ tenant.name }}</h4>
              <p class="text-xs text-purple-400 font-medium">{{ tenant.title_ar || 'استشاري أمراض النساء والتوليد وجراحة المناظير' }}</p>
            </div>
          </div>
          <p class="text-xs text-slate-400 leading-relaxed max-w-sm font-normal">
            عيادة نسائية متخصصة ومجهزة بأحدث أجهزة السونار رباعي الأبعاد ومناظير تجويف الرحم لتقديم أرقى خدمات رعاية الأمومة وعلاج العقم بأعلى درجات الخصوصية.
          </p>
        </div>

        <div class="md:col-span-3 space-y-3 text-xs text-slate-400">
          <h5 class="font-bold text-white text-sm">روابط سريعة</h5>
          <ul class="space-y-2">
            <li><a href="#about" class="hover:text-purple-300 transition">عن الطبيبة والخبرات</a></li>
            <li><a href="#services" class="hover:text-purple-300 transition">رعاية الحمل والسونار</a></li>
            <li><a href="#technology" class="hover:text-purple-300 transition">الأجهزة والخصوصية التامة</a></li>
            <li><a href="#branches" class="hover:text-purple-300 transition">الفروع ومواعيد الاستقبال</a></li>
            <li><a href="#booking-section" class="hover:text-purple-300 transition">حجز كشف أونلاين</a></li>
          </ul>
        </div>

        <div class="md:col-span-4 space-y-3 text-xs text-slate-400">
          <h5 class="font-bold text-white text-sm">التواصل والاستقبال</h5>
          <p class="text-slate-300">مواعيد العيادة: السبت إلى الخميس (صباحاً ومساءً)</p>
          <div v-if="tenant.clinics?.[0]?.phone" class="font-mono text-purple-300" dir="ltr">
            هاتف الاستقبال: {{ tenant.clinics[0].phone }}
          </div>
          <div v-if="tenant.whatsapp_phone_number" class="pt-2">
            <a 
              :href="`https://wa.me/2${tenant.whatsapp_phone_number}`" 
              target="_blank"
              class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition"
            >
              <MessageSquare class="w-3.5 h-3.5" />
              <span>محادثة واتساب سريعة</span>
            </a>
          </div>
        </div>
      </div>

      <div class="max-w-7xl mx-auto pt-8 mt-8 border-t border-slate-800 text-center text-xs text-slate-500">
        جميع الحقوق محفوظة © {{ new Date().getFullYear() }} {{ tenant.name }} • منصة العيادات الذكية
      </div>
    </footer>
  </div>
</template>
