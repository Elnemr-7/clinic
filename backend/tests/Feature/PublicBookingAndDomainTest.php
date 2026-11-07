<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicShift;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingAndDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Clinic $clinic;
    protected ClinicShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name_ar' => 'الباقة المتقدمة',
            'price_monthly' => 500,
            'price_yearly' => 5000,
            'max_clinics' => 2,
            'allow_custom_domain' => true,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'د. شريف رمزي',
            'specialty_key' => 'dental',
            'title_ar' => 'استشاري تجميل الأسنان',
            'plan_id' => $plan->id,
            'status' => 'active',
            'subdomain' => 'dr-sherif-dental',
            'custom_domain' => 'drsherifdental.com',
            'public_booking_api_key' => 'pbk_public_test_999',
            'template_key' => 'dental',
        ]);

        $this->clinic = Clinic::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'فرع مدينة نصر',
            'consultation_price' => 500,
            'followup_price' => 150,
            'avg_consultation_minutes' => 20,
            'tv_screen_code' => 'TVSHRF',
            'is_active' => true,
        ]);

        $this->shift = ClinicShift::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'day_of_week' => today()->dayOfWeek,
            'shift_name_ar' => 'الفترة المسائية',
            'start_time' => '16:00:00',
            'end_time' => '21:00:00',
            'max_patients' => 2, // Low capacity for testing limits
            'is_active' => true,
        ]);
    }

    /**
     * Test tenantByHost resolves tenant by subdomain.
     */
    public function test_tenant_by_host_resolves_subdomain(): void
    {
        $response = $this->getJson('/api/public/tenant-by-host?subdomain=dr-sherif-dental');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.subdomain', 'dr-sherif-dental')
            ->assertJsonPath('data.template_key', 'dental');
    }

    /**
     * Test tenantByHost resolves tenant by custom domain.
     */
    public function test_tenant_by_host_resolves_custom_domain(): void
    {
        $response = $this->getJson('/api/public/tenant-by-host?host=drsherifdental.com');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->tenant->id);
    }

    /**
     * Test public online appointment booking succeeds and creates appointment.
     */
    public function test_public_book_appointment_succeeds(): void
    {
        $payload = [
            'clinic_id' => $this->clinic->id,
            'shift_id' => $this->shift->id,
            'date' => today()->toDateString(),
            'patient_name' => 'عمر خالد',
            'phone' => '01011122233',
            'gender' => 'male',
            'age' => 32,
        ];

        $response = $this->postJson('/api/public/book-appointment', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.queue_number', 1);

        $this->assertDatabaseHas('appointments', [
            'clinic_id' => $this->clinic->id,
            'shift_id' => $this->shift->id,
            'queue_number' => 1,
            'booking_source' => 'online',
        ]);
    }

    /**
     * Test duplicate booking on same day by same phone number is rejected.
     */
    public function test_duplicate_same_day_booking_rejected(): void
    {
        $payload = [
            'clinic_id' => $this->clinic->id,
            'shift_id' => $this->shift->id,
            'date' => today()->toDateString(),
            'patient_name' => 'عمر خالد',
            'phone' => '01011122233',
        ];

        // First booking succeeds
        $this->postJson('/api/public/book-appointment', $payload)->assertStatus(200);

        // Second booking with same phone on same day is rejected
        $secondResponse = $this->postJson('/api/public/book-appointment', $payload);
        $secondResponse->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * Test booking rejected when shift maximum patient capacity is reached.
     */
    public function test_booking_rejected_when_shift_capacity_exceeded(): void
    {
        // Book up to max capacity (2 patients)
        $this->postJson('/api/public/book-appointment', [
            'clinic_id' => $this->clinic->id,
            'shift_id' => $this->shift->id,
            'date' => today()->toDateString(),
            'patient_name' => 'مريض 1',
            'phone' => '01010000001',
        ])->assertStatus(200);

        $this->postJson('/api/public/book-appointment', [
            'clinic_id' => $this->clinic->id,
            'shift_id' => $this->shift->id,
            'date' => today()->toDateString(),
            'patient_name' => 'مريض 2',
            'phone' => '01010000002',
        ])->assertStatus(200);

        // Third booking should be rejected due to capacity limit
        $thirdResponse = $this->postJson('/api/public/book-appointment', [
            'clinic_id' => $this->clinic->id,
            'shift_id' => $this->shift->id,
            'date' => today()->toDateString(),
            'patient_name' => 'مريض 3',
            'phone' => '01010000003',
        ]);

        $this->assertTrue(
            $thirdResponse->status() === 422 || 
            $thirdResponse->json('success') === false
        );
    }

    /**
     * Test liveQueueStatus returns tracking info and current turn.
     */
    public function test_live_queue_status_tracking(): void
    {
        $patient = Patient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'حاتم فهمي',
            'phone' => '01299988877',
        ]);

        $appt = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'shift_id' => $this->shift->id,
            'patient_id' => $patient->id,
            'appointment_date' => today(),
            'queue_number' => 5,
            'reference_code' => 'CLN-TRK55',
            'status' => 'booked',
        ]);

        $response = $this->getJson("/api/public/live-queue/CLN-TRK55");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.appointment.reference_code', 'CLN-TRK55');
    }

    /**
     * Test waiting room TV display endpoint masks patient names for confidentiality.
     */
    public function test_waiting_room_tv_masks_patient_names(): void
    {
        $patient = Patient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'أحمد عبد الرحمن الشناوي',
            'phone' => '01012341234',
        ]);

        Appointment::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'shift_id' => $this->shift->id,
            'patient_id' => $patient->id,
            'appointment_date' => today(),
            'queue_number' => 1,
            'reference_code' => 'CLN-TV001',
            'status' => 'in_consultation',
        ]);

        $response = $this->getJson("/api/public/tv/TVSHRF");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $name = $response->json('data.current_patient.patient_name');
        // Name must be masked with ***
        $this->assertStringContainsString('***', $name);
    }
}
