<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Strict rejection if email belongs to Super Admin
        if (SuperAdmin::where('email', $request->email)->exists()) {
            return $this->errorResponse('البريد الإلكتروني غير مسجل في بوابة الأطباء', null, 401);
        }

        $user = User::where('email', $request->email)->with(['tenant.clinics', 'clinic'])->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('بيانات الدخول غير صحيحة', null, 401);
        }

        if (!$user->is_active) {
            return $this->errorResponse('تم إيقاف هذا الحساب، يرجى التواصل مع إدارة العيادة', null, 403);
        }

        $tokenAbility = $user->role === 'doctor' ? 'role:doctor' : 'role:receptionist';
        $token = $user->createToken('clinic_token', [$tokenAbility])->plainTextToken;

        $clinicId = $user->clinic_id ?? $user->tenant?->clinics?->first()?->id ?? 0;
        $socketSecret = config('services.whatsapp.secret', 'vertex_wa_secure_secret_key_2026');
        $socketToken = hash_hmac('sha256', "clinic_{$clinicId}", $socketSecret);

        return $this->successResponse([
            'token' => $token,
            'socket_token' => $socketToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'clinic_id' => $user->clinic_id,
            ],
            'tenant' => $user->tenant,
            'clinic' => $user->clinic,
        ], 'تم تسجيل الدخول بنجاح');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['tenant.clinics', 'tenant.plan', 'clinic']);
        $clinicId = $user->clinic_id ?? $user->tenant?->clinics?->first()?->id ?? 0;
        $socketSecret = config('services.whatsapp.secret', 'vertex_wa_secure_secret_key_2026');
        $socketToken = hash_hmac('sha256', "clinic_{$clinicId}", $socketSecret);

        return $this->successResponse([
            'user' => $user,
            'socket_token' => $socketToken,
            'tenant' => $user->tenant,
            'clinic' => $user->clinic,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null, 'تم تسجيل الخروج بنجاح');
    }
}
