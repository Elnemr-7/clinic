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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReceptionistTest extends TestCase
{
    use DatabaseTransactions;

    protected Tenant $tenant;
    protected Clinic $clinic;
    protected User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::firstOrCreate(['name_ar' => 'Basic'], ['price_monthly' => 100, 'price_yearly' => 1000, 'max_clinics' => 1]);

        $this->tenant = Tenant::create([
            'name' => 'Dr Test Clinic',
            'subdomain' => 'dr-test-' . uniqid(),
            'plan_id' => $plan->id,
            'status' => 'active',
            'public_booking_api_key' => 'pbk_' . uniqid(),
        ]);

        $this->clinic = Clinic::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'Main Branch',
            'address_ar' => 'Cairo',
            'phone' => '01012345678',
            'tv_screen_code' => 'TV' . rand(1000, 9999),
            'is_active' => true,
        ]);

        $this->doctor = User::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'name' => 'Doctor Test',
            'email' => 'doctor_' . uniqid() . '@clinic.com',
            'password' => Hash::make('password123'),
            'role' => 'doctor',
            'is_active' => true,
        ]);
    }

    public function test_doctor_can_create_and_edit_receptionist()
    {
        $token = $this->doctor->createToken('clinic_token', ['role:doctor'])->plainTextToken;

        // 1. Create receptionist
        $createRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/clinic/settings/receptionists', [
                'name' => 'Sara Ahmed',
                'email' => 'sara_' . uniqid() . '@clinic.com',
                'phone' => '01099887766',
                'password' => 'secret1234',
                'clinic_id' => $this->clinic->id,
            ]);

        $createRes->assertStatus(200);
        $receptionistId = $createRes->json('data.id');
        $this->assertNotNull($receptionistId);
        $this->assertEquals('Sara Ahmed', $createRes->json('data.name'));

        // 2. Edit receptionist (change name, phone, branch, without changing password)
        $editRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/clinic/settings/receptionists', [
                'id' => $receptionistId,
                'name' => 'Sara Ahmed Updated',
                'email' => $createRes->json('data.email'),
                'phone' => '01011223344',
                'clinic_id' => $this->clinic->id,
                'is_active' => true,
            ]);

        $editRes->assertStatus(200);
        $this->assertEquals('Sara Ahmed Updated', $editRes->json('data.name'));
        $this->assertEquals('01011223344', $editRes->json('data.phone'));

        // Verify in database
        $user = User::find($receptionistId);
        $this->assertEquals('Sara Ahmed Updated', $user->name);
        $this->assertEquals('01011223344', $user->phone);
        $this->assertTrue(Hash::check('secret1234', $user->password)); // password preserved!
    }

    public function test_appointment_cancellation_refunds_money_when_requested()
    {
        $token = $this->doctor->createToken('clinic_token', ['role:doctor'])->plainTextToken;

        $shift = ClinicShift::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'shift_name_ar' => 'الوردية الصباحية',
            'day_of_week' => 'wednesday',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'max_patients' => 50,
            'avg_consultation_time' => 15,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Mohamed Ali',
            'phone' => '01011223399',
            'gender' => 'male',
        ]);

        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'shift_id' => $shift->id,
            'patient_id' => $patient->id,
            'appointment_date' => today(),
            'queue_number' => 1,
            'reference_code' => 'CLN-TEST1',
            'booking_source' => 'clinic_desk',
            'visit_type' => 'new_visit',
            'status' => 'booked',
            'consultation_fee' => 300,
            'total_amount' => 300,
            'is_paid' => true,
        ]);

        CashTransaction::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'appointment_id' => $appointment->id,
            'created_by_user_id' => $this->doctor->id,
            'type' => 'income',
            'category' => 'consultation',
            'amount' => 300,
            'description_ar' => 'تحصيل كشف',
            'transaction_date' => today(),
        ]);

        // Cancel appointment with refund_money = true
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/clinic/reception/status/{$appointment->id}", [
                'status' => 'cancelled',
                'refund_money' => true,
            ]);

        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertFalse($appointment->is_paid);

        // Transaction is cleanly reversed/removed so person's cash is NOT counted twice in adding or subtracting
        $transactionsCount = CashTransaction::where('appointment_id', $appointment->id)->count();
        $this->assertEquals(0, $transactionsCount);

        // Cashbox income, expense, and net should all be 0 for this patient
        $cashboxRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/clinic/reception/cashbox?clinic_id={$this->clinic->id}");
        $cashboxRes->assertStatus(200);
        $this->assertEquals(0, $cashboxRes->json('data.total_income'));
        $this->assertEquals(0, $cashboxRes->json('data.total_expense'));
        $this->assertEquals(0, $cashboxRes->json('data.net_in_drawer'));
    }

    public function test_appointment_cancellation_does_not_refund_when_not_requested()
    {
        $token = $this->doctor->createToken('clinic_token', ['role:doctor'])->plainTextToken;

        $shift = ClinicShift::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'shift_name_ar' => 'الوردية الصباحية',
            'day_of_week' => 'wednesday',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'max_patients' => 50,
            'avg_consultation_time' => 15,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Kareem Ahmed',
            'phone' => '01011223388',
            'gender' => 'male',
        ]);

        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'shift_id' => $shift->id,
            'patient_id' => $patient->id,
            'appointment_date' => today(),
            'queue_number' => 2,
            'reference_code' => 'CLN-TEST2',
            'booking_source' => 'clinic_desk',
            'visit_type' => 'new_visit',
            'status' => 'booked',
            'consultation_fee' => 300,
            'total_amount' => 300,
            'is_paid' => true,
        ]);

        CashTransaction::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'appointment_id' => $appointment->id,
            'created_by_user_id' => $this->doctor->id,
            'type' => 'income',
            'category' => 'consultation',
            'amount' => 300,
            'description_ar' => 'تحصيل كشف',
            'transaction_date' => today(),
        ]);

        // Cancel appointment with refund_money = false
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/clinic/reception/status/{$appointment->id}", [
                'status' => 'cancelled',
                'refund_money' => false,
            ]);

        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertTrue($appointment->is_paid); // Money retained

        // No expense transaction
        $refundExpense = CashTransaction::where('appointment_id', $appointment->id)
            ->where('type', 'expense')
            ->first();
        $this->assertNull($refundExpense);

        // Cashbox net stays 300
        $cashboxRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/clinic/reception/cashbox?clinic_id={$this->clinic->id}");
        $cashboxRes->assertStatus(200);
        $this->assertEquals(300, $cashboxRes->json('data.net_in_drawer'));
    }

    public function test_marking_paid_multiple_times_does_not_duplicate_cash_transactions()
    {
        $token = $this->doctor->createToken('clinic_token', ['role:doctor'])->plainTextToken;

        $shift = ClinicShift::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'shift_name_ar' => 'الوردية الصباحية',
            'day_of_week' => 'wednesday',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'max_patients' => 50,
            'avg_consultation_time' => 15,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Tarek Mahmoud',
            'phone' => '01011223377',
            'gender' => 'male',
        ]);

        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'shift_id' => $shift->id,
            'patient_id' => $patient->id,
            'appointment_date' => today(),
            'queue_number' => 3,
            'reference_code' => 'CLN-TEST3',
            'booking_source' => 'clinic_desk',
            'visit_type' => 'new_visit',
            'status' => 'booked',
            'consultation_fee' => 300,
            'total_amount' => 300,
            'is_paid' => false,
        ]);

        // 1. Mark paid first time
        $res1 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/clinic/reception/status/{$appointment->id}", [
                'status' => 'arrived_waiting',
                'mark_paid' => true,
            ]);
        $res1->assertStatus(200);

        // 2. Mark paid second time (e.g. toggle status or repeated call)
        $res2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/clinic/reception/status/{$appointment->id}", [
                'status' => 'in_consultation',
                'mark_paid' => true,
            ]);
        $res2->assertStatus(200);

        // Exactly ONE transaction should exist, not 2
        $count = CashTransaction::where('appointment_id', $appointment->id)
            ->where('type', 'income')
            ->count();
        $this->assertEquals(1, $count);

        $totalIncome = CashTransaction::where('appointment_id', $appointment->id)
            ->where('type', 'income')
            ->sum('amount');
        $this->assertEquals(300, $totalIncome);
    }

    public function test_doctor_can_save_and_retrieve_custom_vitals_and_drugs()
    {
        $token = $this->doctor->createToken('clinic_token', ['role:doctor'])->plainTextToken;

        // 1. Save custom vitals config
        $vitalsData = [
            ['key' => 'bp', 'label' => 'ضغط الدم', 'unit' => 'mmHg', 'enabled' => true],
            ['key' => 'height', 'label' => 'الطول', 'unit' => 'cm', 'enabled' => true],
            ['key' => 'custom_waist', 'label' => 'محيط الخصر', 'unit' => 'cm', 'enabled' => true],
        ];

        $vitalsRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/clinic/settings/vitals', ['vitals_config' => $vitalsData]);
        $vitalsRes->assertStatus(200);

        // Verify in settings response
        $settingsRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/clinic/settings');
        $settingsRes->assertStatus(200);
        $this->assertCount(3, $settingsRes->json('data.vitals_config'));
        $this->assertTrue($settingsRes->json('data.vitals_config.1.enabled'));
        $this->assertEquals('custom_waist', $settingsRes->json('data.vitals_config.2.key'));

        // 2. Save drug with name, dosage, frequency, and instructions
        $drugRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/clinic/settings/drugs', [
                'name' => 'Augmentin 1g',
                'default_dosage' => '1 قرص',
                'default_frequency' => 'كل 12 ساعة',
                'default_instructions' => 'بعد الأكل',
            ]);
        $drugRes->assertStatus(200);
        $drugId = $drugRes->json('data.id');
        $this->assertEquals('Augmentin 1g', $drugRes->json('data.name'));

        // Verify drugs list
        $getDrugsRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/clinic/settings/drugs');
        $getDrugsRes->assertStatus(200);
        $firstDrug = collect($getDrugsRes->json('data'))->firstWhere('id', $drugId);
        $this->assertEquals('Augmentin 1g', $firstDrug['name']);
        $this->assertEquals('1 قرص', $firstDrug['default_dosage']);
        $this->assertEquals('كل 12 ساعة', $firstDrug['default_frequency']);
        $this->assertEquals('بعد الأكل', $firstDrug['default_instructions']);

        // 3. Delete drug
        $delRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/clinic/settings/drugs/{$drugId}");
        $delRes->assertStatus(200);
    }
}