<?php

namespace Tests\Feature;

use App\Jobs\CheckNearTurnNotificationsJob;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicShift;
use App\Models\DoctorLead;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\SuperAdmin;
use App\Models\SuperAdminAuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WalletTopup;
use App\Scopes\TenantScope;
use App\Services\FawryService;
use App\Services\FollowupDetectorService;
use App\Services\SmsGatewayService;
use App\Services\WhatsAppChannelService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuditRemediationsTest extends TestCase
{
    use DatabaseTransactions;
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_sec_be_01_fawry_signature_verification_and_idempotency()
    {
        config(['services.fawry.security_key' => 'test_fawry_secret_key_123']);
        $fawryService = app(FawryService::class);

        $merchantRef = 'TOPUP-' . uniqid();
        $payload = [
            'fawryRefNumber' => '987654321',
            'merchantRefNumber' => $merchantRef,
            'paymentAmount' => '150.00',
            'orderAmount' => '150.00',
            'orderStatus' => 'PAID',
            'paymentMethod' => 'PAYATFAWRY',
            'paymentRefrenceNumber' => 'REF-555',
        ];

        // Correct concatenated string:
        $expected = hash('sha256', "987654321{$merchantRef}150.00150.00PAIDPAYATFAWRYREF-555test_fawry_secret_key_123");

        $payload['messageSignature'] = $expected;
        $this->assertTrue($fawryService->verifySignature($payload));

        $payload['messageSignature'] = 'invalid_signature';
        $this->assertFalse($fawryService->verifySignature($payload));

        // Test Idempotency with WalletTopup
        $tenant = Tenant::first();
        $initialBalance = $tenant->whatsapp_balance;

        $topup = WalletTopup::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->id,
            'package_type' => 'whatsapp',
            'quantity_added' => 100,
            'amount_egp' => 150.0,
            'payment_method' => 'fawry',
            'fawry_reference' => $merchantRef,
            'status' => 'pending',
        ]);

        // First callback should process and credit balance
        $payload['messageSignature'] = $expected;
        $processed = $fawryService->processCallback($payload);
        $this->assertTrue($processed);

        $freshTenant = Tenant::find($tenant->id);
        $this->assertEquals($initialBalance + 100, $freshTenant->whatsapp_balance);

        $freshTopup = WalletTopup::withoutGlobalScope(TenantScope::class)->find($topup->id);
        $this->assertEquals('paid', $freshTopup->status);

        // Replay callback should return false and not double-credit
        $replayed = $fawryService->processCallback($payload);
        $this->assertFalse($replayed);

        $freshTenantAfterReplay = Tenant::find($tenant->id);
        $this->assertEquals($initialBalance + 100, $freshTenantAfterReplay->whatsapp_balance);
    }

    public function test_logic_be_04_emergency_day_off_bulk_reschedule_queue_calculation()
    {
        $doctor = User::where('role', 'doctor')->first();
        $token = $doctor->createToken('test_token', ['role:doctor'])->plainTextToken;
        $clinic = $doctor->tenant->clinics()->first();

        $today = Carbon::today();
        // Day off date = today
        $dayOffDate = $today->toDateString();

        // Target date = next week same weekday or a weekday that has shift
        $targetDate = $today->copy()->addDays(7)->toDateString();

        // Create 2 test appointments on dayOffDate
        $shift = $clinic->shifts()->where('day_of_week', $today->dayOfWeek)->first();
        if (!$shift) {
            $shift = ClinicShift::create([
                'tenant_id' => $clinic->tenant_id,
                'clinic_id' => $clinic->id,
                'day_of_week' => $today->dayOfWeek,
                'shift_name_ar' => 'فترة تجريبية',
                'start_time' => '17:00:00',
                'end_time' => '22:00:00',
                'is_active' => true,
            ]);
        }

        $patient = Patient::first();

        $apt1 = Appointment::create([
            'tenant_id' => $clinic->tenant_id,
            'clinic_id' => $clinic->id,
            'shift_id' => $shift->id,
            'patient_id' => $patient->id,
            'appointment_date' => $dayOffDate,
            'queue_number' => 1001,
            'reference_code' => 'CLN-' . substr(md5(uniqid('a1', true)), 0, 5),
            'status' => 'booked',
            'consultation_fee' => 200,
            'total_amount' => 200,
        ]);

        $apt2 = Appointment::create([
            'tenant_id' => $clinic->tenant_id,
            'clinic_id' => $clinic->id,
            'shift_id' => $shift->id,
            'patient_id' => $patient->id,
            'appointment_date' => $dayOffDate,
            'queue_number' => 1002,
            'reference_code' => 'CLN-' . substr(md5(uniqid('a2', true)), 0, 5),
            'status' => 'booked',
            'consultation_fee' => 200,
            'total_amount' => 200,
        ]);

        // Reschedule to target date
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/clinic/settings/days-off', [
                'clinic_id' => $clinic->id,
                'date' => $dayOffDate,
                'reason_ar' => 'ظرف طارئ للتجربة',
                'action' => 'move_to_date',
                'target_date' => $targetDate,
            ]);

        $response->assertStatus(200);

        $freshApt1 = Appointment::find($apt1->id);
        $freshApt2 = Appointment::find($apt2->id);

        $this->assertEquals($targetDate, $freshApt1->appointment_date->toDateString());
        $this->assertEquals($targetDate, $freshApt2->appointment_date->toDateString());
        $this->assertNotEquals($freshApt1->queue_number, $freshApt2->queue_number);
    }

    public function test_logic_be_05_whatsapp_opt_in_regex_deep_link()
    {
        $tenant = Tenant::first();
        $clinic = $tenant->clinics()->first();
        $patient = Patient::first();
        $shift = $clinic->shifts()->first();

        $ref = 'CLN-' . strtoupper(\Illuminate\Support\Str::random(5));
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $clinic->id,
            'shift_id' => $shift->id,
            'patient_id' => $patient->id,
            'appointment_date' => today(),
            'queue_number' => 999,
            'reference_code' => $ref,
            'status' => 'booked',
            'whatsapp_opted_in' => false,
        ]);

        // Inbound message with "تفعيل حجز رقم CLN-A987"
        $secret = config('services.whatsapp.secret', 'vertex_wa_secure_secret_key_2026');
        $inboundText = "تفعيل حجز رقم {$ref}";
        $response = $this->withHeader('X-Shared-Secret', $secret)
            ->postJson("/api/internal/whatsapp/webhook/{$tenant->id}", [
                'event' => 'messages.upsert',
                'data' => [
                    'from' => $patient->phone . '@s.whatsapp.net',
                    'text' => $inboundText,
                    'is_from_me' => false,
                ],
            ]);

        $response->assertStatus(200);
        $freshApt = Appointment::find($appointment->id);
        $this->assertTrue((bool)$freshApt->whatsapp_opted_in);
    }

    public function test_api_be_09_and_10_quick_book_mapping_and_payment()
    {
        $recUser = User::where('role', 'receptionist')->first();
        $token = $recUser->createToken('test_rec', ['role:receptionist'])->plainTextToken;
        $clinic = $recUser->clinic ?? $recUser->tenant->clinics()->first();

        $res = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/clinic/reception/quick-book', [
                'clinic_id' => $clinic->id,
                'phone' => '01011223344',
                'name' => 'مريض تجريبي للدفع السريع',
                'appointment_type' => 'urgent',
                'mark_paid' => true,
            ]);

        $res->assertStatus(200);
        $data = $res->json('data');

        $apptId = $data['appointment']['id'];
        $appt = Appointment::find($apptId);

        $this->assertEquals('emergency', $appt->visit_type);
        $this->assertTrue((bool)$appt->is_paid);
    }

    public function test_logic_be_06_lead_conversion_creates_working_shifts()
    {
        $admin = SuperAdmin::first();
        $token = $admin->createToken('super_admin')->plainTextToken;

        $lead = DoctorLead::create([
            'doctor_name' => 'د. سمير غانم',
            'phone' => '01234567899',
            'specialty' => 'باطنة',
            'status' => 'new',
        ]);

        $res = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/super-admin/leads/{$lead->id}/convert", [
                'subdomain' => 'dr-samir-' . time(),
            ]);

        $res->assertStatus(200);
        $clinicData = $res->json('data.clinic');
        $clinicId = $clinicData['id'];

        $shiftsCount = ClinicShift::where('clinic_id', $clinicId)->count();
        $this->assertGreaterThanOrEqual(6, $shiftsCount);
    }

    public function test_logic_be_08_followup_eligibility_same_day()
    {
        $tenant = Tenant::first();
        $clinic = $tenant->clinics()->first();
        $phone = '010' . rand(10000000, 99999999);
        $patient = Patient::create([
            'tenant_id' => $tenant->id,
            'name' => 'مريض متابعة نفس اليوم',
            'phone' => $phone,
        ]);
        $shift = $clinic->shifts()->first();

        // Create completed new_visit today
        $completedVisit = Appointment::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $clinic->id,
            'shift_id' => $shift->id,
            'patient_id' => $patient->id,
            'appointment_date' => Carbon::now('Africa/Cairo')->toDateString(),
            'queue_number' => 888,
            'reference_code' => 'CLN-' . substr(md5(uniqid('v1', true)), 0, 5),
            'visit_type' => 'new_visit',
            'status' => 'completed',
        ]);

        $detector = app(FollowupDetectorService::class);
        $check = $detector->checkEligibility($tenant->id, $clinic->id, $patient->phone);
        $this->assertTrue($check['eligible'], 'Should be eligible for followup after completed new_visit');

        // Now create a same-day followup appointment
        $followupAppt = Appointment::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $clinic->id,
            'shift_id' => $shift->id,
            'patient_id' => $patient->id,
            'appointment_date' => Carbon::now('Africa/Cairo')->toDateString(),
            'queue_number' => 889,
            'reference_code' => 'CLN-' . substr(md5(uniqid('v2', true)), 0, 5),
            'visit_type' => 'followup',
            'status' => 'booked',
        ]);

        $checkAfter = $detector->checkEligibility($tenant->id, $clinic->id, $patient->phone);
        // Should no longer be eligible because followup was consumed on same day
        $this->assertFalse($checkAfter['eligible'], 'Should no longer be eligible once same-day followup booked');
    }

    public function test_sec_be_12_impersonation_token_ability_and_audit_log()
    {
        $admin = SuperAdmin::first();
        $token = $admin->createToken('super_admin')->plainTextToken;
        $tenant = Tenant::first();

        $res = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/super-admin/tenants/{$tenant->id}/impersonate");

        $res->assertStatus(200);
        $impersonateToken = $res->json('data.token');
        $this->assertNotEmpty($impersonateToken);

        $auditLog = SuperAdminAuditLog::where('action', 'impersonate_doctor')
            ->where('target_id', $tenant->id)
            ->latest()
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($admin->id, $auditLog->super_admin_id);
    }
}
