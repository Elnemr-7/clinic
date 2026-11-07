<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicShift;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsAndClinicManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Clinic $clinic;
    protected User $doctor;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name_ar' => 'باقة VIP',
            'price_monthly' => 1000,
            'price_yearly' => 10000,
            'max_clinics' => 2, // Limit of 2 clinics
            'allow_custom_domain' => true,
            'allow_custom_website' => true,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'د. حاتم الجندي',
            'specialty_key' => 'orthopedics',
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'subdomain' => 'dr-hatem-ortho',
            'public_booking_api_key' => 'pbk_settings_test_77',
        ]);

        $this->clinic = Clinic::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'فرع الدقي',
            'consultation_price' => 450,
            'followup_price' => 100,
            'tv_screen_code' => 'TVDOKK',
            'is_active' => true,
        ]);

        $this->doctor = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'د. حاتم',
            'email' => 'dr.hatem@clinic.com',
            'password' => bcrypt('password123'),
            'role' => 'doctor',
            'is_active' => true,
        ]);
    }

    /**
     * Test creating a branch within plan limit succeeds.
     */
    public function test_save_branch_within_plan_limit(): void
    {
        $token = $this->doctor->createToken('doc_tok', ['role:doctor'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/clinic/settings/branches', [
                'name_ar' => 'فرع التجمع الخامس',
                'address_ar' => 'شارع التسعين',
                'phone' => '01009988776',
                'consultation_price' => 500,
                'followup_price' => 120,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('clinics', [
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'فرع التجمع الخامس',
        ]);
    }

    /**
     * Test creating a branch exceeding plan limit is blocked with 403.
     */
    public function test_save_branch_exceeding_plan_limit_blocked(): void
    {
        $token = $this->doctor->createToken('doc_tok', ['role:doctor'])->plainTextToken;

        // Add 2nd clinic (reaching limit of 2)
        Clinic::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'فرع 2',
            'is_active' => true,
        ]);

        // Attempting to add 3rd clinic
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/clinic/settings/branches', [
                'name_ar' => 'فرع 3 زائد عن الخطة',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test saving shifts supports multiple days of the week simultaneously.
     */
    public function test_save_shift_supports_multiple_days(): void
    {
        $token = $this->doctor->createToken('doc_tok', ['role:doctor'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/clinic/settings/shifts', [
                'clinic_id' => $this->clinic->id,
                'shift_name_ar' => 'الوردية الصباحية الموحدة',
                'days' => [0, 1, 2], // Sunday, Monday, Tuesday
                'start_time' => '10:00:00',
                'end_time' => '14:00:00',
                'max_patients' => 20,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(3, ClinicShift::where('clinic_id', $this->clinic->id)->count());
    }

    /**
     * Test emergency day-off declaration cancels appointments and records apology.
     */
    public function test_emergency_day_off_cancels_appointments(): void
    {
        $token = $this->doctor->createToken('doc_tok', ['role:doctor'])->plainTextToken;

        $shift = ClinicShift::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'day_of_week' => today()->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '14:00:00',
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'طارق محيي',
            'phone' => '01012345678',
        ]);

        $appt = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'shift_id' => $shift->id,
            'patient_id' => $patient->id,
            'appointment_date' => today(),
            'queue_number' => 1,
            'reference_code' => 'CLN-DAYOFF',
            'status' => 'booked',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/clinic/settings/days-off', [
                'clinic_id' => $this->clinic->id,
                'date' => today()->toDateString(),
                'reason_ar' => 'مؤتمر جراحة العظام السنوي',
                'action' => 'cancel_and_notify',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $appt->refresh();
        $this->assertEquals('cancelled', $appt->status);

        $dayOff = \App\Models\ClinicDayOff::where('clinic_id', $this->clinic->id)->first();
        $this->assertNotNull($dayOff);
        $this->assertEquals(today()->toDateString(), $dayOff->date->toDateString());
    }

    /**
     * Test website mode switching enforces plan capabilities.
     */
    public function test_website_mode_switching_validates_plan_capabilities(): void
    {
        $token = $this->doctor->createToken('doc_tok', ['role:doctor'])->plainTextToken;

        // Plan allows custom domain and custom website (Modes 1 to 4 allowed)
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/clinic/settings/website', [
                'website_mode' => 2,
                'custom_domain' => 'drhatemortho.com',
                'content' => [
                    'hero_title' => 'أفضل رعاية لجراحة العظام والمفاصل',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->tenant->refresh();
        $this->assertEquals('drhatemortho.com', $this->tenant->custom_domain);
    }
}
