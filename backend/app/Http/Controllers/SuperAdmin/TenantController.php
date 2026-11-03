<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\SuperAdminAuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $tenants = Tenant::with(['plan', 'clinics', 'users'])
            ->withCount('clinics')
            ->latest()
            ->paginate(20);

        return $this->successResponse($tenants);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:50|unique:users,phone',
            'password' => 'nullable|string|min:6',
            'specialty_key' => 'nullable|string|max:100',
            'title_ar' => 'nullable|string|max:255',
            'plan_id' => 'nullable|exists:plans,id',
            'subdomain' => 'nullable|string|max:100|unique:tenants,subdomain',
            'max_clinics_override' => 'nullable|integer',
            'whatsapp_enabled' => 'nullable|boolean',
            'sms_enabled' => 'nullable|boolean',
            'whatsapp_balance' => 'nullable|integer|min:0',
            'sms_balance' => 'nullable|integer|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $plan = !empty($validated['plan_id'])
                ? Plan::find($validated['plan_id'])
                : Plan::where('is_active', true)->first();

            // Auto-generate subdomain if not provided
            $subdomain = !empty($validated['subdomain'])
                ? Str::slug($validated['subdomain'])
                : 'dr-' . Str::slug($validated['name']) . '-' . time();

            // Ensure unique subdomain
            if (Tenant::where('subdomain', $subdomain)->exists()) {
                $subdomain .= '-' . Str::random(4);
            }

            $allowedSpecialties = ['general', 'dental', 'pediatrics', 'dermatology', 'obgyn', 'orthopedics'];
            $specialtyKey = in_array($validated['specialty_key'] ?? '', $allowedSpecialties, true)
                ? $validated['specialty_key']
                : 'general';

            $tenant = Tenant::create([
                'name' => $validated['name'],
                'specialty_key' => $specialtyKey,
                'title_ar' => $validated['title_ar'] ?? 'طبيب استشاري',
                'plan_id' => $plan?->id,
                'subscription_ends_at' => now()->addMonth(),
                'status' => 'active',
                'subdomain' => $subdomain,
                'max_clinics_override' => $validated['max_clinics_override'] ?? $plan?->max_clinics ?? 1,
                'whatsapp_enabled' => (bool)($validated['whatsapp_enabled'] ?? (($plan?->free_whatsapp_quota_monthly ?? 0) > 0)),
                'whatsapp_balance' => (int)($validated['whatsapp_balance'] ?? ($plan?->free_whatsapp_quota_monthly ?? 0)),
                'sms_enabled' => (bool)($validated['sms_enabled'] ?? (($plan?->free_sms_quota_monthly ?? 0) > 0)),
                'sms_balance' => (int)($validated['sms_balance'] ?? ($plan?->free_sms_quota_monthly ?? 0)),
                'public_booking_api_key' => 'pbk_' . Str::random(32),
                'template_key' => $specialtyKey,
                'website_settings' => [
                    'primary_color' => '#0F766E',
                    'secondary_color' => '#0F172A',
                    'about_text' => 'عيادة ' . $validated['name'],
                    'services_list' => [],
                ],
                'vitals_config' => [
                    ['key' => 'weight', 'label' => 'الوزن', 'unit' => 'كجم', 'enabled' => true],
                    ['key' => 'bp', 'label' => 'ضغط الدم', 'unit' => 'mmHg', 'enabled' => true],
                    ['key' => 'sugar', 'label' => 'السكر العشوائي', 'unit' => 'mg/dL', 'enabled' => true],
                    ['key' => 'temp', 'label' => 'الحرارة', 'unit' => '°C', 'enabled' => true],
                ],
                'print_settings' => [
                    'use_preprinted_letterhead' => false,
                    'paper_size' => 'A5',
                    'top_margin_mm' => 30,
                    'bottom_margin_mm' => 20,
                    'left_margin_mm' => 15,
                    'right_margin_mm' => 15,
                    'show_header_logo' => true,
                    'footer_text' => '',
                ],
            ]);

            $doctorUser = User::create([
                'tenant_id' => $tenant->id,
                'clinic_id' => null, // Doctor operates across all their clinics
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password'] ?? 'password123'),
                'role' => 'doctor',
                'is_active' => true,
            ]);

            // Audit log
            if (class_exists(SuperAdminAuditLog::class)) {
                try {
                    SuperAdminAuditLog::create([
                        'super_admin_id' => auth()->id(),
                        'action' => 'doctor_created',
                        'target_type' => 'tenant',
                        'target_id' => $tenant->id,
                        'payload' => [
                            'tenant_name' => $tenant->name,
                            'doctor_email' => $doctorUser->email,
                            'subdomain' => $tenant->subdomain,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    // Ignore audit log error if table not ready
                }
            }

            $tenant->load(['plan', 'users']);

            return $this->successResponse([
                'tenant' => $tenant,
                'user' => $doctorUser,
            ], 'تم إنشاء حساب الطبيب والاشتراك بنجاح، ويمكن للطبيب الآن تسجيل الدخول وإضافة عياداته', 201);
        });
    }

    public function show(int $id): JsonResponse
    {
        $tenant = Tenant::with(['plan', 'clinics', 'users'])->findOrFail($id);
        return $this->successResponse($tenant);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'specialty_key' => 'nullable|string|max:100',
            'plan_id' => 'nullable|exists:plans,id',
            'subscription_ends_at' => 'nullable|date',
            'status' => 'nullable|in:active,suspended,expired',
            'template_key' => 'nullable|string|max:50',
            'custom_domain' => 'nullable|string|max:255',
            'subdomain' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('tenants', 'subdomain')->ignore($tenant->id),
            ],
            'is_custom_standalone_website' => 'nullable|boolean',
            'max_clinics_override' => 'nullable|integer',
            'whatsapp_enabled' => 'nullable|boolean',
            'whatsapp_balance' => 'nullable|integer|min:0',
            'sms_enabled' => 'nullable|boolean',
            'sms_balance' => 'nullable|integer|min:0',
            'reset_monthly_whatsapp' => 'nullable|boolean',
            'reset_monthly_sms' => 'nullable|boolean',
        ]);

        if (!empty($validated['subdomain'])) {
            $validated['subdomain'] = Str::slug($validated['subdomain']);
        }

        if (!empty($validated['reset_monthly_whatsapp'])) {
            $validated['whatsapp_used_this_month'] = 0;
            unset($validated['reset_monthly_whatsapp']);
        }

        if (!empty($validated['reset_monthly_sms'])) {
            $validated['sms_used_this_month'] = 0;
            unset($validated['reset_monthly_sms']);
        }

        $tenant->update(array_filter($validated, fn($val) => !is_null($val)));

        return $this->successResponse($tenant, 'تم تحديث بيانات الطبيب بنجاح');
    }

    public function destroy(int $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $doctorName = $tenant->name;

        DB::transaction(function () use ($tenant) {
            // Delete personal access tokens for tenant's users
            $userIds = $tenant->users()->pluck('id');
            if ($userIds->isNotEmpty()) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->whereIn('tokenable_id', $userIds)
                    ->delete();
            }

            // Audit log
            if (class_exists(SuperAdminAuditLog::class)) {
                try {
                    SuperAdminAuditLog::create([
                        'super_admin_id' => auth()->id(),
                        'action' => 'doctor_deleted',
                        'target_type' => 'tenant',
                        'target_id' => $tenant->id,
                        'payload' => [
                            'deleted_tenant_name' => $tenant->name,
                            'subdomain' => $tenant->subdomain,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    // Ignore audit log error if not critical
                }
            }

            // Delete clinics, shifts, users, and the tenant record (cascading handles child entities)
            $tenant->users()->delete();
            $tenant->clinics()->delete();
            $tenant->delete();
        });

        return $this->successResponse(null, "تم حذف حساب الطبيب ({$doctorName}) وجميع بياناته بنجاح");
    }

    public function updateWallet(int $id, Request $request): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $data = $request->only([
            'max_clinics_override',
            'whatsapp_enabled',
            'whatsapp_balance',
            'sms_enabled',
            'sms_balance',
            'reset_monthly_whatsapp',
            'reset_monthly_sms',
        ]);

        if (!empty($data['reset_monthly_whatsapp'])) {
            $data['whatsapp_used_this_month'] = 0;
            unset($data['reset_monthly_whatsapp']);
        }

        if (!empty($data['reset_monthly_sms'])) {
            $data['sms_used_this_month'] = 0;
            unset($data['reset_monthly_sms']);
        }

        $tenant->update($data);

        return $this->successResponse($tenant, 'تم تحديث إعدادات المحفظة والحدود بنجاح');
    }

    public function impersonate(int $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $doctorUser = User::where('tenant_id', $tenant->id)->where('role', 'doctor')->firstOrFail();

        $token = $doctorUser->createToken('impersonation_token', ['role:doctor'])->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'user' => $doctorUser,
            'tenant' => $tenant,
            'redirect_url' => "/doctor/workspace?impersonate_token={$token}",
        ], 'تم إنشاء توكن الدعم الفني بنجاح');
    }
}
