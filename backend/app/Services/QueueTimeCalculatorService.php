<?php

namespace App\Services;

use App\Events\QueueUpdatedEvent;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicShift;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class QueueTimeCalculatorService
{
    public function calculateInitialWindow(ClinicShift $shift, Clinic $clinic, int $queueNumber, Carbon|string $date): array
    {
        $dateObj = is_string($date) ? Carbon::parse($date) : $date->copy();
        $startTime = Carbon::parse($dateObj->format('Y-m-d') . ' ' . $shift->start_time);

        $offsetMinutes = ($queueNumber - 1) * $clinic->avg_consultation_minutes;
        $windowStart = $startTime->copy()->addMinutes($offsetMinutes);
        $windowEnd = $windowStart->copy()->addMinutes(30);

        return [
            'estimated_window_start' => $windowStart->format('H:i:s'),
            'estimated_window_end' => $windowEnd->format('H:i:s'),
        ];
    }

    public function recalculateQueueOnStatusChange(Clinic $clinic, ClinicShift $shift, Carbon|string $date, ?int $enteredQueueNumber = null): Collection
    {
        $dateStr = is_string($date) ? $date : $date->format('Y-m-d');

        // Correction 2: Query all remaining waiting/booked appointments in proper priority order
        $remaining = Appointment::whereDate('appointment_date', $dateStr)
            ->where('clinic_id', $clinic->id)
            ->where('shift_id', $shift->id)
            ->whereIn('status', ['arrived_waiting', 'postponed_waiting_arrival', 'booked'])
            ->orderByRaw("CASE WHEN status = 'arrived_waiting' THEN 0 ELSE 1 END")
            ->orderBy('queue_number', 'asc')
            ->get();

        $baseTime = now();

        foreach ($remaining as $index => $item) {
            $slotsAhead = $index + 1;
            $itemStart = $baseTime->copy()->addMinutes($slotsAhead * $clinic->avg_consultation_minutes);
            $itemEnd = $itemStart->copy()->addMinutes(30);

            $item->update([
                'estimated_window_start' => $itemStart->format('H:i:s'),
                'estimated_window_end' => $itemEnd->format('H:i:s'),
            ]);
        }

        // Broadcast real-time queue update
        event(new QueueUpdatedEvent($clinic->id, $shift->id, $dateStr));

        return $remaining;
    }
}
