<?php

namespace App\Jobs;

use App\Models\Consultation;
use App\Services\WhatsAppChannelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppPrescriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Consultation $consultation;
    public string $pdfPath;

    public function __construct(Consultation $consultation, string $pdfPath)
    {
        $this->consultation = $consultation;
        $this->pdfPath = $pdfPath;
    }

    public function handle(WhatsAppChannelService $whatsappService): void
    {
        $tenant = $this->consultation->tenant;
        $patient = $this->consultation->patient;
        $caption = "مرحباً {$patient->name}، نرفق لك الروشتة الطبية الخاصة بزيارتك اليوم. مع تمنياتنا لك بالشفاء العاجل.";

        $whatsappService->sendPrescriptionPdf(
            $tenant,
            $patient->phone,
            $this->pdfPath,
            $caption,
            $this->consultation->appointment_id
        );
    }
}
