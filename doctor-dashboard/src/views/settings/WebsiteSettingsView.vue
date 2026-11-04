<template>
  <div class="space-y-6 text-right select-none font-sans" dir="rtl">
    <!-- Header -->
    <div class="border-b border-line-subtle pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h2 class="text-base font-black text-content-primary">4. إعدادات الموقع الإلكتروني وقناة الواتساب</h2>
        <p class="text-xs text-content-muted mt-0.5">تحكم كامل ومطلق في كل كلمة ونَص وقسم بالموقع، بالإضافة لإعدادات محركات البحث (SEO) والسوشيال ميديا</p>
      </div>

      <!-- Quick Subdomain Preview Badge -->
      <div class="flex items-center gap-2 self-start md:self-auto bg-surface-card px-3 py-1.5 rounded-xl border border-line-subtle text-xs">
        <span class="text-content-muted">الرابط المباشر:</span>
        <span class="font-mono font-bold text-brand" dir="ltr">{{ currentLiveUrl }}</span>
        <a
          :href="currentLiveUrl"
          target="_blank"
          class="p-1 rounded-lg bg-brand-subtle text-brand border border-brand-border hover:bg-brand hover:text-white transition"
          title="معاينة الموقع مباشرة"
        >
          <ExternalLink class="w-3.5 h-3.5" />
        </a>
      </div>
    </div>

    <!-- Website Settings Tabs Navigation -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 border-b border-line-subtle text-xs">
      <button
        type="button"
        @click="activeTab = 'hosting'"
        :class="[
          'px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap cursor-pointer',
          activeTab === 'hosting'
            ? 'bg-brand text-content-inverse shadow-xs'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary hover:bg-surface-card border border-line-subtle'
        ]"
      >
        <Globe class="w-4 h-4" />
        <span>1. نموذج الاستضافة والدومين</span>
      </button>

      <button
        type="button"
        @click="activeTab = 'content'"
        :class="[
          'px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap cursor-pointer',
          activeTab === 'content'
            ? 'bg-brand text-content-inverse shadow-xs'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary hover:bg-surface-card border border-line-subtle'
        ]"
      >
        <FileText class="w-4 h-4" />
        <span>2. تخصيص محتوى الموقع بالكامل (كل كلمة)</span>
      </button>

      <button
        type="button"
        @click="activeTab = 'seo'"
        :class="[
          'px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap cursor-pointer',
          activeTab === 'seo'
            ? 'bg-brand text-content-inverse shadow-xs'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary hover:bg-surface-card border border-line-subtle'
        ]"
      >
        <Search class="w-4 h-4" />
        <span>3. محركات البحث (Google SEO)</span>
      </button>

      <button
        type="button"
        @click="activeTab = 'og'"
        :class="[
          'px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap cursor-pointer',
          activeTab === 'og'
            ? 'bg-brand text-content-inverse shadow-xs'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary hover:bg-surface-card border border-line-subtle'
        ]"
      >
        <Share2 class="w-4 h-4" />
        <span>4. السوشيال ميديا (Open Graph)</span>
      </button>

      <button
        type="button"
        @click="activeTab = 'whatsapp'"
        :class="[
          'px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap cursor-pointer',
          activeTab === 'whatsapp'
            ? 'bg-brand text-content-inverse shadow-xs'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary hover:bg-surface-card border border-line-subtle'
        ]"
      >
        <Smartphone class="w-4 h-4" />
        <span>5. ربط قناة الواتساب</span>
      </button>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: HOSTING & DOMAIN MODES -->
    <!-- ========================================== -->
    <div v-show="activeTab === 'hosting'" class="bg-surface-elevated border border-line-subtle rounded-2xl p-6 shadow-xs space-y-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2.5 bg-brand-subtle text-brand border border-brand-border rounded-xl">
            <Globe class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-black text-content-primary">نماذج استضافة وتصميم موقع العيادة</h3>
            <p class="text-xs text-content-muted">اختر الطريقة التي تفضلها لظهور موقع عيادتك لجمهورك ومرضاك على الإنترنت</p>
          </div>
        </div>

        <span class="px-3 py-1 rounded-full text-xs font-black bg-brand-subtle text-brand border border-brand-border">
          باقتك الحالية: {{ currentPlanName }}
        </span>
      </div>

      <!-- 4 Modes Radio Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- MODE 1 -->
        <div
          @click="selectMode(1)"
          :class="[
            'relative rounded-2xl p-4 border-2 transition cursor-pointer flex flex-col justify-between',
            selectedMode === 1
              ? 'border-brand bg-brand-subtle/20 shadow-sm'
              : 'border-line-subtle bg-surface-card hover:border-line-strong'
          ]"
        >
          <div class="space-y-2.5">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-status-success-bg text-status-success border border-status-success-border">
                مجاني مع كل الباقات ✓
              </span>
              <div v-if="selectedMode === 1" class="w-4 h-4 rounded-full bg-brand flex items-center justify-center text-white">
                <Check class="w-2.5 h-2.5 stroke-[3]" />
              </div>
            </div>
            <div>
              <h4 class="text-sm font-black text-content-primary">1. قالب المنصة + رابط المنصة</h4>
              <p class="text-[11px] text-content-secondary mt-1 leading-relaxed">
                صفحة حجز قياسية معتمدة متوافقة مع تخصصك الطبي على نطاق المنصة السريع.
              </p>
            </div>
          </div>

          <div class="mt-4 pt-3 border-t border-line-subtle/60 text-[10px] font-mono text-content-muted truncate" dir="ltr">
            {{ subdomain }}.platform.com
          </div>
        </div>

        <!-- MODE 2 -->
        <div
          @click="canUseMode(2) && selectMode(2)"
          :class="[
            'relative rounded-2xl p-4 border-2 transition flex flex-col justify-between',
            !canUseMode(2)
              ? 'opacity-65 border-line-subtle bg-surface-card cursor-not-allowed'
              : selectedMode === 2
                ? 'border-brand bg-brand-subtle/20 shadow-sm cursor-pointer'
                : 'border-line-subtle bg-surface-card hover:border-line-strong cursor-pointer'
          ]"
        >
          <div class="space-y-2.5">
            <div class="flex items-center justify-between">
              <span
                :class="[
                  'text-[10px] font-black px-2 py-0.5 rounded-md border',
                  canUseMode(2)
                    ? 'bg-brand-subtle text-brand border-brand-border'
                    : 'bg-status-warning-bg text-status-warning border-status-warning-border'
                ]"
              >
                {{ canUseMode(2) ? 'متاح بباقتك ✓' : 'يتطلب باقة متقدمة 🔒' }}
              </span>
              <div v-if="selectedMode === 2" class="w-4 h-4 rounded-full bg-brand flex items-center justify-center text-white">
                <Check class="w-2.5 h-2.5 stroke-[3]" />
              </div>
            </div>
            <div>
              <h4 class="text-sm font-black text-content-primary">2. قالب المنصة + دومين خاص</h4>
              <p class="text-[11px] text-content-secondary mt-1 leading-relaxed">
                استخدم قالب المنصة المصمم لتخصصك لكن على عنوان موقعك المستقل (مثل: <code class="font-mono">drahmed.com</code>).
              </p>
            </div>
          </div>

          <div class="mt-4 pt-3 border-t border-line-subtle/60 text-[10px] font-mono text-brand truncate" dir="ltr">
            {{ customDomain || 'yourdomain.com' }}
          </div>
        </div>

        <!-- MODE 3 -->
        <div
          @click="canUseMode(3) && selectMode(3)"
          :class="[
            'relative rounded-2xl p-4 border-2 transition flex flex-col justify-between',
            !canUseMode(3)
              ? 'opacity-65 border-line-subtle bg-surface-card cursor-not-allowed'
              : selectedMode === 3
                ? 'border-brand bg-brand-subtle/20 shadow-sm cursor-pointer'
                : 'border-line-subtle bg-surface-card hover:border-line-strong cursor-pointer'
          ]"
        >
          <div class="space-y-2.5">
            <div class="flex items-center justify-between">
              <span
                :class="[
                  'text-[10px] font-black px-2 py-0.5 rounded-md border',
                  canUseMode(3)
                    ? 'bg-status-purple-bg text-purple-400 border-purple-500/30'
                    : 'bg-status-warning-bg text-status-warning border-status-warning-border'
                ]"
              >
                {{ canUseMode(3) ? 'متاح بباقتك (VIP) ★' : 'حصري لباقة VIP 🔒' }}
              </span>
              <div v-if="selectedMode === 3" class="w-4 h-4 rounded-full bg-brand flex items-center justify-center text-white">
                <Check class="w-2.5 h-2.5 stroke-[3]" />
              </div>
            </div>
            <div>
              <h4 class="text-sm font-black text-content-primary">3. موقع مخصص + رابط المنصة</h4>
              <p class="text-[11px] text-content-secondary mt-1 leading-relaxed">
                تصميم وبرمجة مخصصة بالكامل لعيادتك داخل المنصة، تظهر على رابط المنصة الفرعي.
              </p>
            </div>
          </div>

          <div class="mt-4 pt-3 border-t border-line-subtle/60 text-[10px] font-mono text-content-muted truncate" dir="ltr">
            custom-sites/{{ subdomain }}
          </div>
        </div>

        <!-- MODE 4 -->
        <div
          @click="canUseMode(4) && selectMode(4)"
          :class="[
            'relative rounded-2xl p-4 border-2 transition flex flex-col justify-between',
            !canUseMode(4)
              ? 'opacity-65 border-line-subtle bg-surface-card cursor-not-allowed'
              : selectedMode === 4
                ? 'border-brand bg-brand-subtle/20 shadow-sm cursor-pointer'
                : 'border-line-subtle bg-surface-card hover:border-line-strong cursor-pointer'
          ]"
        >
          <div class="space-y-2.5">
            <div class="flex items-center justify-between">
              <span
                :class="[
                  'text-[10px] font-black px-2 py-0.5 rounded-md border',
                  canUseMode(4)
                    ? 'bg-status-purple-bg text-purple-400 border-purple-500/30'
                    : 'bg-status-warning-bg text-status-warning border-status-warning-border'
                ]"
              >
                {{ canUseMode(4) ? 'متاح بباقتك (VIP) ★' : 'حصري لباقة VIP 🔒' }}
              </span>
              <div v-if="selectedMode === 4" class="w-4 h-4 rounded-full bg-brand flex items-center justify-center text-white">
                <Check class="w-2.5 h-2.5 stroke-[3]" />
              </div>
            </div>
            <div>
              <h4 class="text-sm font-black text-content-primary">4. موقع مخصص + دومين خاص</h4>
              <p class="text-[11px] text-content-secondary mt-1 leading-relaxed">
                استقلالية كاملة: تصميم وبرمجة VIP مخصصة بنسبة 100% تعمل تحت عنوان موقعك الخاص.
              </p>
            </div>
          </div>

          <div class="mt-4 pt-3 border-t border-line-subtle/60 text-[10px] font-mono text-brand truncate" dir="ltr">
            {{ customDomain || 'yourdomain.com' }}
          </div>
        </div>
      </div>

      <!-- Mode-Specific Configuration Panel -->
      <div class="p-5 bg-surface-card border border-line-subtle rounded-2xl space-y-4">
        <!-- MODE 1 INFO -->
        <div v-if="selectedMode === 1" class="space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-sm font-bold text-content-primary">رابط موقع المنصة المعتمد</h4>
              <p class="text-xs text-content-muted">يعمل هذا الرابط تلقائياً ومجهز بنظام الحجز الذكي لجميع فروعك</p>
            </div>
            <a
              :href="fullSubdomainUrl"
              target="_blank"
              class="px-3 py-1.5 rounded-xl bg-brand hover:bg-brand-hover text-white text-xs font-bold transition flex items-center gap-1.5"
            >
              <span>معاينة الرابط</span>
              <ExternalLink class="w-3.5 h-3.5" />
            </a>
          </div>

          <div class="p-3 bg-surface-elevated rounded-xl font-mono text-xs text-brand font-bold select-all" dir="ltr">
            {{ fullSubdomainUrl }}
          </div>
        </div>

        <!-- MODE 2 CONFIG -->
        <div v-else-if="selectedMode === 2" class="space-y-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">النطاق المخصص الخاص بك (Custom Domain):</label>
            <input
              v-model="customDomain"
              type="text"
              placeholder="مثال: drahmed.com أو clinic.dr-name.com"
              class="w-full px-4 py-2 text-xs bg-surface-elevated rounded-xl border border-line-subtle text-content-primary font-mono focus:outline-none focus:border-brand"
              dir="ltr"
            />
          </div>
          <div class="p-3 bg-surface-elevated border border-brand-border/40 rounded-xl text-xs space-y-2 text-content-secondary">
            <p class="font-bold text-brand flex items-center gap-1.5">
              <Server class="w-4 h-4" />
              <span>إعدادات CNAME:</span>
            </p>
            <p class="text-[11px]">أضف سجل CNAME يشير إلى <code class="font-mono font-bold text-brand">cname.platform.com</code></p>
          </div>
        </div>

        <!-- MODE 3 & 4 CONFIG -->
        <div v-else-if="[3, 4].includes(selectedMode)" class="space-y-4">
          <div class="p-3 bg-surface-elevated border border-purple-500/30 rounded-xl text-xs space-y-2 text-content-secondary">
            <p class="font-bold text-purple-400 flex items-center gap-1.5">
              <Code2 class="w-4 h-4" />
              <span>مجلد الموقع المخصص على المنصة (VIP Custom Site):</span>
            </p>
            <div class="p-2.5 bg-surface-card rounded-lg font-mono text-xs text-brand font-bold select-all" dir="ltr">
              web-platform/custom-sites/{{ subdomain }}/index.vue
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 2: COMPREHENSIVE WORD-BY-WORD PAGE CONTENT EDITOR   -->
    <!-- ======================================================== -->
    <div v-show="activeTab === 'content'" class="space-y-4">
      <!-- Section Selector Sub-Bar -->
      <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
        <button
          v-for="sec in contentSections"
          :key="sec.id"
          type="button"
          @click="activeContentSection = sec.id"
          :class="[
            'px-3.5 py-2 rounded-xl font-bold transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer border',
            activeContentSection === sec.id
              ? 'bg-brand/15 text-brand border-brand'
              : 'bg-surface-elevated text-content-secondary hover:text-content-primary border-line-subtle'
          ]"
        >
          <span>{{ sec.icon }}</span>
          <span>{{ sec.label }}</span>
        </button>
      </div>

      <!-- SECTION 1: TOP BAR & HEADER -->
      <div v-show="activeContentSection === 'top_bar'" class="bg-surface-elevated border border-line-subtle rounded-2xl p-6 shadow-xs space-y-4">
        <div class="border-b border-line-subtle pb-3">
          <h3 class="text-sm font-black text-content-primary">1. الشريط العلوي والترويسة (Top Bar & Header)</h3>
          <p class="text-xs text-content-muted">تحكم في نصوص الشريط الإعلاني الأسود الصغير وأزرار الترويسة</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">نص مواعيد العمل بالشريط العلوي:</label>
            <input
              v-model="pageContent.top_bar.hours"
              type="text"
              placeholder="مواعيد العيادة: السبت إلى الخميس (فترات صباحية ومسائية)"
              class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary"
            />
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">عبارة الجودة والاعتماد بالشريط العلوي:</label>
            <input
              v-model="pageContent.top_bar.notice"
              type="text"
              placeholder="رعاية باطنية استشارية قائمة على أحدث البروتوكولات"
              class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary"
            />
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">نص زر استشارة الواتساب بالشريط العلوي:</label>
            <input
              v-model="pageContent.top_bar.whatsapp_btn"
              type="text"
              placeholder="استشارة واتساب سريعة"
              class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary"
            />
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">شارة الطبيب بجوار الاسم (Header Badge):</label>
            <input
              v-model="pageContent.header.badge"
              type="text"
              placeholder="استشاري معتمد"
              class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary"
            />
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">نص زر حجز الموعد بالترويسة الرئيسية (Header CTA):</label>
            <input
              v-model="pageContent.header.booking_btn"
              type="text"
              placeholder="احجز كشفك الآن"
              class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-bold"
            />
          </div>
        </div>
      </div>

      <!-- SECTION 2: HERO & AUTHORITY METRICS -->
      <div v-show="activeContentSection === 'hero'" class="bg-surface-elevated border border-line-subtle rounded-2xl p-6 shadow-xs space-y-5">
        <div class="border-b border-line-subtle pb-3">
          <h3 class="text-sm font-black text-content-primary">2. الواجهة الرئيسية والشارات (Hero Section & Metrics)</h3>
          <p class="text-xs text-content-muted">العناوين الكبرى والأزرار والبطاقات الإحصائية الأربعة في واجهة الصفحة</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5 md:col-span-2">
            <label class="block text-xs font-bold text-content-primary">الشارة الصغيرة أعلى العنوان (Pill Badge):</label>
            <input
              v-model="pageContent.hero.pill"
              type="text"
              placeholder="تشخيص دقيق وعلاج متقدم • رعاية باطنية استشارية موثوقة"
              class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary"
            />
          </div>

          <div class="space-y-1.5 md:col-span-2">
            <label class="block text-xs font-bold text-content-primary">العنوان الرئيسي الأكبر في الصفحة (Hero Headline):</label>
            <input
              v-model="pageContent.hero.title"
              type="text"
              placeholder="صحتك أمانة تستحق تشخيصاً دقيقاً ورعاية طبية مستدامة"
              class="w-full px-4 py-2.5 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-black"
            />
          </div>

          <div class="space-y-1.5 md:col-span-2">
            <label class="block text-xs font-bold text-content-primary">العنوان الفرعي والفقرة الترحيبية (Hero Subtitle):</label>
            <textarea
              v-model="pageContent.hero.subtitle"
              rows="2"
              placeholder="نقدم لك رعاية طبية متكاملة في تشخيص وعلاج أمراض الباطنة العامة..."
              class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary"
            ></textarea>
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">نص زر حجز الكشف أونلاين:</label>
            <input
              v-model="pageContent.hero.booking_btn"
              type="text"
              placeholder="احجز كشفك الآن أونلاين"
              class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary"
            />
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">نص زر محادثة الواتساب:</label>
            <input
              v-model="pageContent.hero.whatsapp_btn"
              type="text"
              placeholder="محادثة واتساب مباشرة"
              class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary"
            />
          </div>
        </div>

        <!-- 4 Authority Metrics -->
        <div class="border-t border-line-subtle pt-4 space-y-3">
          <h4 class="text-xs font-bold text-content-primary">بطاقات الإحصائيات والأرقام الأربعة (Authority Metrics):</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div v-for="(metric, mIdx) in pageContent.metrics" :key="mIdx" class="p-3 bg-surface-card border border-line-subtle rounded-xl space-y-1.5">
              <label class="block text-[11px] font-bold text-brand">الرقم {{ mIdx + 1 }}:</label>
              <input v-model="metric.value" placeholder="+15" class="w-full px-2.5 py-1 text-xs bg-surface-elevated rounded border border-line-subtle text-content-primary font-bold font-mono" />
              <input v-model="metric.label" placeholder="سنة خبرة" class="w-full px-2.5 py-1 text-[11px] bg-surface-elevated rounded border border-line-subtle text-content-secondary" />
            </div>
          </div>
        </div>

        <!-- 3 Highlight Cards -->
        <div class="border-t border-line-subtle pt-4 space-y-3">
          <h4 class="text-xs font-bold text-content-primary">البطاقات السريعة الثلاثة أسفل الواجهة (Highlights):</h4>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div v-for="(card, cIdx) in pageContent.highlights" :key="cIdx" class="p-3 bg-surface-card border border-line-subtle rounded-xl space-y-1.5">
              <label class="block text-[11px] font-bold text-brand">البطاقة {{ cIdx + 1 }}:</label>
              <input v-model="card.title" placeholder="عنوان البطاقة" class="w-full px-2.5 py-1 text-xs bg-surface-elevated rounded border border-line-subtle text-content-primary font-bold" />
              <input v-model="card.subtitle" placeholder="وصف مقتضب" class="w-full px-2.5 py-1 text-[11px] bg-surface-elevated rounded border border-line-subtle text-content-secondary" />
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 3: ABOUT & PHILOSOPHY -->
      <div v-show="activeContentSection === 'about'" class="bg-surface-elevated border border-line-subtle rounded-2xl p-6 shadow-xs space-y-5">
        <div class="border-b border-line-subtle pb-3">
          <h3 class="text-sm font-black text-content-primary">3. نبذة الطبيب وفلسفة الرعاية (Doctor Bio & Approach)</h3>
          <p class="text-xs text-content-muted">تحكم في نص النبذة التعريفية، شهادات الاعتماد، وعناوين منهجية العلاج</p>
        </div>

        <div class="space-y-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">النبذة التعريفية عن الطبيب والخبرات (About Bio):</label>
            <textarea
              v-model="pageContent.about.bio"
              rows="3"
              placeholder="اكتب نبذة عن مؤهلاتك وعضوياتك الطبية وسنوات الخبرة..."
              class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary"
            ></textarea>
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">عنوان قسم منهجية التشخيص والعلاج (Approach Headline):</label>
            <input
              v-model="pageContent.about.headline"
              type="text"
              placeholder="التشخيص الدقيق هو نصف العلاج، وصحة مريضنا أولويتنا المطلقة"
              class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-bold"
            />
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">وصف وتفاصيل منهجية التشخيص والعلاج:</label>
            <textarea
              v-model="pageContent.about.desc"
              rows="2"
              placeholder="نولي كل مريض الوقت الكافي للاستماع لكافة تفاصيل شكواه وتاريخه المرضي..."
              class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary"
            ></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
            <div class="p-3 bg-surface-card border border-line-subtle rounded-xl space-y-1.5">
              <label class="block text-[11px] font-bold text-brand">بطاقة المنهجية الأولى:</label>
              <input v-model="pageContent.about.card1_title" placeholder="علاج قائم على الدليل العلمي" class="w-full px-2.5 py-1 text-xs bg-surface-elevated rounded border border-line-subtle text-content-primary font-bold" />
              <textarea v-model="pageContent.about.card1_desc" rows="2" placeholder="تطبيق بروتوكولات سريرية دولية تضمن أعلى فاعلية..." class="w-full px-2.5 py-1 text-[11px] bg-surface-elevated rounded border border-line-subtle text-content-secondary"></textarea>
            </div>

            <div class="p-3 bg-surface-card border border-line-subtle rounded-xl space-y-1.5">
              <label class="block text-[11px] font-bold text-brand">بطاقة المنهجية الثانية:</label>
              <input v-model="pageContent.about.card2_title" placeholder="متابعة إلكترونية مستمرة" class="w-full px-2.5 py-1 text-xs bg-surface-elevated rounded border border-line-subtle text-content-primary font-bold" />
              <textarea v-model="pageContent.about.card2_desc" rows="2" placeholder="ربط ملفك الطبي بالنظام الذكي لتذكيرك بمواعيد الاستشارات..." class="w-full px-2.5 py-1 text-[11px] bg-surface-elevated rounded border border-line-subtle text-content-secondary"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 4: SERVICES -->
      <div v-show="activeContentSection === 'services'" class="bg-surface-elevated border border-line-subtle rounded-2xl p-6 shadow-xs space-y-5">
        <div class="border-b border-line-subtle pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-black text-content-primary">4. الفحوصات والخدمات الطبية (Services & Treatments)</h3>
            <p class="text-xs text-content-muted">تحكم في عناوين وبطاقات الفحوصات والخدمات المعروضة في الصفحة</p>
          </div>
          <button
            type="button"
            @click="addServiceItem"
            class="px-3 py-1.5 rounded-xl bg-brand-subtle text-brand border border-brand-border text-xs font-bold hover:bg-brand hover:text-white transition flex items-center gap-1 cursor-pointer"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>إضافة خدمة جديدة</span>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">شارة القسم (Section Badge):</label>
            <input v-model="pageContent.services_section.badge" placeholder="الفحوصات والخدمات العلاجية" class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary" />
          </div>
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">عنوان قسم الفحوصات (Section Title):</label>
            <input v-model="pageContent.services_section.title" placeholder="رعاية شاملة للجهاز الهضمي والأمراض الباطنية" class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-bold" />
          </div>
          <div class="space-y-1.5 md:col-span-2">
            <label class="block text-xs font-bold text-content-primary">الوصف الفرعي للقسم:</label>
            <input v-model="pageContent.services_section.subtitle" placeholder="اختر الإجراء أو الفحص المطلوب واحجز كشفك مباشرة..." class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary" />
          </div>
        </div>

        <div class="space-y-3 pt-2">
          <h4 class="text-xs font-bold text-content-primary">قائمة الخدمات المضافة:</h4>
          <div v-if="pageContent.services.length === 0" class="p-4 bg-surface-card rounded-xl text-center text-xs text-content-muted">
            يتم حالياً استخدام القائمة الافتراضية لقالب التخصص. اضغط "+ إضافة خدمة جديدة" لتخصيص قائمتك.
          </div>
          <div v-for="(serv, idx) in pageContent.services" :key="idx" class="p-3 bg-surface-card border border-line-subtle rounded-xl flex items-start gap-3">
            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-2">
              <input v-model="serv.title" placeholder="اسم الفحص / الإجراء" class="px-3 py-1.5 text-xs bg-surface-elevated rounded-lg border border-line-subtle text-content-primary font-bold" />
              <input v-model="serv.badge" placeholder="شارة تميز (مثال: بأحدث الأجهزة)" class="px-3 py-1.5 text-xs bg-surface-elevated rounded-lg border border-line-subtle text-content-primary" />
              <input v-model="serv.description" placeholder="وصف الفحص والتفاصيل..." class="px-3 py-1.5 text-xs bg-surface-elevated rounded-lg border border-line-subtle text-content-primary" />
            </div>
            <button type="button" @click="removeServiceItem(idx)" class="p-1.5 text-status-danger hover:bg-status-danger-bg rounded-lg cursor-pointer">
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- SECTION 5: TECHNOLOGY -->
      <div v-show="activeContentSection === 'tech'" class="bg-surface-elevated border border-line-subtle rounded-2xl p-6 shadow-xs space-y-5">
        <div class="border-b border-line-subtle pb-3">
          <h3 class="text-sm font-black text-content-primary">5. التجهيزات والتقنيات الطبية (Technology & Clinical Standards)</h3>
          <p class="text-xs text-content-muted">تحكم في عناوين وبطاقات الأجهزة والتقنيات بالعيادة</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">عنوان قسم التجهيزات:</label>
            <input v-model="pageContent.tech_section.title" placeholder="تجهيزات طبية متطورة لتشخيص دقيق وسريع" class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-bold" />
          </div>
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">الوصف الفرعي للقسم:</label>
            <input v-model="pageContent.tech_section.subtitle" placeholder="تضم عياداتنا أحدث التجهيزات الطبية لمناظير الجهاز الهضمي..." class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary" />
          </div>
        </div>

        <div class="space-y-3 pt-2">
          <h4 class="text-xs font-bold text-content-primary">بطاقات التجهيزات الأربعة:</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div v-for="(feat, fIdx) in pageContent.tech_section.features" :key="fIdx" class="p-3 bg-surface-card border border-line-subtle rounded-xl space-y-1.5">
              <input v-model="feat.title" placeholder="اسم الجهاز / التقنية" class="w-full px-2.5 py-1 text-xs bg-surface-elevated rounded border border-line-subtle text-content-primary font-bold" />
              <input v-model="feat.tag" placeholder="الوسم الإنجليزي (مثل Advanced Ultrasound)" class="w-full px-2.5 py-1 text-[11px] bg-surface-elevated rounded border border-line-subtle text-content-secondary font-mono" dir="ltr" />
              <textarea v-model="feat.desc" rows="2" placeholder="وصف الجهاز ومميزاته..." class="w-full px-2.5 py-1 text-[11px] bg-surface-elevated rounded border border-line-subtle text-content-secondary"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 6: REVIEWS -->
      <div v-show="activeContentSection === 'reviews'" class="bg-surface-elevated border border-line-subtle rounded-2xl p-6 shadow-xs space-y-5">
        <div class="border-b border-line-subtle pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-black text-content-primary">6. آراء وتقييمات المرضى (Patient Reviews)</h3>
            <p class="text-xs text-content-muted">تحكم في عناوين وبطاقات شهادات وتجارب المرضى</p>
          </div>
          <button
            type="button"
            @click="addReviewItem"
            class="px-3 py-1.5 rounded-xl bg-brand-subtle text-brand border border-brand-border text-xs font-bold hover:bg-brand hover:text-white transition flex items-center gap-1 cursor-pointer"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>إضافة تقييم جديد</span>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">عنوان قسم آراء المرضى:</label>
            <input v-model="pageContent.reviews_section.title" placeholder="ثقة مرضانا شهادة نعتز بها" class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-bold" />
          </div>
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">الوصف الفرعي للقسم:</label>
            <input v-model="pageContent.reviews_section.subtitle" placeholder="تجارب حقيقية لمرضى تلقوا العلاج والمتابعة..." class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary" />
          </div>
        </div>

        <div class="space-y-3 pt-2">
          <div v-for="(rev, rIdx) in pageContent.reviews_section.reviews" :key="rIdx" class="p-3 bg-surface-card border border-line-subtle rounded-xl space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-brand">التقييم {{ rIdx + 1 }}</span>
              <button type="button" @click="removeReviewItem(rIdx)" class="text-status-danger hover:underline text-xs cursor-pointer">حذف</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
              <input v-model="rev.name" placeholder="اسم المريض" class="px-2.5 py-1 text-xs bg-surface-elevated rounded border border-line-subtle text-content-primary font-bold" />
              <input v-model="rev.treatment" placeholder="نوع العلاج / الفحص" class="px-2.5 py-1 text-xs bg-surface-elevated rounded border border-line-subtle text-content-primary" />
              <input v-model="rev.date" placeholder="التاريخ (مثال: منذ 10 أيام)" class="px-2.5 py-1 text-xs bg-surface-elevated rounded border border-line-subtle text-content-primary" />
            </div>
            <textarea v-model="rev.comment" rows="2" placeholder="نص تعليق ورأي المريض..." class="w-full px-2.5 py-1 text-xs bg-surface-elevated rounded border border-line-subtle text-content-secondary"></textarea>
          </div>
        </div>
      </div>

      <!-- SECTION 7: FAQS -->
      <div v-show="activeContentSection === 'faqs'" class="bg-surface-elevated border border-line-subtle rounded-2xl p-6 shadow-xs space-y-5">
        <div class="border-b border-line-subtle pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-black text-content-primary">7. الأسئلة الشائعة (Frequently Asked Questions - FAQ)</h3>
            <p class="text-xs text-content-muted">تحكم في الأسئلة الشائعة وإجاباتها لمرضى العيادة</p>
          </div>
          <button
            type="button"
            @click="addFaqItem"
            class="px-3 py-1.5 rounded-xl bg-brand-subtle text-brand border border-brand-border text-xs font-bold hover:bg-brand hover:text-white transition flex items-center gap-1 cursor-pointer"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>إضافة سؤال وجواب</span>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">عنوان قسم الأسئلة الشائعة:</label>
            <input v-model="pageContent.faq_section.title" placeholder="كل ما يهمك معرفته قبل زيارة العيادة" class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-bold" />
          </div>
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">الوصف الفرعي للقسم:</label>
            <input v-model="pageContent.faq_section.subtitle" placeholder="إجابات وافية على أكثر الأسئلة شيوعاً حول الفحوصات والتحضير للكشف" class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary" />
          </div>
        </div>

        <div class="space-y-3 pt-2">
          <div v-for="(faq, fIdx) in pageContent.faq_section.faqs" :key="fIdx" class="p-3 bg-surface-card border border-line-subtle rounded-xl space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-brand">السؤال {{ fIdx + 1 }}</span>
              <button type="button" @click="removeFaqItem(fIdx)" class="text-status-danger hover:underline text-xs cursor-pointer">حذف</button>
            </div>
            <input v-model="faq.q" placeholder="نص السؤال (مثال: هل منظار المعدة يسبب ألماً؟)" class="w-full px-3 py-1.5 text-xs bg-surface-elevated rounded border border-line-subtle text-content-primary font-bold" />
            <textarea v-model="faq.a" rows="2" placeholder="الإجابة التوضيحية الوافية من الطبيب..." class="w-full px-3 py-1.5 text-xs bg-surface-elevated rounded border border-line-subtle text-content-secondary"></textarea>
          </div>
        </div>
      </div>

      <!-- SECTION 8: FOOTER & SOCIAL LINKS -->
      <div v-show="activeContentSection === 'footer'" class="bg-surface-elevated border border-line-subtle rounded-2xl p-6 shadow-xs space-y-5">
        <div class="border-b border-line-subtle pb-3">
          <h3 class="text-sm font-black text-content-primary">8. التذييل وروابط التواصل (Footer & Social Links)</h3>
          <p class="text-xs text-content-muted">تحكم في نبذة التذييل وروابط التواصل الاجتماعي لعيادتك</p>
        </div>

        <div class="space-y-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">النبذة التعريفية المقتضبة بالتذييل (Footer Bio):</label>
            <textarea v-model="pageContent.footer.about" rows="2" placeholder="عيادة باطنية وجهاز هضمي متخصصة ومجهزة بأحدث مناظير الجهاز الهضمي..." class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary"></textarea>
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-primary">نص مواعيد العمل بالتذييل:</label>
            <input v-model="pageContent.footer.hours" placeholder="مواعيد العيادة: السبت إلى الخميس (صباحاً ومساءً)" class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary" />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2">
            <div class="space-y-1">
              <label class="block text-[11px] font-bold text-content-secondary">رابط فيسبوك (Facebook):</label>
              <input v-model="pageContent.social_links.facebook" type="url" placeholder="https://facebook.com/page" class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-mono" dir="ltr" />
            </div>
            <div class="space-y-1">
              <label class="block text-[11px] font-bold text-content-secondary">رابط إنستجرام (Instagram):</label>
              <input v-model="pageContent.social_links.instagram" type="url" placeholder="https://instagram.com/dr_profile" class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-mono" dir="ltr" />
            </div>
            <div class="space-y-1">
              <label class="block text-[11px] font-bold text-content-secondary">رابط يوتيوب (YouTube):</label>
              <input v-model="pageContent.social_links.youtube" type="url" placeholder="https://youtube.com/@channel" class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-mono" dir="ltr" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 3: GOOGLE SEO SETTINGS & PREVIEW -->
    <!-- ========================================== -->
    <div v-show="activeTab === 'seo'" class="space-y-6">
      <div class="bg-surface-elevated border border-line-subtle rounded-2xl p-6 shadow-xs space-y-6">
        <div class="border-b border-line-subtle pb-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2.5 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-xl">
              <Search class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-black text-content-primary">إعدادات محركات البحث والـ SEO (Google)</h3>
              <p class="text-xs text-content-muted">تحكم في كيفية ظهور عيادتك وموقعك عند بحث المرضى على محرك بحث جوجل</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- Inputs Column -->
          <div class="lg:col-span-7 space-y-4">
            <div class="space-y-1.5">
              <div class="flex items-center justify-between">
                <label class="block text-xs font-bold text-content-primary">عنوان الصفحة في جوجل (Meta Title):</label>
                <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded" :class="metaTitleLength <= 60 ? 'bg-status-success-bg text-status-success' : 'bg-status-warning-bg text-status-warning'">
                  {{ metaTitleLength }} / 60 حرف
                </span>
              </div>
              <input v-model="seoForm.meta_title" type="text" placeholder="مثال: د. محمد نمر | استشاري أمراض الباطنة والكبد | حجز موعد" class="w-full px-4 py-2.5 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-bold" />
            </div>

            <div class="space-y-1.5">
              <div class="flex items-center justify-between">
                <label class="block text-xs font-bold text-content-primary">وصف الموقع لمحركات البحث (Meta Description):</label>
                <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded" :class="metaDescLength <= 160 ? 'bg-status-success-bg text-status-success' : 'bg-status-warning-bg text-status-warning'">
                  {{ metaDescLength }} / 160 حرف
                </span>
              </div>
              <textarea v-model="seoForm.meta_description" rows="3" placeholder="احجز موعدك الآن بعيادة الطبيب وتتبع دورك في صالة الانتظار مباشرة..." class="w-full px-4 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary"></textarea>
            </div>

            <div class="space-y-1.5">
              <label class="block text-xs font-bold text-content-primary">الكلمات المفتاحية والدلالية (Keywords):</label>
              <input v-model="seoForm.meta_keywords" type="text" placeholder="دكتور باطنة, مناظير الجهاز الهضمي, حجز عيادة" class="w-full px-4 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary" />
            </div>
          </div>

          <!-- Live Google SERP Preview Column -->
          <div class="lg:col-span-5 space-y-3">
            <h4 class="text-xs font-bold text-content-primary flex items-center gap-1.5">
              <Eye class="w-4 h-4 text-blue-400" />
              <span>معاينة حية لشكل ظهورك في جوجل (Google SERP Preview):</span>
            </h4>

            <div class="p-4 bg-white text-slate-800 rounded-2xl border border-slate-200 shadow-md space-y-2 select-text font-sans text-right" dir="rtl">
              <div class="flex items-center gap-2 text-xs text-slate-600">
                <div class="w-6 h-6 rounded-full bg-teal-600 text-white text-[10px] font-black flex items-center justify-center shrink-0">
                  ع
                </div>
                <div class="overflow-hidden leading-tight">
                  <span class="block text-xs font-bold text-slate-900 truncate">{{ doctorName }}</span>
                  <span class="block text-[11px] text-slate-500 font-mono truncate" dir="ltr">https://{{ subdomain }}.platform.com</span>
                </div>
              </div>

              <h5 class="text-base font-bold text-[#1a0dab] hover:underline cursor-pointer leading-snug line-clamp-2">
                {{ seoForm.meta_title || `${doctorName} | استشاري الطب الباطني والجهاز الهضمي | حجز موعد` }}
              </h5>

              <p class="text-xs text-[#4d5156] leading-relaxed line-clamp-3">
                {{ seoForm.meta_description || 'احجز موعدك الآن بعيادة الطبيب وتتبع رقمك ودورك بالعيادة مباشرة عبر الهاتف. رعاية استشارية متخصصة بأحدث البروتوكولات الطبية.' }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 4: OPEN GRAPH & SOCIAL PREVIEW -->
    <!-- ========================================== -->
    <div v-show="activeTab === 'og'" class="space-y-6">
      <div class="bg-surface-elevated border border-line-subtle rounded-2xl p-6 shadow-xs space-y-6">
        <div class="border-b border-line-subtle pb-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-xl">
              <Share2 class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-black text-content-primary">بطاقات المشاركة بالسوشيال ميديا (Open Graph)</h3>
              <p class="text-xs text-content-muted">تحكم في صورة وعنوان ووصف الكارت عند مشاركة رابط عيادتك على واتساب وفيسبوك وتويتر</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <div class="lg:col-span-7 space-y-4">
            <div class="space-y-1.5">
              <label class="block text-xs font-bold text-content-primary">عنوان المشاركة الاجتماعية (OG Title):</label>
              <input v-model="ogForm.og_title" type="text" placeholder="مثال: عيادة د. محمد نمر - حجز كشف وتتبع الدور المباشر" class="w-full px-4 py-2.5 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-bold" />
            </div>

            <div class="space-y-1.5">
              <label class="block text-xs font-bold text-content-primary">وصف المشاركة الاجتماعية (OG Description):</label>
              <textarea v-model="ogForm.og_description" rows="3" placeholder="احجز موعدك أونلاين وتتبع دورك في صالة الانتظار مباشرة..." class="w-full px-4 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary"></textarea>
            </div>

            <!-- OG Image Upload & URL Zone -->
            <div class="space-y-2">
              <label class="block text-xs font-bold text-content-primary">صورة المعاينة للسوشيال ميديا (OG Image Upload):</label>
              
              <!-- Hidden native file input -->
              <input
                ref="fileInput"
                type="file"
                accept="image/png,image/jpeg,image/webp,image/gif"
                @change="handleFileSelected"
                class="hidden"
              />

              <!-- Case 1: Uploading State -->
              <div v-if="isUploading" class="p-6 border-2 border-dashed border-brand/50 rounded-2xl bg-surface-card text-center space-y-2">
                <div class="w-8 h-8 mx-auto border-3 border-brand border-t-transparent rounded-full animate-spin"></div>
                <p class="text-xs font-bold text-brand">جاري رفع ومعالجة الصورة إلى خوادم المنصة...</p>
                <p class="text-[11px] text-content-muted">يرجى الانتظار لحظات</p>
              </div>

              <!-- Case 2: Image is present (Uploaded) -->
              <div v-else-if="ogForm.og_image" class="p-4 bg-surface-card border border-line-subtle rounded-2xl space-y-3">
                <div class="flex items-center gap-3">
                  <div class="w-20 h-14 rounded-xl overflow-hidden bg-slate-900 border border-line-subtle shrink-0">
                    <img :src="ogForm.og_image" alt="OG Image Thumbnail" class="w-full h-full object-cover" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-status-success">
                      <Check class="w-4 h-4 stroke-[3]" />
                      <span>تم رفع الصورة بنجاح</span>
                    </div>
                    <p class="text-[11px] text-content-muted font-mono truncate mt-0.5" dir="ltr">{{ ogForm.og_image }}</p>
                  </div>
                </div>

                <div class="flex items-center gap-2 pt-2 border-t border-line-subtle">
                  <button
                    type="button"
                    @click="triggerFileInput"
                    class="px-3 py-1.5 bg-brand-subtle hover:bg-brand hover:text-white text-brand border border-brand-border rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                  >
                    <UploadCloud class="w-3.5 h-3.5" />
                    <span>تغيير الصورة (رفع أخرى)</span>
                  </button>
                  <button
                    type="button"
                    @click="removeOgImage"
                    class="px-3 py-1.5 bg-status-danger/10 hover:bg-status-danger hover:text-white text-status-danger border border-status-danger/20 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                    <span>حذف الصورة</span>
                  </button>
                </div>
              </div>

              <!-- Case 3: No image uploaded yet - Dropzone Card -->
              <div
                v-else
                @click="triggerFileInput"
                class="p-6 border-2 border-dashed border-line-subtle hover:border-brand rounded-2xl bg-surface-card hover:bg-surface-elevated text-center cursor-pointer transition space-y-2.5 group"
              >
                <div class="w-12 h-12 mx-auto rounded-2xl bg-brand-subtle text-brand border border-brand-border flex items-center justify-center group-hover:scale-105 transition">
                  <UploadCloud class="w-6 h-6" />
                </div>
                <div>
                  <p class="text-xs font-bold text-content-primary">انقر هنا لاختيار صورة من جهازك (Upload Image)</p>
                  <p class="text-[11px] text-content-muted mt-0.5">يدعم JPG, PNG, WebP بحجم أقصى 5 ميجابايت (المقاس الأفضل 1200x630)</p>
                </div>
              </div>

              <!-- Collapsible Manual URL input if needed -->
              <div class="pt-1">
                <button
                  type="button"
                  @click="showManualUrl = !showManualUrl"
                  class="text-[11px] text-content-muted hover:text-brand transition flex items-center gap-1"
                >
                  <span>{{ showManualUrl ? '▲ إخفاء الرابط المباشر' : '▼ أو إدخال/نسخ رابط مباشر يدوياً' }}</span>
                </button>
                <div v-if="showManualUrl" class="mt-1.5">
                  <input
                    v-model="ogForm.og_image"
                    type="url"
                    placeholder="https://..."
                    class="w-full px-3 py-1.5 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-mono"
                    dir="ltr"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="lg:col-span-5 space-y-3">
            <h4 class="text-xs font-bold text-content-primary flex items-center gap-1.5">
              <Eye class="w-4 h-4 text-emerald-400" />
              <span>معاينة كارت الواتساب والفيسبوك (Social Card Preview):</span>
            </h4>

            <div class="bg-surface-card rounded-2xl border border-line-subtle overflow-hidden shadow-lg select-none text-right font-sans" dir="rtl">
              <div class="w-full h-36 bg-gradient-to-br from-teal-900 to-slate-900 flex items-center justify-center relative overflow-hidden">
                <img v-if="ogForm.og_image" :src="ogForm.og_image" alt="OG Image" class="w-full h-full object-cover" @error="ogImageError = true" />
                <div v-if="!ogForm.og_image || ogImageError" class="text-center p-4">
                  <div class="w-12 h-12 mx-auto rounded-2xl bg-teal-500/20 text-teal-300 flex items-center justify-center text-xl font-black mb-1">
                    🩺
                  </div>
                  <span class="text-xs font-bold text-white block">{{ doctorName }}</span>
                  <span class="text-[10px] text-teal-200">منظومة الحجز الطبي الذكي</span>
                </div>
              </div>

              <div class="p-3.5 space-y-1 bg-surface-elevated">
                <span class="text-[10px] font-mono text-content-muted block uppercase" dir="ltr">
                  {{ (customDomain || `${subdomain}.platform.com`).toUpperCase() }}
                </span>
                <h5 class="text-xs font-bold text-content-primary leading-snug line-clamp-1">
                  {{ ogForm.og_title || seoForm.meta_title || `${doctorName} - حجز موعد إلكتروني` }}
                </h5>
                <p class="text-[11px] text-content-secondary leading-relaxed line-clamp-2">
                  {{ ogForm.og_description || seoForm.meta_description || 'احجز موعدك وتتبع دورك في صالة الانتظار مباشرة عبر الهاتف.' }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 5: WHATSAPP PAIRING GATEWAY -->
    <!-- ========================================== -->
    <div v-show="activeTab === 'whatsapp'" class="bg-surface-elevated border border-line-subtle rounded-2xl p-6 shadow-xs space-y-4">
      <div class="flex items-center gap-3">
        <div class="p-2.5 bg-status-success-bg text-status-success border border-status-success-border rounded-xl">
          <Smartphone class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-sm font-black text-content-primary">قناة الواتساب المباشرة للعيادة</h3>
          <p class="text-[11px] text-content-muted">إرسال الروشتات الرقمية تلقائياً وإشعارات حجز وتأكيد المواعيد للمرضى</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
        <div class="space-y-2.5">
          <label class="block text-xs font-bold text-content-primary">رقم هاتف الواتساب المربوط</label>
          <div class="flex items-center gap-2">
            <input v-model="waPhone" type="tel" placeholder="2010XXXXXXXX" class="flex-1 px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-mono" />
            <button type="button" @click="handleGeneratePairingCode" :disabled="loadingPairing" class="px-4 py-2 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold transition disabled:opacity-50 shadow-xs cursor-pointer">
              {{ loadingPairing ? 'جارٍ التوليد...' : 'توليد كود الربط' }}
            </button>
          </div>
        </div>

        <div v-if="pairingCode" class="p-4 bg-surface-card border-2 border-brand-border rounded-xl text-center space-y-2">
          <p class="text-[11px] text-content-muted">أدخل هذا الكود في تطبيق واتساب على هاتفك:</p>
          <p class="text-2xl font-mono font-black text-brand tracking-widest">{{ pairingCode }}</p>
          <button type="button" @click="copyPairingCode" class="text-[11px] text-content-secondary hover:underline font-bold">
            {{ copiedCode ? '✅ تم النسخ' : '📋 نسخ الكود' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Unified Floating Action Footer Bar -->
    <div class="p-4 bg-surface-elevated border border-line-subtle rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm sticky bottom-4 z-30">
      <div class="flex items-center gap-2 text-xs text-content-secondary">
        <span class="w-2 h-2 rounded-full bg-status-success animate-pulse"></span>
        <span>النموذج المختار حالياً: <strong>النموذج {{ selectedMode }}</strong></span>
        <span class="text-content-muted">• سيتم حفظ المحتوى المعدل كاملاً وSEO والـ Open Graph فوراً</span>
      </div>

      <button
        type="button"
        @click="handleSaveWebsiteSettings"
        :disabled="saving"
        class="w-full sm:w-auto px-8 py-3 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-black transition disabled:opacity-50 shadow-md shadow-brand/20 cursor-pointer flex items-center justify-center gap-2"
      >
        <span v-if="saving">جاري حفظ وتطبيق الإعدادات...</span>
        <span v-else>حفظ وتطبيق جميع إعدادات الموقع فوراً</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useSettingsStore } from '../../stores/settings';
import alerts from '../../utils/alerts';
import {
  Globe,
  Smartphone,
  Lock,
  ExternalLink,
  Check,
  Server,
  Code2,
  FileText,
  Search,
  Share2,
  Eye,
  Plus,
  Trash2,
  UploadCloud,
} from 'lucide-vue-next';

const settingsStore = useSettingsStore();

const activeTab = ref('hosting');
const selectedMode = ref(1);
const customDomain = ref('');
const customWebsiteUrl = ref('');
const saving = ref(false);
const upgradeAlertMessage = ref('');
const ogImageError = ref(false);

const fileInput = ref(null);
const isUploading = ref(false);
const showManualUrl = ref(false);

const waPhone = ref('201001234567');
const pairingCode = ref('');
const loadingPairing = ref(false);
const copiedCode = ref(false);

// Sub-sections for Tab 2
const activeContentSection = ref('hero');
const contentSections = [
  { id: 'top_bar', label: 'الشريط والترويسة', icon: '📌' },
  { id: 'hero', label: 'الواجهة والشارات', icon: '🚀' },
  { id: 'about', label: 'النبذة والمنهجية', icon: '🩺' },
  { id: 'services', label: 'الفحوصات والخدمات', icon: '🔬' },
  { id: 'tech', label: 'التجهيزات الطبية', icon: '⚡' },
  { id: 'reviews', label: 'آراء المرضى', icon: '⭐' },
  { id: 'faqs', label: 'الأسئلة الشائعة', icon: '❓' },
  { id: 'footer', label: 'التذييل وروابط التواصل', icon: '📍' },
];

// Complete page content data store
const pageContent = ref({
  top_bar: {
    hours: 'مواعيد العيادة: السبت إلى الخميس (فترات صباحية ومسائية)',
    notice: 'رعاية باطنية استشارية قائمة على أحدث البروتوكولات',
    whatsapp_btn: 'استشارة واتساب سريعة',
  },
  header: {
    badge: 'استشاري معتمد',
    booking_btn: 'احجز كشفك الآن',
  },
  hero: {
    pill: 'تشخيص دقيق وعلاج متقدم • رعاية باطنية استشارية موثوقة',
    title: 'صحتك أمانة تستحق تشخيصاً دقيقاً ورعاية طبية مستدامة',
    subtitle: 'نقدم لك رعاية طبية متكاملة في تشخيص وعلاج أمراض الباطنة العامة، أمراض الجهاز الهضمي والكبد، ومتابعة الأمراض المزمنة بأحدث البروتوكولات السريرية الدولية.',
    booking_btn: 'احجز كشفك الآن أونلاين',
    whatsapp_btn: 'محادثة واتساب مباشرة',
  },
  metrics: [
    { value: '+18', label: 'سنة خبرة استشارية' },
    { value: '+25,000', label: 'حالة تم علاجها' },
    { value: '99.4%', label: 'نسبة رضا المرضى' },
    { value: '100%', label: 'دقة تشخيصية' },
  ],
  highlights: [
    { title: 'متابعة دقيقة', subtitle: 'للسكر والضغط والكلى' },
    { title: 'بروتوكولات دولية', subtitle: 'علاج قائم على الدليل' },
    { title: 'خبرة استشارية', subtitle: 'في الباطنة والمناظير' },
  ],
  about: {
    badge: 'منهجية التشخيص والعلاج',
    bio: 'خبرة أكثر من 18 عاماً في تشخيص وعلاج أمراض الباطنة والجهاز الهضمي والكبد، وعضو الجمعية المصرية لأمراض الجهاز الهضمي والجمعية الأوروبية لأمراض الكبد.',
    headline: 'التشخيص الدقيق هو نصف العلاج، وصحة مريضنا أولويتنا المطلقة',
    desc: 'نولي كل مريض الوقت الكافي للاستماع لكافة تفاصيل شكواه وتاريخه المرضي، ونجري الفحص السريري الدقيق والفحوصات التكميلية الموجهة فقط دون إرهاق المريض بتحاليل غير ضرورية.',
    card1_title: 'علاج قائم على الدليل العلمي',
    card1_desc: 'تطبيق بروتوكولات سريرية دولية تضمن أعلى فاعلية علاجية بأقل آثار جانبية ممكنة.',
    card2_title: 'متابعة إلكترونية مستمرة',
    card2_desc: 'ربط ملفك الطبي بالنظام الذكي لتذكيرك بمواعيد الاستشارات والتحاليل الدورية.',
  },
  services_section: {
    badge: 'الفحوصات والخدمات العلاجية',
    title: 'رعاية شاملة للجهاز الهضمي والأمراض الباطنية',
    subtitle: 'اختر الإجراء أو الفحص المطلوب واحجز كشفك مباشرة للحصول على تقييم سريري شامل.',
  },
  services: [],
  tech_section: {
    badge: 'التجهيزات ومعايير الجودة',
    title: 'تجهيزات طبية متطورة لتشخيص دقيق وسريع',
    subtitle: 'تضم عياداتنا أحدث التجهيزات الطبية لمناظير الجهاز الهضمي والسونار لتقديم الرعاية في مكان واحد.',
    features: [
      { title: 'وحدة مناظير الجهاز الهضمي المتقدمة', desc: 'أحدث أنظمة المناظير المرنة عالية الدقة بدقة 4K مع خاصية الصبغة الرقمية (NBI).', tag: 'Advanced Endoscopy' },
      { title: 'موجات صوتية وسونار ملون عالي الدقة', desc: 'جهاز سونار متطور لفحص الكبد والمرار والبنكرياس والطحال والكلى لتقديم تشخيص فوري.', tag: 'Abdominal Ultrasound' },
      { title: 'بروتوكولات علاجية قائمة على الدليل الدولي', desc: 'نطبق أحدث المبادئ التوجيهية للجمعية الأمريكية لأمراض الجهاز الهضمي (AGA).', tag: 'Evidence-Based Medicine' },
      { title: 'ملف طبي إلكتروني ومتابعة دورية مستمرة', desc: 'سجل إلكتروني متكامل لنتائج تحاليلك وتطور مؤشراتك الحيوية مع تذكير تلقائي.', tag: 'Digital Health Records' },
    ],
  },
  reviews_section: {
    badge: 'آراء وتجارب مرضانا',
    title: 'ثقة مرضانا شهادة نعتز بها',
    subtitle: 'تجارب حقيقية لمرضى تلقوا العلاج والمتابعة داخل عياداتنا.',
    reviews: [
      { name: 'الحاج مصطفى النجار', treatment: 'متابعة سكري وضغط وفحص شامل', comment: 'دكتور ممتاز وأمين جداً. فحصني باهتمام ونظم لي جرعات السكر.', rating: 5, date: 'منذ 10 أيام' },
      { name: 'أ. سامح عبد الفتاح', treatment: 'منظار معدة وعلاج جرثومة المعدة', comment: 'المنظار مر في دقائق بدون ألم تماماً بالمهدئ الخفيف، والنتيجة ممتازة.', rating: 5, date: 'منذ شهر' },
      { name: 'مدام جيهان كمال', treatment: 'علاج القولون والتهابات الجهاز الهضمي', comment: 'دقة في المواعيد بدون انتظار طويل، واستماع رائع لشكوى المريض.', rating: 5, date: 'منذ 3 أسابيع' },
    ],
  },
  faq_section: {
    badge: 'الأسئلة الشائعة وإجابات الطبيب',
    title: 'كل ما يهمك معرفته قبل زيارة العيادة',
    subtitle: 'إجابات وافية على أكثر الأسئلة شيوعاً حول الفحوصات والتحضير للكشف',
    faqs: [
      { q: 'هل منظار المعدة أو القولون يسبب ألماً؟', a: 'يتم إجراء مناظير الجهاز الهضمي تحت تأثير مهدئ وريدي قصير المفعول (Sedation) تحت إشراف طبي كامل، بحيث ينام المريض براحة تامة ولا يشعر بأي ألم.' },
      { q: 'كيف أستعد لفحص الموجات الصوتية على البطن؟', a: 'يُفضل الصيام عن الطعام لمدة 6 إلى 8 ساعات قبل موعد الكشف مع شرب المياه النقية فقط.' },
      { q: 'هل كشف الاستشارة والمتابعة مشمول بعد الزيارة الأولى؟', a: 'نعم، يحق للمريض الحصول على استشارة مجانية لمراجعة الفحوصات خلال فترة المتابعة المحددة.' },
      { q: 'كيف يمكنني حجز موعد والحصول على دور مباشر؟', a: 'يمكنك اختيار الفرع والتاريخ والوردية المناسبة عبر استمارة الحجز في هذه الصفحة وسيصلك رقم دورك فوراً.' },
    ],
  },
  footer: {
    about: 'عيادة باطنية وجهاز هضمي متخصصة ومجهزة بأحدث مناظير الجهاز الهضمي والتشخيص السوناري المتقدم لتقديم أعلى مستويات الرعاية الصحية.',
    hours: 'مواعيد العيادة: السبت إلى الخميس (صباحاً ومساءً)',
  },
  social_links: {
    facebook: '',
    instagram: '',
    youtube: '',
  },
});

// SEO form
const seoForm = ref({
  meta_title: '',
  meta_description: '',
  meta_keywords: '',
});

// OG form
const ogForm = ref({
  og_title: '',
  og_description: '',
  og_image: '',
});

const metaTitleLength = computed(() => (seoForm.value.meta_title || '').length);
const metaDescLength = computed(() => (seoForm.value.meta_description || '').length);

const canUseCustomDomain = computed(() => Boolean(settingsStore.settings?.tenant?.plan?.allow_custom_domain));
const canUseCustomWebsite = computed(() => Boolean(settingsStore.settings?.tenant?.plan?.allow_custom_website));
const currentPlanName = computed(() => settingsStore.settings?.tenant?.plan?.name_ar || 'الأساسية');
const doctorName = computed(() => settingsStore.settings?.tenant?.name || 'طبيب العيادة');
const subdomain = computed(() => settingsStore.settings?.tenant?.subdomain || 'dr');
const fullSubdomainUrl = computed(() => `http://localhost:3000/d/${subdomain.value}`);
const currentLiveUrl = computed(() => ([2, 4].includes(selectedMode.value) && customDomain.value ? `https://${customDomain.value}` : fullSubdomainUrl.value));

const canUseMode = (mode) => {
  if (mode === 1) return true;
  if (mode === 2) return canUseCustomDomain.value;
  if (mode === 3) return canUseCustomWebsite.value;
  if (mode === 4) return canUseCustomDomain.value && canUseCustomWebsite.value;
  return false;
};

const selectMode = (mode) => {
  upgradeAlertMessage.value = '';
  if (!canUseMode(mode)) {
    if (mode === 2) {
      upgradeAlertMessage.value = `النموذج 2 يتطلب الترقية للباقة المتقدمة أو باقة VIP.`;
    } else {
      upgradeAlertMessage.value = `النموذج ${mode} ميزة حصرية لمشتركي باقة VIP الشاملة.`;
    }
    return;
  }
  selectedMode.value = mode;
};

const addServiceItem = () => {
  pageContent.value.services.push({
    title: '',
    badge: 'خدمة استشارية',
    description: '',
  });
};

const removeServiceItem = (index) => {
  pageContent.value.services.splice(index, 1);
};

const addReviewItem = () => {
  pageContent.value.reviews_section.reviews.push({
    name: '',
    treatment: 'كشف ومتابعة',
    comment: '',
    rating: 5,
    date: 'حديثاً',
  });
};

const removeReviewItem = (index) => {
  pageContent.value.reviews_section.reviews.splice(index, 1);
};

const addFaqItem = () => {
  pageContent.value.faq_section.faqs.push({
    q: '',
    a: '',
  });
};

const removeFaqItem = (index) => {
  pageContent.value.faq_section.faqs.splice(index, 1);
};

const triggerFileInput = () => {
  if (fileInput.value) {
    fileInput.value.click();
  }
};

const handleFileSelected = async (event) => {
  const file = event.target.files?.[0];
  if (!file) return;

  if (!file.type.startsWith('image/')) {
    alerts.error('نوع ملف غير صالح', 'يرجى اختيار ملف صورة صالح (JPG, PNG, WebP)');
    return;
  }

  if (file.size > 5 * 1024 * 1024) {
    alerts.error('حجم الملف كبير جداً', 'أقصى حجم مسموح به هو 5 ميجابايت');
    return;
  }

  isUploading.value = true;
  ogImageError.value = false;

  try {
    const res = await settingsStore.uploadImage(file, 'og_image');
    if (res.data?.url) {
      ogForm.value.og_image = res.data.url;
      alerts.success('تم رفع الصورة بنجاح', 'تم تحديث صورة المشاركة الاجتماعية');
    }
  } catch (err) {
    console.error('Failed to upload image:', err);
    alerts.error('فشل رفع الصورة', err.response?.data?.message || 'حدث خطأ أثناء رفع الصورة، يرجى المحاولة مرة أخرى');
  } finally {
    isUploading.value = false;
    if (event.target) event.target.value = '';
  }
};

const removeOgImage = () => {
  ogForm.value.og_image = '';
  ogImageError.value = false;
  alerts.toast('تمت إزالة صورة المعاينة');
};

onMounted(async () => {
  await settingsStore.fetchAll();
  const tenant = settingsStore.settings?.tenant;
  if (tenant) {
    if (tenant.custom_domain) customDomain.value = tenant.custom_domain;
    if (tenant.whatsapp_phone_number) waPhone.value = tenant.whatsapp_phone_number;

    if (settingsStore.settings?.website_mode) {
      selectedMode.value = Number(settingsStore.settings.website_mode);
    } else if (tenant.is_custom_standalone_website && tenant.custom_domain) {
      selectedMode.value = 4;
    } else if (tenant.is_custom_standalone_website) {
      selectedMode.value = 3;
    } else if (tenant.custom_domain) {
      selectedMode.value = 2;
    } else {
      selectedMode.value = 1;
    }

    const ws = tenant.website_settings || {};

    // Load full structured content or merge
    if (ws.content && typeof ws.content === 'object') {
      pageContent.value = {
        ...pageContent.value,
        ...ws.content,
      };
      // Ensure nested objects exist
      if (ws.content.top_bar) pageContent.value.top_bar = { ...pageContent.value.top_bar, ...ws.content.top_bar };
      if (ws.content.header) pageContent.value.header = { ...pageContent.value.header, ...ws.content.header };
      if (ws.content.hero) pageContent.value.hero = { ...pageContent.value.hero, ...ws.content.hero };
      if (ws.content.about) pageContent.value.about = { ...pageContent.value.about, ...ws.content.about };
      if (ws.content.services_section) pageContent.value.services_section = { ...pageContent.value.services_section, ...ws.content.services_section };
      if (ws.content.tech_section) pageContent.value.tech_section = { ...pageContent.value.tech_section, ...ws.content.tech_section };
      if (ws.content.reviews_section) pageContent.value.reviews_section = { ...pageContent.value.reviews_section, ...ws.content.reviews_section };
      if (ws.content.faq_section) pageContent.value.faq_section = { ...pageContent.value.faq_section, ...ws.content.faq_section };
      if (ws.content.footer) pageContent.value.footer = { ...pageContent.value.footer, ...ws.content.footer };
      if (ws.content.social_links) pageContent.value.social_links = { ...pageContent.value.social_links, ...ws.content.social_links };
      if (Array.isArray(ws.content.services)) pageContent.value.services = ws.content.services;
    } else {
      // Legacy flat keys fallback
      if (ws.hero_title) pageContent.value.hero.title = ws.hero_title;
      if (ws.hero_subtitle) pageContent.value.hero.subtitle = ws.hero_subtitle;
      if (ws.about_text) pageContent.value.about.bio = ws.about_text;
      if (ws.experience_years) pageContent.value.metrics[0].value = `+${ws.experience_years}`;
      if (Array.isArray(ws.services_list)) pageContent.value.services = ws.services_list;
      if (ws.social_links) pageContent.value.social_links = { ...pageContent.value.social_links, ...ws.social_links };
    }

    if (ws.seo) {
      seoForm.value = {
        meta_title: ws.seo.meta_title || '',
        meta_description: ws.seo.meta_description || '',
        meta_keywords: ws.seo.meta_keywords || '',
      };
    }

    if (ws.og) {
      ogForm.value = {
        og_title: ws.og.og_title || '',
        og_description: ws.og.og_description || '',
        og_image: ws.og.og_image || '',
      };
    }
  }
});

const handleSaveWebsiteSettings = async () => {
  if ([2, 4].includes(selectedMode.value) && !customDomain.value.trim()) {
    alerts.error('يرجى إدخال النطاق المخصص', 'النموذج المختار يتطلب كتابة اسم النطاق الخاص بك (Custom Domain)');
    activeTab.value = 'hosting';
    return;
  }

  saving.value = true;
  try {
    const payload = {
      website_mode: selectedMode.value,
      custom_domain: [2, 4].includes(selectedMode.value) ? customDomain.value.trim() : null,
      custom_website_url: customWebsiteUrl.value ? customWebsiteUrl.value.trim() : null,
      content: pageContent.value,
      seo: {
        meta_title: seoForm.value.meta_title || null,
        meta_description: seoForm.value.meta_description || null,
        meta_keywords: seoForm.value.meta_keywords || null,
      },
      og: {
        og_title: ogForm.value.og_title || null,
        og_description: ogForm.value.og_description || null,
        og_image: ogForm.value.og_image || null,
      },
    };

    await settingsStore.saveWebsiteSettings(payload);
    alerts.success('تم حفظ إعدادات الموقع بنجاح', `تم تحديث وتطبيق كامل محتوى الصفحة وإعدادات SEO بنجاح.`);
  } catch (e) {
    alerts.error('فشل حفظ إعدادات الموقع', e.response?.data?.message || 'حدث خطأ أثناء حفظ الإعدادات');
  } finally {
    saving.value = false;
  }
};

const handleGeneratePairingCode = async () => {
  if (!waPhone.value) return;
  loadingPairing.value = true;
  try {
    const res = await settingsStore.requestWhatsAppPairing(waPhone.value);
    pairingCode.value = res.pairing_code || '1234-5678';
    alerts.success('تم توليد كود الربط', 'أدخل هذا الكود في تطبيق واتساب بهاتفك الآن لربط العيادة');
  } catch (e) {
    alerts.error('فشل توليد كود الربط', e.response?.data?.message || 'تأكد من رقم الهاتف وتشغيل خدمة الواتساب');
  } finally {
    loadingPairing.value = false;
  }
};

const copyPairingCode = async () => {
  if (!pairingCode.value) return;
  await navigator.clipboard.writeText(pairingCode.value);
  copiedCode.value = true;
  alerts.toast('تم نسخ الكود للحافظة');
  setTimeout(() => { copiedCode.value = false; }, 2000);
};
</script>
