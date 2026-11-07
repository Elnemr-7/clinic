<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicShift;
use App\Models\Consultation;
use App\Models\DoctorDrug;
use App\Models\DoctorLabTest;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorWorkspaceConsultationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Clinic $clinic;
    protected User $doctor;
    protected Patient $patient;
    protected ClinicShift $shift;
    protected Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name_ar' => 'باقة الطبيب المتميزة',
            'price_monthly' => 450,
            'price_yearly' => 4500,
            'max_clinics' => 2,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'د. هاني عثمان',
            'specialty_key' => 'general',
            'plan_id' => $plan->id,
            'status' => 'active',
            'subdomain' => 'dr-hany',
            'public_booking_api_key' => 'pbk_doctor_workspace_test',
        ]);

        $this->clinic = Clinic::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'العيادة التخصصية',
            'consultation_price' => 400,
            'followup_price' => 80,
            'avg_consultation_minutes' => 15,
            'tv_screen_code' => 'TVHANY',
            'is_active' => true,
        ]);

        $this->shift = ClinicShift::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'day_of_week' => today()->dayOfWeek,
            'shift_name_ar' => 'الوردية المسائية',
            'start_time' => '17:00:00',
            'end_time' => '22:00:00',
            'max_patients' => 30,
            'is_active' => true,
        ]);

        $this->doctor = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'د. هاني',
            'email' => 'dr.hany@clinic.com',
            'password' => bcrypt('password123'),
            'role' => 'doctor',
            'is_active' => true,
        ]);

        $this->patient = Patient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'مصطفى كامل',
            'phone' => '01122334455',
            'gender' => 'male',
            'birth_year' => 1988,
        ]);

        $this->appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'shift_id' => $this->shift->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => today(),
            'queue_number' => 1,
            'reference_code' => 'CLN-T001',
            'booking_source' => 'clinic_desk',
            'visit_type' => 'new_visit',
            'status' => 'in_consultation',
            'consultation_fee' => 400,
            'total_amount' => 400,
            'is_paid' => true,
        ]);
    }

    /**
     * Test currentPatient endpoint returns active appointment and queue counters.
     */
    public function test_current_patient_returns_active_consultation_and_counts(): void
    {
        $token = $this->doctor->createToken('doc_tok', ['role:doctor'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/clinic/doctor/current-patient?clinic_id={$this->clinic->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.appointment.id', $this->appointment->id)
            ->assertJsonPath('data.appointment.patient.name', 'مصطفى كامل');
    }

    /**
     * Test savePrescription saves drugs and auto-registers new drugs in tenant drug catalogue.
     */
    public function test_save_prescription_saves_consultation_and_auto_catalogs_drugs(): void
    {
        $token = $this->doctor->createToken('doc_tok', ['role:doctor'])->plainTextToken;

        $payload = [
            'diagnosis' => 'التهاب حاد في الحلق والشعب الهوائية',
            'complaints_symptoms' => 'ارتفاع في درجة الحرارة وسعال مستمر',
            'private_doctor_notes' => 'مريض حساسية بنسلين سابقة',
            'prescribed_drugs' => [
                [
                    'drug_name' => 'Augmentin 1g',
                    'form_type' => 'أقراص',
                    'dosage' => 'قرص كل 12 ساعة',
                    'duration' => '7 أيام',
                    'instructions' => 'بعد الأكل مباشرة',
                ],
                [
                    'drug_name' => 'Panadol Extra',
                    'form_type' => 'أقراص',
                    'dosage' => 'قرصان عند اللزوم',
                    'duration' => '5 أيام',
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/clinic/doctor/prescription/{$this->appointment->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('consultations', [
            'appointment_id' => $this->appointment->id,
            'diagnosis' => 'التهاب حاد في الحلق والشعب الهوائية',
        ]);

        // Verify drugs auto-cataloged in doctor_drugs table
        $this->assertDatabaseHas('doctor_drugs', [
            'tenant_id' => $this->tenant->id,
            'drug_name' => 'Augmentin 1g',
        ]);

        $this->assertDatabaseHas('doctor_drugs', [
            'tenant_id' => $this->tenant->id,
            'drug_name' => 'Panadol Extra',
        ]);
    }

    /**
     * Test saveLabsAndNotes records lab tests and auto-registers them in catalog.
     */
    public function test_save_labs_and_notes(): void
    {
        $token = $this->doctor->createToken('doc_tok', ['role:doctor'])->plainTextToken;

        $payload = [
            'requested_labs' => [
                [
                    'test_name' => 'Complete Blood Picture (CBC)',
                    'type' => 'lab',
                    'notes' => 'صيام 8 ساعات',
                ],
                [
                    'test_name' => 'Chest X-Ray PA View',
                    'type' => 'radiology',
                    'notes' => 'استبعاد التهاب رئوي',
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/clinic/doctor/labs/{$this->appointment->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('doctor_lab_tests', [
            'tenant_id' => $this->tenant->id,
            'test_name' => 'Complete Blood Picture (CBC)',
        ]);
    }

    /**
     * Test completeAndCallNextPatient completes current appointment, schedules followup, and calls next.
     */
    public function test_complete_and_call_next_patient_workflow(): void
    {
        $token = $this->doctor->createToken('doc_tok', ['role:doctor'])->plainTextToken;

        // Set consultation with followup date
        Consultation::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->patient->id,
            'diagnosis' => 'نزلة معوية حادة',
            'followup_date' => today()->addDays(7),
            'pdf_short_token' => 'rx_test_token_12',
        ]);

        // Create waiting next patient
        $nextPatient = Patient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'سامي يوسف',
            'phone' => '01099887766',
        ]);

        $nextAppt = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'shift_id' => $this->shift->id,
            'patient_id' => $nextPatient->id,
            'appointment_date' => today(),
            'queue_number' => 2,
            'reference_code' => 'CLN-T002',
            'status' => 'arrived_waiting',
            'consultation_fee' => 400,
            'total_amount' => 400,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/clinic/doctor/next-patient', [
                'clinic_id' => $this->clinic->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Previous appointment marked completed
        $this->appointment->refresh();
        $this->assertEquals('completed', $this->appointment->status);
        $this->assertNotNull($this->appointment->actual_completed_at);

        // Next appointment marked in_consultation
        $nextAppt->refresh();
        $this->assertEquals('in_consultation', $nextAppt->status);
        $this->assertNotNull($nextAppt->actual_entered_at);

        // Followup appointment auto-scheduled for 7 days later
        $this->assertDatabaseHas('appointments', [
            'tenant_id' => $this->tenant->id,
            'patient_id' => $this->patient->id,
            'visit_type' => 'followup',
            'status' => 'booked',
        ]);
    }

    /**
     * Test drug autocomplete returns tenant-scoped matches.
     */
    public function test_drug_autocomplete_scoped_to_tenant(): void
    {
        $token = $this->doctor->createToken('doc_tok', ['role:doctor'])->plainTextToken;

        DoctorDrug::create([
            'tenant_id' => $this->tenant->id,
            'drug_name' => 'Cataflam 50mg',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/clinic/doctor/drugs/autocomplete?q=Cata');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Cataflam 50mg', $data[0]['drug_name']);
    }
}
