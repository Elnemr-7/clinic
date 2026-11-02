<?php

namespace App\Http\Controllers\Public;

use App\Events\QueueUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicShift;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Tenant;
use App\Scopes\TenantScope;
use App\Services\FollowupDetectorService;
use App\Services\QueueTimeCalculatorService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicBookingController extends Controller
{
    use ApiResponseTrait;

    protected FollowupDetectorService $followupService;
    protected QueueTimeCalculatorService $queueService;

    public function __construct(FollowupDetectorService $followupService, QueueTimeCalculatorService $queueService)
    {
        $this->followupService = $followupService;
        $this->queueService = $queueService;
    }

    public function tenantByHost(Request $request): JsonResponse
    {
        $host = $request->input('host', $request->getHost());

        // Clean port if present
        $hostParts = explode(':', $host);
        $cleanHost = $hostParts[0];

        // Check custom domain first
        $tenant = Tenant::withoutGlobalScope(TenantScope::class)
            ->where('custom_domain', $cleanHost)
            ->where('status', 'active')
            ->with(['clinics.shifts', 'services'])
            ->first();

        // Or match subdomain (e.g. dr-ahmed.platform.com -> dr-ahmed)
        if (!$tenant) {
            $subdomain = explode('.', $cleanHost)[0];
            $tenant = Tenant::withoutGlobalScope(TenantScope::class)
                ->where('subdomain', $subdomain)
                ->where('status', 'active')
                ->with(['clinics.shifts', 'services'])
                ->first();
        }

        // Preview fallback /d/{subdomain}
        if (!$tenant && $request->filled('subdomain')) {
            $tenant = Tenant::withoutGlobalScope(TenantScope::class)
                ->where('subdomain', $request->subdomain)
                ->where('status', 'active')
                ->with(['clinics.shifts', 'services'])
                ->first();
        }

        if (!$tenant) {
            return $this->errorResponse('عيادة الطبيب غير موجودة أو معلقة', null, 404);
        }

        return $this->successResponse($tenant);
    }

    public function clinicDetails(int $clinicId): JsonResponse
    {
        $clinic = Clinic::withoutGlobalScope(TenantScope::class)
            ->with(['shifts', 'daysOff'])
            ->findOrFail($clinicId);

        return $this->successResponse($clinic);
    }

    public function checkEligibility(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required',
            'clinic_id' => 'required|integer',
        ]);

        $clinic = Clinic::withoutGlobalScope(TenantScope::class)->findOrFail($request->clinic_id);
        $result = $this->followupService->checkEligibility($clinic->tenant_id, $clinic->id, $request->phone);

        return $this->successResponse($result);
    }

    public function bookAppointment(Request $request): JsonResponse
    {
        if (!$request->has('date') && $request->has('appointment_date')) {
            $request->merge(['date' => $request->appointment_date]);
        }
        if (!$request->has('patient_name') && $request->has('name')) {
            $request->merge(['patient_name' => $request->name]);
        }

        $request->validate([
            'clinic_id' => 'required|integer',
            'shift_id' => 'required|integer',
            'date' => 'required|date',
            'patient_name' => 'required|string',
            'phone' => 'required|string',
            'gender' => 'nullable|in:male,female',
            'age' => 'nullable|integer',
        ]);

        $clinic = Clinic::withoutGlobalScope(TenantScope::class)->findOrFail($request->clinic_id);
        $shift = ClinicShift::withoutGlobalScope(TenantScope::class)->findOrFail($request->shift_id);
        $date = Carbon::parse($request->date);

        // Check if date is in clinic_days_off
        $dayOff = $clinic->daysOff()->where('date', $date->toDateString())->first();
        if ($dayOff) {
            return $this->errorResponse("عذراً، العيادة مغلقة في هذا التاريخ نظراً لـ: {$dayOff->reason_ar}");
        }

        // Check max patients on shift
        $currentBookingsCount = Appointment::withoutGlobalScope(TenantScope::class)
            ->where('clinic_id', $clinic->id)
            ->where('shift_id', $shift->id)
            ->whereDate('appointment_date', $date)
            ->count();

        if ($shift->max_patients && $currentBookingsCount >= $shift->max_patients) {
            return $this->errorResponse('عذراً، اكتمل العدد الأقصى للحجوزات في هذه الفترة، يرجى اختيار موعد آخر');
        }

        // Find or create patient
        $patient = Patient::withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['tenant_id' => $clinic->tenant_id, 'phone' => $request->phone],
            [
                'name' => $request->patient_name,
                'gender' => $request->gender ?? 'male',
                'birth_year' => $request->age ? (date('Y') - (int)$request->age) : null,
            ]
        );

        // Followup Eligibility Check
        $followupCheck = $this->followupService->checkEligibility($clinic->tenant_id, $clinic->id, $request->phone);
        $visitType = $followupCheck['eligible'] ? 'followup' : 'new_visit';
        $fee = ($visitType === 'followup') ? (float)$clinic->followup_price : (float)$clinic->consultation_price;

        $queueNumber = $currentBookingsCount + 1;
        $windows = $this->queueService->calculateInitialWindow($shift, $clinic, $queueNumber, $date);
        $refCode = 'CLN-' . strtoupper(Str::random(5));

        $status = ($clinic->booking_payment_mode === 'require_prepayment') ? 'pending_payment' : 'booked';

        $appointment = Appointment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $clinic->tenant_id,
            'clinic_id' => $clinic->id,
            'shift_id' => $shift->id,
            'patient_id' => $patient->id,
            'appointment_date' => $date,
            'queue_number' => $queueNumber,
            'reference_code' => $refCode,
            'booking_source' => 'online',
            'visit_type' => $visitType,
            'status' => $status,
            'estimated_window_start' => $windows['estimated_window_start'],
            'estimated_window_end' => $windows['estimated_window_end'],
            'consultation_fee' => $fee,
            'total_amount' => $fee,
            'is_paid' => false,
        ]);

        event(new QueueUpdatedEvent($clinic->id, $shift->id, $date->toDateString()));

        // WhatsApp Inbound Activation Deep-link
        $tenant = $clinic->tenant;
        $waPhone = preg_replace('/[^0-9]/', '', $tenant->whatsapp_phone_number ?? '');
        $waText = urlencode("تفعيل حجز رقم {$refCode}");
        $waActivationUrl = !empty($waPhone) ? "https://wa.me/{$waPhone}?text={$waText}" : null;

        return $this->successResponse([
            'queue_number' => $queueNumber,
            'reference_code' => $refCode,
            'estimated_window_start' => $windows['estimated_window_start'],
            'estimated_window_end' => $windows['estimated_window_end'],
            'visit_type' => $visitType,
            'amount_due' => $fee,
            'payment_instructions' => $clinic->prepayment_instructions_ar,
            'whatsapp_activation_url' => $waActivationUrl,
            'is_followup' => $visitType === 'followup',
        ], 'تم تسجيل حجزك بنجاح!');
    }

    public function liveQueueStatus(string $referenceCode): JsonResponse
    {
        $appointment = Appointment::withoutGlobalScope(TenantScope::class)
            ->where('reference_code', $referenceCode)
            ->with(['clinic.tenant', 'patient', 'shift'])
            ->firstOrFail();

        // Get current in consultation
        $currentInRoom = Appointment::withoutGlobalScope(TenantScope::class)
            ->where('clinic_id', $appointment->clinic_id)
            ->where('shift_id', $appointment->shift_id)
            ->whereDate('appointment_date', $appointment->appointment_date)
            ->where('status', 'in_consultation')
            ->first();

        // Remaining ahead
        $remainingAhead = Appointment::withoutGlobalScope(TenantScope::class)
            ->where('clinic_id', $appointment->clinic_id)
            ->where('shift_id', $appointment->shift_id)
            ->whereDate('appointment_date', $appointment->appointment_date)
            ->whereIn('status', ['arrived_waiting', 'booked'])
            ->where('queue_number', '<', $appointment->queue_number)
            ->count();

        $prescriptionToken = null;
        if ($appointment->status === 'completed') {
            $consultation = Consultation::withoutGlobalScope(TenantScope::class)
                ->where('appointment_id', $appointment->id)
                ->first();
            $prescriptionToken = $consultation?->pdf_short_token;
        }

        return $this->successResponse([
            'appointment' => $appointment,
            'current_in_room_queue' => $currentInRoom?->queue_number,
            'remaining_ahead' => $remainingAhead,
            'prescription_token' => $prescriptionToken,
        ]);
    }

    public function tvQueue(string $code): JsonResponse
    {
        $clinic = Clinic::withoutGlobalScope(TenantScope::class)
            ->where('tv_screen_code', $code)
            ->with(['tenant'])
            ->firstOrFail();

        $today = Carbon::today()->toDateString();

        $currentInRoom = Appointment::withoutGlobalScope(TenantScope::class)
            ->where('clinic_id', $clinic->id)
            ->whereDate('appointment_date', $today)
            ->where('status', 'in_consultation')
            ->with('patient')
            ->first();

        $waitingList = Appointment::withoutGlobalScope(TenantScope::class)
            ->where('clinic_id', $clinic->id)
            ->whereDate('appointment_date', $today)
            ->whereIn('status', ['waiting', 'arrived_waiting', 'booked'])
            ->with('patient')
            ->orderBy('queue_number', 'asc')
            ->take(8)
            ->get();

        $maskName = function ($fullName) {
            if (!$fullName) return 'مجهول';
            $parts = explode(' ', trim($fullName));
            if (count($parts) === 1) return $parts[0];
            return $parts[0] . ' ' . mb_substr($parts[count($parts) - 1], 0, 1, 'utf-8') . '***';
        };

        $formatPatient = function ($appt) use ($maskName) {
            if (!$appt) return null;
            return [
                'queue_number' => $appt->queue_number,
                'reference_code' => $appt->reference_code,
                'patient_name' => $maskName($appt->patient?->name),
                'appointment_type' => $appt->appointment_type,
            ];
        };

        $nextPatient = $waitingList->first();
        $restWaiting = $waitingList->slice(1)->values();

        return $this->successResponse([
            'clinic' => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'doctor_name' => $clinic->tenant?->name,
                'specialty' => $clinic->tenant?->medical_specialty,
            ],
            'current_patient' => $formatPatient($currentInRoom),
            'next_patient' => $formatPatient($nextPatient),
            'upcoming' => $restWaiting->map($formatPatient),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function downloadPrescriptionPdf(string $token)
    {
        $consultation = Consultation::withoutGlobalScope(TenantScope::class)
            ->where('pdf_short_token', $token)
            ->firstOrFail();

        $pdfPath = storage_path("app/public/prescriptions/{$token}.pdf");

        if (!file_exists($pdfPath)) {
            $service = app(PrescriptionPdfService::class);
            $pdfPath = $service->generatePrescriptionPdf($consultation);
        }

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="prescription.pdf"',
        ]);
    }
}
