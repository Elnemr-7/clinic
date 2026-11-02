<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\DoctorLead;
use App\Models\Plan;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicPlanController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $plans = Plan::where('is_active', true)->get();
        return $this->successResponse($plans);
    }

    public function submitLead(Request $request): JsonResponse
    {
        // Allow aliases from frontend
        if (!$request->has('doctor_name') && $request->has('name')) {
            $request->merge(['doctor_name' => $request->input('name')]);
        }
        if (!$request->has('specialty') && $request->has('medical_specialty')) {
            $request->merge(['specialty' => $request->input('medical_specialty')]);
        }

        // Correction 6: Public Lead Submission Endpoint for landing page LeadForm.vue
        $data = $request->validate([
            'doctor_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'specialty' => 'required|string|max:100',
            'requested_plan_id' => 'nullable|integer|exists:plans,id',
            'notes' => 'nullable|string',
        ]);

        $lead = DoctorLead::create([
            'doctor_name' => $data['doctor_name'],
            'phone' => $data['phone'],
            'specialty' => $data['specialty'],
            'requested_plan_id' => $data['requested_plan_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'new',
        ]);

        return $this->successResponse($lead, 'شكراً لتواصلك معنا دكتور! سيقوم فريق المنصة بالتواصل معك في أقرب وقت لتفعيل عيادتك.', 201);
    }
}
