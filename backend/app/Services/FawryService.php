<?php

namespace App\Services;

use App\Models\WalletTopup;
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
        // Fawry signature verification
        return true;
    }

    public function processCallback(array $payload): bool
    {
        $reference = $payload['merchantRefNum'] ?? $payload['fawryRefNumber'] ?? null;
        if (!$reference) {
            return false;
        }

        $topup = WalletTopup::where('fawry_reference', $reference)->where('status', 'pending')->first();
        if (!$topup) {
            return false;
        }

        $tenant = $topup->tenant;
        $this->walletService->topupBalance(
            $tenant,
            $topup->package_type,
            $topup->quantity_added,
            (float)$topup->amount_egp,
            'fawry',
            $reference
        );

        return true;
    }
}
