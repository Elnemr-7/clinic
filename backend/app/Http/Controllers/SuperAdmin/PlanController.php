<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $plans = Plan::withCount('tenants')->get();
        return $this->successResponse($plans);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string',
            'price_monthly' => 'required|numeric',
            'price_yearly' => 'required|numeric',
            'max_clinics' => 'required|integer',
            'free_whatsapp_quota_monthly' => 'nullable|integer',
            'free_sms_quota_monthly' => 'nullable|integer',
            'allow_custom_domain' => 'boolean',
            'allow_custom_website' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $plan = Plan::create($data);
        return $this->successResponse($plan, 'تم إضافة الباقة بنجاح');
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        $plan->update($request->all());
        return $this->successResponse($plan, 'تم تحديث الباقة بنجاح');
    }

    public function destroy(int $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        if ($plan->tenants()->count() > 0) {
            return $this->errorResponse('لا يمكن حذف باقة مرتبطة بحسابات أطباء نشطة');
        }
        $plan->delete();
        return $this->successResponse(null, 'تم حذف الباقة بنجاح');
    }
}
