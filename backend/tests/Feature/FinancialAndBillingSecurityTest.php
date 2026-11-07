<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\CashTransaction;
use App\Models\Clinic;
use App\Models\ClinicShift;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WalletTopup;
use App\Services\FawryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialAndBillingSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Clinic $clinic;
    protected User $receptionist;
    protected User $doctor;
    protected Patient $patient;
    protected ClinicShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name_ar' => 'الباقة الأساسية',
            'price_monthly' => 300,
            'price_yearly' => 3000,
            'max_clinics' => 1,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'عيادة الشفاء',
            'specialty_key' => 'general',
            'plan_id' => $plan->id,
            'status' => 'active',
            'subdomain' => 'shifaa-clinic',
            'whatsapp_balance' => 0,
            'sms_balance' => 0,
            'public_booking_api_key' => 'pbk_financial_test_123',
        ]);

        $this->clinic = Clinic::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'الفرع الرئيسي',
            'consultation_price' => 300,
            'followup_price' => 50,
            'tv_screen_code' => 'TVSHIF',
            'is_active' => true,
        ]);

        $this->shift = ClinicShift::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'day_of_week' => today()->dayOfWeek,
            'shift_name_ar' => 'الفترة الصباحية',
            'start_time' => '09:00:00',
            'end_time' => '15:00:00',
            'max_patients' => 25,
            'is_active' => true,
        ]);

        $this->doctor = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'د. محمد',
            'email' => 'dr.mohamed@clinic.com',
            'password' => bcrypt('password123'),
            'role' => 'doctor',
            'is_active' => true,
        ]);

        $this->receptionist = User::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'name' => 'سارة الاستقبال',
            'email' => 'sara@clinic.com',
            'password' => bcrypt('password123'),
            'role' => 'receptionist',
            'is_active' => true,
        ]);

        $this->patient = Patient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'خالد علي',
            'phone' => '01012345678',
            'gender' => 'male',
        ]);
    }

    /**
     * Test Fawry payment signature verification.
     */
    public function test_fawry_signature_verification(): void
    {
        config(['services.fawry.security_key' => 'test_secret_key_123']);
        $fawryService = app(FawryService::class);

        $payload = [
            'fawryRefNumber' => '99887766',
            'merchantRefNumber' => 'TOPUP-1001',
            'paymentAmount' => '500.00',
            'orderAmount' => '500.00',
            'orderStatus' => 'PAID',
            'paymentMethod' => 'CARD',
            'paymentRefrenceNumber' => 'REF12345',
        ];

        // Valid signature computation
        $concatenated = '99887766' . 'TOPUP-1001' . '500.00' . '500.00' . 'PAID' . 'CARD' . 'REF12345' . 'test_secret_key_123';
        $payload['messageSignature'] = hash('sha256', $concatenated);

        $this->assertTrue($fawryService->verifySignature($payload));

        // Tampered signature must fail
        $payload['messageSignature'] = 'invalid_hash_signature';
        $this->assertFalse($fawryService->verifySignature($payload));
    }

    /**
     * Test Fawry callback credits wallet upon matching reference.
     */
    public function test_fawry_callback_credits_wallet_quota(): void
    {
        config(['services.fawry.security_key' => 'test_secret_key_123']);

        $topup = WalletTopup::create([
            'tenant_id' => $this->tenant->id,
            'package_type' => 'whatsapp',
            'quantity_added' => 500,
            'amount_egp' => 250,
            'payment_method' => 'fawry',
            'fawry_reference' => 'FAW-998877',
            'status' => 'pending',
        ]);

        $fawryService = app(FawryService::class);
        $result = $fawryService->processCallback(['fawryRefNumber' => 'FAW-998877']);

        $this->assertTrue($result);
        $topup->refresh();
        $this->assertEquals('paid', $topup->status);
        $this->tenant->refresh();
        $this->assertEquals(500, $this->tenant->whatsapp_balance);
    }

    /**
     * Test recording cash income on quick walk-in booking when marked paid.
     */
    public function test_quick_book_creates_income_transaction_when_paid(): void
    {
        $token = $this->receptionist->createToken('rec_tok', ['role:receptionist'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/clinic/reception/quick-book', [
                'clinic_id' => $this->clinic->id,
                'name' => 'ياسر كمال',
                'phone' => '01234567890',
                'mark_paid' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $apptId = $response->json('data.appointment.id');
        $this->assertDatabaseHas('appointments', [
            'id' => $apptId,
            'is_paid' => true,
        ]);

        $this->assertDatabaseHas('cash_transactions', [
            'appointment_id' => $apptId,
            'type' => 'income',
            'amount' => 300.00,
        ]);
    }

    /**
     * Test cashbox summary accurately calculates income, expense, and net balance.
     */
    public function test_cashbox_summary_net_calculation(): void
    {
        $token = $this->receptionist->createToken('rec_tok', ['role:receptionist'])->plainTextToken;

        CashTransaction::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'created_by_user_id' => $this->receptionist->id,
            'type' => 'income',
            'category' => 'consultation',
            'amount' => 600.00,
            'description_ar' => 'تحصيل كشفين',
            'transaction_date' => today(),
        ]);

        CashTransaction::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'created_by_user_id' => $this->receptionist->id,
            'type' => 'expense',
            'category' => 'clinic_supplies',
            'amount' => 150.00,
            'description_ar' => 'شراء مستلزمات طبية',
            'transaction_date' => today(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/clinic/reception/cashbox?clinic_id={$this->clinic->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.total_income', 600)
            ->assertJsonPath('data.total_expense', 150)
            ->assertJsonPath('data.net_in_drawer', 450);
    }

    /**
     * Test expense recording validation requires valid category and positive amount.
     */
    public function test_record_expense_validation(): void
    {
        $token = $this->receptionist->createToken('rec_tok', ['role:receptionist'])->plainTextToken;

        // Invalid expense with negative amount
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/clinic/reception/expense', [
                'clinic_id' => $this->clinic->id,
                'amount' => -50,
                'category' => 'clinic_supplies',
                'description_ar' => 'مصروف غير صالح',
            ]);

        $response->assertStatus(422);

        // Valid expense recording
        $validResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/clinic/reception/expense', [
                'clinic_id' => $this->clinic->id,
                'amount' => 75.50,
                'category' => 'clinic_supplies',
                'description_ar' => 'أدوات نظافة للمركز',
            ]);

        $validResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('cash_transactions', [
            'clinic_id' => $this->clinic->id,
            'type' => 'expense',
            'amount' => 75.50,
        ]);
    }
}
