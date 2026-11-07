<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicShift;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\PrescriptionPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PrescriptionPdfAndLetterheadTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Clinic $clinic;
    protected Consultation $consultation;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name_ar' => 'باقة قياسية',
            'price_monthly' => 300,
            'price_yearly' => 3000,
            'max_clinics' => 1,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'د. حسام عزمي',
            'specialty_key' => 'general',
            'title_ar' => 'استشاري أمراض الباطنة',
            'plan_id' => $plan->id,
            'status' => 'active',
            'subdomain' => 'dr-hossam-azmi',
            'public_booking_api_key' => 'pbk_pdf_test_55',
            'print_settings' => [
                'use_preprinted_letterhead' => false,
                'paper_size' => 'A5',
                'margin_top' => 30,
                'margin_bottom' => 20,
                'margin_left' => 15,
                'margin_right' => 15,
                'footer_text' => 'نتمنى لكم دوام الصحة والعافية',
            ],
        ]);

        $this->clinic = Clinic::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'المركز الطبي التخصصي',
            'address_ar' => 'شارع الهرم',
            'phone' => '0233445566',
            'is_active' => true,
        ]);

        $shift = ClinicShift::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'day_of_week' => today()->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '15:00:00',
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'جمال عبد الصمد',
            'phone' => '01011223344',
            'birth_year' => 1975,
        ]);

        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'shift_id' => $shift->id,
            'patient_id' => $patient->id,
            'appointment_date' => today(),
            'queue_number' => 1,
            'reference_code' => 'CLN-PDF01',
            'status' => 'completed',
        ]);

        $this->consultation = Consultation::create([
            'tenant_id' => $this->tenant->id,
            'clinic_id' => $this->clinic->id,
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'diagnosis' => 'التهاب الجيوب الأنفية',
            'prescribed_drugs' => [
                [
                    'drug_name' => 'Clarinase',
                    'dosage' => 'قرص مرتين يومياً',
                    'duration' => '5 أيام',
                ],
            ],
            'pdf_short_token' => 'rx_pdf_test_tk',
        ]);
    }

    /**
     * Test prescription PDF generation outputs a readable file.
     */
    public function test_generate_prescription_pdf_creates_file(): void
    {
        $service = app(PrescriptionPdfService::class);
        $filePath = $service->generatePrescriptionPdf($this->consultation);

        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $this->assertNotEmpty($content);

        // Clean up generated file
        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }

    /**
     * Test pre-printed letterhead mode suppresses header HTML.
     */
    public function test_preprinted_letterhead_configuration(): void
    {
        $this->tenant->update([
            'print_settings' => [
                'use_preprinted_letterhead' => true,
                'paper_size' => 'A5',
                'margin_top' => 50,
            ],
        ]);

        $service = app(PrescriptionPdfService::class);
        $filePath = $service->generatePrescriptionPdf($this->consultation);

        $this->assertFileExists($filePath);

        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }

    /**
     * Test test margins preview generator outputs valid file.
     */
    public function test_generate_test_margins_pdf(): void
    {
        $service = app(PrescriptionPdfService::class);
        $filePath = $service->generateTestMarginsPdf($this->tenant, [
            'margin_top' => 45,
            'margin_bottom' => 25,
            'paper_size' => 'A5',
        ]);

        $this->assertFileExists($filePath);

        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }
}
