<?php

namespace App\Jobs;

use App\Models\Consultation;
use App\Services\SmsGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Consultation $consultation;

    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
    }

    public function handle(SmsGatewayService $smsService): void
    {
        $tenant = $this->consultation->tenant;
        $patient = $this->consultation->patient;
        $link = url("/rx/{$this->consultation->pdf_short_token}");
        $message = "تم تسجيل روشتتك الطبية في عيادة {$tenant->name}. يمكنك تحميلها من الرابط التالي: {$link}";

        $smsService->sendSms($tenant, $patient->phone, $message, 'prescription_link', $this->consultation->appointment_id);
    }
}
