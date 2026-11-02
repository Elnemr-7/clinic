<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Scopes\TenantScope;
use App\Services\MessagingWalletService;
use App\Services\WhatsAppChannelService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    use ApiResponseTrait;

    protected MessagingWalletService $walletService;
    protected WhatsAppChannelService $whatsappService;

    public function __construct(MessagingWalletService $walletService, WhatsAppChannelService $whatsappService)
    {
        $this->walletService = $walletService;
        $this->whatsappService = $whatsappService;
    }

    public function handle(int $tenantId, Request $request): JsonResponse
    {
        $event = $request->input('event');
        $data = $request->input('data', []);

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return $this->errorResponse('Tenant not found', null, 404);
        }

        // Connection update event
        if ($event === 'connection.update') {
            $status = $data['status'] ?? 'disconnected';
            $phone = $data['phone_number'] ?? null;

            $tenant->update([
                'whatsapp_session_status' => $status,
                'whatsapp_phone_number' => $phone ?? $tenant->whatsapp_phone_number,
            ]);

            return $this->successResponse(null, 'Session status updated');
        }

        // Inbound message event
        if ($event === 'messages.upsert') {
            $isFromMe = $data['is_from_me'] ?? false;
            if ($isFromMe) {
                return $this->successResponse(null, 'Ignored outbound message');
            }

            $from = $data['from'] ?? '';
            $text = $data['text'] ?? '';
            $senderPhone = preg_replace('/[^0-9]/', '', explode('@', $from)[0]);

            Log::info("WhatsApp Inbound Message for Tenant [{$tenantId}] from [{$senderPhone}]: {$text}");

            // Match booking reference code: e.g. #CLN-4829 or CLN-4829
            if (preg_match('/(?:حجز|كشف|CLN-)?\s*#?([A-Z0-9]{4,8})/iu', $text, $matches)) {
                $refCode = $matches[1];
                if (!str_starts_with($refCode, 'CLN-')) {
                    $refCode = 'CLN-' . $refCode;
                }

                $appointment = Appointment::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $tenantId)
                    ->where('reference_code', $refCode)
                    ->first();

                if ($appointment) {
                    $appointment->update(['whatsapp_opted_in' => true]);

                    // Send confirmation reply + tracking link
                    $clinic = $appointment->clinic;
                    $trackUrl = url("/track/{$appointment->reference_code}");
                    $reply = "أهلاً بك {$appointment->patient->name}! ✅ تم تفعيل تنبيهات دورك والروشتة في عيادة {$clinic->name_ar}.\n"
                           . "رقمك في الكشف: #{$appointment->queue_number}\n"
                           . "الموعد المتوقع: {$appointment->estimated_window_start} - {$appointment->estimated_window_end}\n"
                           . "يمكنك متابعة دورك لحظة بلحظة عبر الرابط:\n{$trackUrl}";

                    $this->whatsappService->sendMessage($tenant, $senderPhone, $reply, $appointment->id, 'booking_confirm');

                    return $this->successResponse(['opted_in' => true, 'reference' => $refCode], 'Opt-in activated');
                }
            }
        }

        return $this->successResponse(null, 'Webhook processed');
    }
}
