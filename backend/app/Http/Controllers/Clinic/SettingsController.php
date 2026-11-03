<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicDayOff;
use App\Models\ClinicShift;
use App\Models\DoctorDrug;
use App\Models\DoctorLabTest;
use App\Models\PrescriptionTemplate;
use App\Models\User;
use App\Models\WalletTopup;
use App\Services\CloudflareDomainService;
use App\Services\PrescriptionPdfService;
use App\Services\SmsGatewayService;
use App\Services\WhatsAppChannelService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    use ApiResponseTrait;

    protected PrescriptionPdfService $pdfService;
    protected WhatsAppChannelService $whatsappService;
    protected SmsGatewayService $smsService;
    protected CloudflareDomainService $cloudflareService;

    public function __construct(
        PrescriptionPdfService $pdfService,
        WhatsAppChannelService $whatsappService,
        SmsGatewayService $smsService,
        CloudflareDomainService $cloudflareService
    ) {
        $this->pdfService = $pdfService;
        $this->whatsappService = $whatsappService;
        $this->smsService = $smsService;
        $this->cloudflareService = $cloudflareService;
    }

    public function getSettings(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant->load(['plan', 'clinics.shifts']);
        $waStatus = $this->whatsappService->getSessionStatus($tenant);

        return $this->successResponse([
            'tenant' => $tenant,
            'whatsapp_status' => $waStatus,
        ]);
    }

    public function updateVitalsConfig(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $vitals = $request->input('vitals_config', []);

        $tenant->update(['vitals_config' => $vitals]);
        return $this->successResponse($vitals, 'تم تحديث قائمة القياسات الطبية بنجاح');
    }

    public function updatePrintMargins(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $settings = $request->input('print_settings', []);

        $tenant->update(['print_settings' => $settings]);
        return $this->successResponse($settings, 'تم تحديث إعدادات طباعة الروشتة بنجاح');
    }

    public function testPrintPdf(Request $request)
    {
        $tenant = $request->user()->tenant;
        $margins = $request->input('print_settings', []);
        if (empty($margins)) {
            $margins = [
                'margin_top' => $request->query('top', $request->query('margin_top', $tenant->print_settings['margin_top'] ?? 45)),
                'margin_bottom' => $request->query('bottom', $request->query('margin_bottom', $tenant->print_settings['margin_bottom'] ?? 25)),
                'margin_left' => $request->query('left', $request->query('margin_left', $tenant->print_settings['margin_left'] ?? 15)),
                'margin_right' => $request->query('right', $request->query('margin_right', $tenant->print_settings['margin_right'] ?? 15)),
                'paper_size' => $request->query('paper_size', $tenant->print_settings['paper_size'] ?? 'A5'),
            ];
        }
        $filePath = $this->pdfService->generateTestMarginsPdf($tenant, $margins);

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function getDrugs(Request $request): JsonResponse
    {
        $drugs = DoctorDrug::where('tenant_id', $request->user()->tenant_id)->get();
        return $this->successResponse($drugs);
    }

    public function saveDrug(Request $request): JsonResponse
    {
        if (!$request->has('drug_name') && $request->has('name')) {
            $request->merge(['drug_name' => $request->name]);
        }
        $request->validate(['drug_name' => 'required']);
        $tenantId = $request->user()->tenant_id;

        $drug = DoctorDrug::updateOrCreate(
            ['tenant_id' => $tenantId, 'drug_name' => $request->drug_name],
            $request->only(['form_type', 'default_dosage', 'default_duration', 'notes'])
        );

        return $this->successResponse($drug, 'تم حفظ الدواء بنجاح');
    }

    public function getTemplates(Request $request): JsonResponse
    {
        $templates = PrescriptionTemplate::where('tenant_id', $request->user()->tenant_id)->get();
        return $this->successResponse($templates);
    }

    public function getServices(Request $request): JsonResponse
    {
        $services = \App\Models\ClinicService::where('tenant_id', $request->user()->tenant_id)->get();
        return $this->successResponse($services);
    }

    public function saveTemplate(Request $request): JsonResponse
    {
        $name = $request->input('template_name') ?? $request->input('name');
        $diagnosis = $request->input('diagnosis_default') ?? $request->input('diagnosis');
        $drugs = $request->input('drugs_json') ?? $request->input('drugs_payload') ?? $request->input('drugs', []);

        if (empty($name)) {
            return $this->errorResponse('اسم نموذج الروشتة مطلوب', 422);
        }

        $template = PrescriptionTemplate::updateOrCreate(
            ['tenant_id' => $request->user()->tenant_id, 'template_name' => $name],
            [
                'diagnosis_default' => $diagnosis,
                'drugs_json' => $drugs,
            ]
        );

        return $this->successResponse($template, 'تم حفظ نموذج الروشتة بنجاح');
    }

    public function deleteTemplate(int $id, Request $request): JsonResponse
    {
        PrescriptionTemplate::where('tenant_id', $request->user()->tenant_id)
            ->where('id', $id)
            ->delete();

        return $this->successResponse(null, 'تم حذف النموذج بنجاح');
    }

    public function getLabs(Request $request): JsonResponse
    {
        $labs = DoctorLabTest::where('tenant_id', $request->user()->tenant_id)->get();
        return $this->successResponse($labs);
    }

    public function saveLab(Request $request): JsonResponse
    {
        $request->validate(['test_name' => 'required']);

        $lab = DoctorLabTest::updateOrCreate(
            ['tenant_id' => $request->user()->tenant_id, 'test_name' => $request->test_name],
            $request->only(['type'])
        );

        return $this->successResponse($lab, 'تم حفظ الفحص بنجاح');
    }

    public function getBranches(Request $request): JsonResponse
    {
        $clinics = Clinic::where('tenant_id', $request->user()->tenant_id)->with('shifts')->get();
        return $this->successResponse($clinics);
    }

    public function saveBranch(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $id = $request->input('id');

        // Correction 5: Check Plan Limit
        if (!$id) {
            $allowed = $tenant->max_clinics_override ?? $tenant->plan->max_clinics;
            if ($allowed !== -1 && $tenant->clinics()->count() >= $allowed) {
                return $this->errorResponse("لقد وصلت للحد الأقصى لعدد العيادات المسموح به في باقتك الحالية ({$allowed} عيادات). يرجى ترقية الباقة أو التواصل مع إدارة المنصة.", null, 403);
            }
        }

        $clinic = Clinic::updateOrCreate(
            ['id' => $id, 'tenant_id' => $tenant->id],
            [
                'name_ar' => $request->name_ar ?? $request->name ?? 'فرع جديد',
                'address_ar' => $request->address_ar ?? $request->address ?? 'الفرع الرئيسي',
                'city_ar' => $request->city_ar ?? $request->city ?? 'القاهرة',
                'phone' => $request->phone,
                'google_maps_url' => $request->google_maps_url,
                'consultation_price' => $request->consultation_price ?? 300,
                'followup_price' => $request->followup_price ?? 50,
                'followup_days_limit' => $request->followup_days_limit ?? 10,
                'avg_consultation_minutes' => $request->avg_consultation_minutes ?? 15,
                'booking_payment_mode' => $request->booking_payment_mode ?? 'pay_at_clinic',
                'prepayment_instructions_ar' => $request->prepayment_instructions_ar,
                'tv_screen_code' => $clinic?->tv_screen_code ?? strtoupper(Str::random(6)),
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        return $this->successResponse($clinic, 'تم حفظ بيانات الفرع بنجاح');
    }

    public function saveShift(Request $request): JsonResponse
    {
        $request->validate([
            'clinic_id' => 'required',
            'day_of_week' => 'required|integer|between:0,6',
            'shift_name_ar' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $shift = ClinicShift::updateOrCreate(
            ['id' => $request->input('id')],
            array_merge($request->only([
                'clinic_id',
                'day_of_week',
                'shift_name_ar',
                'start_time',
                'end_time',
                'max_patients',
                'is_active',
            ]), ['tenant_id' => $request->user()->tenant_id])
        );

        return $this->successResponse($shift, 'تم حفظ فترة العمل بنجاح');
    }

    public function deleteShift(int $id, Request $request): JsonResponse
    {
        $shift = ClinicShift::where('tenant_id', $request->user()->tenant_id)->findOrFail($id);
        $shift->delete();
        return $this->successResponse(null, 'تم حذف فترة العمل بنجاح');
    }

    public function saveDayOff(Request $request): JsonResponse
    {
        // Correction 10: Emergency Day-Off Bulk Reschedule / Cancel Handler
        $request->validate([
            'clinic_id' => 'required|integer',
            'date' => 'required|date',
            'reason_ar' => 'nullable|string',
            'action' => 'required|in:cancel_and_notify,move_to_date',
            'target_date' => 'nullable|required_if:action,move_to_date|date',
        ]);

        $tenant = $request->user()->tenant;
        $clinic = Clinic::findOrFail($request->clinic_id);
        $date = $request->date;
        $action = $request->action;
        $targetDate = $request->target_date;

        return DB::transaction(function () use ($tenant, $clinic, $date, $action, $targetDate, $request) {
            $dayOff = ClinicDayOff::updateOrCreate(
                ['clinic_id' => $clinic->id, 'date' => $date],
                [
                    'tenant_id' => $tenant->id,
                    'reason_ar' => $request->reason_ar ?? 'عطلة طارئة',
                ]
            );

            $affected = Appointment::where('clinic_id', $clinic->id)
                ->whereDate('appointment_date', $date)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->orderBy('queue_number')
                ->lockForUpdate()
                ->get();

            $notificationsToSend = [];

            if ($action === 'move_to_date') {
                $targetDayOfWeek = Carbon::parse($targetDate)->dayOfWeek;
                $targetShift = ClinicShift::where('clinic_id', $clinic->id)
                    ->where('day_of_week', $targetDayOfWeek)
                    ->where('is_active', true)
                    ->first();

                if (!$targetShift) {
                    throw ValidationException::withMessages([
                        'target_date' => ['لا توجد فترة عمل مفعلة في التاريخ البديل المختار. يرجى إضافة فترة عمل أولاً.']
                    ]);
                }

                $currentMaxQueue = (int) Appointment::where('clinic_id', $clinic->id)
                    ->where('shift_id', $targetShift->id)
                    ->whereDate('appointment_date', $targetDate)
                    ->lockForUpdate()
                    ->max('queue_number');

                foreach ($affected as $apt) {
                    $currentMaxQueue++;
                    $apt->update([
                        'appointment_date' => $targetDate,
                        'shift_id'         => $targetShift->id,
                        'queue_number'     => $currentMaxQueue,
                    ]);

                    $msg = "نحيطكم علماً بأنه تم ترحيل حجزكم في عيادة {$clinic->name_ar} إلى تاريخ {$targetDate} نظراً لظرف طارئ: {$dayOff->reason_ar}. رقمكم الجديد: #{$currentMaxQueue}";
                    $notificationsToSend[] = [
                        'apt' => $apt,
                        'msg' => $msg,
                    ];
                }
            } else {
                foreach ($affected as $apt) {
                    $apt->update(['status' => 'cancelled']);
                    $msg = "نعتذر لكم! تم إلغاء حجزكم في عيادة {$clinic->name_ar} بتاريخ {$date} بسبب: {$dayOff->reason_ar}. يرجى حجز موعد جديد.";
                    $notificationsToSend[] = [
                        'apt' => $apt,
                        'msg' => $msg,
                    ];
                }
            }

            DB::afterCommit(function () use ($notificationsToSend, $tenant) {
                foreach ($notificationsToSend as $item) {
                    $apt = $item['apt'];
                    $msg = $item['msg'];
                    try {
                        if ($apt->whatsapp_opted_in && $tenant->whatsapp_enabled) {
                            $this->whatsappService->sendMessage($tenant, $apt->patient->phone, $msg, $apt->id, 'apology_day_off');
                        } elseif ($tenant->sms_enabled) {
                            $this->smsService->sendSms($tenant, $apt->patient->phone, $msg, 'apology_day_off', $apt->id);
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Day off notification dispatch error: " . $e->getMessage());
                    }
                }
            });

            return $this->successResponse([
                'day_off' => $dayOff,
                'affected_appointments_count' => $affected->count(),
            ], "تم تسجيل يوم الإجازة ومعالجة {$affected->count()} حجزاً بنجاح");
        });
    }

    public function getReceptionists(Request $request): JsonResponse
    {
        $users = User::where('tenant_id', $request->user()->tenant_id)
            ->where('role', 'receptionist')
            ->with('clinic')
            ->get();

        return $this->successResponse($users);
    }

    public function saveReceptionist(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'clinic_id' => 'required|integer',
        ]);

        $data = [
            'tenant_id' => $request->user()->tenant_id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'clinic_id' => $request->clinic_id,
            'role' => 'receptionist',
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user = User::updateOrCreate(['id' => $request->input('id')], $data);
        return $this->successResponse($user, 'تم حفظ حساب السكرتارية بنجاح');
    }

    public function getQrCode(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $phone = $request->input('phone', $tenant->whatsapp_phone_number);

        if (!$phone) {
            return $this->errorResponse('يرجى إدخال رقم هاتف الواتساب أولاً');
        }

        $result = $this->whatsappService->requestPairingCode($tenant, $phone);
        return $this->successResponse($result);
    }

    public function requestTopup(Request $request): JsonResponse
    {
        $request->validate([
            'package_type' => 'required|in:whatsapp,sms,subscription',
            'quantity_added' => 'required|integer|min:50',
            'amount_egp' => 'required|numeric',
            'payment_method' => 'required|in:fawry,manual_super_admin',
        ]);

        $topup = WalletTopup::create([
            'tenant_id' => $request->user()->tenant_id,
            'package_type' => $request->package_type,
            'quantity_added' => $request->quantity_added,
            'amount_egp' => $request->amount_egp,
            'payment_method' => $request->payment_method,
            'fawry_reference' => $request->payment_method === 'fawry' ? 'FAW-' . rand(100000, 999999) : null,
            'status' => 'pending',
        ]);

        return $this->successResponse($topup, 'تم إنشاء طلب الشحن بنجاح، بانتظار التأكيد');
    }

    public function updateCustomDomain(Request $request): JsonResponse
    {
        $request->validate(['custom_domain' => 'required|string']);
        $tenant = $request->user()->tenant;

        $result = $this->cloudflareService->registerCustomHostname($tenant, $request->custom_domain);
        return $this->successResponse($result);
    }
}
