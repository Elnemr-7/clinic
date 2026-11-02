<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClinicAuth
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return response()->json([
                'success' => false,
                'message' => 'البريد الإلكتروني غير مسجل في بوابة الأطباء',
                'data' => null,
                'errors' => null,
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'تم تعطيل هذا الحساب، يرجى مراجعة إدارة العيادة',
                'data' => null,
                'errors' => null,
            ], 403);
        }

        if (!empty($roles) && !in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول لهذه العملية',
                'data' => null,
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
