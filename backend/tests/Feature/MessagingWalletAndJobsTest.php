<?php

namespace Tests\Feature;

use App\Jobs\CheckNearTurnNotificationsJob;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicShift;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\MessagingWalletService;
use App\Services\SmsGatewayService;
use App\Services\WhatsAppChannelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingWalletAndJobsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Clinic $clinic;
    protected ClinicShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name_ar' => 'باقة متقدمة',
            'price_monthly' => 500,
            'price_yearly' => 5000,
            'max_clinics' => 2,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'د. حسام السعدني',
            'plan_id' => $plan->id,
            'status' => 'active',
            'subdomain' => 'dr-saadany',
            'whatsapp_enabled' => true,
            'whatsapp_balance' => 5,
            'whatsapp_used_this_month' => 10,
            'sms_enabled' => true,
            'sms_balance' => 2,
            'sms_used_this_month' => 0,
            'public_booking_api_key' => 'pbk_msg_test_01',
            'notification_settings' => [
                'near_turn_threshold' => 3,
            ],
        ]);

        $this->clinic = Clinic::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'فرع الإسكندرية',
            'is_active' => true,
        ]);

        $this->shift = ClinicShift::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'day_of_week' => today()->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '16:00:00',
            'is_active' => true,
        ]);
    }

    /**
     * Test messaging balance deduction decrements balance and increments monthly usage.
     */
    public function test_deduct_message_balance_updates_counters(): void
    {
        $walletService = app(MessagingWalletService::class);

        // WhatsApp deduction
        $result = $walletService->deductMessageBalance($this->tenant, 'whatsapp');
        $this->assertTrue($result);

        $this->tenant->refresh();
        $this->assertEquals(4, $this->tenant->whatsapp_balance);
        $this->assertEquals(11, $this->tenant->whatsapp_used_this_month);

        // SMS deduction
        $resultSms = $walletService->deductMessageBalance($this->tenant, 'sms');
        $this->assertTrue($resultSms);

        $this->tenant->refresh();
        $this->assertEquals(1, $this->tenant->sms_balance);
        $this->assertEquals(1, $this->tenant->sms_used_this_month);
    }

    /**
     * Test balance deduction fails when balance reaches zero.
     */
    public function test_deduct_message_balance_fails_when_exhausted(): void
    {
        $walletService = app(MessagingWalletService::class);
        $this->tenant->update(['whatsapp_balance' => 0]);

        $result = $walletService->deductMessageBalance($this->tenant, 'whatsapp');
        $this->assertFalse($result);

        $this->tenant->refresh();
        $this->assertEquals(0, $this->tenant->whatsapp_balance);
    }

    /**
     * Test CheckNearTurnNotificationsJob notifies patients within threshold and marks notified.
     */
    public function test_near_turn_notifications_job_execution(): void
    {
        $patient1 = Patient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'مريض 1',
            'phone' => '01011111111',
        ]);

        $appt1 = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'shift_id' => $this->shift->id,
            'patient_id' => $patient1->id,
            'appointment_date' => today(),
            'queue_number' => 1,
            'reference_code' => 'CLN-N001',
            'status' => 'arrived_waiting',
            'whatsapp_opted_in' => true,
            'near_turn_notified' => false,
        ]);

        $job = new CheckNearTurnNotificationsJob($this->clinic->id, $this->shift->id, today()->toDateString());
        $job->handle(app(WhatsAppChannelService::class), app(SmsGatewayService::class));

        $appt1->refresh();
        $this->assertTrue($appt1->near_turn_notified);
    }
}
