<script setup>
import { ref } from 'vue';
import { 
  Sparkles, Award, MapPin, ShieldCheck, CheckCircle2, Clock, Calendar, 
  Phone, Star, Heart, Activity, Users, ArrowDown, ChevronRight, Baby, Smile, 
  Stethoscope, ChevronDown, Check, ExternalLink, MessageSquare, Shield
} from 'lucide-vue-next';
import DoctorBookingForm from './DoctorBookingForm.vue';

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
});

const pediatricsServices = [
  { 
    id: 'growth',
    name: 'متابعة النمو والتطور الحركي والذهني للطفل', 
    duration: 'فحص سريري شامل', 
    badge: 'معايير منظمة الصحة WHO', 
    desc: 'قياس دقيق للوزن والطول ومحيط الرأس ومقارنتها بمنحنيات النمو الدولية لاكتشاف وعلاج أي تأخر حركي أو نطقي مبكراً.',
    points: ['تقييم التطور الحركي والتواصلي', 'متابعة تطور الحواس والتركيز', 'جدول قياسات رقمي يسجل في كل زيارة']
  },
  { 
    id: 'vaccines',
    name: 'جدول التطعيمات الإضافية والأساسية المعتمدة', 
    duration: 'وفق جدول عمر الطفل', 
    badge: 'سلسلة تبريد دوائية إلكترونية', 
    desc: 'تطعيمات الروتا، المكورات الرئوية، الجديري المائي، والحمى الشوكية بأعلى معايير الحفظ وسلسلة التبريد لضمان أعلى مناعة.',
    points: ['لقاحات أصلية من كبرى الشركات العالمية', 'شهادة تطعيمات رسمية موثقة', 'نصائح وقائية للتعامل مع أعراض ما بعد التطعيم']
  },
  { 
    id: 'newborn',
    name: 'فحص الأطفال حديثي الولادة والمبتسرين', 
    duration: 'فحص شامل فوري', 
    badge: 'رعاية دقيقة للمواليد', 
    desc: 'متابعة نسب الصفراء بدون وخز، فحص السمع، التغذية السليمة، والتعامل مع ارتجاع المريء والغازات والانتفاخات عند الرضع.',
    points: ['قياس الصفراء عبر الجلد بدون عينة دم', 'إرشادات ودعم الرضاعة الطبيعية', 'فحص سلامة المفاصل والقلب والعمود الفقري']
  },
  { 
    id: 'allergy',
    name: 'علاج حساسية الصدر والربو والنزلات الشعبية', 
    duration: 'جلسات استنشاق متطورة', 
    badge: 'بروتوكولات وقائية حديثة', 
    desc: 'برامج علاجية حديثة للسيطرة على كتمة الصدر والكحة الليلية المتكررة مع جلسات نيبولايزر هادئة ومريحة تزيل الضيق التنفسي.',
    points: ['أجهزة استنشاق صامتة مخصصة للأطفال', 'علاج حساسية الأنف والجيوب الأنفية', 'خطة طوارئ منزلية للتعامل مع النوبات']
  },
  { 
    id: 'digestive',
    name: 'علاج النزلات المعوية وسوء الامتصاص وحساسية اللبن', 
    duration: 'علاج عاجل ووقاية من الجفاف', 
    badge: 'أمان وسرعة تعافي', 
    desc: 'بروتوكولات علاج الإسهال الحاد والقيء والوقاية من الجفاف، وتشخيص وعلاج حساسية بروتين لبن الأبقار والأطعمة.',
    points: ['محاليل تعويض الجفاف السريعة', 'اختبارات حساسية اللبن واللاكتوز', 'تنظيم تغذية الرضيع واختيار اللبن العلاجي']
  },
  { 
    id: 'nutrition',
    name: 'علاج الأنيميا وفقدان الشهية والتغذية السليمة', 
    duration: 'متابعة دورية مستمرة', 
    badge: 'صحة ومناعة قوية', 
    desc: 'تأسيس نظام غذائي صحي وممتع للطفل لعلاج نقص الحديد والكالسيوم وفيتامين د، وزيادة الوزن للطفل النحيف بصورة متوازنة.',
    points: ['تحاليل دم شاملة بدون ألم', 'فيتامينات ومكملات غذائية ممتعة بطعم الفواكه', 'إرشادات عملية للأمهات للتعامل مع رفض الطعام']
  },
];

const pillars = [
  { title: 'بيئة مرحة بدون خوف', desc: 'عيادة مجهزة بألوان مبهجة وألعاب ترفيهية تجعل زيارة الطفل للطبيب تجربة مريحة وسعيدة خالية من البكاء.', tag: 'Kid-Friendly' },
  { title: 'سلسلة تبريد دوائية 24/7', desc: 'حفظ وتخزين اللقاحات في ثلاجات طبية إلكترونية مزودة بمراقبة حرارة رقمية دقيقة.', tag: 'Cold Chain Certified' },
  { title: 'أولوية قصوى للطوارئ', desc: 'استقبال فوري وعناية فائقة للحالات الحرجة كارتفاع الحرارة الحاد والتشنجات والضيق التنفسي.', tag: 'Urgent Care' },
  { title: 'دعم وإرشاد كامل للأم', desc: 'وقت كافٍ للإجابة على جميع تساؤلاتك حول الرضاعة والنوم والتطور السلوكي لطفلك.', tag: 'Mother Support' },
];

const reviews = [
  {
    name: 'أ. سارة مصطفى (أم يوسف)',
    treatment: 'متابعة حديثي ولادة وتطعيمات',
    comment: 'دكتور ممتاز وصبور جداً! فحص ابني من أول يوم ولادة ونصحني بكل تفاصيل الرضاعة والتعامل مع الغازات، والتطعيمات بتتاخد في بيئة معقمة وهادية.',
    rating: 5,
    date: 'منذ أسبوعين'
  },
  {
    name: 'م. تامر الجندي (والد كرمة)',
    treatment: 'علاج حساسية الصدر والنزلات الشعبية',
    comment: 'بنتي كانت بتكح طول الليل وبنعاني مع كل تغيير فصل، الدكتور شخص حساسيتها بدقة وكتب بروتوكول وقائي ريحها جداً وبطلت تتعب زي الأول.',
    rating: 5,
    date: 'منذ شهر'
  },
  {
    name: 'أ. هبة علام (أم فريدة)',
    treatment: 'متابعة النمو وعلاج الأنيميا',
    comment: 'العيادة مبهجة جداً والأطفال مش بيخافوا خالص. الدكتور بيتعامل مع بنتي كأنها بنته وفحصها بكل رقة ووزنها اتحسن جداً بعد خطة التغذية.',
    rating: 5,
    date: 'منذ 3 أسابيع'
  },
];

const faqs = [
  { 
    q: 'متى يجب البدء في إعطاء الطفل الأطعمة الصلبة؟', 
    a: 'يُفضل إدخال الأطعمة الصلبة تدريجياً في الشهر السادس بعد استشارة الطبيب للتأكد من قدرة الطفل على الجلوس وثبات الرقبة واكتمال جاهزية جهازه الهضمي.',
    open: true 
  },
  { 
    q: 'كيف أتعامل مع ارتفاع درجة حرارة طفلي المفاجئ؟', 
    a: 'يتم استخدام كمادات ماء فاتر وتناول خافض الحرارة المناسب لوزن الطفل بالضبط مع تجنب إعطاء الأسبرين تماماً، ومراجعة العيادة فوراً إذا استمرت الحرارة لأكثر من 24 ساعة.',
    open: false 
  },
  { 
    q: 'هل التطعيمات الإضافية ضرورية للطفل؟', 
    a: 'نعم، التطعيمات الإضافية مثل الروتا والمكورات الرئوية توفر حماية حيوية ضد أشد أنواع النزلات المعوية الجافة والالتهابات الرئوية البكتيرية الشائعة عند الرضع.',
    open: false 
  },
  { 
    q: 'كيف يمكنني حجز موعد كشف لطفلي لتجنب الانتظار الطويل؟', 
    a: 'يمكنك اختيار الفرع والتاريخ والوردية المناسبة عبر استمارة الحجز في هذه الصفحة، وسيظهر لك رقم دورك فوراً لتقليل فترة مكوث طفلك بالعيادة.',
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
    <div class="bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 text-white text-xs py-2 px-4 border-b border-emerald-900/50">
      <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="flex items-center gap-4 text-slate-300">
          <div class="flex items-center gap-1.5">
            <Clock class="w-3.5 h-3.5 text-emerald-400 shrink-0" />
            <span>مواعيد العيادة: السبت إلى الخميس (فترات صباحية ومسائية)</span>
          </div>
          <span class="hidden md:inline text-slate-600">•</span>
          <div class="hidden md:flex items-center gap-1.5">
            <ShieldCheck class="w-3.5 h-3.5 text-emerald-400 shrink-0" />
            <span>سلسلة تبريد إلكترونية للقاحات ورعاية حديثي الولادة</span>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <a 
            v-if="tenant.whatsapp_phone_number" 
            :href="`https://wa.me/2${tenant.whatsapp_phone_number}?text=${encodeURIComponent('مرحباً دكتور، أود الاستفسار عن كشف الأطفال وحديثي الولادة وحجز موعد')}`" 
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
            class="inline-flex items-center gap-1 text-emerald-300 hover:text-emerald-200 transition font-mono"
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
          <div class="w-11 h-11 p-2 bg-gradient-to-br from-emerald-500 to-teal-600 text-white rounded-2xl shadow-md shadow-emerald-500/20 flex items-center justify-center shrink-0">
            <Baby class="w-6 h-6 text-white" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-bold text-slate-900 leading-snug">{{ tenant.name }}</h1>
              <span class="hidden sm:inline-block px-2.5 py-0.5 bg-emerald-50 text-emerald-800 text-[11px] font-bold rounded-full border border-emerald-200">
                أطفال وحديثي ولادة
              </span>
            </div>
            <p class="text-xs text-emerald-700 font-semibold mt-0.5">
              {{ tenant.title_ar || 'استشاري طب الأطفال وحديثي الولادة والمبتسرين' }}
            </p>
          </div>
        </div>

        <!-- Navigation Links -->
        <nav class="hidden lg:flex items-center gap-6 text-xs sm:text-sm font-semibold text-slate-600">
          <a href="#about" class="hover:text-emerald-700 transition">عن الطبيب</a>
          <a href="#services" class="hover:text-emerald-700 transition">الفحوصات والتطعيمات</a>
          <a href="#technology" class="hover:text-emerald-700 transition">معايير العيادة</a>
          <a href="#testimonials" class="hover:text-emerald-700 transition">آراء الأمهات</a>
          <a href="#branches" class="hover:text-emerald-700 transition">الفروع والمواعيد</a>
          <a href="#faq" class="hover:text-emerald-700 transition">الأسئلة الشائعة</a>
        </nav>

        <!-- CTA Header Button -->
        <a 
          href="#booking-section" 
          class="px-4 sm:px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-xs sm:text-sm font-bold transition shadow-md shadow-emerald-500/25 flex items-center gap-2 active:scale-95"
        >
          <Calendar class="w-4 h-4" />
          <span>حجز كشف لطفلك</span>
        </a>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative py-10 lg:py-16 px-4 sm:px-6 bg-gradient-to-b from-emerald-50/60 via-teal-50/20 to-slate-50 overflow-hidden">
      <div class="absolute -top-32 -left-32 w-96 h-96 bg-emerald-200/40 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute top-1/2 -right-32 w-96 h-96 bg-cyan-200/30 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start relative z-10">
        <!-- Hero Left Column: Authority & Copy -->
        <div class="lg:col-span-7 space-y-6">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-100 text-emerald-900 text-xs sm:text-sm font-bold border border-emerald-200 shadow-xs">
            <Baby class="w-4 h-4 text-emerald-700 shrink-0" />
            <span>رعاية متخصصة لصحة طفلك وتطوره الحركي والذهني منذ اليوم الأول</span>
          </div>

          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 leading-[1.3] tracking-tight">
            صحة طفلك وابتسامته أمانة بين أيدٍ استشارية حنونة وخبرة علمية دقيقة
          </h2>

          <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-xl font-normal">
            نقدم رعاية طبية شاملة تبدأ من فحص حديثي الولادة والمبتسرين، مروراً بمتابعة منحنيات النمو والتطعيمات، وحتى علاج أمراض الصدر والحساسية وسوء التغذية في بيئة مبهجة خالية من الخوف.
          </p>

          <!-- Authority Metrics (4 Badges) -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
            <div class="p-3 bg-white rounded-2xl border border-emerald-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-emerald-700">+15</span>
              <span class="text-xs font-semibold text-slate-600">سنة خبرة سريرية</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-emerald-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-emerald-700">+20,000</span>
              <span class="text-xs font-semibold text-slate-600">طفل تم علاجهم</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-emerald-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-emerald-700">99.9%</span>
              <span class="text-xs font-semibold text-slate-600">رضا الأمهات</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-emerald-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-emerald-700">100%</span>
              <span class="text-xs font-semibold text-slate-600">لقاحات معتمدة</span>
            </div>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex flex-wrap items-center gap-3 pt-2">
            <a 
              href="#booking-section" 
              class="px-6 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-2xl text-xs sm:text-sm font-bold shadow-lg shadow-emerald-500/25 transition active:scale-95 flex items-center gap-2"
            >
              <Calendar class="w-4 h-4" />
              <span>احجز كشفاً لطفلك أونلاين</span>
            </a>

            <a 
              v-if="tenant.whatsapp_phone_number" 
              :href="`https://wa.me/2${tenant.whatsapp_phone_number}?text=${encodeURIComponent('مرحباً دكتور، أود الاستفسار عن كشف الأطفال وحديثي الولادة وحجز موعد')}`" 
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
              <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl shrink-0">
                <Smile class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">عيادة صديقة للطفل</p>
                <p class="text-[11px] text-slate-500 font-medium">ألعاب وأجواء مرحة مريحة</p>
              </div>
            </div>

            <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
              <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl shrink-0">
                <ShieldCheck class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">سلسلة تبريد اللقاحات</p>
                <p class="text-[11px] text-slate-500 font-medium">حفظ رقمي دقيق للأمصال</p>
              </div>
            </div>

            <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
              <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl shrink-0">
                <Award class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">خبرة استشارية معتمدة</p>
                <p class="text-[11px] text-slate-500 font-medium">في حديثي الولادة والمبتسرين</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Hero Right Column: Booking Container with safe scroll offset -->
        <div id="booking-section" class="lg:col-span-5 scroll-mt-28">
          <div class="relative">
            <div class="absolute -inset-2 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-3xl blur-xl opacity-20"></div>
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
            <div class="relative rounded-3xl bg-gradient-to-tr from-slate-900 via-emerald-950 to-slate-900 p-8 text-white shadow-xl overflow-hidden">
              <div class="absolute -bottom-10 -right-10 w-48 h-48 bg-emerald-500/20 rounded-full blur-2xl"></div>
              <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20">
                  <Baby class="w-10 h-10 text-emerald-300" />
                </div>
                <div>
                  <h3 class="text-xl font-bold">{{ tenant.name }}</h3>
                  <p class="text-xs text-emerald-300 font-medium mt-1">{{ tenant.title_ar || 'استشاري طب الأطفال وحديثي الولادة والمبتسرين' }}</p>
                </div>
              </div>

              <div class="space-y-3 text-xs leading-relaxed text-slate-200">
                <p>
                  {{ tenant.website_settings?.about_text || 'خبرة سريرية تمتد لأكثر من 15 عاماً في رعاية الأطفال وحديثي الولادة والمبتسرين، وعضو الجمعية المصرية لطب الأطفال والجمعية الأوروبية لطب حديثي الولادة.' }}
                </p>
                <div class="pt-3 border-t border-white/15 space-y-2">
                  <div class="flex items-center gap-2 text-emerald-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-emerald-400" />
                    <span>عضو الجمعية الأوروبية لطب حديثي الولادة (ESPR)</span>
                  </div>
                  <div class="flex items-center gap-2 text-emerald-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-emerald-400" />
                    <span>استشاري الحالات الحرجة وحضانات الأطفال المبتسرين</span>
                  </div>
                  <div class="flex items-center gap-2 text-emerald-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-emerald-400" />
                    <span>برامج متخصصة لمتابعة النمو وعلاج حساسية الصدر</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="lg:col-span-7 space-y-5">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-200">
              <Award class="w-4 h-4 text-emerald-600" />
              <span>فلسفة رعاية الطفل والأسرة</span>
            </div>

            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
              نرافقكِ في كل خطوة لتربية طفل سليم معافى جسدياً ونفسياً
            </h3>

            <p class="text-sm text-slate-600 leading-relaxed">
              ندرك مدى قلق كل أم وأب على صحة طفلهم، لذلك نمنح كل طفل الوقت الكافي للفحص السريري الدقيق دون استعجال، ونتيح للأم المساحة الكاملة لطرح كل ما يدور في ذهنها من استفسارات، مع وضع خطط علاجية تحترم طبيعة جسم الطفل الرقيق.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                  <ShieldCheck class="w-4 h-4 text-emerald-600" />
                  <span>أمان دوائي صارم</span>
                </h4>
                <p class="text-xs text-slate-500 leading-relaxed">تجنب الإفراط في المضادات الحيوية واستخدام الأدوية وفق الوزن الدقيق بدقة المليجرام.</p>
              </div>

              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                  <Smile class="w-4 h-4 text-emerald-600" />
                  <span>دعم مستمر للأم</span>
                </h4>
                <p class="text-xs text-slate-500 leading-relaxed">إرشادات مفصلة وتوجيه كامل للتعامل مع نوبات البكاء، النوم، والتغذية السليمة.</p>
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-900 text-xs font-bold">
            <Baby class="w-4 h-4 text-emerald-700" />
            <span>خدمات طب الأطفال والتطعيمات</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            رعاية شاملة ومتكاملة لصحة طفلك في كل مرحلة
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            اختاري الخدمة أو الفحص المطلوب وسجلي موعد كشفك لتأكيد حضورك فوراً.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="item in pediatricsServices" 
            :key="item.id" 
            class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs hover:border-emerald-400 hover:shadow-lg hover:shadow-emerald-500/10 transition flex flex-col justify-between group"
          >
            <div class="space-y-4">
              <div class="flex items-start justify-between gap-3">
                <span class="px-3 py-1 bg-emerald-50 text-emerald-800 text-[11px] font-bold rounded-xl border border-emerald-100">
                  {{ item.badge }}
                </span>
                <span class="text-xs text-slate-500 font-medium flex items-center gap-1">
                  <Clock class="w-3.5 h-3.5 text-slate-400" />
                  {{ item.duration }}
                </span>
              </div>

              <div>
                <h4 class="text-base font-bold text-slate-900 group-hover:text-emerald-700 transition leading-snug">
                  {{ item.name }}
                </h4>
                <p class="text-xs text-slate-600 leading-relaxed mt-2">
                  {{ item.desc }}
                </p>
              </div>

              <div class="space-y-1.5 pt-2 border-t border-slate-100">
                <div v-for="(p, pIdx) in item.points" :key="pIdx" class="flex items-center gap-2 text-xs text-slate-700">
                  <Check class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                  <span>{{ p }}</span>
                </div>
              </div>
            </div>

            <div class="pt-5 mt-4 border-t border-slate-100 flex items-center justify-between">
              <button 
                @click="scrollToBooking(item.name)" 
                class="w-full py-2.5 px-4 bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-800 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 group-hover:bg-emerald-600 group-hover:text-white shadow-xs"
              >
                <span>احجز موعد كشف لهذا الإجراء</span>
                <ChevronDown class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Tech & Safety Standards -->
    <section id="technology" class="py-16 px-4 sm:px-6 bg-gradient-to-b from-white to-emerald-50/40 border-y border-slate-200/80">
      <div class="max-w-7xl mx-auto space-y-10">
        <div class="text-center max-w-2xl mx-auto space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-900 text-xs font-bold">
            <ShieldCheck class="w-4 h-4 text-emerald-700" />
            <span>معايير الأمان والرعاية الطبية</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            أعلى معايير النظافة والتعقيم لسلامة أطفالنا
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            نوفر بيئة آمنة وصحية تخضع للتعقيم المستمر ومجهزة بأحدث أدوات القياس والفحص الدقيقة.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div 
            v-for="(tech, tIdx) in pillars" 
            :key="tIdx"
            class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs hover:border-emerald-300 hover:shadow-md transition space-y-3"
          >
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-sm">
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-900 text-xs font-bold">
            <Star class="w-4 h-4 text-emerald-700 fill-emerald-700" />
            <span>آراء وتجارب الأمهات والآباء</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            ثقة العائلات هي وسام شرف نفتخر به
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            تجارب حقيقية لأمهات وآباء عهدوا إلينا بصحة أطفالهم.
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
                <p class="text-[11px] text-emerald-700 font-medium">{{ rev.treatment }}</p>
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-900 text-xs font-bold">
            <MapPin class="w-4 h-4 text-emerald-700" />
            <span>فروع العيادة ومواعيد العمل</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            مواقعنا وساعات الاستقبال
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            اختاري أقرب فرع إليكِ للاطلاع على مواعيد الاستقبال أو حجز موعد طفلكِ فوراً.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="clinic in tenant.clinics" 
            :key="clinic.id" 
            class="bg-slate-50 rounded-3xl p-6 border border-slate-200/90 shadow-xs hover:border-emerald-400 hover:bg-emerald-50/20 transition space-y-4"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="p-2.5 bg-emerald-100 text-emerald-700 rounded-2xl">
                  <MapPin class="w-5 h-5" />
                </div>
                <div>
                  <h4 class="text-sm font-bold text-slate-900">{{ clinic.name_ar || clinic.name }}</h4>
                  <p class="text-[11px] text-emerald-700 font-medium">كشف: {{ clinic.consultation_price }} ج.م</p>
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
                <Clock class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                <span>السبت إلى الخميس: فترات صباحية ومسائية</span>
              </div>
              <div v-if="clinic.phone" class="flex items-center gap-2 text-slate-600 font-mono" dir="ltr">
                <Phone class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                <a :href="`tel:${clinic.phone}`" class="hover:text-emerald-700 transition">{{ clinic.phone }}</a>
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
                class="flex-1 py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition text-center shadow-xs shadow-emerald-500/20"
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-900 text-xs font-bold">
            <Sparkles class="w-4 h-4 text-emerald-700" />
            <span>نصائح طبية وأسئلة شائعة</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            كل ما يهمك معرفته حول صحة وتغذية طفلك
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
                :class="['w-4 h-4 text-emerald-700 shrink-0 transition-transform duration-200', openFaqIndex === fIdx ? 'rotate-180' : '']" 
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
            <div class="w-10 h-10 p-2 bg-emerald-600 text-white rounded-xl flex items-center justify-center">
              <Baby class="w-6 h-6 text-white" />
            </div>
            <div>
              <h4 class="text-base font-bold text-white leading-snug">{{ tenant.name }}</h4>
              <p class="text-xs text-emerald-400 font-medium">{{ tenant.title_ar || 'استشاري طب الأطفال وحديثي الولادة' }}</p>
            </div>
          </div>
          <p class="text-xs text-slate-400 leading-relaxed max-w-sm font-normal">
            عيادة أطفال متخصصة ومجهزة بأحدث التجهيزات لفحص ومتابعة نمو الأطفال وحديثي الولادة وسلسلة التبريد المعتمدة لحفظ اللقاحات والأمصال.
          </p>
        </div>

        <div class="md:col-span-3 space-y-3 text-xs text-slate-400">
          <h5 class="font-bold text-white text-sm">روابط سريعة</h5>
          <ul class="space-y-2">
            <li><a href="#about" class="hover:text-emerald-300 transition">عن الطبيب والخبرات</a></li>
            <li><a href="#services" class="hover:text-emerald-300 transition">الفحوصات والتطعيمات</a></li>
            <li><a href="#technology" class="hover:text-emerald-300 transition">معايير الأمان والرعاية</a></li>
            <li><a href="#branches" class="hover:text-emerald-300 transition">الفروع ومواعيد الاستقبال</a></li>
            <li><a href="#booking-section" class="hover:text-emerald-300 transition">حجز كشف أونلاين</a></li>
          </ul>
        </div>

        <div class="md:col-span-4 space-y-3 text-xs text-slate-400">
          <h5 class="font-bold text-white text-sm">التواصل والاستقبال</h5>
          <p class="text-slate-300">مواعيد العيادة: السبت إلى الخميس (صباحاً ومساءً)</p>
          <div v-if="tenant.clinics?.[0]?.phone" class="font-mono text-emerald-300" dir="ltr">
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
