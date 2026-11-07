<?php

namespace Tests\Feature;

use App\Models\DoctorLead;
use App\Models\Plan;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\WalletTopup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminControlTest extends TestCase
{
    use RefreshDatabase;

    protected SuperAdmin $admin;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = SuperAdmin::create([
            'name' => 'مدير المنصة الرئيسي',
            'email' => 'admin@platform.com',
            'password' => bcrypt('SuperAdminSecret2026'),
            'is_active' => true,
        ]);

        $this->plan = Plan::create([
            'name_ar' => 'باقة النمو',
            'price_monthly' => 600,
            'price_yearly' => 6000,
            'max_clinics' => 3,
            'is_active' => true,
        ]);
    }

    /**
     * Test super-admin login produces valid sanctum token with role:super-admin.
     */
    public function test_super_admin_login_success(): void
    {
        $response = $this->postJson('/api/super-admin/login', [
            'email' => 'admin@platform.com',
            'password' => 'SuperAdminSecret2026',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'admin']]);
    }

    /**
     * Test platform aggregate statistics computation.
     */
    public function test_super_admin_dashboard_stats(): void
    {
        $token = $this->admin->createToken('adm_tok', ['role:super-admin'])->plainTextToken;

        Tenant::create([
            'name' => 'عيادة تجريبية',
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'subdomain' => 'test-stat-clinic',
            'public_booking_api_key' => 'pbk_stat_1',
            'whatsapp_used_this_month' => 45,
            'sms_used_this_month' => 12,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/super-admin/stats');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_doctors', 1)
            ->assertJsonPath('data.whatsapp_used_this_month', 45);
    }

    /**
     * Test lead conversion creates tenant, user, clinic, and evening shifts.
     */
    public function test_convert_doctor_lead_to_live_tenant(): void
    {
        $token = $this->admin->createToken('adm_tok', ['role:super-admin'])->plainTextToken;

        $lead = DoctorLead::create([
            'doctor_name' => 'د. أشرف فوزي',
            'phone' => '01019998888',
            'specialty' => 'pediatrics',
            'status' => 'new',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/super-admin/leads/{$lead->id}/convert", [
                'plan_id' => $this->plan->id,
                'subdomain' => 'dr-ashraf-pediatrics',
                'specialty_key' => 'pediatrics',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $lead->refresh();
        $this->assertEquals('converted', $lead->status);

        $this->assertDatabaseHas('tenants', [
            'name' => 'د. أشرف فوزي',
            'subdomain' => 'dr-ashraf-pediatrics',
            'specialty_key' => 'pediatrics',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'د. أشرف فوزي',
            'role' => 'doctor',
        ]);

        $this->assertDatabaseHas('clinics', [
            'name_ar' => 'الفرع الرئيسي',
        ]);
    }

    /**
     * Test manual wallet balance topup approval and credit.
     */
    public function test_topup_approval_credits_tenant_wallet(): void
    {
        $token = $this->admin->createToken('adm_tok', ['role:super-admin'])->plainTextToken;

        $tenant = Tenant::create([
            'name' => 'عيادة النور',
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'subdomain' => 'al-noor-clinic',
            'public_booking_api_key' => 'pbk_topup_1',
            'whatsapp_balance' => 10,
        ]);

        $topup = WalletTopup::create([
            'tenant_id' => $tenant->id,
            'package_type' => 'whatsapp',
            'quantity_added' => 1000,
            'amount_egp' => 500,
            'payment_method' => 'manual_super_admin',
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/super-admin/topups/{$topup->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $topup->refresh();
        $this->assertEquals('paid', $topup->status);
        $this->assertEquals($this->admin->id, $topup->approved_by_admin_id);

        $tenant->refresh();
        $this->assertEquals(1010, $tenant->whatsapp_balance);
    }

    /**
     * Test doctor impersonation produces valid doctor bearer token.
     */
    public function test_super_admin_impersonate_doctor(): void
    {
        $token = $this->admin->createToken('adm_tok', ['role:super-admin'])->plainTextToken;

        $tenant = Tenant::create([
            'name' => 'د. سليم خيري',
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'subdomain' => 'dr-seleem',
            'public_booking_api_key' => 'pbk_imp_1',
        ]);

        \App\Models\User::create([
            'tenant_id' => $tenant->id,
            'name' => 'د. سليم',
            'email' => 'dr.seleem@clinic.com',
            'password' => bcrypt('password123'),
            'role' => 'doctor',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/super-admin/tenants/{$tenant->id}/impersonate");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user', 'redirect_url']]);
    }
}
