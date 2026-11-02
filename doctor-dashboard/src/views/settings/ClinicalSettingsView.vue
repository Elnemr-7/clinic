<template>
  <div class="space-y-6 text-right select-none font-sans" dir="rtl">
    <!-- Section Title & Tabs Header -->
    <div class="border-b border-line-subtle pb-4">
      <h2 class="text-base font-black text-content-primary">1. الإعدادات السريرية والتخصيص الطبي</h2>
      <p class="text-xs text-content-muted mt-0.5">تخصيص الروشتة، هوامش الطباعة، العلامات الحيوية، وقوائم الأدوية</p>

      <!-- Sub-tabs -->
      <div class="flex flex-wrap gap-2 mt-4">
        <button
          type="button"
          @click="activeTab = 'vitals'"
          class="px-3.5 py-1.5 text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer"
          :class="activeTab === 'vitals'
            ? 'bg-brand text-content-inverse shadow-xs'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary'"
        >
          <Activity class="w-3.5 h-3.5" />
          <span>العلامات الحيوية</span>
        </button>

        <button
          type="button"
          @click="activeTab = 'print'"
          class="px-3.5 py-1.5 text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer"
          :class="activeTab === 'print'
            ? 'bg-brand text-content-inverse shadow-xs'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary'"
        >
          <Printer class="w-3.5 h-3.5" />
          <span>هوامش ومعاينة الطباعة (PDF)</span>
        </button>

        <button
          type="button"
          @click="activeTab = 'drugs'"
          class="px-3.5 py-1.5 text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer"
          :class="activeTab === 'drugs'
            ? 'bg-brand text-content-inverse shadow-xs'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary'"
        >
          <Pill class="w-3.5 h-3.5" />
          <span>قائمة الأدوية ({{ settingsStore.drugs?.length || 0 }})</span>
        </button>

        <button
          type="button"
          @click="activeTab = 'labs'"
          class="px-3.5 py-1.5 text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer"
          :class="activeTab === 'labs'
            ? 'bg-brand text-content-inverse shadow-xs'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary'"
        >
          <FlaskConical class="w-3.5 h-3.5" />
          <span>كتالوج التحاليل ({{ settingsStore.labs?.length || 0 }})</span>
        </button>

        <button
          type="button"
          @click="activeTab = 'templates'"
          class="px-3.5 py-1.5 text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer"
          :class="activeTab === 'templates'
            ? 'bg-brand text-content-inverse shadow-xs'
            : 'bg-surface-elevated text-content-secondary hover:text-content-primary'"
        >
          <Sparkles class="w-3.5 h-3.5" />
          <span>قوالب الروشتات ({{ settingsStore.templates?.length || 0 }})</span>
        </button>
      </div>
    </div>

    <!-- Tab 1: Vitals Config -->
    <div v-if="activeTab === 'vitals'" class="space-y-5">
      <div>
        <h3 class="text-sm font-bold text-content-primary">تخصيص القياسات الحيوية (Biological Vitals)</h3>
        <p class="text-xs text-content-muted">القياسات المفعلة تظهر فوراً لموظف الاستقبال عند الحضور وتُقارن تلقائياً في غرفة الكشف</p>
      </div>

      <!-- Add Custom Vital Strip -->
      <div class="bg-surface-elevated border border-line-subtle p-3.5 rounded-2xl space-y-2.5">
        <h4 class="text-xs font-bold text-content-primary flex items-center gap-1.5">
          <Plus class="w-4 h-4 text-brand" />
          <span>إضافة قياس حيوي جديد</span>
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
          <input
            v-model="newVital.label"
            type="text"
            placeholder="اسم القياس (مثال: محيط الخصر، معدل التنفس...)"
            class="px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary focus:outline-none focus:border-brand"
          />
          <input
            v-model="newVital.unit"
            type="text"
            placeholder="وحدة القياس (مثال: سم، bpm، ٪...)"
            class="px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary focus:outline-none focus:border-brand"
          />
          <button
            @click="addCustomVital"
            type="button"
            class="px-4 py-2 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-xs cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            <span>إضافة للقائمة</span>
          </button>
        </div>
      </div>

      <!-- Vitals List Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div
          v-for="(vital, index) in vitalsList"
          :key="vital.key"
          class="p-3.5 rounded-xl border flex items-center justify-between transition-all"
          :class="vital.enabled
            ? 'border-brand-border bg-brand-subtle'
            : 'border-line-subtle bg-surface-elevated/40 opacity-70'"
        >
          <div class="flex items-center gap-2.5">
            <input
              v-model="vital.enabled"
              type="checkbox"
              class="w-4 h-4 text-brand rounded border-line-subtle focus:ring-brand cursor-pointer"
            />
            <div>
              <p class="text-xs font-bold text-content-primary">{{ vital.label }}</p>
              <p class="text-[10px] text-content-muted font-mono">{{ vital.unit || 'بدون وحدة' }}</p>
            </div>
          </div>

          <button
            v-if="vital.key.startsWith('custom_')"
            @click="removeVital(index)"
            type="button"
            class="p-1.5 text-content-muted hover:text-status-danger hover:bg-status-danger-bg rounded-lg transition cursor-pointer"
            title="حذف هذا القياس"
          >
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <div class="pt-4 border-t border-line-subtle flex justify-end">
        <button
          type="button"
          @click="saveVitals"
          :disabled="saving"
          class="px-6 py-2.5 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer disabled:opacity-50"
        >
          <Save class="w-4 h-4" />
          <span>{{ saving ? 'جارٍ الحفظ...' : 'حفظ إعدادات القياسات' }}</span>
        </button>
      </div>
    </div>

    <!-- Tab 2: Print Margins & Live Scaled Paper Simulator -->
    <div v-if="activeTab === 'print'" class="space-y-6">
      <div>
        <h3 class="text-sm font-bold text-content-primary">هوامش ورق الروشتة المطبوع مسبقاً (Letterhead Print Margins)</h3>
        <p class="text-xs text-content-muted">إذا كانت عيادتك تستخدم أوراقاً مطبوعة مسبقاً بشعارك وبياناتك، قم بضبط الهوامش بالمليمتر (mm) لطباعة الروشتة في الفراغ المخصص بدقة تامة.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Right Half: Millimeter Inputs (6 Cols) -->
        <div class="lg:col-span-6 space-y-4 bg-surface-elevated/60 border border-line-subtle p-4 rounded-2xl">
          <div class="flex items-center justify-between pb-2 border-b border-line-subtle">
            <span class="text-xs font-bold text-content-primary">قياسات الهوامش (بالمليمتر)</span>
            <div class="flex items-center gap-2">
              <span class="text-[11px] text-content-muted">حجم الورقة:</span>
              <select
                v-model="paperSize"
                class="px-2 py-1 text-xs bg-surface-card border border-line-subtle rounded-lg font-bold text-content-primary focus:outline-none"
              >
                <option value="A5">A5 (148 × 210 mm)</option>
                <option value="A4">A4 (210 × 297 mm)</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-content-primary mb-1">الهامش العلوي (Top mm)</label>
              <input
                v-model.number="printMargins.margin_top"
                type="number"
                min="0"
                max="120"
                class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-mono focus:outline-none focus:border-brand"
              />
              <span class="text-[10px] text-content-muted">مساحة الترويسة المطبوعة</span>
            </div>

            <div>
              <label class="block text-xs font-bold text-content-primary mb-1">الهامش السفلي (Bottom mm)</label>
              <input
                v-model.number="printMargins.margin_bottom"
                type="number"
                min="0"
                max="100"
                class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-mono focus:outline-none focus:border-brand"
              />
              <span class="text-[10px] text-content-muted">مساحة التذييل المطبوع</span>
            </div>

            <div>
              <label class="block text-xs font-bold text-content-primary mb-1">الهامش الأيمن (Right mm)</label>
              <input
                v-model.number="printMargins.margin_right"
                type="number"
                min="0"
                max="60"
                class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-mono focus:outline-none focus:border-brand"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-content-primary mb-1">الهامش الأيسر (Left mm)</label>
              <input
                v-model.number="printMargins.margin_left"
                type="number"
                min="0"
                max="60"
                class="w-full px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary font-mono focus:outline-none focus:border-brand"
              />
            </div>
          </div>

          <div class="pt-3 border-t border-line-subtle flex items-center justify-between">
            <button
              type="button"
              @click="handleTestPrint"
              class="px-4 py-2 bg-surface-card hover:bg-surface-elevated text-brand border border-brand-border rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs"
            >
              <Printer class="w-3.5 h-3.5" />
              <span>🖨️ طباعة ورقة اختبار القياسات</span>
            </button>

            <button
              type="button"
              @click="saveMargins"
              :disabled="saving"
              class="px-5 py-2 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50 shadow-xs"
            >
              <Save class="w-3.5 h-3.5" />
              <span>{{ saving ? 'جارٍ الحفظ...' : 'حفظ الهوامش' }}</span>
            </button>
          </div>
        </div>

        <!-- Left Half: LIVE SCALED PAPER VISUAL SIMULATOR (6 Cols) -->
        <div class="lg:col-span-6 bg-surface-elevated border border-line-subtle rounded-2xl p-5 flex flex-col items-center justify-center">
          <p class="text-[11px] font-bold text-content-secondary mb-3 flex items-center gap-1">
            <span>📄</span>
            <span>معاينة حية دقيقة لورقة الروشتة ({{ paperSize }})</span>
          </p>

          <!-- Scaled Physical Sheet -->
          <div
            class="bg-white text-slate-800 rounded-lg shadow-md border border-slate-300 relative flex flex-col justify-between overflow-hidden transition-all"
            :style="{
              width: paperSize === 'A5' ? '240px' : '280px',
              height: paperSize === 'A5' ? '340px' : '396px',
            }"
          >
            <!-- Top Margin Zone (Header) -->
            <div
              class="border-b-2 border-dashed border-rose-400 bg-rose-50/70 text-rose-700 text-[10px] font-bold flex items-center justify-center text-center px-1 transition-all"
              :style="{ height: `${Math.min(printMargins.margin_top * 1.5, 120)}px` }"
            >
              <span>منطقة الترويسة المطبوعة مسبقاً ({{ printMargins.margin_top }}mm)</span>
            </div>

            <!-- Printable Middle Region -->
            <div
              class="flex-1 flex flex-col justify-between p-2 text-[10px] bg-white"
              :style="{
                paddingRight: `${Math.min(printMargins.margin_right * 1.2, 40)}px`,
                paddingLeft: `${Math.min(printMargins.margin_left * 1.2, 40)}px`,
              }"
            >
              <div class="space-y-1">
                <div class="h-2 bg-slate-200 rounded w-2/3"></div>
                <div class="h-2 bg-slate-100 rounded w-1/2"></div>
              </div>
              <div class="py-2 text-center text-emerald-700 font-bold bg-emerald-50/60 rounded border border-emerald-200 text-[10px]">
                ℞ مساحة طباعة الأدوية والتشخيص
              </div>
              <div class="space-y-1">
                <div class="h-1.5 bg-slate-100 rounded w-full"></div>
                <div class="h-1.5 bg-slate-100 rounded w-4/5"></div>
              </div>
            </div>

            <!-- Bottom Margin Zone (Footer) -->
            <div
              class="border-t-2 border-dashed border-rose-400 bg-rose-50/70 text-rose-700 text-[10px] font-bold flex items-center justify-center text-center px-1 transition-all"
              :style="{ height: `${Math.min(printMargins.margin_bottom * 1.5, 100)}px` }"
            >
              <span>منطقة التذييل المطبوع مسبقاً ({{ printMargins.margin_bottom }}mm)</span>
            </div>
          </div>

          <p class="text-[10px] text-content-muted mt-3 text-center">
            المنطقة باللون الأحمر لن يُطبع فيها أي نص لحماية الورق المطبوع مسبقاً
          </p>
        </div>
      </div>
    </div>

    <!-- Tab 3: Drugs Catalog -->
    <div v-if="activeTab === 'drugs'" class="space-y-5">
      <div>
        <h3 class="text-sm font-bold text-content-primary">كتالوج الأدوية المفضلة والجرعات</h3>
        <p class="text-xs text-content-muted">إضافة الأدوية هنا تتيح الإكمال التلقائي الذكي فوراً أثناء كتابة الروشتة في غرفة الكشف</p>
      </div>

      <!-- Add Drug Form -->
      <div class="bg-surface-elevated border border-line-subtle p-3.5 rounded-2xl space-y-3">
        <h4 class="text-xs font-bold text-content-primary flex items-center gap-1.5">
          <Plus class="w-4 h-4 text-brand" />
          <span>إضافة دواء جديد للكتالوج</span>
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
          <input
            v-model="newDrug.name"
            type="text"
            placeholder="اسم الدواء التجاري..."
            class="px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary focus:outline-none focus:border-brand"
          />
          <input
            v-model="newDrug.default_dosage"
            type="text"
            placeholder="الجرعة (قرص 500mg)..."
            class="px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary focus:outline-none focus:border-brand"
          />
          <input
            v-model="newDrug.default_frequency"
            type="text"
            placeholder="التكرار (كل 8 ساعات)..."
            class="px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary focus:outline-none focus:border-brand"
          />
          <input
            v-model="newDrug.default_instructions"
            type="text"
            placeholder="التعليمات (بعد الأكل)..."
            class="px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary focus:outline-none focus:border-brand"
          />
          <button
            type="button"
            @click="handleSaveDrug"
            class="px-4 py-2 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
          >
            <Plus class="w-4 h-4" />
            <span>حفظ الدواء</span>
          </button>
        </div>
      </div>

      <!-- Drugs Table -->
      <div class="bg-surface-elevated border border-line-subtle rounded-2xl overflow-hidden">
        <table class="w-full text-right text-xs">
          <thead class="bg-surface-card border-b border-line-subtle font-bold text-content-secondary">
            <tr>
              <th class="py-2.5 px-3">اسم الدواء</th>
              <th class="py-2.5 px-3">الجرعة الافتراضية</th>
              <th class="py-2.5 px-3">التكرار</th>
              <th class="py-2.5 px-3">التعليمات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line-subtle">
            <tr v-for="d in settingsStore.drugs" :key="d.id" class="hover:bg-surface-card/60 transition">
              <td class="py-2 px-3 font-bold text-content-primary">{{ d.name }}</td>
              <td class="py-2 px-3 text-content-secondary">{{ d.default_dosage || '-' }}</td>
              <td class="py-2 px-3 text-content-secondary">{{ d.default_frequency || '-' }}</td>
              <td class="py-2 px-3 text-content-muted">{{ d.default_instructions || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tab 4: Labs Catalog -->
    <div v-if="activeTab === 'labs'" class="space-y-5">
      <div>
        <h3 class="text-sm font-bold text-content-primary">كتالوج التحاليل الطبية وصور الأشعة</h3>
        <p class="text-xs text-content-muted">إضافة الفحوصات هنا لطلبها بضغطة زر وتضمينها بالروشتة المطبوعة</p>
      </div>

      <div class="bg-surface-elevated border border-line-subtle p-3.5 rounded-2xl space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
          <input
            v-model="newLab.name"
            type="text"
            placeholder="اسم التحليل أو الفحص (CBC، سونار بطن وحوض...)"
            class="px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary focus:outline-none focus:border-brand"
          />
          <select
            v-model="newLab.category"
            class="px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary focus:outline-none"
          >
            <option value="lab">تحليل دم / مختبرات (Lab Test)</option>
            <option value="radiology">أشعة / سونار / رنين (Radiology)</option>
            <option value="procedure">إجراء طبي / منظار (Procedure)</option>
          </select>
          <button
            type="button"
            @click="handleSaveLab"
            class="px-4 py-2 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
          >
            <Plus class="w-4 h-4" />
            <span>إضافة الفحص</span>
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
        <div
          v-for="lab in settingsStore.labs"
          :key="lab.id"
          class="p-3 bg-surface-elevated border border-line-subtle rounded-xl flex items-center justify-between"
        >
          <div>
            <p class="text-xs font-bold text-content-primary">{{ lab.name }}</p>
            <p class="text-[10px] text-brand font-medium">
              {{ lab.category === 'radiology' ? 'أشعة وتصوير' : 'تحليل مختبر' }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 5: Templates -->
    <div v-if="activeTab === 'templates'" class="space-y-5">
      <div>
        <h3 class="text-sm font-bold text-content-primary">قوالب الروشتات الجاهزة (Protocol Prescriptions)</h3>
        <p class="text-xs text-content-muted">إنشاء بروتوكولات علاجية مسبقة لإدراج مجموعة أدوية وتشخيص بضغطة واحدة</p>
      </div>

      <div class="bg-surface-elevated border border-line-subtle p-4 rounded-2xl space-y-3">
        <h4 class="text-xs font-bold text-content-primary">إنشاء نموذج جديد</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <input
            v-model="newTemplate.template_name"
            type="text"
            placeholder="اسم النموذج (بروتوكول جرثومة المعدة...)"
            class="px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary focus:outline-none focus:border-brand"
          />
          <input
            v-model="newTemplate.diagnosis_default"
            type="text"
            placeholder="التشخيص الافتراضي (H. Pylori Gastritis...)"
            class="px-3 py-2 text-xs bg-surface-card rounded-xl border border-line-subtle text-content-primary focus:outline-none focus:border-brand"
          />
        </div>

        <div class="space-y-2 pt-2">
          <div
            v-for="(row, idx) in newTemplate.drugs_json"
            :key="idx"
            class="grid grid-cols-1 sm:grid-cols-4 gap-2 items-center"
          >
            <input
              v-model="row.drug_name"
              type="text"
              placeholder="اسم الدواء..."
              class="px-3 py-1.5 text-xs bg-surface-card rounded-lg border border-line-subtle text-content-primary"
            />
            <input
              v-model="row.dosage"
              type="text"
              placeholder="الجرعة..."
              class="px-3 py-1.5 text-xs bg-surface-card rounded-lg border border-line-subtle text-content-primary"
            />
            <input
              v-model="row.instructions"
              type="text"
              placeholder="التعليمات..."
              class="px-3 py-1.5 text-xs bg-surface-card rounded-lg border border-line-subtle text-content-primary"
            />
            <button
              type="button"
              @click="removeTemplateDrugRow(idx)"
              class="text-content-muted hover:text-status-danger p-1 text-xs self-center"
            >
              حذف
            </button>
          </div>

          <button
            type="button"
            @click="addTemplateDrugRow"
            class="text-xs text-brand hover:underline font-bold"
          >
            + إضافة دواء آخر للنموذج
          </button>
        </div>

        <div class="pt-3 border-t border-line-subtle flex justify-end">
          <button
            type="button"
            @click="handleSaveTemplate"
            class="px-5 py-2 bg-brand hover:bg-brand-hover text-content-inverse rounded-xl text-xs font-bold shadow-xs cursor-pointer"
          >
            حفظ نموذج الروشتة
          </button>
        </div>
      </div>

      <!-- Existing Templates -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div
          v-for="tpl in settingsStore.templates"
          :key="tpl.id"
          class="p-4 bg-surface-elevated border border-line-subtle rounded-2xl flex flex-col justify-between"
        >
          <div>
            <h4 class="text-xs font-bold text-content-primary">{{ tpl.template_name }}</h4>
            <p class="text-[11px] text-brand font-medium mt-0.5">{{ tpl.diagnosis_default || 'بدون تشخيص' }}</p>
            <p class="text-[10px] text-content-muted mt-2">
              الأدوية: {{ (tpl.drugs_json || []).map(d => d.drug_name).join('، ') }}
            </p>
          </div>
          <div class="mt-3 pt-2 border-t border-line-subtle flex justify-end">
            <button
              type="button"
              @click="settingsStore.deleteTemplate(tpl.id)"
              class="text-[11px] text-status-danger hover:underline font-bold"
            >
              حذف النموذج
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useSettingsStore } from '../../stores/settings';
import { useAuthStore } from '../../stores/auth';
import alerts from '../../utils/alerts';
import {
  Activity,
  Printer,
  Pill,
  Sparkles,
  FlaskConical,
  Plus,
  Save,
  Trash2,
} from 'lucide-vue-next';

const settingsStore = useSettingsStore();
const authStore = useAuthStore();

const activeTab = ref('print'); // Default to print tab as requested
const paperSize = ref('A5');
const saving = ref(false);

const defaultVitalsList = [
  { key: 'bp', label: 'ضغط الدم (BP)', unit: 'mmHg', enabled: true },
  { key: 'pulse', label: 'معدل النبض (Pulse)', unit: 'bpm', enabled: true },
  { key: 'temp', label: 'درجة الحرارة (Temp)', unit: '°C', enabled: true },
  { key: 'weight', label: 'الوزن (Weight)', unit: 'kg', enabled: true },
  { key: 'height', label: 'الطول (Height)', unit: 'cm', enabled: false },
  { key: 'sugar', label: 'السكر العشوائي (Glucose)', unit: 'mg/dL', enabled: true },
  { key: 'oxygen', label: 'نسبة الأكسجين (SpO2)', unit: '%', enabled: true },
];

const vitalsList = ref([...defaultVitalsList]);
const newVital = ref({ label: '', unit: '', enabled: true });

const printMargins = ref({
  margin_top: 35,
  margin_bottom: 25,
  margin_left: 15,
  margin_right: 15,
});

const newDrug = ref({
  name: '',
  default_dosage: '',
  default_frequency: 'كل 8 ساعات',
  default_instructions: 'بعد الأكل',
});

const newLab = ref({ name: '', category: 'lab' });

const newTemplate = ref({
  template_name: '',
  diagnosis_default: '',
  drugs_json: [{ drug_name: '', dosage: '', instructions: '' }],
});

const addTemplateDrugRow = () => {
  newTemplate.value.drugs_json.push({ drug_name: '', dosage: '', instructions: '' });
};

const removeTemplateDrugRow = (idx) => {
  newTemplate.value.drugs_json.splice(idx, 1);
  if (newTemplate.value.drugs_json.length === 0) {
    addTemplateDrugRow();
  }
};

onMounted(async () => {
  await settingsStore.fetchAll();
  if (settingsStore.settings?.vitals_config) {
    const cfg = settingsStore.settings.vitals_config;
    if (Array.isArray(cfg)) {
      vitalsList.value = cfg;
    }
  }
  if (settingsStore.settings?.print_settings) {
    printMargins.value = { ...printMargins.value, ...settingsStore.settings.print_settings };
  }
});

const addCustomVital = () => {
  if (!newVital.value.label.trim()) return;
  const key = 'custom_' + Date.now().toString(36);
  vitalsList.value.push({
    key,
    label: newVital.value.label.trim(),
    unit: newVital.value.unit.trim(),
    enabled: true,
  });
  newVital.value = { label: '', unit: '', enabled: true };
};

const removeVital = (idx) => {
  vitalsList.value.splice(idx, 1);
};

const saveVitals = async () => {
  saving.value = true;
  try {
    await settingsStore.updateVitals(vitalsList.value);
    await authStore.fetchProfile();
    alerts.success('تم الحفظ بنجاح', 'تم تحديث قائمة القياسات الحيوية وسيرى موظف الاستقبال القياسات الجديدة فوراً');
  } catch (e) {
    alerts.error('فشل حفظ الإعدادات', e.response?.data?.message || 'حدث خطأ');
  } finally {
    saving.value = false;
  }
};

const saveMargins = async () => {
  saving.value = true;
  try {
    await settingsStore.updatePrintMargins(printMargins.value);
    alerts.success('تم حفظ الهوامش بنجاح', 'سيتم تطبيق الهوامش على كافة الروشتات المطبوعة من العيادة فوراً');
  } catch (e) {
    alerts.error('فشل حفظ الهوامش', e.response?.data?.message || 'حدث خطأ');
  } finally {
    saving.value = false;
  }
};

const handleTestPrint = () => {
  const top = printMargins.value.margin_top;
  const bottom = printMargins.value.margin_bottom;
  const right = printMargins.value.margin_right;
  const left = printMargins.value.margin_left;
  const size = paperSize.value;

  const win = window.open('', '_blank');
  win.document.write(`
    <!DOCTYPE html>
    <html dir="rtl">
    <head>
      <meta charset="utf-8">
      <title>ورقة اختبار هوامش الطباعة</title>
      <style>
        @page {
          size: ${size};
          margin: 0;
        }
        body {
          margin: 0;
          padding: 0;
          font-family: sans-serif;
          box-sizing: border-box;
        }
        .container {
          box-sizing: border-box;
          width: 100vw;
          height: 100vh;
          padding-top: ${top}mm;
          padding-bottom: ${bottom}mm;
          padding-right: ${right}mm;
          padding-left: ${left}mm;
          display: flex;
          flex-direction: column;
          justify-content: space-between;
        }
        .header-box {
          border: 2px dashed #0f766e;
          background: #f0fdfa;
          padding: 10px;
          text-align: center;
          font-size: 14px;
          font-weight: bold;
          color: #0f766e;
        }
        .body-box {
          border: 1px solid #94a3b8;
          padding: 15px;
          text-align: center;
          font-size: 12px;
          color: #334155;
          flex: 1;
          margin: 10px 0;
        }
      </style>
    </head>
    <body onload="window.print()">
      <div class="container">
        <div class="header-box">
          بداية منطقة الطباعة (أعلى ورقة الروشتة بعد ${top}mm)
        </div>
        <div class="body-box">
          <p><strong>منطقة طباعة الأدوية والتعليمات</strong></p>
          <p>الهامش الأيمن: ${right}mm | الهامش الأيسر: ${left}mm</p>
          <p>إذا تطابقت هذه الحدود مع الفراغ المخصص في مطبوعاتك، فإن القياسات صحيحة تماماً.</p>
        </div>
        <div class="header-box">
          نهاية منطقة الطباعة (أسفل ورقة الروشتة قبل ${bottom}mm)
        </div>
      </div>
    </body>
    </html>
  `);
  win.document.close();
};

const handleSaveDrug = async () => {
  if (!newDrug.value.name.trim()) return;
  await settingsStore.saveDrug(newDrug.value);
  newDrug.value = { name: '', default_dosage: '', default_frequency: 'كل 8 ساعات', default_instructions: 'بعد الأكل' };
  alerts.toast('تمت إضافة الدواء بنجاح');
};

const handleSaveLab = async () => {
  if (!newLab.value.name.trim()) return;
  await settingsStore.saveLab(newLab.value);
  newLab.value = { name: '', category: 'lab' };
  alerts.toast('تمت إضافة الفحص بنجاح');
};

const handleSaveTemplate = async () => {
  if (!newTemplate.value.template_name.trim()) return;
  await settingsStore.saveTemplate(newTemplate.value);
  newTemplate.value = { template_name: '', diagnosis_default: '', drugs_json: [{ drug_name: '', dosage: '', instructions: '' }] };
  alerts.toast('تم حفظ النموذج بنجاح');
};
</script>
