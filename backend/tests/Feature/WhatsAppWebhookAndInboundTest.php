<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicShift;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\WhatsAppTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppWebhookAndInboundTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Clinic $clinic;
    protected ClinicShift $shift;
    protected Patient $patient;
    protected Appointment $appointment;
    protected string $sharedSecret = 'vertex_wa_secure_secret_key_2026';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.secret' => $this->sharedSecret]);

        $plan = Plan::create([
            'name_ar' => 'باقة متقدمة',
            'price_monthly' => 500,
            'price_yearly' => 5000,
            'max_clinics' => 2,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'د. ياسر رضوان',
            'specialty_key' => 'general',
            'plan_id' => $plan->id,
            'status' => 'active',
            'subdomain' => 'dr-yasser',
            'whatsapp_enabled' => true,
            'whatsapp_balance' => 100,
            'public_booking_api_key' => 'pbk_wa_test_123',
        ]);

        $this->clinic = Clinic::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'فرع المهندسين',
            'consultation_price' => 300,
            'followup_price' => 50,
            'is_active' => true,
        ]);

        $this->shift = ClinicShift::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'day_of_week' => today()->dayOfWeek,
            'shift_name_ar' => 'الوردية المسائية',
            'start_time' => '17:00:00',
            'end_time' => '22:00:00',
            'is_active' => true,
        ]);

        $this->patient = Patient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'هشام سليم',
            'phone' => '01055566677',
        ]);

        $this->appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'shift_id' => $this->shift->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => today(),
            'queue_number' => 8,
            'reference_code' => 'CLN-WA78',
            'booking_source' => 'online',
            'status' => 'booked',
            'whatsapp_opted_in' => false,
        ]);
    }

    /**
     * Test webhook rejects calls without valid rotating HMAC server token or secret.
     */
    public function test_webhook_unauthorized_without_secret_or_token(): void
    {
        $response = $this->postJson("/api/internal/whatsapp/webhook/{$this->tenant->id}", [
            'event' => 'connection.update',
            'data' => ['status' => 'connected'],
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test connection.update event updates tenant WhatsApp session status and phone.
     */
    public function test_connection_update_event_updates_tenant_status(): void
    {
        $token = WhatsAppTokenService::generateToken($this->sharedSecret);

        $response = $this->withHeader('X-Server-Token', $token)
            ->postJson("/api/internal/whatsapp/webhook/{$this->tenant->id}", [
                'event' => 'connection.update',
                'data' => [
                    'status' => 'connected',
                    'phone_number' => '201055566677',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->tenant->refresh();
        $this->assertEquals('connected', $this->tenant->whatsapp_session_status);
        $this->assertEquals('201055566677', $this->tenant->whatsapp_phone_number);
    }

    /**
     * Test messages.upsert ignores outbound messages sent by bot itself.
     */
    public function test_messages_upsert_ignores_outbound_messages(): void
    {
        $token = WhatsAppTokenService::generateToken($this->sharedSecret);

        $response = $this->withHeader('X-Server-Token', $token)
            ->postJson("/api/internal/whatsapp/webhook/{$this->tenant->id}", [
                'event' => 'messages.upsert',
                'data' => [
                    'is_from_me' => true,
                    'from' => '201055566677@s.whatsapp.net',
                    'text' => 'Outbound test message',
                ],
            ]);

        $response->assertStatus(200);
        $this->appointment->refresh();
        $this->assertFalse($this->appointment->whatsapp_opted_in);
    }

    /**
     * Test inbound WhatsApp activation text enables opt-in on matching appointment.
     */
    public function test_inbound_activation_activates_appointment_opt_in(): void
    {
        $token = WhatsAppTokenService::generateToken($this->sharedSecret);

        $response = $this->withHeader('X-Server-Token', $token)
            ->postJson("/api/internal/whatsapp/webhook/{$this->tenant->id}", [
                'event' => 'messages.upsert',
                'data' => [
                    'is_from_me' => false,
                    'from' => '201055566677@s.whatsapp.net',
                    'text' => 'تفعيل حجز رقم CLN-WA78',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.opted_in', true);

        $this->appointment->refresh();
        $this->assertTrue($this->appointment->whatsapp_opted_in);
    }
}
