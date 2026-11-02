<?php

namespace App\Services;

use App\Models\MessageLog;
use App\Models\Tenant;
use App\Models\WalletTopup;
use Illuminate\Support\Facades\DB;

class MessagingWalletService
{
    public function deductMessageBalance(Tenant $tenant, string $channel): bool
    {
        return DB::transaction(function () use ($tenant, $channel) {
            $locked = Tenant::where('id', $tenant->id)->lockForUpdate()->first();

            if ($channel === 'whatsapp') {
                if (!$locked->whatsapp_enabled || $locked->whatsapp_balance <= 0) {
                    return false;
                }
                $locked->decrement('whatsapp_balance', 1);
                $locked->increment('whatsapp_used_this_month', 1);
                return true;
            }

            if ($channel === 'sms') {
                if (!$locked->sms_enabled || $locked->sms_balance <= 0) {
                    return false;
                }
                $locked->decrement('sms_balance', 1);
                $locked->increment('sms_used_this_month', 1);
                return true;
            }

            return false;
        });
    }

    public function logMessage(
        int $tenantId,
        ?int $appointmentId,
        string $channel,
        string $phone,
        string $type,
        string $content,
        string $status = 'sent'
    ): MessageLog {
        return MessageLog::create([
            'tenant_id' => $tenantId,
            'appointment_id' => $appointmentId,
            'channel' => $channel,
            'recipient_phone' => $phone,
            'message_type' => $type,
            'content' => $content,
            'status' => $status,
        ]);
    }

    public function topupBalance(
        Tenant $tenant,
        string $packageType,
        int $quantity,
        float $amountEgp,
        string $paymentMethod = 'manual_super_admin',
        ?string $fawryRef = null,
        ?int $approvedByAdminId = null
    ): WalletTopup {
        return DB::transaction(function () use ($tenant, $packageType, $quantity, $amountEgp, $paymentMethod, $fawryRef, $approvedByAdminId) {
            $topup = WalletTopup::create([
                'tenant_id' => $tenant->id,
                'package_type' => $packageType,
                'quantity_added' => $quantity,
                'amount_egp' => $amountEgp,
                'payment_method' => $paymentMethod,
                'fawry_reference' => $fawryRef,
                'status' => 'paid',
                'approved_by_admin_id' => $approvedByAdminId,
            ]);

            if ($packageType === 'whatsapp') {
                $tenant->increment('whatsapp_balance', $quantity);
            } elseif ($packageType === 'sms') {
                $tenant->increment('sms_balance', $quantity);
            }

            return $topup;
        });
    }
}
