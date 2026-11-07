<?php

namespace Tests\Unit;

use App\Models\Clinic;
use App\Models\ClinicShift;
use App\Services\QueueTimeCalculatorService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class QueueTimeCalculatorServiceTest extends TestCase
{
    /**
     * Test initial arrival window calculation offset by queue position.
     */
    public function test_calculate_initial_window(): void
    {
        $service = new QueueTimeCalculatorService();

        $clinic = new Clinic();
        $clinic->avg_consultation_minutes = 15;

        $shift = new ClinicShift();
        $shift->start_time = '10:00:00';

        // Patient #1: Expected 10:00 to 10:30
        $win1 = $service->calculateInitialWindow($shift, $clinic, 1, '2026-11-07');
        $this->assertStringContainsString('10:00:00', $win1['estimated_window_start']);
        $this->assertStringContainsString('10:30:00', $win1['estimated_window_end']);

        // Patient #3: (3 - 1) * 15 = 30 min offset -> Expected 10:30 to 11:00
        $win3 = $service->calculateInitialWindow($shift, $clinic, 3, '2026-11-07');
        $this->assertStringContainsString('10:30:00', $win3['estimated_window_start']);
        $this->assertStringContainsString('11:00:00', $win3['estimated_window_end']);
    }
}
