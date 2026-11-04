<?php

namespace App\Services;

use App\Models\WalletTopup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FawryService
{
    protected MessagingWalletService $walletService;

    public function __construct(MessagingWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function verifySignature(array $payload): bool
    {
        $securityKey = config('services.fawry.security_key');
        if (empty($securityKey) || empty($payload['messageSignature'])) {
            return false;
        }

        $fawryRefNo       = (string) ($payload['fawryRefNumber'] ?? '');
        $merchantRefNo    = (string) ($payload['merchantRefNumber'] ?? $payload['merchantRefNum'] ?? '');
        $paymentAmount    = number_format((float) ($payload['paymentAmount'] ?? 0), 2, '.', '');
        $orderAmount      = number_format((float) ($payload['orderAmount'] ?? 0), 2, '.', '');
        $orderStatus      = (string) ($payload['orderStatus'] ?? '');
        $paymentMethod    = (string) ($payload['paymentMethod'] ?? '');
        $paymentRefNumber = (string) ($payload['paymentRefrenceNumber'] ?? $payload['paymentRefNumber'] ?? '');

        $concatenated = $fawryRefNo . $merchantRefNo . $paymentAmount . $orderAmount . $orderStatus . $paymentMethod . $paymentRefNumber . $securityKey;
        $expectedSignature = hash('sha256', $concatenated);

        return hash_equals(strtolower($expectedSignature), strtolower((string) $payload['messageSignature']));
    }

    public function processCallback(array $payload): bool
    {
        $reference = $payload['merchantRefNumber'] ?? $payload['merchantRefNum'] ?? $payload['fawryRefNumber'] ?? null;
        if (!$reference) {
            return false;
        }

        return DB::transaction(function () use ($reference) {
            $topup = WalletTopup::where(function ($query) use ($reference) {
                $query->where('fawry_reference', $reference)
                      ->orWhere('id', $reference);
            })->lockForUpdate()->first();

            if (!$topup || $topup->status === 'paid') {
                return false;
            }

            $tenant = $topup->tenant;
            if ($topup->package_type === 'whatsapp') {
                $tenant->increment('whatsapp_balance', $topup->quantity_added);
            } elseif ($topup->package_type === 'sms') {
                $tenant->increment('sms_balance', $topup->quantity_added);
            }

            $topup->update([
                'status' => 'paid',
            ]);

            return true;
        });
    }
}
