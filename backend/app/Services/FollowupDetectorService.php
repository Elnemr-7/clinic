<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Scopes\TenantScope;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;

class FollowupDetectorService
{
    public function checkEligibility(int $tenantId, int $clinicId, string $phone): array
    {
        $normalizedPhone = PhoneNormalizer::normalizeEgyptianPhone($phone);

        $patient = Patient::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($normalizedPhone, $phone) {
                $q->where('phone', $normalizedPhone)
                  ->orWhere('phone', $phone);
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

        $daysLimit = (int)($clinic->followup_days_limit ?? 10);
        $cutOffDate = Carbon::now('Africa/Cairo')->startOfDay()->subDays($daysLimit);

        $lastCompletedVisit = Appointment::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('clinic_id', $clinicId)
            ->where('patient_id', $patient->id)
            ->where('visit_type', 'new_visit')
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $cutOffDate->toDateString())
            ->latest('appointment_date')
            ->first();

        if ($lastCompletedVisit) {
            // Verify no subsequent followup appointment exists (status IN booked, arrived_waiting, in_consultation, completed)
            $consumedFollowup = Appointment::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('clinic_id', $clinicId)
                ->where('patient_id', $patient->id)
                ->where('visit_type', 'followup')
                ->where('appointment_date', '>=', $lastCompletedVisit->appointment_date)
                ->where('id', '!=', $lastCompletedVisit->id)
                ->whereIn('status', ['booked', 'arrived_waiting', 'in_consultation', 'completed'])
                ->exists();

            if (!$consumedFollowup) {
                $visitDate = Carbon::parse($lastCompletedVisit->appointment_date, 'Africa/Cairo')->startOfDay();
                $todayCairo = Carbon::now('Africa/Cairo')->startOfDay();
                $daysElapsed = $visitDate->diffInDays($todayCairo);
                $daysRemaining = max(0, $daysLimit - $daysElapsed);

                return [
                    'eligible' => true,
                    'is_followup' => true,
                    'patient' => $patient,
                    'patient_name' => $patient->name,
                    'last_visit_date' => $lastCompletedVisit->appointment_date->format('Y-m-d'),
                    'days_remaining' => $daysRemaining,
                    'followup_price' => (float)$clinic->followup_price,
                    'message' => 'المريض داخل فترة الاستشارة المسموحة (آخر كشف بتاريخ ' . $lastCompletedVisit->appointment_date->format('Y-m-d') . ')',
                ];
            }
        }

        return [
            'eligible' => false,
            'is_followup' => false,
            'patient' => $patient,
            'patient_name' => $patient->name,
            'reason' => 'no_valid_new_visit',
            'consultation_price' => (float)$clinic->consultation_price,
        ];
    }
}
