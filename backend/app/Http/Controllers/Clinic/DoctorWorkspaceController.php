<?php

namespace App\Http\Controllers\Clinic;

use App\Events\ConsultationFinishedEvent;
use App\Events\QueueUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Jobs\CheckNearTurnNotificationsJob;
use App\Jobs\SendSmsNotificationJob;
use App\Jobs\SendWhatsAppPrescriptionJob;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Consultation;
use App\Models\DoctorDrug;
use App\Models\DoctorLabTest;
use App\Models\Patient;
use App\Models\PatientAttachment;
use App\Services\PrescriptionPdfService;
use App\Services\QueueTimeCalculatorService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DoctorWorkspaceController extends Controller
{
    use ApiResponseTrait;

    protected PrescriptionPdfService $pdfService;
    protected QueueTimeCalculatorService $queueService;

    public function __construct(PrescriptionPdfService $pdfService, QueueTimeCalculatorService $queueService)
    {
        $this->pdfService = $pdfService;
        $this->queueService = $queueService;
    }

    public function currentPatient(Request $request): JsonResponse
    {
        $clinicId = $request->input('clinic_id');
        $tenantId = $request->user()->tenant_id;

        $query = Appointment::where('tenant_id', $tenantId)
            ->where('status', 'in_consultation')
            ->whereDate('appointment_date', today())
            ->with(['patient', 'clinic', 'shift', 'consultation']);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        $appointment = $query->first();

        // Calculate queue counts for badges (includes arrived_waiting and pre-booked waiting today)
        $countsQuery = Appointment::where('tenant_id', $tenantId)
            ->whereDate('appointment_date', today());
        if ($clinicId) {
            $countsQuery->where('clinic_id', $clinicId);
        }

        $waitingCount = (clone $countsQuery)->whereIn('status', ['arrived_waiting', 'waiting', 'booked'])->count();
        $completedCount = (clone $countsQuery)->where('status', 'completed')->count();
        $postponedCount = (clone $countsQuery)->whereIn('status', ['postponed', 'postponed_waiting_arrival'])->count();

        // Previous vitals comparison if patient exists
        $previousVitals = null;
        if ($appointment) {
            $prev = Appointment::where('patient_id', $appointment->patient_id)
                ->where('id', '!=', $appointment->id)
                ->whereNotNull('vitals_data')
                ->latest('appointment_date')
                ->first();
            $previousVitals = $prev?->vitals_data;
        }

        return $this->successResponse([
            'appointment' => $appointment,
            'previous_vitals' => $previousVitals,
            'counts' => [
                'waiting' => $waitingCount,
                'completed' => $completedCount,
                'postponed' => $postponedCount,
            ],
        ]);
    }

    public function savePrescription($appointmentId, Request $request): JsonResponse
    {
        $appointmentId = (int)$appointmentId;
        $tenantId = $request->user()->tenant_id;
        $appointment = Appointment::where('tenant_id', $tenantId)->findOrFail($appointmentId);

        $request->validate([
            'diagnosis' => 'required|string',
            'complaints_symptoms' => 'nullable|string',
            'symptoms' => 'nullable|string',
            'private_doctor_notes' => 'nullable|string',
            'private_notes' => 'nullable|string',
            'prescribed_drugs' => 'nullable|array',
            'drugs' => 'nullable|array',
        ]);

        $drugs = $request->input('prescribed_drugs') ?? $request->input('drugs', []);
        $symptoms = $request->input('complaints_symptoms') ?? $request->input('symptoms');
        $notes = $request->input('private_doctor_notes') ?? $request->input('private_notes');

        // Auto-save unknown drugs into doctor_drugs for this tenant
        foreach ($drugs as $item) {
            if (!empty($item['drug_name'])) {
                DoctorDrug::firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'drug_name' => trim($item['drug_name']),
                    ],
                    [
                        'form_type' => $item['form_type'] ?? 'أقراص',
                        'default_dosage' => $item['dosage'] ?? null,
                        'default_duration' => $item['duration'] ?? null,
                    ]
                );
            }
        }

        $consultation = Consultation::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'tenant_id' => $tenantId,
                'clinic_id' => $appointment->clinic_id,
                'patient_id' => $appointment->patient_id,
                'diagnosis' => $request->diagnosis,
                'complaints_symptoms' => $symptoms,
                'private_doctor_notes' => $notes,
                'prescribed_drugs' => $drugs,
                'pdf_short_token' => $appointment->consultation?->pdf_short_token ?? Str::random(12),
            ]
        );

        // Generate PDF
        $pdfPath = $this->pdfService->generatePrescriptionPdf($consultation);

        // Optional send if requested
        if ($request->boolean('send_whatsapp')) {
            $appointment->update(['whatsapp_opted_in' => true]);
            try {
                SendWhatsAppPrescriptionJob::dispatch($consultation, $pdfPath);
            } catch (\Throwable $e) {
                \Log::warning("Failed to dispatch WhatsApp prescription: " . $e->getMessage());
            }
        }

        if ($request->boolean('send_sms')) {
            try {
                dispatch_sync(new SendSmsNotificationJob($consultation));
            } catch (\Throwable $e) {
                \Log::warning("Failed to dispatch SMS notification: " . $e->getMessage());
            }
        }

        // Correction 1: Keep appointment status as in_consultation!
        return $this->successResponse([
            'consultation' => $consultation->load(['patient', 'clinic']),
            'pdf_url' => url("/rx/{$consultation->pdf_short_token}"),
        ], 'تم حفظ الروشتة الطبية بنجاح');
    }

    public function saveLabsAndNotes($appointmentId, Request $request): JsonResponse
    {
        $appointmentId = (int)$appointmentId;
        $tenantId = $request->user()->tenant_id;
        $appointment = Appointment::where('tenant_id', $tenantId)->findOrFail($appointmentId);

        $labs = $request->input('requested_labs', $request->input('lab_requests', []));

        // Auto-save unknown lab tests into doctor_lab_tests
        foreach ($labs as $test) {
            if (!empty($test['test_name'])) {
                DoctorLabTest::firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'test_name' => trim($test['test_name']),
                        'type' => $test['type'] ?? 'lab',
                    ]
                );
            }
        }

        $consultation = Consultation::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'tenant_id' => $tenantId,
                'clinic_id' => $appointment->clinic_id,
                'patient_id' => $appointment->patient_id,
                'diagnosis' => $appointment->consultation?->diagnosis ?? 'قيد الفحص',
                'requested_labs' => $labs,
                'pdf_short_token' => $appointment->consultation?->pdf_short_token ?? Str::random(12),
            ]
        );

        return $this->successResponse($consultation, 'تم حفظ الفحوصات والتحاليل بنجاح');
    }

    public function saveExtraServicesAndFollowup($appointmentId, Request $request): JsonResponse
    {
        $appointmentId = (int)$appointmentId;
        $tenantId = $request->user()->tenant_id;
        $appointment = Appointment::where('tenant_id', $tenantId)->findOrFail($appointmentId);

        $services = $request->input('selected_services', $request->input('extra_services_billed', []));
        $serviceIds = (array) $request->input('service_ids', []);

        // Collect service IDs from both service_ids input and selected_services items
        if (is_array($services)) {
            foreach ($services as $srv) {
                if (!empty($srv['id'])) {
                    $serviceIds[] = $srv['id'];
                }
            }
        }
        $serviceIds = array_values(array_unique(array_filter($serviceIds)));

        $clinicServices = !empty($serviceIds)
            ? \App\Models\ClinicService::whereIn('id', $serviceIds)->get()->keyBy('id')
            : collect();

        $sanitizedServices = [];
        $extraTotal = 0.0;

        if (empty($services) && !empty($serviceIds)) {
            foreach ($serviceIds as $id) {
                if (isset($clinicServices[$id])) {
                    $s = $clinicServices[$id];
                    $price = max(0.0, (float)$s->price);
                    $sanitizedServices[] = [
                        'id' => $s->id,
                        'service_name' => $s->name_ar ?? $s->name,
                        'price' => $price,
                    ];
                    $extraTotal += $price;
                }
            }
        } else {
            foreach ($services as $srv) {
                $id = $srv['id'] ?? null;
                if ($id && isset($clinicServices[$id])) {
                    $s = $clinicServices[$id];
                    $price = max(0.0, (float)$s->price);
                    $sanitizedServices[] = [
                        'id' => $s->id,
                        'service_name' => $s->name_ar ?? $s->name ?? ($srv['service_name'] ?? ''),
                        'price' => $price,
                    ];
                } else {
                    $price = max(0.0, (float)($srv['price'] ?? 0));
                    $sanitizedServices[] = [
                        'id' => $id,
                        'service_name' => $srv['service_name'] ?? '',
                        'price' => $price,
                    ];
                }
                $extraTotal += $price;
            }
        }

        $services = $sanitizedServices;
        $followupDate = $request->input('followup_date');

        $appointment->update([
            'extra_services_total' => $extraTotal,
            'total_amount' => (float)$appointment->consultation_fee + $extraTotal,
        ]);

        $consultation = Consultation::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'tenant_id' => $tenantId,
                'clinic_id' => $appointment->clinic_id,
                'patient_id' => $appointment->patient_id,
                'diagnosis' => $appointment->consultation?->diagnosis ?? 'فحص عام',
                'selected_services' => $services,
                'followup_date' => $followupDate,
                'pdf_short_token' => $appointment->consultation?->pdf_short_token ?? Str::random(12),
            ]
        );

        return $this->successResponse([
            'appointment' => $appointment,
            'consultation' => $consultation,
        ], 'تم تحديث الخدمات الإضافية وتاريخ الاستشارة بنجاح');
    }

    public function completeAndCallNextPatient(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $clinicId = $request->input('clinic_id');

        return DB::transaction(function () use ($tenantId, $clinicId) {
            $current = Appointment::where('tenant_id', $tenantId)
                ->where('status', 'in_consultation')
                ->whereDate('appointment_date', today())
                ->when($clinicId, fn($q) => $q->where('clinic_id', $clinicId))
                ->lockForUpdate()
                ->first();

            if ($current) {
                $current->update([
                    'status' => 'completed',
                    'actual_completed_at' => now(),
                ]);

                // Auto-create followup appointment if date was specified
                if ($current->consultation && $current->consultation->followup_date) {
                    $targetDate = $current->consultation->followup_date;
                    $dayOfWeek = $targetDate->dayOfWeek;

                    $shift = $current->clinic->shifts()->where('day_of_week', $dayOfWeek)->where('is_active', true)->lockForUpdate()->first();
                    if ($shift) {
                        $nextQueue = Appointment::where('clinic_id', $current->clinic_id)
                            ->where('shift_id', $shift->id)
                            ->whereDate('appointment_date', $targetDate)
                            ->lockForUpdate()
                            ->max('queue_number') + 1;

                        $windows = $this->queueService->calculateInitialWindow($shift, $current->clinic, $nextQueue, $targetDate);

                        Appointment::create([
                            'tenant_id' => $tenantId,
                            'clinic_id' => $current->clinic_id,
                            'shift_id' => $shift->id,
                            'patient_id' => $current->patient_id,
                            'appointment_date' => $targetDate,
                            'queue_number' => $nextQueue,
                            'reference_code' => 'CLN-' . strtoupper(Str::random(5)),
                            'booking_source' => 'clinic_desk',
                            'visit_type' => 'followup',
                            'status' => 'booked',
                            'estimated_window_start' => $windows['estimated_window_start'],
                            'estimated_window_end' => $windows['estimated_window_end'],
                            'consultation_fee' => $current->clinic->followup_price,
                            'total_amount' => $current->clinic->followup_price,
                        ]);
                    }
                }

                event(new ConsultationFinishedEvent($current->consultation ?? new Consultation()));
            }

            // Pull next patient in queue (supports arrived_waiting, waiting, or booked for today) with row lock
            $nextPatient = Appointment::where('tenant_id', $tenantId)
                ->whereIn('status', ['arrived_waiting', 'waiting', 'booked'])
                ->whereDate('appointment_date', today())
                ->when($clinicId, fn($q) => $q->where('clinic_id', $clinicId))
                ->orderByRaw("CASE WHEN status = 'arrived_waiting' THEN 1 WHEN status = 'waiting' THEN 2 ELSE 3 END")
                ->orderBy('queue_number', 'asc')
                ->lockForUpdate()
                ->first();

            if ($nextPatient) {
                $nextPatient->update([
                    'status' => 'in_consultation',
                    'actual_entered_at' => now(),
                ]);

                // Recalculate queue time for remaining patients
                $this->queueService->recalculateQueueOnStatusChange(
                    $nextPatient->clinic,
                    $nextPatient->shift,
                    today(),
                    $nextPatient->queue_number
                );

                // Trigger turn notifications
                dispatch(new CheckNearTurnNotificationsJob($nextPatient->clinic_id, $nextPatient->shift_id, today()->toDateString()));
            }

            $activeClinicId = $clinicId ?? $current?->clinic_id ?? $nextPatient?->clinic_id;
            if ($activeClinicId) {
                $activeClinic = Clinic::find($activeClinicId);
                $shift = $nextPatient?->shift ?? $current?->shift ?? $activeClinic?->shifts()->first();
                if ($shift) {
                    event(new QueueUpdatedEvent($activeClinicId, $shift->id, today()->toDateString()));
                }
            }

            return $this->successResponse([
                'completed_appointment' => $current,
                'next_patient' => $nextPatient?->load(['patient', 'clinic']),
            ], 'تم إنهاء الكشف وإدخال المريض التالي بنجاح');
        });
    }

    public function previousVisits($patientId, Request $request): JsonResponse
    {
        $patientId = (int)$patientId;
        $tenantId = $request->user()->tenant_id;
        $patient = Patient::where('tenant_id', $tenantId)->findOrFail($patientId);

        $visits = Appointment::where('tenant_id', $tenantId)
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->with(['consultation', 'clinic'])
            ->latest('appointment_date')
            ->get();

        return $this->successResponse([
            'patient' => $patient,
            'visits' => $visits,
        ]);
    }

    public function saveAttachments($patientId, Request $request): JsonResponse
    {
        $patientId = (int)$patientId;
        $tenantId = $request->user()->tenant_id;
        $patient = Patient::where('tenant_id', $tenantId)->findOrFail($patientId);

        $request->validate([
            'title' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store("attachments/{$tenantId}/{$patientId}", 'local');

        $attachment = PatientAttachment::create([
            'tenant_id' => $tenantId,
            'patient_id' => $patient->id,
            'consultation_id' => $request->input('consultation_id'),
            'title' => $request->title,
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
        ]);

        return $this->successResponse($attachment, 'تم رفع المرفق بنجاح');
    }

    public function downloadAttachment($attachmentId, Request $request)
    {
        $attachmentId = (int)$attachmentId;
        $tenantId = $request->user()->tenant_id;

        $attachment = PatientAttachment::where('tenant_id', $tenantId)->findOrFail($attachmentId);

        // Verify patient ownership within tenant
        Patient::where('tenant_id', $tenantId)->findOrFail($attachment->patient_id);

        $disk = Storage::disk('local');
        $filePath = ltrim(str_replace('/storage/', '', $attachment->file_path), '/');

        if (!$disk->exists($filePath)) {
            return $this->errorResponse('الملف غير موجود', null, 404);
        }

        if ($request->boolean('download')) {
            return $disk->download($filePath, $attachment->title . '.' . ($attachment->file_type ?: pathinfo($filePath, PATHINFO_EXTENSION)));
        }

        return $disk->response($filePath);
    }

    public function autoCompleteDrugs(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $query = $request->input('q', '');

        $drugs = DoctorDrug::where('tenant_id', $tenantId)
            ->when($query, fn($q) => $q->where('drug_name', 'like', "%{$query}%"))
            ->limit(20)
            ->get();

        return $this->successResponse($drugs);
    }

    public function autoCompleteLabs(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $query = $request->input('q', '');

        $tests = DoctorLabTest::where('tenant_id', $tenantId)
            ->when($query, fn($q) => $q->where('test_name', 'like', "%{$query}%"))
            ->limit(20)
            ->get();

        return $this->successResponse($tests);
    }
}
