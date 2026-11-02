<?php

namespace App\Http\Controllers\Clinic;

use App\Events\QueueUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Jobs\CheckNearTurnNotificationsJob;
use App\Models\Appointment;
use App\Models\CashTransaction;
use App\Models\Clinic;
use App\Models\ClinicShift;
use App\Models\Patient;
use App\Services\FollowupDetectorService;
use App\Services\QueueTimeCalculatorService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReceptionController extends Controller
{
    use ApiResponseTrait;

    protected FollowupDetectorService $followupService;
    protected QueueTimeCalculatorService $queueService;

    public function __construct(FollowupDetectorService $followupService, QueueTimeCalculatorService $queueService)
    {
        $this->followupService = $followupService;
        $this->queueService = $queueService;
    }

    public function queue(Request $request): JsonResponse
    {
        $clinicId = $request->input('clinic_id') ?? $request->user()->clinic_id;
        $date = $request->input('date', today()->toDateString());
        $shiftId = $request->input('shift_id');

        $query = Appointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $date)
            ->with(['patient', 'shift', 'consultation'])
            ->orderBy('queue_number', 'asc');

        if ($shiftId) {
            $query->where('shift_id', $shiftId);
        }

        $appointments = $query->get();

        return $this->successResponse($appointments);
    }

    public function checkFollowup(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required',
            'clinic_id' => 'required|integer',
        ]);

        $tenantId = $request->user()->tenant_id;
        $result = $this->followupService->checkEligibility($tenantId, (int)$request->clinic_id, $request->phone);

        return $this->successResponse($result);
    }

    public function quickBook(Request $request): JsonResponse
    {
        if (!$request->clinic_id) {
            $fallbackClinicId = $request->user()->clinic_id ?: Clinic::where('tenant_id', $request->user()->tenant_id)->value('id');
            $request->merge(['clinic_id' => $fallbackClinicId]);
        }

        $request->validate([
            'clinic_id' => 'required|integer',
            'phone' => 'required|string',
            'name' => 'required|string',
            'shift_id' => 'nullable|integer',
            'visit_type' => 'nullable|in:new_visit,followup,emergency,procedure',
            'gender' => 'nullable|in:male,female',
            'age' => 'nullable|integer',
        ]);

        $tenantId = $request->user()->tenant_id;
        $clinic = Clinic::findOrFail($request->clinic_id);

        // Find or create patient
        $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);
        $patient = Patient::firstOrCreate(
            ['tenant_id' => $tenantId, 'phone' => $request->phone],
            [
                'name' => $request->name,
                'gender' => $request->gender ?? 'male',
                'birth_year' => $request->age ? (date('Y') - (int)$request->age) : null,
            ]
        );

        // Resolve Shift
        $dayOfWeek = Carbon::today()->dayOfWeek;
        $shiftId = $request->shift_id;
        if (!$shiftId) {
            $shift = $clinic->shifts()->where('day_of_week', $dayOfWeek)->where('is_active', true)->first();
            $shiftId = $shift?->id;
        } else {
            $shift = ClinicShift::find($shiftId);
        }

        if (!$shift) {
            return $this->errorResponse('لا توجد فترات عمل متاحة اليوم لهذا الفرع');
        }

        // Auto followup detection
        $followupCheck = $this->followupService->checkEligibility($tenantId, $clinic->id, $request->phone);
        $visitType = $request->input('visit_type');

        if (!$visitType) {
            $visitType = $followupCheck['eligible'] ? 'followup' : 'new_visit';
        }

        $fee = ($visitType === 'followup')
            ? (float)$clinic->followup_price
            : (float)$clinic->consultation_price;

        // Next queue number
        $queueNumber = Appointment::where('clinic_id', $clinic->id)
            ->where('shift_id', $shift->id)
            ->whereDate('appointment_date', today())
            ->max('queue_number') + 1;

        $windows = $this->queueService->calculateInitialWindow($shift, $clinic, $queueNumber, today());
        $refCode = 'CLN-' . strtoupper(Str::random(5));

        $appointment = Appointment::create([
            'tenant_id' => $tenantId,
            'clinic_id' => $clinic->id,
            'shift_id' => $shift->id,
            'patient_id' => $patient->id,
            'appointment_date' => today(),
            'queue_number' => $queueNumber,
            'reference_code' => $refCode,
            'booking_source' => 'clinic_desk',
            'visit_type' => $visitType,
            'status' => 'booked',
            'estimated_window_start' => $windows['estimated_window_start'],
            'estimated_window_end' => $windows['estimated_window_end'],
            'consultation_fee' => $fee,
            'total_amount' => $fee,
            'is_paid' => false,
        ]);

        event(new QueueUpdatedEvent($clinic->id, $shift->id, today()->toDateString()));

        return $this->successResponse([
            'appointment' => $appointment->load('patient'),
            'reference_code' => $refCode,
            'queue_number' => $queueNumber,
            'estimated_window_start' => $windows['estimated_window_start'],
            'estimated_window_end' => $windows['estimated_window_end'],
            'is_followup' => $visitType === 'followup',
        ], 'تم تسجيل الحجز بنجاح');
    }

    public function updateStatus(int $appointmentId, Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:arrived_waiting,in_consultation,postponed_waiting_arrival,completed,cancelled',
            'mark_paid' => 'nullable|boolean',
        ]);

        $appointment = Appointment::findOrFail($appointmentId);
        $oldStatus = $appointment->status;
        $newStatus = $request->status;

        $appointment->status = $newStatus;

        if ($request->boolean('mark_paid') || $newStatus === 'arrived_waiting') {
            if (!$appointment->is_paid) {
                $appointment->is_paid = true;

                // Log into cashbox
                CashTransaction::create([
                    'tenant_id' => $appointment->tenant_id,
                    'clinic_id' => $appointment->clinic_id,
                    'appointment_id' => $appointment->id,
                    'created_by_user_id' => $request->user()->id,
                    'type' => 'income',
                    'category' => $appointment->visit_type === 'followup' ? 'followup' : 'consultation',
                    'amount' => $appointment->consultation_fee,
                    'description_ar' => "تحصيل كشف المريض: {$appointment->patient->name} (رقم #{$appointment->queue_number})",
                    'transaction_date' => today(),
                ]);
            }
        }

        if ($newStatus === 'in_consultation') {
            $appointment->actual_entered_at = now();
        } elseif ($newStatus === 'completed') {
            $appointment->actual_completed_at = now();
        }

        $appointment->save();

        // Recalculate remaining queue
        $this->queueService->recalculateQueueOnStatusChange(
            $appointment->clinic,
            $appointment->shift,
            $appointment->appointment_date,
            $appointment->queue_number
        );

        // Turn alerts
        dispatch(new CheckNearTurnNotificationsJob($appointment->clinic_id, $appointment->shift_id, $appointment->appointment_date->toDateString()));

        return $this->successResponse($appointment, 'تم تحديث حالة المريض بنجاح');
    }

    public function collectExtraServices(int $appointmentId, Request $request): JsonResponse
    {
        // Correction 4: Dedicated Extra Procedure Fees collection endpoint
        $appointment = Appointment::findOrFail($appointmentId);

        if ($appointment->extra_services_paid) {
            return $this->errorResponse('تم تحصيل الخدمات الإضافية مسبقاً');
        }

        if ($appointment->extra_services_total <= 0) {
            return $this->errorResponse('لا توجد خدمات إضافية مستحقة التحصيل');
        }

        $appointment->update([
            'extra_services_paid' => true,
        ]);

        // Log into cashbox
        CashTransaction::create([
            'tenant_id' => $appointment->tenant_id,
            'clinic_id' => $appointment->clinic_id,
            'appointment_id' => $appointment->id,
            'created_by_user_id' => $request->user()->id,
            'type' => 'income',
            'category' => 'medical_service',
            'amount' => $appointment->extra_services_total,
            'description_ar' => "تحصيل خدمات إضافية للمريض: {$appointment->patient->name} (رقم #{$appointment->queue_number})",
            'transaction_date' => today(),
        ]);

        return $this->successResponse($appointment, 'تم تحصيل قيمة الخدمات الإضافية وإيداعها بالخزينة بنجاح');
    }

    public function saveVitals(int $appointmentId, Request $request): JsonResponse
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $vitals = $request->input('vitals_data', []);

        $appointment->update([
            'vitals_data' => $vitals,
        ]);

        return $this->successResponse($appointment, 'تم تسجيل القياسات بنجاح');
    }

    public function cashboxSummary(Request $request): JsonResponse
    {
        $clinicId = $request->input('clinic_id') ?? $request->user()->clinic_id;
        $date = $request->input('date', today()->toDateString());

        $transactions = CashTransaction::where('clinic_id', $clinicId)
            ->whereDate('transaction_date', $date)
            ->with(['appointment.patient', 'user'])
            ->latest()
            ->get();

        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');
        $net = $income - $expense;

        return $this->successResponse([
            'total_income' => (float)$income,
            'total_expense' => (float)$expense,
            'net_in_drawer' => (float)$net,
            'transactions' => $transactions,
        ]);
    }

    public function recordExpense(Request $request): JsonResponse
    {
        if (!$request->clinic_id) {
            $fallbackClinicId = $request->user()->clinic_id ?: Clinic::where('tenant_id', $request->user()->tenant_id)->value('id');
            $request->merge(['clinic_id' => $fallbackClinicId]);
        }

        if (!$request->has('description_ar') && $request->has('description')) {
            $request->merge(['description_ar' => $request->description]);
        }

        $categoryMap = [
            'supplies' => 'clinic_supplies',
            'maintenance' => 'other_expense',
            'other' => 'other_expense',
        ];
        if (isset($categoryMap[$request->category])) {
            $request->merge(['category' => $categoryMap[$request->category]]);
        }

        $request->validate([
            'clinic_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.5',
            'description_ar' => 'required|string',
            'category' => 'required|in:clinic_supplies,utilities,other_expense',
        ]);

        $tx = CashTransaction::create([
            'tenant_id' => $request->user()->tenant_id,
            'clinic_id' => $request->clinic_id,
            'created_by_user_id' => $request->user()->id,
            'type' => 'expense',
            'category' => $request->category,
            'amount' => $request->amount,
            'description_ar' => $request->description_ar,
            'transaction_date' => today(),
        ]);

        return $this->successResponse($tx, 'تم تسجيل المصروف بنجاح');
    }
}
