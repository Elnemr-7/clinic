<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    public function show(int $id): JsonResponse
    {
        $tenant = Tenant::with(['plan', 'clinics', 'users'])->findOrFail($id);
        return $this->successResponse($tenant);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update($request->only([
            'name',
            'title_ar',
            'specialty_key',
            'plan_id',
            'subscription_ends_at',
            'status',
            'template_key',
            'custom_domain',
            'is_custom_standalone_website',
            'max_clinics_override',
            'whatsapp_enabled',
            'sms_enabled',
        ]));

        return $this->successResponse($tenant, 'تم تحديث بيانات الطبيب بنجاح');
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
