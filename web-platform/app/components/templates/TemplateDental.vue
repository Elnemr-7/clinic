<script setup>
import { ref } from 'vue';
import { 
  Sparkles, Award, MapPin, ShieldCheck, CheckCircle2, Clock, Calendar, 
  Phone, Star, HeartHandshake, Zap, Activity, Users, ArrowDown, ChevronRight, 
  ChevronDown, Check, ExternalLink, MessageSquare, Shield, Smile, Eye
} from 'lucide-vue-next';
import IconTooth from '../icons/IconTooth.vue';
import DoctorBookingForm from './DoctorBookingForm.vue';

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
});

// Treatment list with clinical details
const dentalTreatments = [
  { 
    id: 'hollywood',
    name: 'تصميم ابتسامة هوليوود (Digital Smile Makeover)', 
    duration: 'جلستان فقط', 
    badge: 'عدسات E-max أصلية',
    desc: 'تصميم ومحاكاة الابتسامة رقمياً بتقنية 3D قبل البدء، مع قشور خزفية متناهية الدقة تحافظ على مينا الأسنان وتدوم لأكثر من 15 سنة.',
    points: ['محاكاة ثلاثية الأبعاد قبل البدء', 'خامات E-max الألمانية الأصلية', 'ضمان معتمد 10 سنوات على اللون والبريق']
  },
  { 
    id: 'implants',
    name: 'زراعة الأسنان الفورية بدون جراحة مؤلمة', 
    duration: 'جلسة واحدة للغرس', 
    badge: 'غرسات سويسرية وألمانية',
    desc: 'تعويض الأسنان المفقودة باستخدام غرسات تيتانيوم حيوية متقدمة ونظام الملاحة الحاسوبي الدقيق دون فتح جراحي كبير وبنسبة نجاح 99%.',
    points: ['غرسات Straumann السويسرية المعتمدة', 'التئام سريع وتوافق عظمي فائق', 'إمكانية التركيب الفوري المؤقت بنفس اليوم']
  },
  { 
    id: 'root-canal',
    name: 'علاج جذور وأعصاب الأسنان بالمايكروسكوب', 
    duration: 'جلسة واحدة (45 دقيقة)', 
    badge: 'تنظيف ميكروسكوبي دقيق',
    desc: 'علاج ألم العصب المستعصي بدقة متناهية تحت المجهر الجراحي مع تعقيم القنوات الدقيقة بالليزر للحفاظ التام على السن الطبيعي.',
    points: ['جلسة واحدة بدون ألم', 'حشو قنوات العصب ثلاثي الأبعاد', 'حماية الضرس من الكسر بطربوش الزيركون']
  },
  { 
    id: 'aligners',
    name: 'تقويم الأسنان الشفاف (Clear Aligners)', 
    duration: 'خطة علاجية 6 - 12 شهراً', 
    badge: 'غير مرئي وقابل للإزالة',
    desc: 'تعديل وتنسيق اصطفاف الأسنان بدون أسلاك معدنية محرجة، بواسطة قوالب شفافة ومريحة يمكنك خلعها بسهولة أثناء تناول الطعام.',
    points: ['مظهر جمالي غير ملحوظ إطلاقاً', 'سهولة تنظيف الأسنان والأكل', 'متابعة رقمية لمراحل حركة الأسنان']
  },
  { 
    id: 'whitening',
    name: 'تبييض الأسنان بالليزر ونظام Zoom 4', 
    duration: 'جلسة واحدة (40 دقيقة)', 
    badge: 'تفتيح حتى 8 درجات',
    desc: 'إزالة التصبغات والاصفرار الناتج عن التدخين والقهوة بأمان تام، مع حماية خاصة للثة ومينا الأسنان لابتسامة ناصعة البياض.',
    points: ['نتيجة فورية بنفس الجلسة', 'حماية طبقة المينا دون أي تحسس', 'جلسة تلميع وتطبيق الفلورايد مجاناً']
  },
  { 
    id: 'pediatric',
    name: 'طب أسنان الأطفال الخالي من الخوف والألم', 
    duration: 'جلسة ممتعة ومريحة', 
    badge: 'بيئة صديقة للأطفال',
    desc: 'عناية وقائية وعلاجية للأطفال في جو مرح يزيل رهبة طبيب الأسنان، مع جلسات تطبيق الفلورايد وحفظ المسافات للأسنان الدائمة.',
    points: ['بنج موضعي بطعم الفواكه بدون وخز', 'جلسات توعية وتدريب على غسيل الأسنان', 'سد الشقوق العميقة للوقاية من التسوس']
  },
];

// Clinic Technology & European Sterilization
const techFeatures = [
  {
    title: 'الماسح الفموي الرقمي ثلاثي الأبعاد (3D Scanner)',
    desc: 'وداعاً لعجينة ومقاسات الفم التقليدية المزعجة؛ نقوم بأخذ مقاس رقمي فائق الدقة في ثوانٍ معدودة لعرض ابتسامتك المستقبلية فوراً.',
    tag: '3D Intraoral Scan'
  },
  {
    title: 'التعقيم الأوروبي الصارم (Class B Autoclaves)',
    desc: 'نطبق أعلى بروتوكولات مكافحة العدوى والتعقيم الحراري المزدوج للأدوات وتغليفها الفردي ليتم فتحها أمام المريض مباشرة.',
    tag: 'Sterilization Protocol'
  },
  {
    title: 'التخدير الحاسوبي الذكي بدون وخز مؤلم',
    desc: 'حقن البنج الموضعي بالتحكم الرقمي فائق الهدوء، ليتم امتصاصه دون الشعور بأي وخزة إبرة أو انزعاج طوال الجلسة.',
    tag: 'Pain-Free Anesthesia'
  },
  {
    title: 'الأشعة المقطعية الرقمية البانورامية (CBCT)',
    desc: 'فحص مقطعي ثلاثي الأبعاد لعظام الفك والأعصاب لتخطيط زراعة الأسنان وجراحات الفم بأعلى درجات الأمان ودقة المليمتر.',
    tag: 'Digital Imaging'
  },
];

// Patient Testimonials
const reviews = [
  {
    name: 'م. أحمد الشريف',
    treatment: 'زراعة أسنان وتصميم ابتسامة',
    comment: 'تجربة ممتازة وفاقت توقعاتي! كنت خائفاً جداً من زراعة الأسنان لكن الإجراء تم بدون أي ألم إطلاقاً. الفريق محترف جداً والعيادة على أعلى مستوى من النظافة والتعقيم.',
    rating: 5,
    date: 'منذ أسبوعين'
  },
  {
    name: 'د. ميادة إبراهيم',
    treatment: 'عدسات فينير E-max',
    comment: 'دقة لا توصف واهتمام بأدق التفاصيل الجمالية. الابتسامة طلعت طبيعية جداً ومرتاحة في الأكل والكلام من أول يوم. شكراً جزيلاً للدكتور وفريق العمل الرائع.',
    rating: 5,
    date: 'منذ شهر'
  },
  {
    name: 'أ. محمود عبد الرحمن',
    treatment: 'علاج عصب بالمايكروسكوب',
    comment: 'كنت بعاني من ألم شديد في الضرس، الدكتور خلصه في جلسة واحدة مدتها نص ساعة وبدون ما أحس بحاجة. التخدير كان مريح جداً ومفيش وجع بعد ما البنج راح.',
    rating: 5,
    date: 'منذ 3 أسابيع'
  },
];

// FAQ
const faqs = [
  { 
    q: 'هل زراعة الأسنان مؤلمة وهل تناسب كبار السن ومرضى السكر؟', 
    a: 'تتم الزراعة تحت تأثير التخدير الرقمي الموضعي بدون ألم إطلاقاً. كما تناسب الزراعة مرضى السكري المنضبط وكبار السن بعد عمل الفحص المقطعي ثلاثي الأبعاد والتأكد من كثافة العظام.',
    open: true 
  },
  { 
    q: 'كم تدوم عدسات الأسنان (الفينير) وهل يتغير لونها مع الشاي والقهوة؟', 
    a: 'عدسات الفينير المصنوعة من خزف E-max السويسري مقاومة تماماً للتصبغات وتدوم لأكثر من 15 سنة مع المحافظة على نظافة الفم والمتابعة الدورية كل 6 أشهر.',
    open: false 
  },
  { 
    q: 'ما هي مواعيد استقبال العيادة وكيف أحصل على دور سريع؟', 
    a: 'نستقبلكم يومياً من السبت إلى الخميس من الساعة 5:00 مساءً حتى 11:00 مساءً. يمكنك حجز موعدك أونلاين عبر هذه الصفحة للحصول على رقم دورك فوراً وتجنب فترات الانتظار.',
    open: false 
  },
  { 
    q: 'هل كشف الاستشارة والمتابعة مجاني بعد الإجراء العلاجي؟', 
    a: 'نعم، تشمل جميع الإجراءات زيارات متابعة دورية مجانية ضمن فترة المتابعة المقررة لضمان أفضل نتيجة وراحة مستدامة لمريضنا.',
    open: false 
  },
];

const openFaqIndex = ref(0);

const toggleFaq = (index) => {
  openFaqIndex.value = openFaqIndex.value === index ? null : index;
};

const scrollToBooking = (complaintText = '') => {
  const el = document.getElementById('booking-section');
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' });
  }
};
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 selection:bg-cyan-100 selection:text-cyan-900" dir="rtl">
    <!-- Top Utility Bar: Working Hours & Emergency Contact (Inspired by okdental-eg.com) -->
    <div class="bg-gradient-to-r from-slate-900 via-cyan-950 to-slate-900 text-white text-xs py-2 px-4 border-b border-cyan-900/50">
      <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="flex items-center gap-4 text-slate-300">
          <div class="flex items-center gap-1.5">
            <Clock class="w-3.5 h-3.5 text-cyan-400 shrink-0" />
            <span>مواعيد العمل: السبت إلى الخميس: 5:00 م – 11:00 م</span>
          </div>
          <span class="hidden md:inline text-slate-600">•</span>
          <div class="hidden md:flex items-center gap-1.5">
            <ShieldCheck class="w-3.5 h-3.5 text-emerald-400 shrink-0" />
            <span>أعلى معايير التعقيم الأوروبية Class B</span>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <a 
            v-if="tenant.whatsapp_phone_number" 
            :href="`https://wa.me/2${tenant.whatsapp_phone_number}?text=${encodeURIComponent('مرحباً، أود الاستفسار عن كشف الأسنان وحجز موعد')}`" 
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
            class="inline-flex items-center gap-1 text-cyan-300 hover:text-cyan-200 transition font-mono"
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
          <div class="w-11 h-11 p-2 bg-gradient-to-br from-cyan-500 to-blue-600 text-white rounded-2xl shadow-md shadow-cyan-500/20 flex items-center justify-center shrink-0">
            <IconTooth class="w-7 h-7 text-white fill-white/20" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-bold text-slate-900 leading-snug">{{ tenant.name }}</h1>
              <span class="hidden sm:inline-block px-2.5 py-0.5 bg-cyan-50 text-cyan-800 text-[11px] font-bold rounded-full border border-cyan-200">
                عيادة أسنان تخصصية
              </span>
            </div>
            <p class="text-xs text-cyan-700 font-semibold mt-0.5">
              {{ tenant.title_ar || 'استشاري طب وجراحة وتجميل الفم وزراعة الأسنان' }}
            </p>
          </div>
        </div>

        <!-- Navigation Links -->
        <nav class="hidden lg:flex items-center gap-6 text-xs sm:text-sm font-semibold text-slate-600">
          <a href="#about" class="hover:text-cyan-700 transition">عن العيادة</a>
          <a href="#treatments" class="hover:text-cyan-700 transition">الخدمات والعلاجات</a>
          <a href="#technology" class="hover:text-cyan-700 transition">الأجهزة والتعقيم</a>
          <a href="#testimonials" class="hover:text-cyan-700 transition">آراء المرضى</a>
          <a href="#branches" class="hover:text-cyan-700 transition">الفروع والمواعيد</a>
          <a href="#faq" class="hover:text-cyan-700 transition">الأسئلة الشائعة</a>
        </nav>

        <!-- CTA Header Button -->
        <a 
          href="#booking-section" 
          class="px-4 sm:px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white rounded-xl text-xs sm:text-sm font-bold transition shadow-md shadow-cyan-500/25 flex items-center gap-2 active:scale-95"
        >
          <Calendar class="w-4 h-4" />
          <span>احجز كشفك الآن</span>
        </a>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative py-10 lg:py-16 px-4 sm:px-6 bg-gradient-to-b from-cyan-50/60 via-sky-50/30 to-slate-50 overflow-hidden">
      <div class="absolute -top-32 -left-32 w-96 h-96 bg-cyan-200/40 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute top-1/2 -right-32 w-96 h-96 bg-blue-200/30 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start relative z-10">
        <!-- Hero Left Column: Authority & Copy -->
        <div class="lg:col-span-7 space-y-6">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-cyan-100 text-cyan-900 text-xs sm:text-sm font-bold border border-cyan-200 shadow-xs">
            <Sparkles class="w-4 h-4 text-cyan-700 shrink-0" />
            <span>ابتسامة هوليوود الرقمية • زراعة وتجميل الأسنان بدون ألم</span>
          </div>

          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 leading-[1.3] tracking-tight">
            ابتسامتك المثالية تبدأ برعاية طبية فائقة وتقنيات عالمية
          </h2>

          <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-xl font-normal">
            نجمع بين الفن التشكيلي وأحدث تقنيات طب الأسنان الرقمي (3D CAD/CAM) لنمنحك ابتسامة مشرقة تدوم طويلاً، دون ألم وبأعلى معايير التعقيم السويسرية الصارمة.
          </p>

          <!-- Authority Metrics (4 Badges) -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
            <div class="p-3 bg-white rounded-2xl border border-cyan-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-cyan-700">+15</span>
              <span class="text-xs font-semibold text-slate-600">سنة خبرة سريرية</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-cyan-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-cyan-700">+12,000</span>
              <span class="text-xs font-semibold text-slate-600">ابتسامة مشرقة</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-cyan-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-cyan-700">99.8%</span>
              <span class="text-xs font-semibold text-slate-600">نسبة رضا المرضى</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-cyan-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-cyan-700">100%</span>
              <span class="text-xs font-semibold text-slate-600">تعقيم أوروبي B</span>
            </div>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex flex-wrap items-center gap-3 pt-2">
            <a 
              href="#booking-section" 
              class="px-6 py-3.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white rounded-2xl text-xs sm:text-sm font-bold shadow-lg shadow-cyan-500/25 transition active:scale-95 flex items-center gap-2"
            >
              <Calendar class="w-4 h-4" />
              <span>احجز كشفك الآن أونلاين</span>
            </a>

            <a 
              v-if="tenant.whatsapp_phone_number" 
              :href="`https://wa.me/2${tenant.whatsapp_phone_number}?text=${encodeURIComponent('مرحباً، أود الاستفسار عن كشف الأسنان وحجز موعد')}`" 
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
              <div class="p-2.5 bg-cyan-50 text-cyan-600 rounded-xl shrink-0">
                <IconTooth class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">زراعة وتجميل فوري</p>
                <p class="text-[11px] text-slate-500 font-medium">بأحدث خامات E-max</p>
              </div>
            </div>

            <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
              <div class="p-2.5 bg-cyan-50 text-cyan-600 rounded-xl shrink-0">
                <ShieldCheck class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">تخدير بدون وخز</p>
                <p class="text-[11px] text-slate-500 font-medium">حقن حاسوبي بدون ألم</p>
              </div>
            </div>

            <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
              <div class="p-2.5 bg-cyan-50 text-cyan-600 rounded-xl shrink-0">
                <Award class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">خبرة استشارية معتمدة</p>
                <p class="text-[11px] text-slate-500 font-medium">عضو الجمعيات الدولية</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Hero Right Column: Seamless Booking Card -->
        <div id="booking-section" class="lg:col-span-5 scroll-mt-28">
          <div class="relative">
            <div class="absolute -inset-2 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-3xl blur-xl opacity-20"></div>
            <div class="relative">
              <DoctorBookingForm :tenant="tenant" accent-color="cyan" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Doctor Authority & Clinical Philosophy -->
    <section id="about" class="py-16 px-4 sm:px-6 bg-white border-y border-slate-200/70">
      <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
          <div class="lg:col-span-5">
            <div class="relative rounded-3xl bg-gradient-to-tr from-cyan-900 to-slate-900 p-8 text-white shadow-xl overflow-hidden">
              <div class="absolute -bottom-10 -right-10 w-48 h-48 bg-cyan-500/20 rounded-full blur-2xl"></div>
              <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20">
                  <IconTooth class="w-10 h-10 text-cyan-300 fill-cyan-300/30" />
                </div>
                <div>
                  <h3 class="text-xl font-bold">{{ tenant.name }}</h3>
                  <p class="text-xs text-cyan-300 font-medium mt-1">{{ tenant.title_ar || 'استشاري جراحة وتجميل وزراعة الأسنان' }}</p>
                </div>
              </div>

              <div class="space-y-3 text-xs leading-relaxed text-slate-200">
                <p>
                  {{ tenant.website_settings?.about_text || 'نكرس خبرتنا الطبية والأكاديمية لتقديم أعلى مستويات الرعاية السريرية في تجميل وزراعة الأسنان وعلاج الجذور، بالاعتماد على أدق الأجهزة الرقمية والبروتوكولات الدولية المعتمدة.' }}
                </p>
                <div class="pt-3 border-t border-white/15 space-y-2">
                  <div class="flex items-center gap-2 text-cyan-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-cyan-400" />
                    <span>عضو الجمعية الأمريكية لتجميل وزراعة الأسنان (AACD)</span>
                  </div>
                  <div class="flex items-center gap-2 text-cyan-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-cyan-400" />
                    <span>زمالة الكلية الملكية للجراحين في طب الأسنان</span>
                  </div>
                  <div class="flex items-center gap-2 text-cyan-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-cyan-400" />
                    <span>أكثر من 15 عاماً في تصميم الابتسامة وزراعة الأسنان الفورية</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="lg:col-span-7 space-y-5">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-50 text-cyan-800 text-xs font-bold border border-cyan-200">
              <Award class="w-4 h-4 text-cyan-600" />
              <span>فلسفة الرعاية السريرية</span>
            </div>

            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
              نؤمن بأن كل ابتسامة هي بصمة فريدة تتطلب دراسة دقيقة وتخطيطاً تشريحياً متكاملاً
            </h3>

            <p class="text-sm text-slate-600 leading-relaxed">
              في عيادتنا، لا نكتفي بمعالجة الألم فقط، بل نعمل على إعادة التوازن الوظيفي والجمالي لأسنانك. نستخدم أحدث أجهزة المسح الضوئي الرقمي (Digital Scanners) بدلاً من الطبعات التقليدية، مما يتيح لك رؤية نتيجة ابتسامتك ومناقشتها معنا قبل بدء أي علاج.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                  <ShieldCheck class="w-4 h-4 text-cyan-600" />
                  <span>بروتوكول Zero Pain</span>
                </h4>
                <p class="text-xs text-slate-500 leading-relaxed">تطبيق تكنيكات التخدير التدريجي لضمان جلسة علاجية هادئة وخالية تماماً من الألم أو التوتر.</p>
              </div>

              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                  <Sparkles class="w-4 h-4 text-cyan-600" />
                  <span>ضمان الجودة والمتانة</span>
                </h4>
                <p class="text-xs text-slate-500 leading-relaxed">استخدام خامات خزفية وتيتانيوم أصلية معتمدة من FDA مع شهادات ضمان رسمية مسجلة.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Specialty Treatments & Services Grid -->
    <section id="treatments" class="py-16 px-4 sm:px-6 bg-slate-50">
      <div class="max-w-7xl mx-auto space-y-10">
        <div class="text-center max-w-2xl mx-auto space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-100 text-cyan-900 text-xs font-bold">
            <IconTooth class="w-4 h-4 text-cyan-700" />
            <span>خدمات وعلاجات الأسنان التخصصية</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            حلول متكاملة لصحة وجمال أسنانك بأحدث التقنيات
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            اختر الإجراء الطبي المطلوب واحجز موعد كشفك مباشرة للحصول على خطة علاجية مخصصة لحالتك.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="item in dentalTreatments" 
            :key="item.id" 
            class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs hover:border-cyan-400 hover:shadow-lg hover:shadow-cyan-500/10 transition flex flex-col justify-between group"
          >
            <div class="space-y-4">
              <div class="flex items-start justify-between gap-3">
                <span class="px-3 py-1 bg-cyan-50 text-cyan-800 text-[11px] font-bold rounded-xl border border-cyan-100">
                  {{ item.badge }}
                </span>
                <span class="text-xs text-slate-500 font-medium flex items-center gap-1">
                  <Clock class="w-3.5 h-3.5 text-slate-400" />
                  {{ item.duration }}
                </span>
              </div>

              <div>
                <h4 class="text-base font-bold text-slate-900 group-hover:text-cyan-700 transition leading-snug">
                  {{ item.name }}
                </h4>
                <p class="text-xs text-slate-600 leading-relaxed mt-2">
                  {{ item.desc }}
                </p>
              </div>

              <div class="space-y-1.5 pt-2 border-t border-slate-100">
                <div v-for="(p, pIdx) in item.points" :key="pIdx" class="flex items-center gap-2 text-xs text-slate-700">
                  <Check class="w-3.5 h-3.5 text-cyan-600 shrink-0" />
                  <span>{{ p }}</span>
                </div>
              </div>
            </div>

            <div class="pt-5 mt-4 border-t border-slate-100 flex items-center justify-between">
              <button 
                @click="scrollToBooking(item.name)" 
                class="w-full py-2.5 px-4 bg-slate-100 hover:bg-cyan-600 hover:text-white text-slate-800 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 group-hover:bg-cyan-600 group-hover:text-white shadow-xs"
              >
                <span>احجز هذا الإجراء الآن</span>
                <ChevronDown class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Technology & European Sterilization Spotlight (Inspired by okdental-eg.com) -->
    <section id="technology" class="py-16 px-4 sm:px-6 bg-gradient-to-b from-white to-cyan-50/40 border-y border-slate-200/80">
      <div class="max-w-7xl mx-auto space-y-10">
        <div class="text-center max-w-2xl mx-auto space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-100 text-cyan-900 text-xs font-bold">
            <ShieldCheck class="w-4 h-4 text-cyan-700" />
            <span>معايير الأمان والتكنولوجيا</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            أحدث المعدات والتعقيم الأوروبي لراحتك وسلامتك
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            نستثمر في أحدث الأجهزة الطبية الرقمية لتوفير علاج فائق الدقة، بدون ألم وبأعلى درجات الأمان الطبي.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div 
            v-for="(tech, tIdx) in techFeatures" 
            :key="tIdx"
            class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs hover:border-cyan-300 hover:shadow-md transition space-y-3"
          >
            <div class="w-12 h-12 rounded-2xl bg-cyan-50 text-cyan-700 flex items-center justify-center font-bold text-sm">
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-100 text-cyan-900 text-xs font-bold">
            <Star class="w-4 h-4 text-cyan-700 fill-cyan-700" />
            <span>آراء وتجارب مرضانا</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            ثقة مرضانا هي أثمن ما نملك
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            تجارب حقيقية لمرضى استعادوا ثقتهم وابتسامتهم داخل عياداتنا.
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
                <p class="text-[11px] text-cyan-700 font-medium">{{ rev.treatment }}</p>
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-100 text-cyan-900 text-xs font-bold">
            <MapPin class="w-4 h-4 text-cyan-700" />
            <span>فروع العيادة ومواعيد العمل</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            مواقعنا وساعات الاستقبال
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            تفضل بزيارتنا في أقرب فرع إليك، أو احجز كشفك مسبقاً لتفادي الانتظار.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="clinic in tenant.clinics" 
            :key="clinic.id" 
            class="bg-slate-50 rounded-3xl p-6 border border-slate-200/90 shadow-xs hover:border-cyan-400 hover:bg-cyan-50/20 transition space-y-4"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="p-2.5 bg-cyan-100 text-cyan-700 rounded-2xl">
                  <MapPin class="w-5 h-5" />
                </div>
                <div>
                  <h4 class="text-sm font-bold text-slate-900">{{ clinic.name_ar || clinic.name }}</h4>
                  <p class="text-[11px] text-cyan-700 font-medium">كشف: {{ clinic.consultation_price }} ج.م</p>
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
                <Clock class="w-3.5 h-3.5 text-cyan-600 shrink-0" />
                <span>السبت إلى الخميس: 5:00 م – 11:00 م</span>
              </div>
              <div v-if="clinic.phone" class="flex items-center gap-2 text-slate-600 font-mono" dir="ltr">
                <Phone class="w-3.5 h-3.5 text-cyan-600 shrink-0" />
                <a :href="`tel:${clinic.phone}`" class="hover:text-cyan-700 transition">{{ clinic.phone }}</a>
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
                class="flex-1 py-2 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl text-xs font-bold transition text-center shadow-xs shadow-cyan-500/20"
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-100 text-cyan-900 text-xs font-bold">
            <Sparkles class="w-4 h-4 text-cyan-700" />
            <span>الأسئلة الشائعة وإجابات الأطباء</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            كل ما يدور في ذهنك حول علاجات الأسنان
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
                :class="['w-4 h-4 text-cyan-700 shrink-0 transition-transform duration-200', openFaqIndex === fIdx ? 'rotate-180' : '']" 
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
            <div class="w-10 h-10 p-2 bg-cyan-600 text-white rounded-xl flex items-center justify-center">
              <IconTooth class="w-6 h-6 text-white" />
            </div>
            <div>
              <h4 class="text-base font-bold text-white leading-snug">{{ tenant.name }}</h4>
              <p class="text-xs text-cyan-400 font-medium">{{ tenant.title_ar || 'طب وجراحة وتجميل الأسنان' }}</p>
            </div>
          </div>
          <p class="text-xs text-slate-400 leading-relaxed max-w-sm font-normal">
            عيادة أسنان تخصصية مجهزة بأحدث تقنيات تجميل وزراعة الأسنان الرقمية ونظم التعقيم السويسرية لضمان أعلى مستويات الرعاية الصحية بدون ألم.
          </p>
        </div>

        <div class="md:col-span-3 space-y-3 text-xs text-slate-400">
          <h5 class="font-bold text-white text-sm">روابط سريعة</h5>
          <ul class="space-y-2">
            <li><a href="#about" class="hover:text-cyan-300 transition">عن العيادة والطبيب</a></li>
            <li><a href="#treatments" class="hover:text-cyan-300 transition">خدمات وتجميل الأسنان</a></li>
            <li><a href="#technology" class="hover:text-cyan-300 transition">الأجهزة والتعقيم الأوروبي</a></li>
            <li><a href="#branches" class="hover:text-cyan-300 transition">الفروع ومواعيد الاستقبال</a></li>
            <li><a href="#booking-section" class="hover:text-cyan-300 transition">حجز كشف أونلاين</a></li>
          </ul>
        </div>

        <div class="md:col-span-4 space-y-3 text-xs text-slate-400">
          <h5 class="font-bold text-white text-sm">التواصل والاستقبال</h5>
          <p class="text-slate-300">مواعيد العمل: السبت إلى الخميس: 5:00 م – 11:00 م</p>
          <div v-if="tenant.clinics?.[0]?.phone" class="font-mono text-cyan-300" dir="ltr">
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
