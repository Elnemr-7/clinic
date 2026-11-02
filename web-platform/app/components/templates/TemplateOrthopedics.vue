<script setup>
import { ref } from 'vue';
import { 
  Sparkles, Award, MapPin, ShieldCheck, CheckCircle2, Clock, Calendar, 
  Phone, Star, Activity, Users, ArrowDown, ChevronRight, Bone, Zap, Shield,
  ChevronDown, Check, ExternalLink, MessageSquare
} from 'lucide-vue-next';
import DoctorBookingForm from './DoctorBookingForm.vue';

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
});

const orthoServices = [
  { 
    id: 'joints',
    name: 'علاج خشونة المفاصل واحتكاك الركبة بالحقن الموضعي', 
    duration: 'جلسة حقن سريعة وتأهيل', 
    badge: 'تخفيف فوري ومستدام للألم', 
    desc: 'حقن الهيالورونيك عالي اللزوجة، البلازما الغنية بالصفائح (PRP)، والتردد الحراري لتسكين ألم المفاصل وتأخير أو تجنب الحاجة للتدخل الجراحي.',
    points: ['حقن زلالي معتمد من كبرى الشركات العالمية', 'تقليل احتكاك العظام وتسهيل الحركة والمشي', 'بروتوكول تمارين علاج طبيعي لتقوية العضلات']
  },
  { 
    id: 'arthroscopy',
    name: 'مناظير المفاصل وإصلاح الرباط الصليبي والغضروف الهلالي', 
    duration: 'جراحة اليوم الواحد بالمنظار', 
    badge: 'تعافي سريع وعودة للرياضة', 
    desc: 'إعادة بناء الرباط الصليبي الأمامي (ACL) وخياطة أو تهذيب الغضاريف عبر كاميرا المنظار الدقيقة دون فتح جراحي كبير وبأعلى درجات الدقة.',
    points: ['تدخل جراحي طفيف عبر ثقوب مليمترية', 'برنامج تأهيل حركي خاص للرياضيين', 'خروج من المستشفى في نفس اليوم']
  },
  { 
    id: 'replacement',
    name: 'جراحات استبدال المفاصل الصناعية (الركبة والحوض)', 
    duration: 'تقنية ملاحية دقيقة', 
    badge: 'مفاصل Zimmer الأمريكية الأصلية', 
    desc: 'تغيير مفصل الركبة والحوض بأحدث المفاصل المصنوعة من التيتانيوم والسيراميك المقاوم للتآكل لاستعادة المشي الطبيعي تماماً بدون ألم.',
    points: ['مفاصل متطورة تدوم لأكثر من 25 عاماً', 'بروتوكول المشي في نفس يوم العملية', 'تسكين عصبي موضعي متقدم يمنع ألم ما بعد الجراحة']
  },
  { 
    id: 'spine',
    name: 'علاج آلام العمود الفقري والانزلاق الغضروفي (عرق النسا)', 
    duration: 'علاج تحفظي وتداخلي', 
    badge: 'تسكين دائم للأعصاب', 
    desc: 'حقن جذور الأعصاب الموجه بالأشعة، التردد الحراري، وعلاج ضيق القناة العصبية وعرق النسا بدون جراحة مفتوحة للعودة لممارسة الحياة براحة.',
    points: ['علاج موجه بدقة مليمترية تحت الأشعة', 'تخفيف تنميل وخدر الساقين والظهر', 'تجنب الجراحات الكبرى في 85% من الحالات']
  },
  { 
    id: 'fractures',
    name: 'علاج الكسور المعقدة وإصابات الملاعب الحادة', 
    duration: 'تثبيت متطور عالي الدقة', 
    badge: 'التئام سليم وسريع', 
    desc: 'تثبيت الكسور بالشرائح والمسامير متناهية الصغر من التيتانيوم الخامل حيوياً مع برامج تأهيل حركي مبكر لمنع تيبس المفاصل وضعف العضلات.',
    points: ['رد الكسور والتثبيت التشريحي الدقيق', 'جبائر هوائية حديثة مريحة وخفيفة الوزن', 'متابعة دورية بالأشعة حتى اكتمال الالتئام']
  },
  { 
    id: 'pediatric-ortho',
    name: 'علاج تشوهات عظام الأطفال والقدم المسطحة (Flatfoot)', 
    duration: 'تقويم مبكر غير جراحي', 
    badge: 'نمو حركي سليم ومتناسق', 
    desc: 'تشخيص وتصحيح تقوس الساقين، خلع الورك الولادي، وتشوهات القدم عند الأطفال بالدعامات الطبية الحديثة والتدخلات المحدودة.',
    points: ['فحص اتزان المشية والمفاصل للطفل', 'دعامات وأحذية طبية مصممة خصيصاً', 'علاج مبكر يضمن نمواً طبيعياً خالياً من التشوهات']
  },
];

const pillars = [
  { title: 'أولوية العلاج التحفظي أولاً', desc: 'نبدأ دائماً بالخيارات غير الجراحية، العلاج الطبيعي، والحقن الموضعي قبل طرح أي خيار جراحي.', tag: 'Conservative First' },
  { title: 'مفاصل أمريكية تدوم +25 سنة', desc: 'نعتمد حصرياً مفاصل Zimmer & Stryker المصنوعة من التيتانيوم والسيراميك عالي النقاء.', tag: 'US Implants' },
  { title: 'بروتوكول المشي السريع', desc: 'بفضل التخدير العصبي المتطور، يبدأ مريض تغيير المفصل في المشي بعد ساعات قليلة من العملية.', tag: 'Rapid Recovery' },
  { title: 'تأهيل ومتابعة حركية مستمرة', desc: 'إشراف مباشر على مراحل العلاج الطبيعي والتأهيل الحركي حتى استعادة الحركة والمرونة الكاملة.', tag: 'Full Mobility' },
];

const reviews = [
  {
    name: 'الحاج إبراهيم منصور',
    treatment: 'عملية استبدال مفصل الركبة',
    comment: 'كنت عاجز عن المشي والصلاة بسبب خشونة الركبة، بعد العملية مع الدكتور وتغيير المفصل قدرت أمشي في نفس اليوم والحمد لله الألم اختفى تماماً ورجعت لحياتي الطبيعية.',
    rating: 5,
    date: 'منذ أسبوعين'
  },
  {
    name: 'ك. حسام عبد الرازق',
    treatment: 'منظار الركبة وإعادة بناء الرباط الصليبي',
    comment: 'اتصابت بالرباط الصليبي في ماتش كورة، الدكتور عملي العملية بالمنظار بأعلى احترافية ونظم لي برنامج التأهيل ودلوقتي رجعت للملعب بكامل قوتي وثقتي.',
    rating: 5,
    date: 'منذ شهر'
  },
  {
    name: 'أ. طارق عبد الحميد',
    treatment: 'علاج الانزلاق الغضروفي بالتردد الحراري',
    comment: 'عانيت من عرق النسا وألم الظهر لسنتين وكنت خايف من الجراحة، الدكتور عملي حقن بالتردد الحراري في نص ساعة والألم راح بنسبة 95%. شكراً جزيلاً.',
    rating: 5,
    date: 'منذ 3 أسابيع'
  },
];

const faqs = [
  { 
    q: 'متى يصبح تغيير مفصل الركبة أو الحوض ضرورياً؟', 
    a: 'يتم اللجوء لتغيير المفصل عندما تتآكل غضاريف الركبة أو الحوض تماماً وتصبح الآلام مستمرة ومقيدة للمشي والأنشطة اليومية والنوم بعد فشل العلاج الدوائي والحقن التحفظي.',
    open: true 
  },
  { 
    q: 'كم يستغرق التأهيل بعد جراحة الرباط الصليبي بالمنظار؟', 
    a: 'يبدأ المشي والتمارين الخفيفة خلال الأيام الأولى، بينما يبدأ الرياضي بالجري الخفيف بعد 3 إلى 4 أشهر، والعودة الكاملة للمباريات التنافسية بعد 6 إلى 8 أشهر تحت إشراف التأهيل.',
    open: false 
  },
  { 
    q: 'هل حقن البلازما (PRP) والزلال فعالة في علاج خشونة الركبة؟', 
    a: 'نعم بالتأكيد، أثبتت الدراسات الدولية فعاليتها الكبيرة في تقليل الالتهاب وتسكين الألم وتسهيل حركة المفصل لعدة شهور، خاصة في المراحل الأولى والمتوسطة من الخشونة.',
    open: false 
  },
  { 
    q: 'كيف يمكنني حجز موعد كشف واستشارة عظام؟', 
    a: 'يمكنك اختيار الفرع والتاريخ والوردية المناسبة عبر استمارة الحجز في هذه الصفحة لتأكيد دورك فوراً واستلام رسالة التأكيد عبر واتساب.',
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
  <div class="min-h-screen bg-slate-50 text-slate-800 selection:bg-blue-100 selection:text-blue-900" dir="rtl">
    <!-- Top Utility Bar: Working Hours & Contact -->
    <div class="bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 text-white text-xs py-2 px-4 border-b border-blue-900/50">
      <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="flex items-center gap-4 text-slate-300">
          <div class="flex items-center gap-1.5">
            <Clock class="w-3.5 h-3.5 text-blue-400 shrink-0" />
            <span>مواعيد العيادة: السبت إلى الخميس (فترات صباحية ومسائية)</span>
          </div>
          <span class="hidden md:inline text-slate-600">•</span>
          <div class="hidden md:flex items-center gap-1.5">
            <ShieldCheck class="w-3.5 h-3.5 text-emerald-400 shrink-0" />
            <span>مفاصل أمريكية معتمدة وبروتوكول المشي السريع</span>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <a 
            v-if="tenant.whatsapp_phone_number" 
            :href="`https://wa.me/2${tenant.whatsapp_phone_number}?text=${encodeURIComponent('مرحباً دكتور، أود الاستفسار عن كشف العظام والمفاصل وحجز موعد')}`" 
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
            class="inline-flex items-center gap-1 text-blue-300 hover:text-blue-200 transition font-mono"
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
          <div class="w-11 h-11 p-2 bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-2xl shadow-md shadow-blue-500/20 flex items-center justify-center shrink-0">
            <Bone class="w-6 h-6 text-white" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-bold text-slate-900 leading-snug">{{ tenant.name }}</h1>
              <span class="hidden sm:inline-block px-2.5 py-0.5 bg-blue-50 text-blue-800 text-[11px] font-bold rounded-full border border-blue-200">
                عظام ومفاصل ومناظير
              </span>
            </div>
            <p class="text-xs text-blue-700 font-semibold mt-0.5">
              {{ tenant.title_ar || 'استشاري جراحة العظام والمفاصل الصناعية والمناظير والعمود الفقري' }}
            </p>
          </div>
        </div>

        <!-- Navigation Links -->
        <nav class="hidden lg:flex items-center gap-6 text-xs sm:text-sm font-semibold text-slate-600">
          <a href="#about" class="hover:text-blue-700 transition">عن الطبيب</a>
          <a href="#services" class="hover:text-blue-700 transition">جراحات وعلاجات المفاصل</a>
          <a href="#technology" class="hover:text-blue-700 transition">المفاصل والتقنيات</a>
          <a href="#testimonials" class="hover:text-blue-700 transition">آراء المرضى</a>
          <a href="#branches" class="hover:text-blue-700 transition">الفروع والمواعيد</a>
          <a href="#faq" class="hover:text-blue-700 transition">الأسئلة الشائعة</a>
        </nav>

        <!-- CTA Header Button -->
        <a 
          href="#booking-section" 
          class="px-4 sm:px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white rounded-xl text-xs sm:text-sm font-bold transition shadow-md shadow-blue-500/25 flex items-center gap-2 active:scale-95"
        >
          <Calendar class="w-4 h-4" />
          <span>حجز كشف عظام</span>
        </a>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative py-10 lg:py-16 px-4 sm:px-6 bg-gradient-to-b from-blue-50/60 via-indigo-50/20 to-slate-50 overflow-hidden">
      <div class="absolute -top-32 -left-32 w-96 h-96 bg-blue-200/40 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute top-1/2 -right-32 w-96 h-96 bg-indigo-200/30 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start relative z-10">
        <!-- Hero Left Column: Authority & Copy -->
        <div class="lg:col-span-7 space-y-6">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-100 text-blue-900 text-xs sm:text-sm font-bold border border-blue-200 shadow-xs">
            <Bone class="w-4 h-4 text-blue-700 shrink-0" />
            <span>حياة نشطة خالية من الألم • أحدث تقنيات علاج المفاصل والعمود الفقري</span>
          </div>

          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 leading-[1.3] tracking-tight">
            استعد حريتك في الحركة وممارسة حياتك بدون ألم بأعلى خبرة جراحية وتقنيات عالمية
          </h2>

          <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-xl font-normal">
            نساعدك على التخلص من آلام المفاصل والخشونة والانزلاق الغضروفي، بدءاً من أحدث العلاجات التحفظية والحقن الموضعي، وحتى جراحات المناظير والمفاصل الصناعية بأسرع فترة تعافي.
          </p>

          <!-- Authority Metrics (4 Badges) -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
            <div class="p-3 bg-white rounded-2xl border border-blue-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-blue-700">+18</span>
              <span class="text-xs font-semibold text-slate-600">سنة خبرة جراحية</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-blue-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-blue-700">+8,000</span>
              <span class="text-xs font-semibold text-slate-600">جراحة ناجحة</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-blue-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-blue-700">99.5%</span>
              <span class="text-xs font-semibold text-slate-600">نسبة استعادة الحركة</span>
            </div>
            <div class="p-3 bg-white rounded-2xl border border-blue-100 shadow-xs text-center">
              <span class="block text-2xl font-black text-blue-700">100%</span>
              <span class="text-xs font-semibold text-slate-600">مفاصل أصلية</span>
            </div>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex flex-wrap items-center gap-3 pt-2">
            <a 
              href="#booking-section" 
              class="px-6 py-3.5 bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white rounded-2xl text-xs sm:text-sm font-bold shadow-lg shadow-blue-500/25 transition active:scale-95 flex items-center gap-2"
            >
              <Calendar class="w-4 h-4" />
              <span>احجز كشفك الآن أونلاين</span>
            </a>

            <a 
              v-if="tenant.whatsapp_phone_number" 
              :href="`https://wa.me/2${tenant.whatsapp_phone_number}?text=${encodeURIComponent('مرحباً دكتور، أود الاستفسار عن كشف العظام والمفاصل وحجز موعد')}`" 
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
              <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl shrink-0">
                <Bone class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">مفاصل Zimmer الأمريكية</p>
                <p class="text-[11px] text-slate-500 font-medium">عمر افتراضي يتجاوز 25 سنة</p>
              </div>
            </div>

            <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
              <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl shrink-0">
                <ShieldCheck class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">المشي في نفس اليوم</p>
                <p class="text-[11px] text-slate-500 font-medium">بروتوكول تعافي حركي سريع</p>
              </div>
            </div>

            <div class="p-3.5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
              <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl shrink-0">
                <Award class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 leading-snug">خبرة جراحية دولية</p>
                <p class="text-[11px] text-slate-500 font-medium">مناظير مفاصل وإصابات ملاعب</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Hero Right Column: Booking Container with safe scroll offset -->
        <div id="booking-section" class="lg:col-span-5 scroll-mt-28">
          <div class="relative">
            <div class="absolute -inset-2 bg-gradient-to-r from-blue-600 to-indigo-700 rounded-3xl blur-xl opacity-20"></div>
            <div class="relative">
              <DoctorBookingForm :tenant="tenant" accent-color="blue" />
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
            <div class="relative rounded-3xl bg-gradient-to-tr from-slate-900 via-blue-950 to-slate-900 p-8 text-white shadow-xl overflow-hidden">
              <div class="absolute -bottom-10 -right-10 w-48 h-48 bg-blue-500/20 rounded-full blur-2xl"></div>
              <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20">
                  <Bone class="w-10 h-10 text-blue-300" />
                </div>
                <div>
                  <h3 class="text-xl font-bold">{{ tenant.name }}</h3>
                  <p class="text-xs text-blue-300 font-medium mt-1">{{ tenant.title_ar || 'استشاري جراحة العظام والمفاصل الصناعية والمناظير' }}</p>
                </div>
              </div>

              <div class="space-y-3 text-xs leading-relaxed text-slate-200">
                <p>
                  {{ tenant.website_settings?.about_text || 'خبرة سريرية وأكاديمية تفوق 18 عاماً في جراحات تغيير المفاصل الصناعية المتقدمة، مناظير الركبة والكتف، وعلاج كسور العظام المعقدة والانزلاق الغضروفي بأحدث التقنيات التداخلية الدقيقة.' }}
                </p>
                <div class="pt-3 border-t border-white/15 space-y-2">
                  <div class="flex items-center gap-2 text-blue-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-blue-400" />
                    <span>عضو الجمعية السويسرية لتثبيت الكسور وجراحة العظام (AO Spine)</span>
                  </div>
                  <div class="flex items-center gap-2 text-blue-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-blue-400" />
                    <span>زمالة جراحة المفاصل الصناعية وإصابات الملاعب المتقدمة</span>
                  </div>
                  <div class="flex items-center gap-2 text-blue-200">
                    <CheckCircle2 class="w-4 h-4 shrink-0 text-blue-400" />
                    <span>أكثر من 8,000 عملية ناجحة لاستبدال مفاصل الركبة والحوض</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="lg:col-span-7 space-y-5">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-800 text-xs font-bold border border-blue-200">
              <Award class="w-4 h-4 text-blue-600" />
              <span>فلسفة العلاج واستعادة الحركة</span>
            </div>

            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
              هدفنا استعادة قدرتك على المشي والحركة الطبيعية بأقل تدخل جراحي ممكن
            </h3>

            <p class="text-sm text-slate-600 leading-relaxed">
              نؤمن بأن الجراحة هي الخيار الأخير؛ لذلك نستنفد كافة الحلول التحفظية والتقنيات الحديثة مثل الحقن الزلالي وحقن البلازما والتردد الحراري. وعندما تكون الجراحة حتمية، نطبق أحدث تقنيات الجراحة طفيفة التوغل والملاحة الدقيقة لضمان أعلى نسب نجاح وأسرع فترة تعافي.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                  <ShieldCheck class="w-4 h-4 text-blue-600" />
                  <span>دقة جراحية متناهية</span>
                </h4>
                <p class="text-xs text-slate-500 leading-relaxed">استخدام أحدث أجهزة الملاحة والمناظير ثلاثية الأبعاد بدقة المليمتر.</p>
              </div>

              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                  <Activity class="w-4 h-4 text-blue-600" />
                  <span>برنامج تأهيلي متكامل</span>
                </h4>
                <p class="text-xs text-slate-500 leading-relaxed">متابعة مستمرة مع أخصائي العلاج الطبيعي حتى استعادة المرونة والقوة العضلية.</p>
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 text-blue-900 text-xs font-bold">
            <Bone class="w-4 h-4 text-blue-700" />
            <span>جراحات وعلاجات العظام والمفاصل</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            حلول تخصصية متكاملة لآلام المفاصل والعمود الفقري
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            اختر الإجراء أو الفحص المطلوب وسجل موعد كشفك للحصول على تقييم سريري وإشعاعي شامل.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="item in orthoServices" 
            :key="item.id" 
            class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs hover:border-blue-400 hover:shadow-lg hover:shadow-blue-500/10 transition flex flex-col justify-between group"
          >
            <div class="space-y-4">
              <div class="flex items-start justify-between gap-3">
                <span class="px-3 py-1 bg-blue-50 text-blue-800 text-[11px] font-bold rounded-xl border border-blue-100">
                  {{ item.badge }}
                </span>
                <span class="text-xs text-slate-500 font-medium flex items-center gap-1">
                  <Clock class="w-3.5 h-3.5 text-slate-400" />
                  {{ item.duration }}
                </span>
              </div>

              <div>
                <h4 class="text-base font-bold text-slate-900 group-hover:text-blue-700 transition leading-snug">
                  {{ item.name }}
                </h4>
                <p class="text-xs text-slate-600 leading-relaxed mt-2">
                  {{ item.desc }}
                </p>
              </div>

              <div class="space-y-1.5 pt-2 border-t border-slate-100">
                <div v-for="(p, pIdx) in item.points" :key="pIdx" class="flex items-center gap-2 text-xs text-slate-700">
                  <Check class="w-3.5 h-3.5 text-blue-600 shrink-0" />
                  <span>{{ p }}</span>
                </div>
              </div>
            </div>

            <div class="pt-5 mt-4 border-t border-slate-100 flex items-center justify-between">
              <button 
                @click="scrollToBooking(item.name)" 
                class="w-full py-2.5 px-4 bg-slate-100 hover:bg-blue-600 hover:text-white text-slate-800 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 group-hover:bg-blue-600 group-hover:text-white shadow-xs"
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
    <section id="technology" class="py-16 px-4 sm:px-6 bg-gradient-to-b from-white to-blue-50/40 border-y border-slate-200/80">
      <div class="max-w-7xl mx-auto space-y-10">
        <div class="text-center max-w-2xl mx-auto space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 text-blue-900 text-xs font-bold">
            <ShieldCheck class="w-4 h-4 text-blue-700" />
            <span>المفاصل والتجهيزات العالمية</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            أحدث المفاصل الصناعية والمناظير الجراحية
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            نعتمد حصرياً أحدث المنتجات والتقنيات الطبية المعتمدة من هيئة الغذاء والدواء الأمريكية لضمان أمان يدوم عقوداً.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div 
            v-for="(tech, tIdx) in pillars" 
            :key="tIdx"
            class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs hover:border-blue-300 hover:shadow-md transition space-y-3"
          >
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-sm">
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 text-blue-900 text-xs font-bold">
            <Star class="w-4 h-4 text-blue-700 fill-blue-700" />
            <span>آراء وتجارب مرضانا</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            قصص نجاح واستعادة لحرية الحركة والمشي
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            تجارب حقيقية لمرضى استعادوا قدرتهم على المشي وممارسة حياتهم بعد العلاج داخل عياداتنا.
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
                <p class="text-[11px] text-blue-700 font-medium">{{ rev.treatment }}</p>
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 text-blue-900 text-xs font-bold">
            <MapPin class="w-4 h-4 text-blue-700" />
            <span>فروع العيادة ومواعيد العمل</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            مواقعنا وساعات الاستقبال
          </h3>
          <p class="text-xs sm:text-sm text-slate-600">
            اختر أقرب فرع إليك للاطلاع على مواعيد الاستقبال أو حجز موعد كشفك فوراً.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="clinic in tenant.clinics" 
            :key="clinic.id" 
            class="bg-slate-50 rounded-3xl p-6 border border-slate-200/90 shadow-xs hover:border-blue-400 hover:bg-blue-50/20 transition space-y-4"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="p-2.5 bg-blue-100 text-blue-700 rounded-2xl">
                  <MapPin class="w-5 h-5" />
                </div>
                <div>
                  <h4 class="text-sm font-bold text-slate-900">{{ clinic.name_ar || clinic.name }}</h4>
                  <p class="text-[11px] text-blue-700 font-medium">كشف: {{ clinic.consultation_price }} ج.م</p>
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
                <Clock class="w-3.5 h-3.5 text-blue-600 shrink-0" />
                <span>السبت إلى الخميس: فترات صباحية ومسائية</span>
              </div>
              <div v-if="clinic.phone" class="flex items-center gap-2 text-slate-600 font-mono" dir="ltr">
                <Phone class="w-3.5 h-3.5 text-blue-600 shrink-0" />
                <a :href="`tel:${clinic.phone}`" class="hover:text-blue-700 transition">{{ clinic.phone }}</a>
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
                class="flex-1 py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition text-center shadow-xs shadow-blue-500/20"
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
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 text-blue-900 text-xs font-bold">
            <Sparkles class="w-4 h-4 text-blue-700" />
            <span>الأسئلة الشائعة وإجابات الطبيب</span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
            كل ما يهمك معرفته حول صحة العظام والمفاصل
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
                :class="['w-4 h-4 text-blue-700 shrink-0 transition-transform duration-200', openFaqIndex === fIdx ? 'rotate-180' : '']" 
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
            <div class="w-10 h-10 p-2 bg-blue-600 text-white rounded-xl flex items-center justify-center">
              <Bone class="w-6 h-6 text-white" />
            </div>
            <div>
              <h4 class="text-base font-bold text-white leading-snug">{{ tenant.name }}</h4>
              <p class="text-xs text-blue-400 font-medium">{{ tenant.title_ar || 'استشاري جراحة العظام والمفاصل الصناعية' }}</p>
            </div>
          </div>
          <p class="text-xs text-slate-400 leading-relaxed max-w-sm font-normal">
            عيادة عظام ومفاصل متخصصة ومجهزة بأحدث مناظير المفاصل وأحدث المفاصل الصناعية الأمريكية لتقديم أعلى مستويات الرعاية الصحية واستعادة حرية الحركة بدون ألم.
          </p>
        </div>

        <div class="md:col-span-3 space-y-3 text-xs text-slate-400">
          <h5 class="font-bold text-white text-sm">روابط سريعة</h5>
          <ul class="space-y-2">
            <li><a href="#about" class="hover:text-blue-300 transition">عن الطبيب والخبرات</a></li>
            <li><a href="#services" class="hover:text-blue-300 transition">جراحات وعلاجات المفاصل</a></li>
            <li><a href="#technology" class="hover:text-blue-300 transition">المفاصل الصناعية الأمريكية</a></li>
            <li><a href="#branches" class="hover:text-blue-300 transition">الفروع ومواعيد الاستقبال</a></li>
            <li><a href="#booking-section" class="hover:text-blue-300 transition">حجز كشف أونلاين</a></li>
          </ul>
        </div>

        <div class="md:col-span-4 space-y-3 text-xs text-slate-400">
          <h5 class="font-bold text-white text-sm">التواصل والاستقبال</h5>
          <p class="text-slate-300">مواعيد العيادة: السبت إلى الخميس (صباحاً ومساءً)</p>
          <div v-if="tenant.clinics?.[0]?.phone" class="font-mono text-blue-300" dir="ltr">
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
