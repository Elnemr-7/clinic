<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class SmsGatewayService
{
    protected MessagingWalletService $walletService;

    public function __construct(MessagingWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function sendSms(Tenant $tenant, string $phone, string $message, string $type = 'booking_confirm', ?int $appointmentId = null): bool
    {
        if (!$tenant->sms_enabled) {
            return false;
        }

        if (!$this->walletService->deductMessageBalance($tenant, 'sms')) {
            $this->walletService->logMessage($tenant->id, $appointmentId, 'sms', $phone, $type, $message, 'failed');
            return false;
        }

        // Mock / Local logging driver
        Log::info("SMS DISPATCHED to [{$phone}]: {$message}");

        $this->walletService->logMessage($tenant->id, $appointmentId, 'sms', $phone, $type, $message, 'sent');
        return true;
    }
}
