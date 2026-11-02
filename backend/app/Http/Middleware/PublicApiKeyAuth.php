<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Clinic-Api-Key') ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Missing clinic API key',
            ], 401);
        }

        $tenant = Tenant::where('public_booking_api_key', $apiKey)
            ->where('status', 'active')
            ->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive clinic API key',
            ], 401);
        }

        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
