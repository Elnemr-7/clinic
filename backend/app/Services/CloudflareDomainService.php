<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareDomainService
{
    public function registerCustomHostname(Tenant $tenant, string $hostname): array
    {
        $zoneId = config('services.cloudflare.zone_id');
        $apiToken = config('services.cloudflare.api_token');

        if (!$zoneId || !$apiToken) {
            // Mock fallback
            $tenant->update([
                'custom_domain' => $hostname,
                'cloudflare_hostname_id' => 'cf_' . md5($hostname),
                'domain_status' => 'pending_cname',
            ]);

            return [
                'success' => true,
                'cname_target' => 'cname.platform.com',
                'status' => 'pending_cname',
            ];
        }

        try {
            $response = Http::withToken($apiToken)
                ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/custom_hostnames", [
                    'hostname' => $hostname,
                    'ssl' => [
                        'method' => 'http',
                        'type' => 'dv',
                    ],
                ]);

            if ($response->successful()) {
                $id = $response->json('result.id');
                $tenant->update([
                    'custom_domain' => $hostname,
                    'cloudflare_hostname_id' => $id,
                    'domain_status' => 'pending_cname',
                ]);

                return [
                    'success' => true,
                    'cname_target' => config('services.cloudflare.fallback_origin', 'cname.platform.com'),
                    'status' => 'pending_cname',
                ];
            }
        } catch (\Throwable $e) {
            Log::error("Cloudflare registration error: {$e->getMessage()}");
        }

        return ['success' => false, 'message' => 'تعذر تسجيل الدومين لدى Cloudflare'];
    }

    public function checkHostnameStatus(Tenant $tenant): array
    {
        return [
            'status' => $tenant->domain_status,
            'custom_domain' => $tenant->custom_domain,
            'cname_target' => 'cname.platform.com',
        ];
    }
}
