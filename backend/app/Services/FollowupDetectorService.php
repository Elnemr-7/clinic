<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Scopes\TenantScope;
use Carbon\Carbon;

class FollowupDetectorService
{
    public function checkEligibility(int $tenantId, int $clinicId, string $phone): array
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        $patient = Patient::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($cleanPhone, $phone) {
                $q->where('phone', $phone)
                  ->orWhere('phone', $cleanPhone);
            })
            ->first();

        if (!$patient) {
            return [
                'eligible' => false,
                'patient' => null,
                'reason' => 'patient_not_found',
            ];
        }

        $clinic = Clinic::withoutGlobalScope(TenantScope::class)->find($clinicId);
        if (!$clinic) {
            return [
                'eligible' => false,
                'patient' => $patient,
                'reason' => 'clinic_not_found',
            ];
        }

        $daysLimit = $clinic->followup_days_limit ?? 10;
        $cutOffDate = Carbon::today()->subDays($daysLimit);

        $lastCompletedVisit = Appointment::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('clinic_id', $clinicId)
            ->where('patient_id', $patient->id)
            ->where('visit_type', 'new_visit')
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $cutOffDate)
            ->latest('appointment_date')
            ->first();

        if ($lastCompletedVisit) {
            // Check if follow-up was already consumed for this specific previous appointment date
            $consumedFollowup = Appointment::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('clinic_id', $clinicId)
                ->where('patient_id', $patient->id)
                ->where('visit_type', 'followup')
                ->where('appointment_date', '>', $lastCompletedVisit->appointment_date)
                ->whereIn('status', ['arrived_waiting', 'in_consultation', 'completed'])
                ->exists();

            if (!$consumedFollowup) {
                return [
                    'eligible' => true,
                    'patient' => $patient,
                    'last_visit_date' => $lastCompletedVisit->appointment_date->format('Y-m-d'),
                    'followup_price' => (float)$clinic->followup_price,
                    'message' => 'المريض داخل فترة الاستشارة المسموحة (آخر كشف بتاريخ ' . $lastCompletedVisit->appointment_date->format('Y-m-d') . ')',
                ];
            }
        }

        return [
            'eligible' => false,
            'patient' => $patient,
            'reason' => 'no_valid_new_visit',
            'consultation_price' => (float)$clinic->consultation_price,
        ];
    }
}
