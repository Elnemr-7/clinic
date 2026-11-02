<script setup>
import { ref } from 'vue';
import { 
  Sparkles, Award, MapPin, ShieldCheck, CheckCircle2, Clock, Calendar, 
  Phone, Star, Heart, Activity, Users, ArrowDown, ChevronRight, Flower2, Zap, 
  Shield, ChevronDown, Check, ExternalLink, MessageSquare
} from 'lucide-vue-next';
import DoctorBookingForm from './DoctorBookingForm.vue';

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
});

const dermaServices = [
  { 
    id: 'laser-hair',
    name: 'إزالة الشعر بالليزر (Candela GentleLase Pro)', 
    duration: 'جلسة حسب المنطقة', 
    badge: 'تبريد ديناميكي DCD بدون ألم', 
    desc: 'أقوى أجهزة الليزر الطبية المعتمدة عالمياً المناسبة لجميع درجات البشرة مع تبريد مستمر لراحة وأمان تام وسرعة فائقة في النتائج.',
    points: ['نتائج ملحوظة من أول جلستين', 'نظام تبريد ذكي يمنع أي حروق', 'جلسات رتوش مجانية بعد كل جلسة']
  },
  { 
    id: 'hydrafacial',
    name: 'جلسات النضارة العميقة (HydraFacial Elite)', 
    duration: '45 دقيقة', 
    badge: 'نضارة فورية وإشراقة طبيعية', 
    desc: 'تنظيف عميق للمسام وإزالة الرؤوس السوداء والجلد الميت مع ضخ سيرومات الهيالورونيك ومضادات الأكسدة لترطيب ونضارة البشرة.',
    points: ['تنظيف وتقشير وترطيب في جلسة واحدة', 'مناسب للبشرة الحساسة والمجهدة', 'إشراقة واضحة تلاحظينها بنفس اليوم']
  },
  { 
    id: 'filler',
    name: 'حقن الفيلر التجميلي وتحديد الشفاه والفك (Texas)', 
    duration: '30 دقيقة', 
    badge: 'خامات سويسرية وأمريكية معتمدة', 
    desc: 'استعادة الحجم الطبيعي وتحديد ملامح الوجه والشفاه وتنسيق زوايا الفك بدقة متناهية وبمظهر جذاب وطبيعي بدون مبالغة.',
    points: ['فيلر Juvederm وRestylane أصلي', 'تحديد ملامح ناعم ومتناسق', 'يدوم من 9 إلى 18 شهراً بأمان تام']
  },
  { 
    id: 'botox',
    name: 'حقن البوتوكس للتجاعيد التعبيرية ورفع الحواجب', 
    duration: '15 دقيقة', 
    badge: 'نعومة وشباب للبشرة', 
    desc: 'إخفاء خطوط الجبهة وبين الحاجبين وحول العينين وعلاج الابتسامة اللثوية وفرط التعرق بتقنية الحقن الميكروي الدقيق.',
    points: ['بوتوكس Allergan الأصلي المعتمد', 'مظهر مرتاح وحيوي بدون تجميد تعابير الوجه', 'جلسة رتوش مجانية بعد 14 يوماً']
  },
  { 
    id: 'fractional',
    name: 'علاج حب الشباب والندبات بالليزر (Fractional CO2)', 
    duration: 'جلسات شهرية', 
    badge: 'تجديد طبقات الجلد العميقة', 
    desc: 'تحفيز الكولاجين الطبيعي لترميم آثار الندبات والمسام الواسعة والتصبغات الجلدية العنيدة لبشرة ناعمة وموحدة الملمس واللون.',
    points: ['علاج فعال لحفر وندبات حب الشباب القديمة', 'تضييق المسام وتوحيد ملمس البشرة', 'بروتوكول كريمات حماية وترميم ما بعد الجلسة']
  },
  { 
    id: 'prp',
    name: 'جلسات البلازما الملكية (PRP) والميزوثيرابي للشعر', 
    duration: 'جلسة كل 3 أسابيع', 
    badge: 'إنبات وتكثيف الشعر الطبيعي', 
    desc: 'استخلاص الصفائح الدموية الغنية بعوامل النمو وإعادة حقنها لتغذية بصيلات الشعر الضعيفة ووقف التساقط وإعادة إنبات الشعر.',
    points: ['فصل بلازما بأنابيب هلامية معتمدة', 'تغذية عميقة لبصيلات الشعر والفروة', 'تحفيز ملحوظ لكثافة وطول الشعر']
  },
];

const pillars = [
  { title: 'أجهزة طبية معتمدة من FDA', desc: 'نستخدم حصرياً تقنيات وأجهزة الليزر الأمريكية والأوروبية المعتمدة عالمياً لأعلى درجات الأمان.', tag: 'FDA Approved' },
  { title: 'بروتوكولات علاج شخصية', desc: 'فحص مجهري دقيق للبشرة والشعر لتحديد الخطة المناسبة بدقة لكل حالة دون أي عشوائية.', tag: 'Personalized Care' },
  { title: 'نتائج طبيعية متناسقة', desc: 'فلسفتنا في التجميل ترتكز على إبراز جمالك الطبيعي وتناسق ملامحك دون مبالغة أو تصنع.', tag: 'Natural Aesthetics' },
  { title: 'خصوصية وراحة تامة VIP', desc: 'غرف علاجية مستقلة ومجهزة بأعلى درجات الرفاهية والتعقيم المستمر لخصوصيتك الكاملة.', tag: 'VIP Suites' },
];

const reviews = [
  {
    name: 'أ. ياسمين خيري',
    treatment: 'جلسات إزالة الشعر بالليزر وبوتوكس',
    comment: 'العيادة تجنن والنظافة 10 على 10. جهاز الليزر مريح جداً ومفيش وجع بفضل التبريد، ودكتورة شاطرة جداً في حقن البوتوكس النتيجة طلعت طبيعية أوي.',
    rating: 5,
    date: 'منذ أسبوعين'
  },
  {
    name: 'د. نوران الشافعي',
    treatment: 'تنظيف هيدرافيشل وفيلر شفاه',
    comment: 'أول مرة أعمل فيلر ومكنتش متخيلة النتيجة هتكون بالجمال والنعومة دي. شكل الشفايف طبيعي جداً ومفيش أي تورم، وجلسة الهيدرافيشل فرقت جداً في بشرتي.',
    rating: 5,
    date: 'منذ شهر'
  },
  {
    name: 'أ. رانيا عادل',
    treatment: 'جلسات بلازما ومايكرونيدلينج',
    comment: 'عالجت تساقط الشعر بعد شهور من المعاناة، شعري بدأ ينبت من تاني والتساقط وقف بنسبة كبيرة بعد تالت جلسة. شكراً للدكتورة وفريق التمريض الودود.',
    rating: 5,
    date: 'منذ 3 أسابيع'
  },
];

const faqs = [
  { 
    q: 'متى تظهر نتائج حقن البوتوكس والفيلر؟', 
    a: 'يظهر الفيلر فورياً مع تحسن النتيجة واستقرار الشكل النهائي خلال يومين إلى 3 أيام، بينما يبدأ مفعول البوتوكس بالظهور بين اليوم الثالث والخامس ويكتمل تماماً بعد أسبوعين.',
    open: true 
  },
  { 
    q: 'كم عدد جلسات الليزر المطلوبة لإزالة الشعر نهائياً؟', 
    a: 'يتراوح متوسط الجلسات بين 6 إلى 8 جلسات بفاصل 4 إلى 6 أسابيع بين الجلسة والأخرى، وتتحقق نسبة اختفاء وتلاشي شعر تصل إلى أكثر من 90% مع جلسات صيانة سنوية.',
    open: false 
  },
  { 
    q: 'هل جلسات تنظيف البشرة تناسب البشرة الحساسة أو المعرضة للحبوب؟', 
    a: 'نعم بالتأكيد، يتم فحص نوع بشرتك وضبط محاليل الـ HydraFacial ونسب التقشير خصيصاً بمحاليل مهدئة ومضادة للبكتيريا تناسب البشرة الحساسة والملتهبة.',
    open: false 
  },
  { 
    q: 'كيف يمكنني حجز موعد كشف أو جلسة ليزر؟', 
    a: 'يمكنك اختيار الفرع والخدمة والتاريخ المناسب عبر استمارة الحجز في هذه الصفحة للحصول على رقم حجز وتأكيد مباشر عبر واتساب.',
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
  <div class="min-h-screen bg-rose-50/20 text-slate-800 selection:bg-rose-100 selection:text-rose-900" dir="rtl">
    <!-- Top Utility Bar: Working Hours & Contact -->
    <div class="bg-gradient-to-r from-slate-900 via-rose-950 to-slate-900 text-white text-xs py-2 px-4 border-b border-rose-900/50">
      <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="flex items-center gap-4 text-slate-300">
          <div class="flex items-center gap-1.5">
            <Clock class="w-3.5 h-3.5 text-rose-400 shrink-0" />
            <span>مواعيد العيادة: السبت إلى الخميس: 2:00 ظهراً – 10:00 مساءً</span>
          </div>
          <span class="hidden md:inline text-slate-600">•</span>
          <div class="hidden md:flex items-center gap-1.5">
            <ShieldCheck class="w-3.5 h-3.5 text-emerald-400 shrink-0" />
            <span>أجهزة ليزر أصلية معتمدة من FDA وخصوصية تامة</span>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <a 
            v-if="tenant.whatsapp_phone_number" 
            :href="`https://wa.me/2${tenant.whatsapp_phone_number}?text=${encodeURIComponent('مرحباً، أود الاستفسار عن جلسات الجلدية والليزر وحجز موعد')}`" 
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
            class="inline-flex items-center gap-1 text-rose-300 hover:text-rose-200 transition font-mono"
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
          <div class="w-11 h-11 p-2 bg-gradient-to-br from-rose-500 to-pink-600 text-white rounded-2xl shadow-md shadow-rose-500/20 flex items-center justify-center shrink-0">
            <Flower2 class="w-6 h-6 text-white" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-bold text-slate-900 leading-snug">{{ tenant.name }}</h1>
              <span class="hidden sm:inline-block px-2.5 py-0.5 bg-rose-50 text-rose-800 text-[11px] font-bold rounded-full border border-rose-200">
                جلدية وليزر وتجميل
              </span>
            </div>
            <p class="text-xs text-rose-700 font-semibold mt-0.5">
              {{ tenant.title_ar || 'أخصائية الأمراض الجلدية والليزر وتجميل البشرة والشعر' }}
            </p>
          </div>
        </div>

        <!-- Navigation Links -->
        <nav class="hidden lg:flex items-center gap-6 text-xs sm:text-sm font-semibold text-slate-600">
          <a href="#about" class="hover:text-rose-600 transition">عن الطبيبة</a>
          <a href="#services" class="hover:text-rose-600 transition">الجلسات والعلاجات</a>
          <a href="#technology" class="hover:text-rose-600 transition">الأجهزة والتقنيات</a>
          <a href="#testimonials" class="hover:text-rose-600 transition">آراء العميلات</a>
          <a href="#branches" class="hover:text-rose-600 transition">الفروع والمواعيد</a>
          <a href="#faq" class="hover:text-rose-600 transition">الأسئلة الشائعة</a>
        </nav>

        <!-- CTA Header Button -->
        <a 
          href="#booking-section" 
          class="px-4 sm:px-5 py-2.5 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 text-white rounded-xl text-xs sm:text-sm font-bold transition shadow-md shadow-rose-500/25 flex items-center gap-2 active:scale-95"
        >
          <Calendar class="w-4 h-4" />
          <span>حجز استشارة تجميلية</span>
        </a>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative py-10 lg:py-16 px-4 sm:px-6 bg-gradient-to-b from-rose-100/50 via-pink-50/20 to-slate-50 overflow-hidden">
      <div class="absolute -top-32 -left-32 w-96 h-96 bg-rose-200/40 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute top-1/2 -right-32 w-96 h-96 bg-purple-200/30 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start relative z-10">
        <!-- Hero Left Column: Authority & Copy -->
        <div class="lg:col-span-7 space-y-6">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-rose-100 text-rose-900 text-xs sm:text-sm font-bold border border-rose-200 shadow-xs">
            <Sparkles class="w-4 h-4 text-rose-700 shrink-0" />
            <span>نضارة طبيعية ساحرة • أحدث تقنيات الليزر والعناية بالبشرة والشعر</span>
          </div>

          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 leading-[1.3] tracking-tight">
            جمالك الطبيعي يستحق رعاية استشارية متخصصة وأحدث أجهزة الليزر
          </h2>

          <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-xl font-normal">
            نساعدك على استعادة إشراقة بشرتك وصحة شعرك بأحدث الأجهزة الطبية المعتمدة دولياً، مع خطط علاجية مخصصة تبرز جمالك الطبيعي دون أي تصنع.
          </p>

          <!-- Authority Metrics (4 Badges) -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
            <div class="p-3 bg-white rounded-2xl border border-rose-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-rose-600">+12</span>
              <span class="text-xs font-semibold text-slate-600">سنة خبرة تجميلية</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-rose-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-rose-600">+18,000</span>
              <span class="text-xs font-semibold text-slate-600">جلسة ناجحة</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-rose-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-rose-600">99.7%</span>
              <span class="text-xs font-semibold text-slate-600">نسبة رضا العميلات</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-rose-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-rose-600">100%</span>
              <span class="text-xs font-semibold text-slate-600">أجهزة أصلية FDA</span>
            </div>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex flex-wrap items-center gap-3 pt-2">
            <a 
              href="#booking-section" 
              class="px-6 py-3.5 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 text-white rounded-2xl text-xs sm:text-sm font-bold shadow-lg shadow-rose-500/25 transition active:scale-95 flex items-center gap-2"
            >
              <Calendar class="w-4 h-4" />
              <span>احجزي جلستك الآن أونلاين</span>
            </a>

            <a 
              v-if="tenant.whatsapp_phone_number" 
              :href="`https://wa.me/2${tenant.whatsapp_phone_number}?text=${encodeURIComponent('مرحباً دكتورة، أود الاستفسار عن جلسات الجلدية والليزر وحجز موعد')}`" 
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
              <div class="p-2.5 bg-rose-50 text-rose-600 rounded-xl shrink-0">
                <Sparkles class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">نضارة وإشراقة طبيعية</p>
                <p class="text-[11px] text-slate-500 font-medium">هيدرافيشل وتقشير كولاجين</p>
              </div>
            </div>

            <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
              <div class="p-2.5 bg-rose-50 text-rose-600 rounded-xl shrink-0">
                <ShieldCheck class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">ليزر كانديلا الأصلي</p>
                <p class="text-[11px] text-slate-500 font-medium">أمان وتبريد بدون ألم</p>
              </div>
            </div>

            <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
              <div class="p-2.5 bg-rose-50 text-rose-600 rounded-xl shrink-0">
                <Award class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">خبرة استشارية معتمدة</p>
                <p class="text-[11px] text-slate-500 font-medium">حقن تجميلي دقيق ومتناسق</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Hero Right Column: Booking Card with safe scroll offset -->
        <div id="booking-section" class="lg:col-span-5 scroll-mt-28">
          <div class="relative">
            <div class="absolute -inset-2 bg-gradient-to-r from-rose-500 to-pink-600 rounded-3xl blur-xl opacity-20"></div>
            <div class="relative">
              <DoctorBookingForm :tenant="tenant" accent-color="rose" />
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
            <div class="relative rounded-3xl bg-gradient-to-tr from-slate-900 via-rose-950 to-slate-900 p-8 text-white shadow-xl overflow-hidden">
              <div class="absolute -bottom-10 -right-10 w-48 h-48 bg-rose-500/20 rounded-full blur-2xl"></div>
              <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20">
                  <Flower2 class="w-10 h-10 text-rose-300" />
                </div>
                <div>
                  <h3 class="text-xl font-bold">{{ tenant.name }}</h3>
                  <p class="text-xs text-rose-300 font-medium mt-1">{{ tenant.title_ar || 'أخصائية الأمراض الجلدية والليزر والتجميل' }}</p>
                </div>
              </div>

              <div class="space-y-3 text-xs leading-relaxed text-slate-200">
                <p>
                  {{ tenant.website_settings?.about_text || 'خبرة متميزة في علاج الأمراض الجلدية المستعصية واستخدام أحدث أجهزة الليزر الطبية والحقن التجميلي المتقدم، مع التركيز على المظهر الطبيعي المتناسق.' }}
                </p>
                <div class="pt-3 border-t border-white/15 space-y-2">
                  <div class="flex items-center gap-2 text-rose-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-rose-400" />
                    <span>عضو الجمعية الأمريكية لأمراض الجلد والتجميل (AAD)</span>
                  </div>
                  <div class="flex items-center gap-2 text-rose-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-rose-400" />
                    <span>شهادات معتمدة في الحقن التجميلي المتقدم (فيلر وبوتوكس)</span>
                  </div>
                  <div class="flex items-center gap-2 text-rose-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-rose-400" />
                    <span>بروتوكولات متكاملة لعلاج تساقط الشعر وتجديد البشرة</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="lg:col-span-7 space-y-5">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 text-rose-800 text-xs font-bold border border-rose-200">
              <Award class="w-4 h-4 text-rose-600" />
              <span>فلسفة الجمال الطبيعي</span>
            </div>

            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
              هدفنا إبراز جمالك الفريد وصحة بشرتك دون تغيير ملامحك الأصلية
            </h3>

            <p class="text-sm text-slate-600 leading-relaxed">
              نؤمن بأن كل بشرة لها احتياجاتها الخاصة؛ لذلك نبدأ دائماً بجلسة كشف واستشارة تشخيصية مجهرية للبشرة والشعر لتحديد بروتوكول العلاج بدقة فائقة، واستخدام خامات أصلية معتمدة من هيئة الغذاء والدواء الأمريكية لضمان أعلى درجات الأمان والفاعلية.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                  <ShieldCheck class="w-4 h-4 text-rose-600" />
                  <span>خامات أصلية 100%</span>
                </h4>
                <p class="text-xs text-slate-500 leading-relaxed">استخدام حصري لمنتجات Juvederm وRestylane وAllergan بعبوات أصلية تفتح أمامك.</p>
              </div>

              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                  <Sparkles class="w-4 h-4 text-rose-600" />
                  <span>عناية فائقة وخصوصية VIP</span>
                </h4>
                <p class="text-xs text-slate-500 leading-relaxed">أجواء هادئة وأجنحة علاجية مخصصة لضمان راحتك وخصوصيتك طوال الجلسة.</p>
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-100 text-rose-900 text-xs font-bold">
            <Flower2 class="w-4 h-4 text-rose-700" />
            <span>جلسات الجلدية والليزر والتجميل</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            خدمات متكاملة لبشرة نضرة وشعر صحي
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            اختاري الجلسة المناسبة لاحتياجاتك واحجزي موعدك المسبق لتأكيد دورك فوراً.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="item in dermaServices" 
            :key="item.id" 
            class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs hover:border-rose-400 hover:shadow-lg hover:shadow-rose-500/10 transition flex flex-col justify-between group"
          >
            <div class="space-y-4">
              <div class="flex items-start justify-between gap-3">
                <span class="px-3 py-1 bg-rose-50 text-rose-800 text-[11px] font-bold rounded-xl border border-rose-100">
                  {{ item.badge }}
                </span>
                <span class="text-xs text-slate-500 font-medium flex items-center gap-1">
                  <Clock class="w-3.5 h-3.5 text-slate-400" />
                  {{ item.duration }}
                </span>
              </div>

              <div>
                <h4 class="text-base font-bold text-slate-900 group-hover:text-rose-600 transition leading-snug">
                  {{ item.name }}
                </h4>
                <p class="text-xs text-slate-600 leading-relaxed mt-2">
                  {{ item.desc }}
                </p>
              </div>

              <div class="space-y-1.5 pt-2 border-t border-slate-100">
                <div v-for="(p, pIdx) in item.points" :key="pIdx" class="flex items-center gap-2 text-xs text-slate-700">
                  <Check class="w-3.5 h-3.5 text-rose-600 shrink-0" />
                  <span>{{ p }}</span>
                </div>
              </div>
            </div>

            <div class="pt-5 mt-4 border-t border-slate-100 flex items-center justify-between">
              <button 
                @click="scrollToBooking(item.name)" 
                class="w-full py-2.5 px-4 bg-slate-100 hover:bg-rose-600 hover:text-white text-slate-800 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 group-hover:bg-rose-600 group-hover:text-white shadow-xs"
              >
                <span>احجزي هذه الجلسة الآن</span>
                <ChevronDown class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Tech & Safety Standards -->
    <section id="technology" class="py-16 px-4 sm:px-6 bg-gradient-to-b from-white to-rose-50/40 border-y border-slate-200/80">
      <div class="max-w-7xl mx-auto space-y-10">
        <div class="text-center max-w-2xl mx-auto space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-100 text-rose-900 text-xs font-bold">
            <ShieldCheck class="w-4 h-4 text-rose-700" />
            <span>الأجهزة ومعايير الأمان</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            أحدث أجهزة التجميل العالمية لنتائج مبهرة
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            نستثمر في أرقى وأحدث تقنيات الليزر والعناية بالبشرة لضمان أقصى درجات الأمان والفاعلية.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div 
            v-for="(tech, tIdx) in pillars" 
            :key="tIdx"
            class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs hover:border-rose-300 hover:shadow-md transition space-y-3"
          >
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-700 flex items-center justify-center font-bold text-sm">
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-100 text-rose-900 text-xs font-bold">
            <Star class="w-4 h-4 text-rose-600 fill-rose-600" />
            <span>آراء وتجارب العميلات</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            تجارب حقيقية لجمال وثقة متجددة
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            سعداء بمشاركة تجارب مراجعاتنا الإيجابية ورأيهن في نتائج الجلسات.
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
                <p class="text-[11px] text-rose-600 font-medium">{{ rev.treatment }}</p>
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-100 text-rose-900 text-xs font-bold">
            <MapPin class="w-4 h-4 text-rose-700" />
            <span>فروع العيادة ومواعيد الاستقبال</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            مواقعنا وساعات العمل
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            تفضلي بزيارتنا في أقرب فرع، أو احجزي موعدك مسبقاً لتفادي الانتظار.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="clinic in tenant.clinics" 
            :key="clinic.id" 
            class="bg-slate-50 rounded-3xl p-6 border border-slate-200/90 shadow-xs hover:border-rose-400 hover:bg-rose-50/20 transition space-y-4"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="p-2.5 bg-rose-100 text-rose-700 rounded-2xl">
                  <MapPin class="w-5 h-5" />
                </div>
                <div>
                  <h4 class="text-sm font-bold text-slate-900">{{ clinic.name_ar || clinic.name }}</h4>
                  <p class="text-[11px] text-rose-600 font-medium">كشف: {{ clinic.consultation_price }} ج.م</p>
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
                <Clock class="w-3.5 h-3.5 text-rose-600 shrink-0" />
                <span>السبت إلى الخميس: 2:00 م – 10:00 م</span>
              </div>
              <div v-if="clinic.phone" class="flex items-center gap-2 text-slate-600 font-mono" dir="ltr">
                <Phone class="w-3.5 h-3.5 text-rose-600 shrink-0" />
                <a :href="`tel:${clinic.phone}`" class="hover:text-rose-600 transition">{{ clinic.phone }}</a>
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
                class="flex-1 py-2 px-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition text-center shadow-xs shadow-rose-500/20"
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-100 text-rose-900 text-xs font-bold">
            <Sparkles class="w-4 h-4 text-rose-700" />
            <span>الأسئلة الشائعة وإجابات الطبيبة</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            كل ما يهمك معرفته حول جلسات البشرة والليزر
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
                :class="['w-4 h-4 text-rose-700 shrink-0 transition-transform duration-200', openFaqIndex === fIdx ? 'rotate-180' : '']" 
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
            <div class="w-10 h-10 p-2 bg-rose-600 text-white rounded-xl flex items-center justify-center">
              <Flower2 class="w-6 h-6 text-white" />
            </div>
            <div>
              <h4 class="text-base font-bold text-white leading-snug">{{ tenant.name }}</h4>
              <p class="text-xs text-rose-400 font-medium">{{ tenant.title_ar || 'أخصائية الأمراض الجلدية والليزر والتجميل' }}</p>
            </div>
          </div>
          <p class="text-xs text-slate-400 leading-relaxed max-w-sm font-normal">
            عيادة جلدية وليزر وتجميل تخصصية مجهزة بأحدث أجهزة كانديلا وهيدرافيشل لتقديم أرقى خدمات العناية بالبشرة والشعر بأعلى معايير الأمان الطبي.
          </p>
        </div>

        <div class="md:col-span-3 space-y-3 text-xs text-slate-400">
          <h5 class="font-bold text-white text-sm">روابط سريعة</h5>
          <ul class="space-y-2">
            <li><a href="#about" class="hover:text-rose-300 transition">عن الطبيبة والخبرات</a></li>
            <li><a href="#services" class="hover:text-rose-300 transition">جلسات الليزر والتجميل</a></li>
            <li><a href="#technology" class="hover:text-rose-300 transition">الأجهزة ومعايير الأمان</a></li>
            <li><a href="#branches" class="hover:text-rose-300 transition">الفروع ومواعيد الاستقبال</a></li>
            <li><a href="#booking-section" class="hover:text-rose-300 transition">حجز جلسة أونلاين</a></li>
          </ul>
        </div>

        <div class="md:col-span-4 space-y-3 text-xs text-slate-400">
          <h5 class="font-bold text-white text-sm">التواصل والاستقبال</h5>
          <p class="text-slate-300">مواعيد العيادة: السبت إلى الخميس: 2:00 م – 10:00 م</p>
          <div v-if="tenant.clinics?.[0]?.phone" class="font-mono text-rose-300" dir="ltr">
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
