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
use Illuminate\Support\Facades\Storage;
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

        // Calculate current website mode if not explicitly set (Default is Mode 1)
        $currentMode = $tenant->website_settings['website_mode'] ?? null;
        if (!$currentMode) {
            if ($tenant->is_custom_standalone_website && $tenant->custom_domain) {
                $currentMode = 4;
            } elseif ($tenant->is_custom_standalone_website) {
                $currentMode = 3;
            } elseif ($tenant->custom_domain) {
                $currentMode = 2;
            } else {
                $currentMode = 1;
            }
        }

        return $this->successResponse([
            'tenant' => $tenant,
            'vitals_config' => $tenant->vitals_config,
            'print_settings' => $tenant->print_settings,
            'whatsapp_status' => $waStatus,
            'website_mode' => (int)$currentMode,
            'plan_capabilities' => [
                'allow_custom_domain' => (bool)$tenant->plan?->allow_custom_domain,
                'allow_custom_website' => (bool)$tenant->plan?->allow_custom_website,
            ],
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

        $content = file_get_contents($filePath);
        $isPdf = str_starts_with($content, '%PDF');

        if ($isPdf) {
            return response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="test_margins.pdf"',
                'Content-Length' => strlen($content),
            ]);
        }

        return response($content, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    public function getDrugs(Request $request): JsonResponse
    {
        $drugs = DoctorDrug::where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'tenant_id' => $d->tenant_id,
                    'name' => $d->drug_name,
                    'drug_name' => $d->drug_name,
                    'form_type' => $d->form_type,
                    'default_dosage' => $d->default_dosage,
                    'default_frequency' => $d->default_duration,
                    'default_duration' => $d->default_duration,
                    'default_instructions' => $d->notes,
                    'notes' => $d->notes,
                ];
            });
        return $this->successResponse($drugs);
    }

    public function saveDrug(Request $request): JsonResponse
    {
        $drugName = $request->input('drug_name') ?: $request->input('name');
        if (!$drugName) {
            return $this->errorResponse('اسم الدواء مطلوب');
        }
        $tenantId = $request->user()->tenant_id;

        $drug = DoctorDrug::updateOrCreate(
            ['tenant_id' => $tenantId, 'drug_name' => trim($drugName)],
            [
                'form_type' => $request->input('form_type'),
                'default_dosage' => $request->input('default_dosage'),
                'default_duration' => $request->input('default_frequency') ?: $request->input('default_duration'),
                'notes' => $request->input('default_instructions') ?: $request->input('notes'),
            ]
        );

        return $this->successResponse([
            'id' => $drug->id,
            'name' => $drug->drug_name,
            'drug_name' => $drug->drug_name,
            'default_dosage' => $drug->default_dosage,
            'default_frequency' => $drug->default_duration,
            'default_instructions' => $drug->notes,
        ], 'تم حفظ الدواء بنجاح');
    }

    public function deleteDrug(int $id, Request $request): JsonResponse
    {
        DoctorDrug::where('tenant_id', $request->user()->tenant_id)
            ->where('id', $id)
            ->delete();

        return $this->successResponse(null, 'تم حذف الدواء من الكتالوج بنجاح');
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

        $tenantId = $request->user()->tenant_id;

        if ($request->filled('id')) {
            $template = PrescriptionTemplate::where('tenant_id', $tenantId)->find($request->input('id'));
            if ($template) {
                $template->update([
                    'template_name' => $name,
                    'diagnosis_default' => $diagnosis,
                    'drugs_json' => $drugs,
                ]);
                return $this->successResponse($template, 'تم تحديث نموذج الروشتة بنجاح');
            }
        }

        $template = PrescriptionTemplate::updateOrCreate(
            ['tenant_id' => $tenantId, 'template_name' => $name],
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

        $clinic = $id ? Clinic::where('tenant_id', $tenant->id)->find($id) : null;

        // Correction 5: Check Plan Limit
        if (!$clinic) {
            $allowed = $tenant->max_clinics_override ?? $tenant->plan->max_clinics;
            if ($allowed !== -1 && $tenant->clinics()->count() >= $allowed) {
                return $this->errorResponse("لقد وصلت للحد الأقصى لعدد العيادات المسموح به في باقتك الحالية ({$allowed} عيادات). يرجى ترقية الباقة أو التواصل مع إدارة المنصة.", null, 403);
            }
        }

        $tvScreenCode = $clinic?->tv_screen_code ?? strtoupper(Str::random(6));

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
                'tv_screen_code' => $tvScreenCode,
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        return $this->successResponse($clinic, 'تم حفظ بيانات الفرع بنجاح');
    }

    public function saveShift(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        // Support array of days or single day_of_week
        $days = $request->input('days', $request->input('days_of_week'));
        if (is_null($days) && $request->has('day_of_week')) {
            $days = [$request->input('day_of_week')];
        }

        $shiftName = $request->input('shift_name_ar', $request->input('name'));

        $request->merge([
            'shift_name_ar' => $shiftName,
            'days' => (array) $days,
        ]);

        $request->validate([
            'clinic_id' => 'required',
            'days' => 'required|array|min:1',
            'days.*' => 'integer|between:0,6',
            'shift_name_ar' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $daysList = $request->input('days');
        $shiftId = $request->input('id');
        $savedShifts = [];

        foreach ($daysList as $day) {
            $shift = ClinicShift::updateOrCreate(
                [
                    'id' => (count($daysList) === 1 && $shiftId) ? $shiftId : null,
                    'clinic_id' => $request->input('clinic_id'),
                    'day_of_week' => $day,
                    'tenant_id' => $tenantId,
                ],
                [
                    'shift_name_ar' => $shiftName,
                    'start_time' => $request->input('start_time'),
                    'end_time' => $request->input('end_time'),
                    'max_patients' => $request->input('max_patients', 20),
                    'is_active' => $request->input('is_active', true),
                ]
            );
            $savedShifts[] = $shift;
        }

        return $this->successResponse($savedShifts, 'تم حفظ فترة العمل بنجاح');
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
        $id = $request->input('id');
        $tenantId = $request->user()->tenant_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . ($id ?: 'NULL') . ',id',
            'clinic_id' => 'required|integer',
            'password' => $id ? 'nullable|min:6' : 'required|min:6',
        ]);

        $data = [
            'tenant_id' => $tenantId,
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

        if ($id) {
            $user = User::where('tenant_id', $tenantId)
                ->where('role', 'receptionist')
                ->findOrFail($id);
            $user->update($data);
        } else {
            $user = User::create($data);
        }

        return $this->successResponse($user->load('clinic'), 'تم حفظ حساب السكرتارية بنجاح');
    }

    public function deleteReceptionist(int $id, Request $request): JsonResponse
    {
        $user = User::where('tenant_id', $request->user()->tenant_id)
            ->where('role', 'receptionist')
            ->findOrFail($id);
        $user->delete();
        return $this->successResponse(null, 'تم حذف حساب موظف الاستقبال بنجاح');
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

        if (!$tenant->plan || !$tenant->plan->allow_custom_domain) {
            return $this->errorResponse(
                'باقتك الحالية لا تدعم ربط نطاق مخصص (Custom Domain). يرجى ترقية باقتك إلى الباقة المتقدمة أو باقة VIP للاستفادة من هذه الميزة.',
                null,
                403
            );
        }

        $result = $this->cloudflareService->registerCustomHostname($tenant, $request->custom_domain);
        return $this->successResponse($result);
    }

    public function saveWebsiteSettings(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant->load('plan');
        $plan = $tenant->plan;

        $validated = $request->validate([
            'website_mode' => 'required|integer|between:1,4',
            'custom_domain' => 'nullable|string|max:255',
            'custom_website_url' => 'nullable|string|max:255',
            'template_key' => 'nullable|string|max:50',
            'content' => 'nullable|array',
            'seo' => 'nullable|array',
            'seo.meta_title' => 'nullable|string|max:255',
            'seo.meta_description' => 'nullable|string|max:500',
            'seo.meta_keywords' => 'nullable|string|max:500',
            'og' => 'nullable|array',
            'og.og_title' => 'nullable|string|max:255',
            'og.og_description' => 'nullable|string|max:500',
            'og.og_image' => 'nullable|string|max:500',
        ]);

        $mode = (int)$validated['website_mode'];

        // Plan capability checks
        if ($mode === 2 && (!$plan || !$plan->allow_custom_domain)) {
            return $this->errorResponse(
                'النموذج 2 (صفحة المنصة + نطاق مخصص) غير متاح في باقتك الحالية. يرجى الترقية للباقة المتقدمة أو باقة VIP.',
                null,
                403
            );
        }

        if ($mode === 3 && (!$plan || !$plan->allow_custom_website)) {
            return $this->errorResponse(
                'النموذج 3 (صفحة هبوط مخصصة + نطاق المنصة) ميزة حصرية لمشتركي باقة VIP الشاملة. يرجى ترقية باقتك للاستفادة منها.',
                null,
                403
            );
        }

        if ($mode === 4 && (!$plan || !$plan->allow_custom_domain || !$plan->allow_custom_website)) {
            return $this->errorResponse(
                'النموذج 4 (صفحة هبوط مخصصة + نطاق مخصص) ميزة حصرية لمشتركي باقة VIP الشاملة. يرجى ترقية باقتك للاستفادة منها.',
                null,
                403
            );
        }

        // Validate custom domain if required for modes 2 and 4
        if (in_array($mode, [2, 4], true)) {
            if (empty($validated['custom_domain'])) {
                return $this->errorResponse('يرجى إدخال النطاق المخصص (Custom Domain)', null, 422);
            }
            $cleanDomain = strtolower(trim($validated['custom_domain']));
            $cleanDomain = preg_replace('#^https?://#', '', $cleanDomain);
            $cleanDomain = rtrim($cleanDomain, '/');

            // Unique check ignoring this tenant
            if (\App\Models\Tenant::where('custom_domain', $cleanDomain)->where('id', '!=', $tenant->id)->exists()) {
                return $this->errorResponse('هذا النطاق المخصص مستخدم بالفعل لعيادة أخرى', null, 422);
            }
            $tenant->custom_domain = $cleanDomain;

            // Attempt Cloudflare register
            try {
                $this->cloudflareService->registerCustomHostname($tenant, $cleanDomain);
            } catch (\Throwable $e) {
                // Ignore cloudflare network error in local dev
            }
        } else {
            // Modes 1 and 3 use platform subdomain only
            $tenant->custom_domain = null;
        }

        // Is custom standalone website?
        $tenant->is_custom_standalone_website = in_array($mode, [3, 4], true);

        if (!empty($validated['template_key'])) {
            $tenant->template_key = $validated['template_key'];
        }

        $currentSettings = $tenant->website_settings ?? [];
        $currentSettings['website_mode'] = $mode;
        if (isset($validated['custom_website_url'])) {
            $currentSettings['custom_website_url'] = trim($validated['custom_website_url']);
        }

        if (array_key_exists('content', $validated)) {
            $c = $validated['content'] ?? [];
            $currentSettings['content'] = $c;
            $currentSettings['hero_title'] = $c['hero_title'] ?? ($c['hero']['title'] ?? null);
            $currentSettings['hero_subtitle'] = $c['hero_subtitle'] ?? ($c['hero']['subtitle'] ?? null);
            $currentSettings['about_text'] = $c['about_text'] ?? ($c['about']['bio'] ?? null);
            $currentSettings['experience_years'] = $c['experience_years'] ?? ($c['hero']['metrics'][0]['value'] ?? null);
            $currentSettings['primary_color'] = $c['primary_color'] ?? '#0F766E';
            $currentSettings['phone'] = $c['phone'] ?? ($c['contact']['phone'] ?? null);
            $currentSettings['address'] = $c['address'] ?? ($c['contact']['address'] ?? null);
            $currentSettings['social_links'] = $c['social_links'] ?? [];
            $currentSettings['services_list'] = $c['services'] ?? [];
        }

        if (array_key_exists('seo', $validated)) {
            $currentSettings['seo'] = [
                'meta_title' => $validated['seo']['meta_title'] ?? null,
                'meta_description' => $validated['seo']['meta_description'] ?? null,
                'meta_keywords' => $validated['seo']['meta_keywords'] ?? null,
            ];
        }

        if (array_key_exists('og', $validated)) {
            $currentSettings['og'] = [
                'og_title' => $validated['og']['og_title'] ?? null,
                'og_description' => $validated['og']['og_description'] ?? null,
                'og_image' => $validated['og']['og_image'] ?? null,
            ];
        }

        $tenant->website_settings = $currentSettings;
        $tenant->save();

        return $this->successResponse([
            'tenant' => $tenant->fresh(['plan', 'clinics.shifts']),
            'website_mode' => $mode,
        ], 'تم حفظ إعدادات الموقع ونموذج الاستضافة بنجاح');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'type' => 'nullable|string|in:og_image,logo,avatar,banner',
        ]);

        $tenant = $request->user()->tenant;
        $file = $request->file('image');
        $type = $request->input('type', 'og_image');

        $folder = "tenants/{$tenant->id}/{$type}";
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $filename, 'public');

        $url = asset('storage/' . $path);

        if ($type === 'og_image') {
            $settings = $tenant->website_settings ?? [];
            if (!isset($settings['og'])) {
                $settings['og'] = [];
            }
            $settings['og']['og_image'] = $url;
            $tenant->website_settings = $settings;
            $tenant->save();
        }

        return $this->successResponse([
            'path' => $path,
            'url' => $url,
            'filename' => $filename,
        ], 'تم رفع وحفظ الصورة بنجاح');
    }
}
