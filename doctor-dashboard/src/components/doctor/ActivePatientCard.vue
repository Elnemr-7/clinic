<template>
  <div class="bg-surface-card border border-line-subtle rounded-2xl p-3 lg:p-3.5 shadow-xs shrink-0 transition-all">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3 min-w-0">
        <!-- Turn Number Badge -->
        <div class="w-11 h-11 rounded-xl bg-brand text-content-inverse flex flex-col items-center justify-center shadow-xs font-black shrink-0">
          <span class="text-[8px] font-bold uppercase tracking-wider opacity-85 leading-none">الدور</span>
          <span class="text-xl leading-none font-mono font-black mt-0.5">#{{ appointment?.queue_number || '-' }}</span>
        </div>

        <div class="min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <h2 class="text-base lg:text-lg font-black text-content-primary leading-normal truncate">
              {{ appointment?.patient?.name || 'مريض بدون اسم' }}
            </h2>
            <span
              class="px-2 py-0.5 rounded-full text-[10px] font-bold border shrink-0"
              :class="appointment?.appointment_type === 'followup'
                ? 'bg-status-info-bg text-status-info border-status-info-border'
                : 'bg-status-success-bg text-status-success border-status-success-border'"
            >
              {{ appointment?.appointment_type === 'followup' ? 'استشارة مجانية' : 'كشف جديد' }}
            </span>
          </div>

          <div class="flex flex-wrap items-center gap-2.5 text-xs text-content-secondary mt-0.5 font-medium">
            <span>👤 {{ appointment?.patient?.gender === 'female' ? 'أنثى' : 'ذكر' }}</span>
            <span v-if="patientAge" class="font-mono">🎂 {{ patientAge }} سنة</span>
            <span class="font-mono text-content-muted" dir="ltr">{{ appointment?.patient?.phone }}</span>
            <span class="font-mono text-brand font-bold">#{{ appointment?.reference_code }}</span>
          </div>
        </div>
      </div>

      <!-- Actual Entrance Time -->
      <div class="text-left bg-surface-elevated border border-line-subtle px-3 py-1.5 rounded-xl shrink-0">
        <p class="text-[9px] text-content-muted font-bold">وقت الدخول</p>
        <p class="text-xs font-bold text-brand font-mono">
          {{ appointment?.actual_entered_at ? new Date(appointment.actual_entered_at).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }) : 'الآن' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  appointment: Object,
  patientData: Object,
});

const patientAge = computed(() => {
  const p = props.appointment?.patient || props.patientData?.patient;
  if (!p) return null;
  if (p.age) return p.age;
  if (p.birth_year) return new Date().getFullYear() - p.birth_year;
  return null;
});
</script>
