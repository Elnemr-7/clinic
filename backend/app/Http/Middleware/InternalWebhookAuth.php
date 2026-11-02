<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalWebhookAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $sharedSecret = config('services.whatsapp.secret', 'vertex_wa_secure_secret_key_2026');
        $token = $request->header('X-Server-Token');
        $secretHeader = $request->header('X-Shared-Secret');

        // Check fallback direct secret
        if ($secretHeader && hash_equals($sharedSecret, $secretHeader)) {
            return $next($request);
        }

        // Check 5-minute rotating token
        if ($token && \App\Services\WhatsAppTokenService::verifyToken($token, $sharedSecret)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized webhook token',
        ], 401);
    }
}
