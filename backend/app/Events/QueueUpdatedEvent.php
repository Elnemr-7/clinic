<?php

namespace App\Events;

use App\Models\Clinic;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueUpdatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $clinicId;
    public int $shiftId;
    public string $date;
    public ?string $tvCode;

    public function __construct(int $clinicId, int $shiftId, string $date)
    {
        $this->clinicId = $clinicId;
        $this->shiftId = $shiftId;
        $this->date = $date;

        $clinic = Clinic::find($clinicId);
        $this->tvCode = $clinic?->tv_screen_code;

        // Dispatch broadcast to JS-server Socket.io for ReceptionDesk & TV Screens
        try {
            $serviceUrl = config('services.whatsapp.service_url', 'http://127.0.0.1:3000');
            $secret = config('services.whatsapp.secret');
            \Illuminate\Support\Facades\Http::timeout(1)->withHeaders([
                'X-Shared-Secret' => $secret,
            ])->post("{$serviceUrl}/internal/broadcast-queue", [
                'clinic_id' => $this->clinicId,
                'tv_code' => $this->tvCode,
                'shift_id' => $this->shiftId,
                'date' => $this->date,
                'event' => 'queue:updated',
            ]);
        } catch (\Throwable $e) {
            // Non-blocking fallback
        }
    }

    public function broadcastOn(): array
    {
        $channels = [
            new Channel("clinic.{$this->clinicId}.queue"),
        ];

        if ($this->tvCode) {
            $channels[] = new Channel("tv.{$this->tvCode}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'QueueUpdated';
    }
}
