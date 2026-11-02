<template>
  <div class="bg-surface-card border border-line-subtle rounded-2xl p-4 lg:p-5 shadow-xs relative overflow-hidden transition-all">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <!-- Turn Number Badge -->
        <div class="w-14 h-14 rounded-2xl bg-brand text-content-inverse flex flex-col items-center justify-center shadow-xs font-black shrink-0">
          <span class="text-[9px] font-bold uppercase tracking-wider opacity-90">الدور</span>
          <span class="text-2xl leading-none font-mono">#{{ appointment?.queue_number || '-' }}</span>
        </div>

        <div>
          <div class="flex items-center gap-2.5">
            <h2 class="text-lg lg:text-xl font-black text-content-primary">{{ appointment?.patient?.name }}</h2>
            <span
              class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
              :class="appointment?.appointment_type === 'followup'
                ? 'bg-status-info-bg text-status-info border-status-info-border'
                : 'bg-status-success-bg text-status-success border-status-success-border'"
            >
              {{ appointment?.appointment_type === 'followup' ? 'استشارة مجانية' : 'كشف جديد' }}
            </span>
          </div>

          <div class="flex flex-wrap items-center gap-3 text-xs text-content-secondary mt-1 font-medium">
            <span>👤 {{ appointment?.patient?.gender === 'female' ? 'أنثى' : 'ذكر' }}</span>
            <span v-if="patientAge">🎂 {{ patientAge }} سنة</span>
            <span class="font-mono text-content-muted" dir="ltr">{{ appointment?.patient?.phone }}</span>
            <span class="font-mono text-brand font-bold">#{{ appointment?.reference_code }}</span>
          </div>
        </div>
      </div>

      <!-- Actual Entrance Time -->
      <div class="text-left bg-surface-elevated border border-line-subtle px-3.5 py-2.5 rounded-xl">
        <p class="text-[10px] text-content-muted font-bold">وقت الدخول الفعلي</p>
        <p class="text-xs font-bold text-brand font-mono mt-0.5">
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
