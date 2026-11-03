<?php

namespace App\Events;

use App\Models\Clinic;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        $clinicIdVal = $this->clinicId;
        $shiftIdVal = $this->shiftId;
        $dateVal = $this->date;
        $tvCodeVal = $this->tvCode;

        // Dispatch broadcast to JS-server Socket.io after DB commit to avoid holding locks
        DB::afterCommit(function () use ($clinicIdVal, $shiftIdVal, $dateVal, $tvCodeVal) {
            try {
                $serviceUrl = config('services.whatsapp.service_url', 'http://127.0.0.1:3000');
                $secret = config('services.whatsapp.secret');
                Http::timeout(2)->withHeaders([
                    'X-Shared-Secret' => $secret,
                    'X-Internal-Secret' => $secret,
                ])->post("{$serviceUrl}/internal/broadcast-queue", [
                    'clinic_id' => $clinicIdVal,
                    'tv_code' => $tvCodeVal,
                    'shift_id' => $shiftIdVal,
                    'date' => $dateVal,
                    'event' => 'queue:updated',
                ]);
            } catch (\Throwable $e) {
                // Non-blocking fallback
                Log::debug("QueueUpdatedEvent broadcast-queue non-blocking error: " . $e->getMessage());
            }
        });
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
        return 'queue.updated';
    }
}
