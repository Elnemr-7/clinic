<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\ClinicShift;
use App\Models\DoctorLead;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DoctorLeadController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $leads = DoctorLead::with('plan')->latest()->paginate(20);
        return $this->successResponse($leads);
    }

    public function convert(int $leadId, Request $request): JsonResponse
    {
        $lead = DoctorLead::findOrFail($leadId);

        return DB::transaction(function () use ($lead, $request) {
            $planId = $request->input('plan_id', $lead->requested_plan_id);
            $plan = $planId ? Plan::find($planId) : Plan::first();
            if (!$plan) {
                $plan = Plan::create([
                    'name_ar' => 'الخطة القياسية',
                    'price_monthly' => 500,
                    'price_yearly' => 5000,
                    'features' => [],
                    'is_active' => true,
                ]);
            }

            $subdomain = Str::slug($request->input('subdomain', 'dr-' . Str::random(6)));

            $tenant = Tenant::create([
                'name' => $lead->doctor_name,
                'specialty_key' => $request->input('specialty_key', 'general'),
                'title_ar' => $request->input('title_ar', 'طبيب متخصص'),
                'plan_id' => $plan->id,
                'subscription_ends_at' => now()->addMonth(),
                'status' => 'active',
                'subdomain' => $subdomain,
                'public_booking_api_key' => 'pbk_' . Str::random(32),
                'template_key' => 'general',
                'whatsapp_enabled' => false,
                'sms_enabled' => false,
            ]);

            $doctorUser = User::create([
                'tenant_id' => $tenant->id,
                'name' => $lead->doctor_name,
                'email' => $request->input('email', Str::slug($lead->doctor_name) . '_' . $tenant->id . '@clinic.com'),
                'phone' => $lead->phone,
                'password' => $request->input('password', 'password123'),
                'role' => 'doctor',
                'is_active' => true,
            ]);

            $clinic = Clinic::create([
                'tenant_id' => $tenant->id,
                'name_ar' => 'الفرع الرئيسي',
                'address_ar' => 'الفرع الرئيسي',
                'phone' => $lead->phone,
                'consultation_price' => 300,
                'followup_price' => 50,
                'tv_screen_code' => strtoupper(Str::random(6)),
                'is_active' => true,
            ]);

            // Seed default working shifts (Saturday through Thursday, 17:00:00 to 22:00:00)
            $shiftDays = [
                6 => 'السبت',
                0 => 'الأحد',
                1 => 'الإثنين',
                2 => 'الثلاثاء',
                3 => 'الأربعاء',
                4 => 'الخميس',
            ];
            foreach ($shiftDays as $dayOfWeek => $dayName) {
                ClinicShift::create([
                    'tenant_id' => $tenant->id,
                    'clinic_id' => $clinic->id,
                    'day_of_week' => $dayOfWeek,
                    'shift_name_ar' => 'الفترة المسائية - ' . $dayName,
                    'start_time' => '17:00:00',
                    'end_time' => '22:00:00',
                    'max_patients' => 30,
                    'is_active' => true,
                ]);
            }

            $lead->update(['status' => 'converted']);

            return $this->successResponse([
                'tenant' => $tenant,
                'user' => $doctorUser,
                'clinic' => $clinic,
            ], 'تم تحويل الطلب إلى حساب طبيب بنجاح');
        });
    }
}
