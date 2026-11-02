<?php

namespace App\Jobs;

use App\Events\PatientNearTurnEvent;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Services\SmsGatewayService;
use App\Services\WhatsAppChannelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckNearTurnNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $clinicId;
    public int $shiftId;
    public string $date;

    public function __construct(int $clinicId, int $shiftId, string $date)
    {
        $this->clinicId = $clinicId;
        $this->shiftId = $shiftId;
        $this->date = $date;
    }

    public function handle(WhatsAppChannelService $whatsappService, SmsGatewayService $smsService): void
    {
        $clinic = Clinic::find($this->clinicId);
        if (!$clinic) return;

        $tenant = $clinic->tenant;
        if (!$tenant) return;

        $threshold = $tenant->notification_settings['near_turn_threshold'] ?? 3;

        // Query remaining queue in order
        $remaining = Appointment::whereDate('appointment_date', $this->date)
            ->where('clinic_id', $this->clinicId)
            ->where('shift_id', $this->shiftId)
            ->whereIn('status', ['arrived_waiting', 'booked'])
            ->orderByRaw("CASE WHEN status = 'arrived_waiting' THEN 0 ELSE 1 END")
            ->orderBy('queue_number', 'asc')
            ->get();

        foreach ($remaining as $index => $appointment) {
            $slotsAhead = $index + 1;

            if ($slotsAhead <= $threshold && !$appointment->near_turn_notified) {
                $text = "اقترب دورك في {$clinic->name_ar}! متبقي أمامك {$slotsAhead} مرضى، يرجى الاستعداد والتواجد بالعيادة.";

                if ($appointment->whatsapp_opted_in && $tenant->whatsapp_enabled) {
                    $whatsappService->sendMessage($tenant, $appointment->patient->phone, $text, $appointment->id, 'near_turn');
                } elseif ($tenant->sms_enabled) {
                    $smsService->sendSms($tenant, $appointment->patient->phone, $text, 'near_turn', $appointment->id);
                }

                $appointment->update(['near_turn_notified' => true]);

                event(new PatientNearTurnEvent($appointment, $slotsAhead));
            }
        }
    }
}
