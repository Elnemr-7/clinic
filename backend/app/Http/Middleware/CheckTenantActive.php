<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user instanceof \App\Models\User && $user->tenant) {
            $tenant = $user->tenant;

            if ($tenant->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'حساب الطبيب معلق أو غير مفعل، يرجى مراجعة إدارة المنصة',
                    'data' => null,
                    'errors' => null,
                ], 403);
            }

            if ($tenant->subscription_ends_at && $tenant->subscription_ends_at->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'انتهى اشتراك حساب الطبيب، يرجى التجديد للمتابعة',
                    'data' => null,
                    'errors' => null,
                ], 403);
            }
        }

        return $next($request);
    }
}
