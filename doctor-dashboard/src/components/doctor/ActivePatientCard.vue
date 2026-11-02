<template>
  <div class="bg-slate-950 border border-slate-800 rounded-3xl p-6 shadow-xl relative overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-5">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white flex flex-col items-center justify-center shadow-lg font-black">
          <span class="text-[10px] font-bold uppercase tracking-wider opacity-80">رقم</span>
          <span class="text-2xl leading-none">#{{ patientData?.queue_number }}</span>
        </div>

        <div>
          <div class="flex items-center gap-3">
            <h2 class="text-2xl font-black text-white">{{ patientData?.patient?.name }}</h2>
            <span
              class="px-3 py-1 rounded-full text-xs font-bold"
              :class="patientData?.visit_type === 'followup' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'"
            >
              {{ patientData?.visit_type === 'followup' ? 'استشارة / إعادة' : 'كشف جديد' }}
            </span>
          </div>

          <div class="flex items-center gap-4 text-xs text-slate-400 mt-2 font-medium">
            <span>👤 {{ patientData?.patient?.gender === 'female' ? 'أنثى' : 'ذكر' }}</span>
            <span v-if="patientData?.patient?.birth_year">🎂 {{ new Date().getFullYear() - patientData?.patient?.birth_year }} سنة</span>
            <span>📞 {{ patientData?.patient?.phone }}</span>
            <span class="font-mono text-slate-500">كود: {{ patientData?.reference_code }}</span>
          </div>
        </div>
      </div>

      <div class="text-left bg-slate-900 border border-slate-800 px-4 py-3 rounded-2xl">
        <p class="text-[11px] text-slate-400">وقت الدخول الفعلي</p>
        <p class="text-sm font-bold text-emerald-400 font-mono mt-0.5">
          {{ patientData?.actual_entered_at ? new Date(patientData.actual_entered_at).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }) : 'الآن' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  patientData: Object,
});
</script>
