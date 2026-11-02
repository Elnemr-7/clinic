<script setup>
import { ref } from 'vue';
import { 
  Sparkles, Award, MapPin, ShieldCheck, CheckCircle2, Clock, Calendar, 
  Phone, Star, HeartHandshake, Zap, Activity, Users, ArrowDown, ChevronRight, 
  ChevronDown, Check, ExternalLink, MessageSquare, Shield, Stethoscope, HeartPulse
} from 'lucide-vue-next';
import DoctorBookingForm from './DoctorBookingForm.vue';

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
});

// Clinical services
const services = [
  {
    id: 'endoscopy',
    name: 'مناظير الجهاز الهضمي والقولون التشخيصية والعلاجية',
    duration: 'إجراء يومي مريح',
    badge: 'بأحدث مناظير Olympus اليابانية',
    desc: 'تنظير دقيق للمعدة والاثني عشر والقولون للكشف المبكر عن الالتهابات والقرح واستئصال الزوائد اللحمية تحت مهدئ خفيف دون أي شعور بالألم.',
    points: ['مهدئ وريدي آمن بدون ألم', 'فحص عينات الأنسجة والجرثومة فوراً', 'أحدث مناظير بدقة 4K عالية التباين']
  },
  {
    id: 'h-pylori',
    name: 'علاج جرثومة المعدة المقاومة وارتجاع المريء',
    duration: 'بروتوكول علاجي متكامل',
    badge: 'بروتوكولات سريرية دولية',
    desc: 'تشخيص دقيق لنشاط جرثومة المعدة الحلزونية (H. Pylori) واختيار أفضل خطة مضادات مخصصة مع علاج حموضة وارتجاع المريء والتهابات فم المعدة.',
    points: ['اختبارات التنفس والبراز الدقيقة', 'علاج حماية الغشاء المخاطي للمعدة', 'متابعة دورية حتى القضاء التام على البكتيريا']
  },
  {
    id: 'chronic',
    name: 'متابعة السكري والضغط وأمراض الكلى المزمنة',
    duration: 'فحص دوري شامل',
    badge: 'وقاية ومتابعة مستدامة',
    desc: 'تنظيم جرعات الإنسولين وأدوية الضغط الحديثة للوقاية من المضاعفات الشريانية والكلوية، مع قياسات السكر التراكمي ووظائف الأعضاء الحيوية.',
    points: ['ضبط السكر التراكمي ومستوى الدهون', 'حماية وظائف الكلى والأوعية الدموية', 'برنامج تغذية سريرية متوازن لمريض السكر']
  },
  {
    id: 'liver',
    name: 'تشخيص وعلاج أمراض الكبد الدهني والفيروسي',
    duration: 'تقييم شامل وموجات صوتية',
    badge: 'حماية وتجديد خلايا الكبد',
    desc: 'تقييم درجات دهون الكبد وارتفاع الإنزيمات بالموجات فوق الصوتية والتحاليل الدقيقة، مع برامج علاجية فعالة لتنشيط وتجديد خلايا الكبد.',
    points: ['فحص بالموجات فوق الصوتية للبطن والحوض', 'متابعة إنزيمات الكبد ونسب التليف', 'خطة علاجية مخصصة لإنقاص دهون الكبد']
  },
  {
    id: 'ibs',
    name: 'علاج متلازمة القولون العصبي واضطرابات الهضم',
    duration: 'علاج دوائي وتوجيه غذائي',
    badge: 'راحة الجهاز الهضمي',
    desc: 'حلول طبية متطورة للانتفاخات والغازات وعسر الهضم المرتبط بالتوتر أو اختلال ميكروبيوم الأمعاء، لإعادة التوازن الطبيعي لحركة الجهاز الهضمي.',
    points: ['تحسين الهضم وامتصاص الغذاء', 'تنظيم حركة الأمعاء والتخلص من الانتفاخ', 'إرشادات حمية غذائية منخفضة الفودماب']
  },
  {
    id: 'checkup',
    name: 'الفحص الطبي الدوري الشامل لكبار السن والبالغين',
    duration: 'جلسة تقييم شاملة',
    badge: 'كشف مبكر ووقاية',
    desc: 'فحص سريري كامل وتقييم لعلامات الحيوية وصحة القلب والأوعية الدموية والجهاز التنفسي لاكتشاف أي خلل صحي في مراحله الأولى.',
    points: ['قياس ضغط الدم والعلامات الحيوية', 'فحص الصدر والبطن سريرياً بدقة', 'مراجعة وتعديل التداخلات الدوائية']
  },
];

// Tech & Clinical Quality
const clinicalFeatures = [
  {
    title: 'وحدة مناظير الجهاز الهضمي المتقدمة',
    desc: 'أحدث أنظمة المناظير المرنة عالية الدقة بدقة 4K مع خاصية الصبغة الرقمية (NBI) للكشف المبكر عن أدق التغيرات المخاطية.',
    tag: 'Advanced Endoscopy'
  },
  {
    title: 'موجات صوتية وسونار ملون عالي الدقة',
    desc: 'جهاز سونار متطور لفحص الكبد والمرار والبنكرياس والطحال والكلى لتقديم تشخيص فوري أثناء الكشف دون الحاجة لتحويلات إضافية.',
    tag: 'Abdominal Ultrasound'
  },
  {
    title: 'بروتوكولات علاجية قائمة على الدليل الدولي',
    desc: 'نطبق أحدث المبادئ التوجيهية للجمعية الأمريكية لأمراض الجهاز الهضمي (AGA) والجمعية الأوروبية لدراسة الكبد (EASL).',
    tag: 'Evidence-Based Medicine'
  },
  {
    title: 'ملف طبي إلكتروني ومتابعة دورية مستمرة',
    desc: 'سجل إلكتروني متكامل لنتائج تحاليلك وتطور مؤشراتك الحيوية مع تذكير تلقائي بمواعيد الفحوصات الدورية والاستشارات.',
    tag: 'Digital Health Records'
  },
];

// Patient Testimonials
const reviews = [
  {
    name: 'الحاج مصطفى النجار',
    treatment: 'متابعة سكري وضغط وفحص شامل',
    comment: 'دكتور أحمد من أفضل وأكثر الأطباء أمانة وإخلاصاً. فحصني باهتمام شديد وراجع كل أدويتي القديمة ونظم لي جرعات السكر بعد ما كانت متلخبطة لسنين.',
    rating: 5,
    date: 'منذ 10 أيام'
  },
  {
    name: 'أ. سامح عبد الفتاح',
    treatment: 'منظار معدة وعلاج جرثومة المعدة',
    comment: 'كنت خايف جداً من المنظار لكن الإجراء مر في دقائق بدون ما أحس بأي تعب بالمهدئ الخفيف. الحمد لله أعراض الحموضة والارتجاع اختفت تماماً بعد كورس العلاج.',
    rating: 5,
    date: 'منذ شهر'
  },
  {
    name: 'مدام جيهان كمال',
    treatment: 'علاج القولون والتهابات الجهاز الهضمي',
    comment: 'العيادة منظمة جداً ودقة في المواعيد بدون انتظار طويل. الدكتور بيسمع المريض بهدوء ويفهم طبيعة شكواه بدقة ويديله وقته الكافي في الكشف.',
    rating: 5,
    date: 'منذ 3 أسابيع'
  },
];

// FAQs
const faqs = [
  {
    q: 'هل منظار المعدة أو القولون يسبب ألماً؟',
    a: 'يتم إجراء مناظير الجهاز الهضمي تحت تأثير مهدئ وريدي قصير المفعول (Sedation) تحت إشراف طبي كامل، بحيث ينام المريض براحة تامة ولا يشعر بأي ألم أو انزعاج طوال فترة الفحص.',
    open: true
  },
  {
    q: 'كيف أستعد لفحص الموجات الصوتية على البطن؟',
    a: 'يُفضل الصيام عن الطعام لمدة 6 إلى 8 ساعات قبل موعد الكشف مع شرب المياه النقية فقط، وذلك لتقليل الغازات المعوية ورؤية المرارة والكبد بأعلى وضوح ممكن.',
    open: false
  },
  {
    q: 'هل كشف الاستشارة والمتابعة مشمول بعد الزيارة الأولى؟',
    a: 'نعم، يحق للمريض الحصول على استشارة مجانية لمراجعة الفحوصات والتحاليل خلال فترة المتابعة المحددة لكل فرع (10 إلى 14 يوماً من تاريخ الكشف الأصلي).',
    open: false
  },
  {
    q: 'كيف يمكنني حجز موعد والحصول على دور مباشر؟',
    a: 'يمكنك اختيار الفرع والتاريخ والوردية المناسبة عبر استمارة الحجز في هذه الصفحة، وسيظهر لك رقم دورك فوراً مع رسالة تأكيد مباشرة عبر واتساب.',
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
  <div class="min-h-screen bg-slate-50 text-slate-800 selection:bg-teal-100 selection:text-teal-900" dir="rtl">
    <!-- Top Utility Bar: Working Hours & Emergency Contact -->
    <div class="bg-gradient-to-r from-slate-900 via-teal-950 to-slate-900 text-white text-xs py-2 px-4 border-b border-teal-900/50">
      <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="flex items-center gap-4 text-slate-300">
          <div class="flex items-center gap-1.5">
            <Clock class="w-3.5 h-3.5 text-teal-400 shrink-0" />
            <span>مواعيد العيادة: السبت إلى الخميس (فترات صباحية ومسائية)</span>
          </div>
          <span class="hidden md:inline text-slate-600">•</span>
          <div class="hidden md:flex items-center gap-1.5">
            <ShieldCheck class="w-3.5 h-3.5 text-emerald-400 shrink-0" />
            <span>رعاية باطنية استشارية قائمة على أحدث البروتوكولات</span>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <a 
            v-if="tenant.whatsapp_phone_number" 
            :href="`https://wa.me/2${tenant.whatsapp_phone_number}?text=${encodeURIComponent('مرحباً دكتور، أود الاستفسار عن كشف الباطنة والجهاز الهضمي وحجز موعد')}`" 
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
            class="inline-flex items-center gap-1 text-teal-300 hover:text-teal-200 transition font-mono"
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
          <div class="w-11 h-11 p-2 bg-gradient-to-br from-teal-600 to-cyan-700 text-white rounded-2xl shadow-md shadow-teal-600/20 flex items-center justify-center shrink-0">
            <Stethoscope class="w-6 h-6 text-white" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-bold text-slate-900 leading-snug">{{ tenant.name }}</h1>
              <span class="hidden sm:inline-block px-2.5 py-0.5 bg-teal-50 text-teal-800 text-[11px] font-bold rounded-full border border-teal-200">
                استشاري معتمد
              </span>
            </div>
            <p class="text-xs text-teal-700 font-semibold mt-0.5">
              {{ tenant.title_ar || 'استشاري أمراض الباطنة والجهاز الهضمي والمناظير والكبد' }}
            </p>
          </div>
        </div>

        <!-- Navigation Links -->
        <nav class="hidden lg:flex items-center gap-6 text-xs sm:text-sm font-semibold text-slate-600">
          <a href="#about" class="hover:text-teal-700 transition">عن الطبيب</a>
          <a href="#services" class="hover:text-teal-700 transition">الفحوصات والعلاجات</a>
          <a href="#technology" class="hover:text-teal-700 transition">مميزات العيادة</a>
          <a href="#testimonials" class="hover:text-teal-700 transition">آراء المرضى</a>
          <a href="#branches" class="hover:text-teal-700 transition">الفروع والمواعيد</a>
          <a href="#faq" class="hover:text-teal-700 transition">الأسئلة الشائعة</a>
        </nav>

        <!-- CTA Header Button -->
        <a 
          href="#booking-section" 
          class="px-4 sm:px-5 py-2.5 bg-gradient-to-r from-teal-600 to-cyan-700 hover:from-teal-700 hover:to-cyan-800 text-white rounded-xl text-xs sm:text-sm font-bold transition shadow-md shadow-teal-600/25 flex items-center gap-2 active:scale-95"
        >
          <Calendar class="w-4 h-4" />
          <span>احجز كشفك الآن</span>
        </a>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative py-10 lg:py-16 px-4 sm:px-6 bg-gradient-to-b from-teal-50/60 via-cyan-50/30 to-slate-50 overflow-hidden">
      <div class="absolute -top-32 -left-32 w-96 h-96 bg-teal-200/40 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute top-1/2 -right-32 w-96 h-96 bg-cyan-200/30 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start relative z-10">
        <!-- Hero Left Column: Authority & Copy -->
        <div class="lg:col-span-7 space-y-6">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-teal-100 text-teal-900 text-xs sm:text-sm font-bold border border-teal-200 shadow-xs">
            <Sparkles class="w-4 h-4 text-teal-700 shrink-0" />
            <span>تشخيص دقيق وعلاج متقدم • رعاية باطنية استشارية موثوقة</span>
          </div>

          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 leading-[1.3] tracking-tight">
            صحتك أمانة تستحق تشخيصاً دقيقاً ورعاية طبية مستدامة
          </h2>

          <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-xl font-normal">
            نقدم لك رعاية طبية متكاملة في تشخيص وعلاج أمراض الباطنة العامة، أمراض الجهاز الهضمي والكبد، ومتابعة الأمراض المزمنة بأحدث البروتوكولات السريرية الدولية.
          </p>

          <!-- Authority Metrics (4 Badges) -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
            <div class="p-3 bg-white rounded-2xl border border-teal-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-teal-700">+15</span>
              <span class="text-xs font-semibold text-slate-600">سنة خبرة استشارية</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-teal-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-teal-700">+25,000</span>
              <span class="text-xs font-semibold text-slate-600">حالة تم علاجها</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-teal-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-teal-700">99.4%</span>
              <span class="text-xs font-semibold text-slate-600">نسبة رضا المرضى</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-teal-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-teal-700">100%</span>
              <span class="text-xs font-semibold text-slate-600">دقة تشخيصية</span>
            </div>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex flex-wrap items-center gap-3 pt-2">
            <a 
              href="#booking-section" 
              class="px-6 py-3.5 bg-gradient-to-r from-teal-600 to-cyan-700 hover:from-teal-700 hover:to-cyan-800 text-white rounded-2xl text-xs sm:text-sm font-bold shadow-lg shadow-teal-600/25 transition active:scale-95 flex items-center gap-2"
            >
              <Calendar class="w-4 h-4" />
              <span>احجز كشفك الآن أونلاين</span>
            </a>

            <a 
              v-if="tenant.whatsapp_phone_number" 
              :href="`https://wa.me/2${tenant.whatsapp_phone_number}?text=${encodeURIComponent('مرحباً دكتور، أود الاستفسار عن كشف الباطنة وحجز موعد')}`" 
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
              <div class="p-2.5 bg-teal-50 text-teal-600 rounded-xl shrink-0">
                <HeartPulse class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">متابعة دقيقة</p>
                <p class="text-[11px] text-slate-500 font-medium">للسكر والضغط والكلى</p>
              </div>
            </div>

            <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
              <div class="p-2.5 bg-teal-50 text-teal-600 rounded-xl shrink-0">
                <ShieldCheck class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">بروتوكولات دولية</p>
                <p class="text-[11px] text-slate-500 font-medium">علاج قائم على الدليل</p>
              </div>
            </div>

            <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
              <div class="p-2.5 bg-teal-50 text-teal-600 rounded-xl shrink-0">
                <Award class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">خبرة استشارية</p>
                <p class="text-[11px] text-slate-500 font-medium">في الباطنة والمناظير</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Hero Right Column: Booking Container with safe scroll offset -->
        <div id="booking-section" class="lg:col-span-5 scroll-mt-28">
          <div class="relative">
            <div class="absolute -inset-2 bg-gradient-to-r from-teal-600 to-cyan-600 rounded-3xl blur-xl opacity-20"></div>
            <div class="relative">
              <DoctorBookingForm :tenant="tenant" accent-color="teal" />
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
            <div class="relative rounded-3xl bg-gradient-to-tr from-slate-900 via-teal-950 to-slate-900 p-8 text-white shadow-xl overflow-hidden">
              <div class="absolute -bottom-10 -right-10 w-48 h-48 bg-teal-500/20 rounded-full blur-2xl"></div>
              <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20">
                  <Stethoscope class="w-10 h-10 text-teal-300" />
                </div>
                <div>
                  <h3 class="text-xl font-bold">{{ tenant.name }}</h3>
                  <p class="text-xs text-teal-300 font-medium mt-1">{{ tenant.title_ar || 'استشاري أمراض الباطنة والجهاز الهضمي والمناظير' }}</p>
                </div>
              </div>

              <div class="space-y-3 text-xs leading-relaxed text-slate-200">
                <p>
                  {{ tenant.website_settings?.about_text || 'خبرة أكثر من 15 عاماً في تشخيص وعلاج أمراض الباطنة والجهاز الهضمي والكبد، وعضو الجمعية المصرية لأمراض الجهاز الهضمي والجمعية الأوروبية لأمراض الكبد.' }}
                </p>
                <div class="pt-3 border-t border-white/15 space-y-2">
                  <div class="flex items-center gap-2 text-teal-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-teal-400" />
                    <span>عضو الجمعية الأوروبية لأمراض الكبد (EASL)</span>
                  </div>
                  <div class="flex items-center gap-2 text-teal-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-teal-400" />
                    <span>استشاري مناظير الجهاز الهضمي المتقدمة والقولون</span>
                  </div>
                  <div class="flex items-center gap-2 text-teal-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-teal-400" />
                    <span>برامج متخصصة لمتابعة السكري والضغط والأمراض المزمنة</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="lg:col-span-7 space-y-5">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-50 text-teal-800 text-xs font-bold border border-teal-200">
              <Award class="w-4 h-4 text-teal-600" />
              <span>منهجية التشخيص والعلاج</span>
            </div>

            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
              التشخيص الدقيق هو نصف العلاج، وصحة مريضنا أولويتنا المطلقة
            </h3>

            <p class="text-sm text-slate-600 leading-relaxed">
              نولي كل مريض الوقت الكافي للاستماع لكافة تفاصيل شكواه وتاريخه المرضي، ونجري الفحص السريري الدقيق والفحوصات التكميلية الموجهة فقط دون إرهاق المريض بتحاليل غير ضرورية، مع وضع خطة علاجية مخصصة ومتابعة مستمرة لتطور حالته.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                  <ShieldCheck class="w-4 h-4 text-teal-600" />
                  <span>علاج قائم على الدليل العلمي</span>
                </h4>
                <p class="text-xs text-slate-500 leading-relaxed">تطبيق بروتوكولات سريرية دولية تضمن أعلى فاعلية علاجية بأقل آثار جانبية ممكنة.</p>
              </div>

              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                  <Activity class="w-4 h-4 text-teal-600" />
                  <span>متابعة إلكترونية مستمرة</span>
                </h4>
                <p class="text-xs text-slate-500 leading-relaxed">ربط ملفك الطبي بالنظام الذكي لتذكيرك بمواعيد الاستشارات والتحاليل الدورية.</p>
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-100 text-teal-900 text-xs font-bold">
            <Stethoscope class="w-4 h-4 text-teal-700" />
            <span>الفحوصات والخدمات العلاجية</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            رعاية شاملة للجهاز الهضمي والأمراض الباطنية
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            اختر الإجراء أو الفحص المطلوب واحجز كشفك مباشرة للحصول على تقييم سريري شامل.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="item in services" 
            :key="item.id" 
            class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs hover:border-teal-400 hover:shadow-lg hover:shadow-teal-500/10 transition flex flex-col justify-between group"
          >
            <div class="space-y-4">
              <div class="flex items-start justify-between gap-3">
                <span class="px-3 py-1 bg-teal-50 text-teal-800 text-[11px] font-bold rounded-xl border border-teal-100">
                  {{ item.badge }}
                </span>
                <span class="text-xs text-slate-500 font-medium flex items-center gap-1">
                  <Clock class="w-3.5 h-3.5 text-slate-400" />
                  {{ item.duration }}
                </span>
              </div>

              <div>
                <h4 class="text-base font-bold text-slate-900 group-hover:text-teal-700 transition leading-snug">
                  {{ item.name }}
                </h4>
                <p class="text-xs text-slate-600 leading-relaxed mt-2">
                  {{ item.desc }}
                </p>
              </div>

              <div class="space-y-1.5 pt-2 border-t border-slate-100">
                <div v-for="(p, pIdx) in item.points" :key="pIdx" class="flex items-center gap-2 text-xs text-slate-700">
                  <Check class="w-3.5 h-3.5 text-teal-600 shrink-0" />
                  <span>{{ p }}</span>
                </div>
              </div>
            </div>

            <div class="pt-5 mt-4 border-t border-slate-100 flex items-center justify-between">
              <button 
                @click="scrollToBooking(item.name)" 
                class="w-full py-2.5 px-4 bg-slate-100 hover:bg-teal-600 hover:text-white text-slate-800 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 group-hover:bg-teal-600 group-hover:text-white shadow-xs"
              >
                <span>احجز موعد كشف لهذا الفحص</span>
                <ChevronDown class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Clinic Technology & Standards -->
    <section id="technology" class="py-16 px-4 sm:px-6 bg-gradient-to-b from-white to-teal-50/40 border-y border-slate-200/80">
      <div class="max-w-7xl mx-auto space-y-10">
        <div class="text-center max-w-2xl mx-auto space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-100 text-teal-900 text-xs font-bold">
            <ShieldCheck class="w-4 h-4 text-teal-700" />
            <span>التجهيزات ومعايير الجودة</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            تجهيزات طبية متطورة لتشخيص دقيق وسريع
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            تضم عياداتنا أحدث التجهيزات الطبية لمناظير الجهاز الهضمي والسونار لتقديم الرعاية في مكان واحد.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div 
            v-for="(tech, tIdx) in clinicalFeatures" 
            :key="tIdx"
            class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs hover:border-teal-300 hover:shadow-md transition space-y-3"
          >
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center font-bold text-sm">
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-100 text-teal-900 text-xs font-bold">
            <Star class="w-4 h-4 text-teal-700 fill-teal-700" />
            <span>آراء وتجارب مرضانا</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            ثقة مرضانا شهادة نعتز بها
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            تجارب حقيقية لمرضى تلقوا العلاج والمتابعة داخل عياداتنا.
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
                <p class="text-[11px] text-teal-700 font-medium">{{ rev.treatment }}</p>
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-100 text-teal-900 text-xs font-bold">
            <MapPin class="w-4 h-4 text-teal-700" />
            <span>فروع العيادة ومواعيد العمل</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            مواقعنا وساعات الاستقبال
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            اختر أقرب فرع إليك للاطلاع على مواعيد الاستقبال أو حجز موعدك فوراً.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="clinic in tenant.clinics" 
            :key="clinic.id" 
            class="bg-slate-50 rounded-3xl p-6 border border-slate-200/90 shadow-xs hover:border-teal-400 hover:bg-teal-50/20 transition space-y-4"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="p-2.5 bg-teal-100 text-teal-700 rounded-2xl">
                  <MapPin class="w-5 h-5" />
                </div>
                <div>
                  <h4 class="text-sm font-bold text-slate-900">{{ clinic.name_ar || clinic.name }}</h4>
                  <p class="text-[11px] text-teal-700 font-medium">كشف: {{ clinic.consultation_price }} ج.م</p>
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
                <Clock class="w-3.5 h-3.5 text-teal-600 shrink-0" />
                <span>السبت إلى الخميس: فترات صباحية ومسائية</span>
              </div>
              <div v-if="clinic.phone" class="flex items-center gap-2 text-slate-600 font-mono" dir="ltr">
                <Phone class="w-3.5 h-3.5 text-teal-600 shrink-0" />
                <a :href="`tel:${clinic.phone}`" class="hover:text-teal-700 transition">{{ clinic.phone }}</a>
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
                class="flex-1 py-2 px-3 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition text-center shadow-xs shadow-teal-600/20"
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-100 text-teal-900 text-xs font-bold">
            <Sparkles class="w-4 h-4 text-teal-700" />
            <span>الأسئلة الشائعة وإجابات الطبيب</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            كل ما يهمك معرفته قبل زيارة العيادة
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
                :class="['w-4 h-4 text-teal-700 shrink-0 transition-transform duration-200', openFaqIndex === fIdx ? 'rotate-180' : '']" 
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
            <div class="w-10 h-10 p-2 bg-teal-600 text-white rounded-xl flex items-center justify-center">
              <Stethoscope class="w-6 h-6 text-white" />
            </div>
            <div>
              <h4 class="text-base font-bold text-white leading-snug">{{ tenant.name }}</h4>
              <p class="text-xs text-teal-400 font-medium">{{ tenant.title_ar || 'استشاري أمراض الباطنة والجهاز الهضمي والمناظير' }}</p>
            </div>
          </div>
          <p class="text-xs text-slate-400 leading-relaxed max-w-sm font-normal">
            عيادة باطنية وجهاز هضمي متخصصة ومجهزة بأحدث مناظير الجهاز الهضمي والتشخيص السوناري المتقدم لتقديم أعلى مستويات الرعاية الصحية.
          </p>
        </div>

        <div class="md:col-span-3 space-y-3 text-xs text-slate-400">
          <h5 class="font-bold text-white text-sm">روابط سريعة</h5>
          <ul class="space-y-2">
            <li><a href="#about" class="hover:text-teal-300 transition">عن الطبيب وفلسفة الرعاية</a></li>
            <li><a href="#services" class="hover:text-teal-300 transition">الفحوصات ومناظير الجهاز الهضمي</a></li>
            <li><a href="#technology" class="hover:text-teal-300 transition">التجهيزات والموجات الصوتية</a></li>
            <li><a href="#branches" class="hover:text-teal-300 transition">الفروع ومواعيد الاستقبال</a></li>
            <li><a href="#booking-section" class="hover:text-teal-300 transition">حجز كشف أونلاين</a></li>
          </ul>
        </div>

        <div class="md:col-span-4 space-y-3 text-xs text-slate-400">
          <h5 class="font-bold text-white text-sm">التواصل والاستقبال</h5>
          <p class="text-slate-300">مواعيد العيادة: السبت إلى الخميس (صباحاً ومساءً)</p>
          <div v-if="tenant.clinics?.[0]?.phone" class="font-mono text-teal-300" dir="ltr">
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
