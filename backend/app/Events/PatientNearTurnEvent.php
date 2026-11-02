<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientNearTurnEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Appointment $appointment;
    public int $remainingAhead;

    public function __construct(Appointment $appointment, int $remainingAhead)
    {
        $this->appointment = $appointment;
        $this->remainingAhead = $remainingAhead;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("appointment.{$this->appointment->reference_code}"),
        ];
    }
}
