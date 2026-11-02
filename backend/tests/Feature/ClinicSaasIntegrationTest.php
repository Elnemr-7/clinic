<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicShift;
use App\Models\Consultation;
use App\Models\DoctorLead;
use App\Models\Patient;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\User;
use App\Scopes\TenantScope;
use App\Services\WhatsAppTokenService;
use Carbon\Carbon;
use Tests\TestCase;

class ClinicSaasIntegrationTest extends TestCase
{
    public function test_super_admin_login_and_stats()
    {
        $response = $this->postJson('/api/super-admin/login', [
            'email' => 'admin@platform.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['token', 'admin']]);

        $token = $response->json('data.token');

        // Test stats
        $statsRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/super-admin/stats');

        $statsRes->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['total_doctors', 'active_clinics', 'bookings_today']]);
    }

    public function test_doctor_login_rejects_super_admin()
    {
        // Clinic login should reject super admin credentials
        $response = $this->postJson('/api/clinic/login', [
            'email' => 'admin@platform.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_lead_submission_and_conversion()
    {
        // Correction 6: Public leads endpoint
        $leadRes = $this->postJson('/api/public/leads', [
            'name' => 'د. محمود سامي',
            'phone' => '01099887766',
            'email' => 'dr.mahmoud@example.com',
            'medical_specialty' => 'طب وجراحة العيون',
            'clinic_name' => 'مركز النور للعيون',
        ]);

        $leadRes->assertStatus(201);
        $leadId = $leadRes->json('data.id');

        // Login as Super Admin to convert
        $adminLogin = $this->postJson('/api/super-admin/login', [
            'email' => 'admin@platform.com',
            'password' => 'password123',
        ]);
        $token = $adminLogin->json('data.token');

        $convertRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/super-admin/leads/{$leadId}/convert", [
                'plan_id' => 1,
                'subdomain' => 'dr-mahmoud-samy-' . time(),
            ]);

        $convertRes->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['tenant', 'user', 'clinic']]);
    }

    public function test_decoupled_prescription_and_extra_services_collection()
    {
        // Login as Doctor
        $docLogin = $this->postJson('/api/clinic/login', [
            'email' => 'doctor@clinic.com',
            'password' => 'password123',
        ]);
        $token = $docLogin->json('data.token');

        $appointment = Appointment::withoutGlobalScope(TenantScope::class)->first();
        $this->assertNotNull($appointment);

        // Reset extra services paid for clean test
        $appointment->update([
            'extra_services_paid' => false,
            'extra_services_total' => 150.0,
        ]);

        // Correction 1: Decoupled modal save for prescription
        $rxRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/clinic/doctor/prescription/{$appointment->id}", [
                'symptoms' => 'كحة حادة وارتفاع طفيف في الحرارة',
                'diagnosis' => 'التهاب حاد في القصبة الهوائية',
                'drugs' => [
                    [
                        'drug_name' => 'Ciprodar 500mg',
                        'dosage' => '500mg',
                        'frequency' => 'كل 12 ساعة',
                        'duration' => '7 أيام',
                        'instructions' => 'بعد الأكل',
                    ]
                ],
            ]);

        $rxRes->assertStatus(200);

        // Save extra services
        $extraRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/clinic/doctor/services-and-followup/{$appointment->id}", [
                'selected_services' => [
                    ['service_name' => 'رسم قلب ECG', 'price' => 150.0]
                ],
                'followup_date' => Carbon::today()->addDays(7)->toDateString(),
            ]);

        $extraRes->assertStatus(200);

        // Login as Receptionist to collect extra services (Correction 4)
        $recLogin = $this->postJson('/api/clinic/login', [
            'email' => 'reception1@clinic.com',
            'password' => 'password123',
        ]);
        $recToken = $recLogin->json('data.token');

        $collectRes = $this->withHeader('Authorization', 'Bearer ' . $recToken)
            ->postJson("/api/clinic/reception/collect-extra/{$appointment->id}");

        $collectRes->assertStatus(200);

        $freshAppt = Appointment::withoutGlobalScope(TenantScope::class)->find($appointment->id);
        $this->assertTrue((bool)$freshAppt->extra_services_paid);
    }

    public function test_whatsapp_token_service_hmac_validity()
    {
        $token = WhatsAppTokenService::generateToken('custom_secret');

        $this->assertNotEmpty($token);
        $this->assertTrue(WhatsAppTokenService::verifyToken($token, 'custom_secret'));
        $this->assertFalse(WhatsAppTokenService::verifyToken($token, 'wrong_secret'));
    }
}
