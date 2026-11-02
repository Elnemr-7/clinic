<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !($user instanceof \App\Models\SuperAdmin) || !$user->tokenCan('role:super-admin')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالدخول',
                'data' => null,
                'errors' => null,
            ], 401);
        }

        return $next($request);
    }
}
